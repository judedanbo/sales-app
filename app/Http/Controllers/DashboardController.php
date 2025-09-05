<?php

namespace App\Http\Controllers;

use App\Models\School;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $dashboardData = $this->getDashboardData();

        return Inertia::render('Dashboard', $dashboardData);
    }

    private function getDashboardData(): array
    {
        // Basic stats
        $totalSchools = School::count();
        $activeSchools = School::where('status', 'active')->count();
        $inactiveSchools = School::where('status', 'inactive')->count();

        // Student and teacher totals
        // $totalStudents = School::sum('total_students') ?: 0;
        // $totalTeachers = School::sum('total_teachers') ?: 0;

        // Recent schools (last 30 days)
        $recentSchools = School::with(['contacts', 'addresses'])
            ->where('created_at', '>=', now()->subDays(30))
            ->latest()
            ->limit(5)
            ->get();

        // Schools by type
        $schoolsByType = School::selectRaw('school_type, COUNT(*) as count')
            ->groupBy('school_type')
            ->pluck('count', 'school_type')
            ->toArray();

        // Schools by board affiliation
        $schoolsByBoard = School::selectRaw('board_affiliation, COUNT(*) as count')
            ->whereNotNull('board_affiliation')
            ->groupBy('board_affiliation')
            ->pluck('count', 'board_affiliation')
            ->toArray();

        // Schools needing attention (missing key data)
        // $schoolsNeedingAttention = School::where(function ($query) {
        //     $query->where('is_active', false)
        //         ->orWhereNull('principal_name')
        //         ->orWhereNull('total_students')
        //         ->orWhereNull('total_teachers');
        // })->count();

        // Data completeness
        $schoolsWithContacts = School::whereHas('contacts')->count();
        $schoolsWithAddresses = School::whereHas('addresses')->count();

        return [
            'stats' => [
                'total_schools' => $totalSchools,
                'active_schools' => $activeSchools,
                'inactive_schools' => $inactiveSchools,
                // 'total_students' => $totalStudents,
                // 'total_teachers' => $totalTeachers,
                // 'student_teacher_ratio' => $totalTeachers > 0 ? round($totalStudents / $totalTeachers, 1) : 0,
                // 'schools_needing_attention' => $schoolsNeedingAttention,
                'schools_with_contacts' => $schoolsWithContacts,
                'schools_with_addresses' => $schoolsWithAddresses,
                'data_completeness_percentage' => $totalSchools > 0 ? round((($schoolsWithContacts + $schoolsWithAddresses) / ($totalSchools * 2)) * 100) : 0,
            ],
            'charts' => [
                'schools_by_type' => $schoolsByType,
                'schools_by_board' => $schoolsByBoard,
            ],
            'recent_schools' => $recentSchools,
        ];
    }
}
