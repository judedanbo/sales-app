<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of audit records
     */
    public function index(Request $request): Response
    {
        $this->authorizeAuditAccess();
        $audits = Audit::with(['user'])
            ->orderByDesc('created_at')
            // Apply filters
            ->when($request->auditable_type, function ($query) use ($request) {
                $query->where('auditable_type', $request->auditable_type);
            })
            ->when($request->user_id, function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            })
            ->when($request->event, function ($query) use ($request) {
                $query->where('event', $request->event);
            })
            ->when($request->from_date, function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->to_date, function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->to_date);
            })
            ->paginate(15)
            ->withQueryString();

        // $audits = $query->paginate(15);

        // Get filter options
        $filterOptions = [
            'models' => Audit::distinct()
                ->pluck('auditable_type')
                ->filter()
                ->values()
                ->map(fn ($model) => [
                    'value' => $model,
                    'label' => class_basename($model),
                ]),
            'events' => ['created', 'updated', 'deleted', 'restored'],
        ];

        return Inertia::render('Audits/Index', [
            'audits' => $audits,
            'filters' => $request->only(['auditable_type', 'user_id', 'event', 'from_date', 'to_date', 'search', 'sort_by', 'sort_direction']),
            'filter_options' => $filterOptions,
        ]);
    }

    /**
     * Display the specified audit record
     */
    public function show(Audit $audit): Response
    {
        $this->authorizeAuditAccess();
        $audit->load(['user']);

        return Inertia::render('Audits/Show', [
            'audit' => $audit,
            'old_values' => $audit->old_values,
            'new_values' => $audit->new_values,
        ]);
    }

    /**
     * Display audit statistics dashboard
     */
    public function dashboard(): Response
    {
        $this->authorizeAuditAccess();
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

        // Recent audits
        $recentAudits = Audit::with(['user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Events breakdown
        $events = Audit::selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        // Models breakdown
        $models = Audit::selectRaw('auditable_type, COUNT(*) as count')
            ->groupBy('auditable_type')
            ->pluck('count', 'auditable_type')
            ->mapWithKeys(function ($count, $model) {
                return [class_basename($model) => $count];
            })
            ->toArray();

        // Top users (by audit count)
        $topUsersData = Audit::selectRaw('user_id, COUNT(*) as audit_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->having('audit_count', '>', 0)
            ->orderByDesc('audit_count')
            ->limit(5)
            ->get();

        $topUsers = collect();
        foreach ($topUsersData as $userData) {
            $user = \App\Models\User::find($userData->user_id);
            if ($user) {
                $topUsers->push([
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'audit_count' => (int) $userData->audit_count,
                ]);
            }
        }

        return Inertia::render('Audits/Dashboard', [
            'stats' => $stats,
            'recent_audits' => $recentAudits,
            'events_breakdown' => $events,
            'models_breakdown' => $models,
            'top_users' => $topUsers,
        ]);
    }

    /**
     * Display audit timeline for a specific record
     */
    public function timeline(Request $request, string $modelType, int $modelId): Response
    {
        $this->authorizeAuditAccess();

        $fullModelType = 'App\Models\\'.$modelType;
        // dd($fullModelType);
        $audits = Audit::with(['user'])
            ->where('auditable_type', $fullModelType)
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

        $modelName = class_basename($modelType);

        return Inertia::render('Audits/Timeline', [
            'audits' => $audits,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'model_name' => $modelName,
        ]);
    }

    /**
     * Display audits for a specific user
     */
    public function forUser(Request $request, int $userId): Response
    {
        $this->authorizeAuditAccess();
        $query = Audit::with(['user'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        $audits = $query->paginate(15);

        // Get the user
        $user = \App\Models\User::findOrFail($userId);

        return Inertia::render('Audits/UserAudits', [
            'audits' => $audits,
            'user' => $user,
            'filters' => $request->only(['auditable_type', 'event']),
        ]);
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
