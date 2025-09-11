<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Http\Resources\SchoolCollection;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeViewAny(School::class);
        $query = School::with(['contacts', 'addresses', 'management', 'officials', 'documents', 'academicYears', 'schoolClasses']);

        // Apply filters
        if ($request->filled('school_type')) {
            $query->where('school_type', $request->school_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('board_affiliation')) {
            $query->where('board_affiliation', $request->board_affiliation);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('school_name', 'like', "%{$search}%")
                    ->orWhere('school_code', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'school_name');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['school_name', 'school_code', 'school_type', 'established_date', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Paginate results
        $perPage = min($request->get('per_page', 15), 100);
        $schools = $query->paginate($perPage);

        return new SchoolCollection($schools);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSchoolRequest $request)
    {
        $this->authorizeCreate(School::class);
        $validated = $request->validated();
        $school = School::create($validated);
        $school->load(['contacts', 'addresses', 'management', 'officials', 'documents', 'academicYears', 'schoolClasses']);

        return (new SchoolResource($school))
            ->additional(['message' => 'School created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(School $school)
    {
        $this->authorizeView($school);
        $school->load(['contacts', 'addresses', 'management', 'officials', 'documents', 'academicYears', 'schoolClasses']);

        return new SchoolResource($school);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolRequest $request, School $school)
    {
        $this->authorizeUpdate($school);
        $validated = $request->validated();
        $school->update($validated);
        $school->load(['contacts', 'addresses', 'management', 'officials', 'documents', 'academicYears', 'schoolClasses']);

        return (new SchoolResource($school))
            ->additional(['message' => 'School updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(School $school): JsonResponse
    {
        $this->authorizeDelete($school);
        $school->delete();

        return response()->json([
            'success' => true,
            'message' => 'School deleted successfully',
        ]);
    }

    /**
     * Restore a soft deleted school.
     */
    public function restore(int $id): JsonResponse
    {
        $school = School::withTrashed()->findOrFail($id);
        $this->authorizeRestore($school);
        $school->restore();

        return response()->json([
            'success' => true,
            'message' => 'School restored successfully',
            'data' => $school,
        ]);
    }

    /**
     * Force delete a school.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $school = School::withTrashed()->findOrFail($id);
        $this->authorizeForceDelete($school);
        $school->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'School permanently deleted',
        ]);
    }

    /**
     * Get schools with trashed records.
     */
    public function withTrashed(Request $request): JsonResponse
    {
        $this->authorizeViewAny(School::class);
        $query = School::withTrashed()->with(['contacts', 'addresses', 'management', 'officials', 'documents', 'academicYears', 'schoolClasses']);

        $perPage = min($request->get('per_page', 15), 100);
        $schools = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $schools->items(),
            'meta' => [
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'per_page' => $schools->perPage(),
                'total' => $schools->total(),
            ],
        ]);
    }

    /**
     * Get only trashed schools.
     */
    public function onlyTrashed(Request $request): JsonResponse
    {
        $this->authorizeViewAny(School::class);
        $query = School::onlyTrashed()->with(['contacts', 'addresses', 'management', 'officials', 'documents', 'academicYears', 'schoolClasses']);

        $perPage = min($request->get('per_page', 15), 100);
        $schools = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $schools->items(),
            'meta' => [
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'per_page' => $schools->perPage(),
                'total' => $schools->total(),
            ],
        ]);
    }

    /**
     * Bulk update schools status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorizeBulkOperation('update', School::class);
        $this->validateBulkOperationLimits($request->input('school_ids', []));
        $validated = $request->validate([
            'school_ids' => 'required|array',
            'school_ids.*' => 'integer|exists:schools,id',
            'status' => 'required|string|in:active,inactive',
        ]);

        $updatedCount = School::whereIn('id', $validated['school_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$updatedCount} schools",
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Get school statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorizeStatistics(School::class);
        $stats = [
            'total_schools' => School::count(),
            'active_schools' => School::where('status', 'active')->count(),
            'inactive_schools' => School::where('status', 'inactive')->count(),
            'deleted_schools' => School::onlyTrashed()->count(),
            'by_type' => School::selectRaw('school_type, COUNT(*) as count')
                ->groupBy('school_type')
                ->pluck('count', 'school_type'),
            'by_board' => School::selectRaw('board_affiliation, COUNT(*) as count')
                ->whereNotNull('board_affiliation')
                ->groupBy('board_affiliation')
                ->pluck('count', 'board_affiliation'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
