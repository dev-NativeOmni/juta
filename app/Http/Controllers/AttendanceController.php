<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    private function userHasAnyRole(User $user, array $roles): bool
    {
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($roles);
        }

        return in_array($user->role?->name ?? null, $roles);
    }

    private function visibleStudentIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        if ($this->userHasAnyRole($user, ['super_admin', 'admin', 'headmaster', 'supervisor', 'coordinator_tahfizh'])) {
            return Student::query()->pluck('id');
        }

        if ($this->userHasAnyRole($user, ['teacher'])) {
            $teacherId = TeacherProfile::query()
                ->where('user_id', $user->id)
                ->value('id');

            if (! $teacherId) {
                return collect();
            }

            return Student::query()
                ->where('teacher_id', $teacherId)
                ->pluck('id');
        }

        return collect();
    }

    public function check(Request $request)
    {
        $user = $request->user();
        $visibleStudentIds = $this->visibleStudentIds($user);

        $classRoomId = $request->integer('class_room_id');
        $date = $request->input('date', date('Y-m-d'));

        if (!$classRoomId) {
            return response()->json([
                'success' => false,
                'message' => 'Classroom ID is required.'
            ], 400);
        }

        // Fetch students in this classroom visible to this user
        $students = Student::query()
            ->where('class_room_id', $classRoomId)
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        // Fetch existing attendance records for these students on this date
        $attendances = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('tanggal', $date)
            ->pluck('status', 'student_id');

        $result = [];
        foreach ($students as $student) {
            $result[] = [
                'id' => $student->id,
                'name' => $student->name,
                'className' => $student->classRoom?->name,
                'status' => $attendances->get($student->id, null), // null means not filled yet
            ];
        }

        return response()->json([
            'success' => true,
            'attendances' => $result,
        ]);
    }

    public function save(Request $request)
    {
        $user = $request->user();
        $visibleStudentIds = $this->visibleStudentIds($user);

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'string', 'in:hadir,sakit,izin,alpa'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Check auth
        if (!$visibleStudentIds->contains($validated['student_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        // Determine teacher_id
        $student = Student::query()->find($validated['student_id']);
        $teacherId = $student ? $student->teacher_id : null;

        // Save attendance record
        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'tanggal' => $validated['tanggal'],
            ],
            [
                'class_room_id' => $validated['class_room_id'],
                'teacher_id' => $teacherId,
                'status' => $validated['status'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved successfully.',
            'attendance' => $attendance,
        ]);
    }
}
