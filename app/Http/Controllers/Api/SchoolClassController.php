<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolClassController extends Controller
{
    /**
     * Store a newly created school class.
     */
    public function store(Request $request, School $school): JsonResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:255'],
            'class_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('school_classes')->where(function ($query) use ($school) {
                    return $query->where('school_id', $school->id);
                })->whereNull('deleted_at'),
            ],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'min_age' => ['nullable', 'integer', 'min:3', 'max:25'],
            'max_age' => ['nullable', 'integer', 'min:3', 'max:25', 'gte:min_age'],
            'order_sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $schoolClass = $school->classes()->create($validated);

        return response()->json([
            'message' => 'Class created successfully',
            'data' => $schoolClass,
        ], 201);
    }

    /**
     * Display the specified school class.
     */
    public function show(School $school, SchoolClass $class): JsonResponse
    {
        return response()->json([
            'data' => $class,
        ]);
    }

    /**
     * Update the specified school class.
     */
    public function update(Request $request, School $school, SchoolClass $class): JsonResponse
    {
        $validated = $request->validate([
            'class_name' => ['sometimes', 'required', 'string', 'max:255'],
            'class_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('school_classes')->where(function ($query) use ($school) {
                    return $query->where('school_id', $school->id);
                })->ignore($class->id)->whereNull('deleted_at'),
            ],
            'grade_level' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],
            'min_age' => ['nullable', 'integer', 'min:3', 'max:25'],
            'max_age' => ['nullable', 'integer', 'min:3', 'max:25', 'gte:min_age'],
            'order_sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $class->update($validated);

        return response()->json([
            'message' => 'Class updated successfully',
            'data' => $class,
        ]);
    }

    /**
     * Remove the specified school class.
     */
    public function destroy(School $school, SchoolClass $class): JsonResponse
    {
        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully',
        ]);
    }

    /**
     * Get all classes for a specific school.
     */
    public function index(School $school): JsonResponse
    {
        $classes = $school->classes()
            ->orderBy('order_sequence')
            ->orderBy('grade_level')
            ->get();

        return response()->json([
            'data' => $classes,
        ]);
    }
}
