<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    /**
     * Display a paginated listing of audit records
     */
    public function index(Request $request): JsonResponse
    {
        $query = Audit::with(['user'])
            ->orderByDesc('created_at');

        // Filter by auditable type (model)
        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        // Filter by auditable ID
        if ($request->has('auditable_id')) {
            $query->where('auditable_id', $request->auditable_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event type (created, updated, deleted, etc.)
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $audits = $query->paginate($request->get('per_page', 15));

        return response()->json($audits);
    }

    /**
     * Display the specified audit record
     */
    public function show(Audit $audit): JsonResponse
    {
        $audit->load(['user']);

        return response()->json([
            'audit' => $audit,
            'old_values' => $audit->old_values,
            'new_values' => $audit->new_values,
        ]);
    }

    /**
     * Get audit statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_audits' => Audit::count(),
            'today_audits' => Audit::whereDate('created_at', today())->count(),
            'this_week_audits' => Audit::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'this_month_audits' => Audit::whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->count(),
        ];

        // Events breakdown
        $events = Audit::selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        // Models breakdown
        $models = Audit::selectRaw('auditable_type, COUNT(*) as count')
            ->groupBy('auditable_type')
            ->pluck('count', 'auditable_type')
            ->toArray();

        // Top users (by audit count)
        $topUsers = Audit::with(['user'])
            ->selectRaw('user_id, COUNT(*) as audit_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('audit_count')
            ->limit(10)
            ->get()
            ->map(function ($audit) {
                return [
                    'user' => $audit->user,
                    'audit_count' => $audit->audit_count,
                ];
            });

        return response()->json([
            'stats' => $stats,
            'events_breakdown' => $events,
            'models_breakdown' => $models,
            'top_users' => $topUsers,
        ]);
    }

    /**
     * Get audits for a specific model
     */
    public function forModel(Request $request, string $modelType, int $modelId): JsonResponse
    {
        $query = Audit::with(['user'])
            ->where('auditable_type', $modelType)
            ->where('auditable_id', $modelId)
            ->orderByDesc('created_at');

        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        $audits = $query->paginate($request->get('per_page', 15));

        return response()->json($audits);
    }

    /**
     * Get audits for a specific user
     */
    public function forUser(Request $request, int $userId): JsonResponse
    {
        $query = Audit::with(['user'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        $audits = $query->paginate($request->get('per_page', 15));

        return response()->json($audits);
    }

    /**
     * Get available auditable models
     */
    public function models(): JsonResponse
    {
        $models = Audit::distinct()
            ->pluck('auditable_type')
            ->filter()
            ->values()
            ->map(function ($model) {
                return [
                    'class' => $model,
                    'name' => class_basename($model),
                    'count' => Audit::where('auditable_type', $model)->count(),
                ];
            });

        return response()->json($models);
    }

    /**
     * Get audit trail for a specific record with timeline view
     */
    public function timeline(string $modelType, int $modelId): JsonResponse
    {
        $audits = Audit::with(['user'])
            ->where('auditable_type', $modelType)
            ->where('auditable_id', $modelId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'user' => $audit->user,
                    'created_at' => $audit->created_at,
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'changes_summary' => $this->getChangesSummary($audit),
                ];
            });

        return response()->json($audits);
    }

    /**
     * Generate a summary of changes for an audit record
     */
    private function getChangesSummary(Audit $audit): array
    {
        $summary = [];

        if ($audit->event === 'created') {
            $fields = array_keys($audit->new_values ?? []);
            if (count($fields) > 0) {
                $summary[] = 'Record created with '.count($fields).' field'.(count($fields) > 1 ? 's' : '');
                if (count($fields) <= 5) {
                    $summary[] = 'Fields set: '.implode(', ', $fields);
                }
            } else {
                $summary[] = 'Record created';
            }
        } elseif ($audit->event === 'updated') {
            $oldValues = $audit->old_values ?? [];
            $newValues = $audit->new_values ?? [];
            $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

            $changedFields = [];
            foreach ($allKeys as $key) {
                $oldVal = $oldValues[$key] ?? null;
                $newVal = $newValues[$key] ?? null;
                if (json_encode($oldVal) !== json_encode($newVal)) {
                    $changedFields[] = $key;
                }
            }

            if (count($changedFields) > 0) {
                $summary[] = 'Updated '.count($changedFields).' field'.(count($changedFields) > 1 ? 's' : '');
                if (count($changedFields) <= 5) {
                    $summary[] = 'Changed: '.implode(', ', $changedFields);
                }
            } else {
                $summary[] = 'Record updated (no field changes detected)';
            }
        } elseif ($audit->event === 'deleted') {
            $fields = array_keys($audit->old_values ?? []);
            if (count($fields) > 0) {
                $summary[] = 'Record deleted with '.count($fields).' field'.(count($fields) > 1 ? 's' : '');
            } else {
                $summary[] = 'Record deleted';
            }
        } elseif ($audit->event === 'restored') {
            $summary[] = 'Record restored';
        } elseif ($audit->event === 'login') {
            $data = $audit->new_values ?? [];
            if (isset($data['user_name'])) {
                $summary[] = 'User login: '.$data['user_name'];
            } else {
                $summary[] = 'User login event';
            }
        } else {
            // Handle other custom events
            $summary[] = ucfirst($audit->event).' event';
            if ($audit->new_values && count($audit->new_values) > 0) {
                $summary[] = 'Data: '.json_encode($audit->new_values);
            }
        }

        return $summary;
    }
}
