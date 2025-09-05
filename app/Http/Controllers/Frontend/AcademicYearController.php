<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of academic years.
     */
    public function index(School $school): Response
    {
        $academicYears = $school->academicYears()
            ->orderBy('is_current', 'desc')
            ->orderBy('start_date', 'desc')
            ->get();

        return Inertia::render('Schools/AcademicYears/Index', [
            'school' => $school->load('addresses', 'contacts'),
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Show the form for creating a new academic year.
     */
    public function create(School $school): Response
    {
        return Inertia::render('Schools/AcademicYears/Create', [
            'school' => $school,
        ]);
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'year_name' => [
                'required',
                'string',
                'max:255',
                'unique:academic_years,year_name,NULL,id,school_id,' . $school->id . ',deleted_at,NULL',
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['boolean'],
        ]);

        DB::transaction(function () use ($school, $validated) {
            // If marking as current, unset other current years for this school
            if ($validated['is_current'] ?? false) {
                $school->academicYears()->update(['is_current' => false]);
            }

            $school->academicYears()->create($validated);
        });

        return redirect()
            ->route('schools.academic-years.index', $school)
            ->with('success', 'Academic year created successfully.');
    }

    /**
     * Display the specified academic year.
     */
    public function show(School $school, AcademicYear $academicYear): Response
    {
        // Ensure the academic year belongs to the school
        if ($academicYear->school_id !== $school->id) {
            abort(404);
        }

        return Inertia::render('Schools/AcademicYears/Show', [
            'school' => $school,
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Show the form for editing the specified academic year.
     */
    public function edit(School $school, AcademicYear $academicYear): Response
    {
        // Ensure the academic year belongs to the school
        if ($academicYear->school_id !== $school->id) {
            abort(404);
        }

        return Inertia::render('Schools/AcademicYears/Edit', [
            'school' => $school,
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, School $school, AcademicYear $academicYear): RedirectResponse
    {
        // Ensure the academic year belongs to the school
        if ($academicYear->school_id !== $school->id) {
            abort(404);
        }

        $validated = $request->validate([
            'year_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'unique:academic_years,year_name,' . $academicYear->id . ',id,school_id,' . $school->id . ',deleted_at,NULL',
            ],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'is_current' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($school, $academicYear, $validated) {
            // If marking as current, unset other current years for this school
            if (isset($validated['is_current']) && $validated['is_current']) {
                $school->academicYears()
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_current' => false]);
            }

            $academicYear->update($validated);
        });

        return redirect()
            ->route('schools.academic-years.index', $school)
            ->with('success', 'Academic year updated successfully.');
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(School $school, AcademicYear $academicYear): RedirectResponse
    {
        // Ensure the academic year belongs to the school
        if ($academicYear->school_id !== $school->id) {
            abort(404);
        }

        $academicYear->delete();

        return redirect()
            ->route('schools.academic-years.index', $school)
            ->with('success', 'Academic year deleted successfully.');
    }

    /**
     * Set an academic year as current.
     */
    public function setCurrent(School $school, AcademicYear $academicYear): RedirectResponse
    {
        // Ensure the academic year belongs to the school
        if ($academicYear->school_id !== $school->id) {
            abort(404);
        }

        DB::transaction(function () use ($school, $academicYear) {
            // Unset all current years for this school
            $school->academicYears()->update(['is_current' => false]);
            
            // Set the specified year as current
            $academicYear->update(['is_current' => true]);
        });

        return redirect()
            ->route('schools.academic-years.index', $school)
            ->with('success', 'Academic year set as current successfully.');
    }

    /**
     * Restore a soft-deleted academic year.
     */
    public function restore(School $school, int $yearId): RedirectResponse
    {
        $academicYear = AcademicYear::onlyTrashed()->findOrFail($yearId);
        
        // Ensure the academic year belongs to the school
        if ($academicYear->school_id !== $school->id) {
            abort(403);
        }

        $academicYear->restore();

        return redirect()
            ->route('schools.academic-years.index', $school)
            ->with('success', 'Academic year restored successfully.');
    }

    /**
     * Permanently delete an academic year.
     */
    public function forceDelete(School $school, int $yearId): RedirectResponse
    {
        $academicYear = AcademicYear::withTrashed()->findOrFail($yearId);
        
        // Ensure the academic year belongs to the school
        if ($academicYear->school_id !== $school->id) {
            abort(403);
        }

        $academicYear->forceDelete();

        return redirect()
            ->route('schools.academic-years.index', $school)
            ->with('success', 'Academic year permanently deleted.');
    }
}
