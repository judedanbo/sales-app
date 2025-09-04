<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\BoardAffiliation;
use App\Enums\MediumOfInstruction;
use App\Enums\SchoolStatus;
use App\Enums\SchoolType;
use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    /**
     * Display a listing of schools.
     */
    public function index(Request $request): Response
    {
        $query = School::with(['contacts', 'addresses', 'management', 'officials']);

        // Get filter data from request (works for both GET and POST/reload)
        $filters = $request->all();

        // Apply filters
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('school_name', 'like', "%{$search}%")
                    ->orWhere('school_code', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['school_type'])) {
            $query->where('school_type', $filters['school_type']);
        }

        if (! empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['board_affiliation'])) {
            $query->where('board_affiliation', $filters['board_affiliation']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'school_name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        if (in_array($sortBy, ['school_name', 'school_code', 'school_type', 'established_date', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Handle pagination
        $page = $filters['page'] ?? 1;
        $schools = $query->paginate(15, ['*'], 'page', $page);

        return Inertia::render('Schools/Index', [
            'schools' => $schools,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'school_type' => $filters['school_type'] ?? '',
                'is_active' => $filters['is_active'] ?? '',
                'board_affiliation' => $filters['board_affiliation'] ?? '',
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'schoolTypes' => SchoolType::options(),
            'boardAffiliations' => BoardAffiliation::options(),
            'schoolStatuses' => SchoolStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a new school.
     */
    public function create(): Response
    {
        return Inertia::render('Schools/Create', [
            'schoolTypes' => SchoolType::options(),
            'boardAffiliations' => BoardAffiliation::options(),
            'mediumOfInstructions' => MediumOfInstruction::options(),
        ]);
    }

    /**
     * Store a newly created school.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'required|string|max:50|unique:schools,school_code',
            'school_type' => 'required|string|in:'.implode(',', SchoolType::values()),
            'board_affiliation' => 'nullable|string|in:'.implode(',', BoardAffiliation::values()),
            'established_date' => 'nullable|date',
            'principal_name' => 'nullable|string|max:255',
            'medium_of_instruction' => 'nullable|string|in:'.implode(',', MediumOfInstruction::values()),
            'total_students' => 'nullable|integer|min:0',
            'total_teachers' => 'nullable|integer|min:0',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $school = School::create($validated);

        return redirect()->route('schools.show', $school)->with('success', 'School created successfully.');
    }

    /**
     * Display the specified school.
     */
    public function show(School $school): Response
    {
        $school->load(['contacts', 'addresses', 'management', 'officials', 'documents', 'academicYears', 'schoolClasses']);

        return Inertia::render('Schools/Show', [
            'school' => $school,
        ]);
    }

    /**
     * Show the form for editing the specified school.
     */
    public function edit(School $school): Response
    {
        $school->load(['contacts', 'addresses', 'management', 'officials']);

        return Inertia::render('Schools/Edit', [
            'school' => $school,
            'schoolTypes' => SchoolType::options(),
            'boardAffiliations' => BoardAffiliation::options(),
            'mediumOfInstructions' => MediumOfInstruction::options(),
        ]);
    }

    /**
     * Update the specified school.
     */
    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_code' => 'required|string|max:50|unique:schools,school_code,'.$school->id,
            'school_type' => 'required|string|in:'.implode(',', SchoolType::values()),
            'board_affiliation' => 'nullable|string|in:'.implode(',', BoardAffiliation::values()),
            'established_date' => 'nullable|date',
            'principal_name' => 'nullable|string|max:255',
            'medium_of_instruction' => 'nullable|string|in:'.implode(',', MediumOfInstruction::values()),
            'total_students' => 'nullable|integer|min:0',
            'total_teachers' => 'nullable|integer|min:0',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $school->update($validated);

        return redirect()->route('schools.show', $school)->with('success', 'School updated successfully.');
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school)
    {
        $school->delete();

        return redirect()->route('schools.index')->with('success', 'School deleted successfully.');
    }

    /**
     * Display dashboard with school statistics.
     */
    public function dashboard(): Response
    {
        $stats = [
            'total_schools' => School::count(),
            'active_schools' => School::where('is_active', true)->count(),
            'inactive_schools' => School::where('is_active', false)->count(),
            'recent_schools' => School::latest()->take(5)->get(),
            'by_type' => School::selectRaw('school_type, COUNT(*) as count')
                ->groupBy('school_type')
                ->pluck('count', 'school_type'),
            'by_board' => School::selectRaw('board_affiliation, COUNT(*) as count')
                ->whereNotNull('board_affiliation')
                ->groupBy('board_affiliation')
                ->pluck('count', 'board_affiliation'),
        ];

        return Inertia::render('Schools/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
