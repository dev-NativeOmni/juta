<?php

namespace App\Policies;

use App\Models\TahfizhExam;
use App\Models\User;
use App\Services\UserAccessService;

class TahfizhExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'headmaster', 'coordinator_tahfizh']);
    }

    public function view(User $user, TahfizhExam $tahfizhExam): bool
    {
        return $tahfizhExam->student
            && app(UserAccessService::class)->canViewStudent($user, $tahfizhExam->student);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'teacher', 'headmaster', 'coordinator_tahfizh']);
    }

    public function update(User $user, TahfizhExam $tahfizhExam): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'headmaster', 'coordinator_tahfizh'])) {
            return true;
        }

        if (! $user->hasRole('teacher')) {
            return false;
        }

        return (int) $tahfizhExam->teacher_id === (int) $user->teacherProfile?->id;
    }

    public function delete(User $user, TahfizhExam $tahfizhExam): bool
    {
        return $this->update($user, $tahfizhExam);
    }
}
