<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    /**
     * Store a newly created academic year.
     */
    public function store(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'year_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_years')->where(function ($query) use ($school) {
                    return $query->where('school_id', $school->id);
                })->whereNull('deleted_at'),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['boolean'],
        ]);

        // If marking as current, unset other current years for this school
        if ($validated['is_current'] ?? false) {
            $school->academicYears()->update(['is_current' => false]);
        }

        $academicYear = $school->academicYears()->create($validated);

        return response()->json([
            'message' => 'Academic year created successfully',
            'data' => $academicYear,
        ], 201);
    }

    /**
     * Display the specified academic year.
     */
    public function show(School $school, AcademicYear $academicYear): JsonResponse
    {
        return response()->json([
            'data' => $academicYear,
        ]);
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, School $school, AcademicYear $academicYear): JsonResponse
    {
        $validated = $request->validate([
            'year_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('academic_years')->where(function ($query) use ($school) {
                    return $query->where('school_id', $school->id);
                })->ignore($academicYear->id)->whereNull('deleted_at'),
            ],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'is_current' => ['sometimes', 'boolean'],
        ]);

        // If marking as current, unset other current years for this school
        if (isset($validated['is_current']) && $validated['is_current']) {
            $school->academicYears()
                ->where('id', '!=', $academicYear->id)
                ->update(['is_current' => false]);
        }

        $academicYear->update($validated);

        return response()->json([
            'message' => 'Academic year updated successfully',
            'data' => $academicYear,
        ]);
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(School $school, AcademicYear $academicYear): JsonResponse
    {
        $academicYear->delete();

        return response()->json([
            'message' => 'Academic year deleted successfully',
        ]);
    }

    /**
     * Get all academic years for a specific school.
     */
    public function index(School $school): JsonResponse
    {
        $academicYears = $school->academicYears()
            ->orderBy('is_current', 'desc')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'data' => $academicYears,
        ]);
    }

    /**
     * Set an academic year as current.
     */
    public function setCurrent(School $school, AcademicYear $academicYear): JsonResponse
    {
        DB::transaction(function () use ($school, $academicYear) {
            // Unset all current years for this school
            $school->academicYears()->update(['is_current' => false]);
            
            // Set the specified year as current
            $academicYear->update(['is_current' => true]);
        });

        return response()->json([
            'message' => 'Academic year set as current successfully',
            'data' => $academicYear->fresh(),
        ]);
    }
}
