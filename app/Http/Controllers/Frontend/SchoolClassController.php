<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolClassRequest;
use App\Http\Requests\UpdateSchoolClassRequest;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of school classes.
     */
    public function index(School $school): Response
    {
        $classes = $school->classes()
            ->orderBy('order_sequence')
            ->orderBy('grade_level')
            ->get();

        return Inertia::render('Schools/Classes/Index', [
            'school' => $school->load('addresses', 'contacts'),
            'classes' => $classes,
        ]);
    }

    /**
     * Show the form for creating a new school class.
     */
    public function create(School $school): Response
    {
        return Inertia::render('Schools/Classes/Create', [
            'school' => $school,
        ]);
    }

    /**
     * Store a newly created school class.
     */
    public function store(Request $request, School $school): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:255'],
            'class_code' => [
                'required',
                'string',
                'max:50',
                'unique:school_classes,class_code,NULL,id,school_id,' . $school->id . ',deleted_at,NULL',
            ],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'min_age' => ['nullable', 'integer', 'min:3', 'max:25'],
            'max_age' => ['nullable', 'integer', 'min:3', 'max:25', 'gte:min_age'],
            'order_sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $school->classes()->create($validated);

        return redirect()
            ->route('schools.classes.index', $school)
            ->with('success', 'Class created successfully.');
    }

    /**
     * Display the specified school class.
     */
    public function show(School $school, SchoolClass $class): Response
    {
        return Inertia::render('Schools/Classes/Show', [
            'school' => $school,
            'class' => $class,
        ]);
    }

    /**
     * Show the form for editing the specified school class.
     */
    public function edit(School $school, SchoolClass $class): Response
    {
        return Inertia::render('Schools/Classes/Edit', [
            'school' => $school,
            'class' => $class,
        ]);
    }

    /**
     * Update the specified school class.
     */
    public function update(Request $request, School $school, SchoolClass $class): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['sometimes', 'required', 'string', 'max:255'],
            'class_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'unique:school_classes,class_code,' . $class->id . ',id,school_id,' . $school->id . ',deleted_at,NULL',
            ],
            'grade_level' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],
            'min_age' => ['nullable', 'integer', 'min:3', 'max:25'],
            'max_age' => ['nullable', 'integer', 'min:3', 'max:25', 'gte:min_age'],
            'order_sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $class->update($validated);

        return redirect()
            ->route('schools.classes.index', $school)
            ->with('success', 'Class updated successfully.');
    }

    /**
     * Remove the specified school class.
     */
    public function destroy(School $school, SchoolClass $class): RedirectResponse
    {
        $class->delete();

        return redirect()
            ->route('schools.classes.index', $school)
            ->with('success', 'Class deleted successfully.');
    }

    /**
     * Restore a soft-deleted school class.
     */
    public function restore(School $school, int $classId): RedirectResponse
    {
        $class = SchoolClass::onlyTrashed()->findOrFail($classId);
        
        // Ensure the class belongs to the school
        if ($class->school_id !== $school->id) {
            abort(403);
        }

        $class->restore();

        return redirect()
            ->route('schools.classes.index', $school)
            ->with('success', 'Class restored successfully.');
    }

    /**
     * Permanently delete a school class.
     */
    public function forceDelete(School $school, int $classId): RedirectResponse
    {
        $class = SchoolClass::withTrashed()->findOrFail($classId);
        
        // Ensure the class belongs to the school
        if ($class->school_id !== $school->id) {
            abort(403);
        }

        $class->forceDelete();

        return redirect()
            ->route('schools.classes.index', $school)
            ->with('success', 'Class permanently deleted.');
    }
}
