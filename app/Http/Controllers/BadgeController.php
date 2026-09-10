<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BadgeController extends Controller
{
    public function index(Request $request)
    {
        $dbMissing = ! Schema::hasTable('badges');
        $badges = collect();
        $typeLabels = Badge::typeLabels();

        if (! $dbMissing) {
            try {
                $badges = Badge::orderBy('sort_order')->orderBy('id')->get();
            } catch (\Throwable $e) {
                $dbMissing = true;
            }
        }

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'badges' => $badges,
                'typeLabels' => $typeLabels,
                'dbMissing' => $dbMissing,
            ]);
        }

        return view('badges.index', compact('badges', 'typeLabels', 'dbMissing'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key'          => 'required|string|unique:badges,key|regex:/^[a-z0-9_]+$/',
            'title'        => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
            'icon'         => 'required|string|max:50',
            'type'         => 'required|in:count_hafalan,passed_hafalan,percent_quran,count_murajaah,completed_targets,clean_target,score_quality,completed_juz',
            'target_value' => 'required|numeric|min:0',
            'target_juz'   => 'nullable|integer|min:1|max:30',
            'sort_order'   => 'required|integer|min:0',
        ]);

        $badge = Badge::create($validated + ['is_active' => true]);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Badge berhasil dibuat!', 'badge' => $badge]);
        }

        return redirect()->route('badges.index')->with('success', 'Badge berhasil dibuat!');
    }

    public function update(Request $request, Badge $badge)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
            'icon'         => 'required|string|max:50',
            'type'         => 'required|in:count_hafalan,passed_hafalan,percent_quran,count_murajaah,completed_targets,clean_target,score_quality,completed_juz',
            'target_value' => 'required|numeric|min:0',
            'target_juz'   => 'nullable|integer|min:1|max:30',
            'sort_order'   => 'required|integer|min:0',
        ]);

        $badge->update($validated);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Badge berhasil diperbarui!', 'badge' => $badge]);
        }

        return redirect()->route('badges.index')->with('success', 'Badge berhasil diperbarui!');
    }

    public function destroy(Request $request, Badge $badge)
    {
        $badge->delete();

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Badge berhasil dihapus!']);
        }

        return redirect()->route('badges.index')->with('success', 'Badge berhasil dihapus!');
    }

    public function toggleActive(Request $request, Badge $badge)
    {
        $badge->update(['is_active' => ! $badge->is_active]);

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $badge->is_active ? 'Badge diaktifkan.' : 'Badge dinonaktifkan.',
                'is_active' => $badge->is_active,
            ]);
        }

        return redirect()->route('badges.index')->with('success', $badge->is_active ? 'Badge diaktifkan.' : 'Badge dinonaktifkan.');
    }
}
