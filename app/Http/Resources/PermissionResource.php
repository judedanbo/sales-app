<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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

            // Get category from permission name (first word before underscore)
            'category' => $this->getCategory(),
            'category_display' => ucfirst($this->getCategory()),

            // Action (second word after underscore, if exists)
            'action' => $this->getAction(),
            'action_display' => ucfirst($this->getAction()),

            // Count relationships
            'roles_count' => $this->whenCounted('roles'),

            // Related roles
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                        'guard_name' => $role->guard_name,
                    ];
                });
            }),

            // Sample roles (first 5)
            'sample_roles' => $this->when($this->relationLoaded('roles') && $this->roles->count() > 0, function () {
                return $this->roles->take(5)->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                    ];
                });
            }),

            // Users count (through roles)
            'users_count' => $this->when($this->relationLoaded('roles'), function () {
                return $this->roles->sum(function ($role) {
                    return $role->users->count();
                });
            }),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get the category from permission name.
     */
    private function getCategory(): string
    {
        $parts = explode('_', $this->name);

        return $parts[0] ?? 'other';
    }

    /**
     * Get the action from permission name.
     */
    private function getAction(): string
    {
        $parts = explode('_', $this->name);

        return isset($parts[1]) ? implode('_', array_slice($parts, 1)) : '';
    }
}
