<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,

            // Format display name (convert underscores to spaces and capitalize)
            'display_name' => ucwords(str_replace('_', ' ', $this->name)),

            // Count relationships
            'users_count' => $this->whenCounted('users'),
            'permissions_count' => $this->whenCounted('permissions'),

            // Related permissions
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                    ];
                });
            }),

            // Related users (limited for performance)
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'user_type' => $user->user_type,
                        'is_active' => $user->is_active,
                    ];
                });
            }),

            // Sample users (first 5)
            'sample_users' => $this->when($this->relationLoaded('users') && $this->users->count() > 0, function () {
                return $this->users->take(5)->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });
            }),

            // Permission groups (organized by prefix)
            'permission_groups' => $this->when($this->relationLoaded('permissions'), function () {
                return $this->permissions->groupBy(function ($permission) {
                    $parts = explode('_', $permission->name);

                    return $parts[0] ?? 'other'; // Group by first word (create, view, edit, etc.)
                })->map(function ($permissions, $group) {
                    return [
                        'group' => $group,
                        'permissions' => $permissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                            ];
                        }),
                    ];
                });
            }),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
