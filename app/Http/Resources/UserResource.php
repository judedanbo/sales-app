<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'user_type' => $this->user_type,
            'user_type_label' => $this->user_type->label(),
            'user_type_description' => $this->user_type->description(),
            'school_id' => $this->school_id,
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id,
                    'school_name' => $this->school->school_name,
                    'school_code' => $this->school->school_code,
                    'school_type' => $this->school->school_type,
                ];
            }),
            'phone' => $this->phone,
            'department' => $this->department,
            'bio' => $this->bio,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'display_name' => $this->display_name,

            // Role and permission information
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                    ];
                });
            }),
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                    ];
                });
            }),
            'all_permissions' => $this->when($this->relationLoaded('roles') || $this->relationLoaded('permissions'), function () {
                return $this->getAllPermissions()->pluck('name');
            }),

            // School official information if applicable
            'school_official' => $this->whenLoaded('schoolOfficial', function () {
                return [
                    'id' => $this->schoolOfficial->id,
                    'official_type' => $this->schoolOfficial->official_type,
                    'qualification' => $this->schoolOfficial->qualification,
                    'is_primary' => $this->schoolOfficial->is_primary,
                ];
            }),

            // Helper flags
            'is_school_user' => $this->isSchoolUser(),
            'is_system_user' => $this->isSystemUser(),
            'can_manage_schools' => $this->when($this->relationLoaded('roles') || $this->relationLoaded('permissions'), function () {
                return $this->canManageSchools();
            }),
            'can_manage_users' => $this->when($this->relationLoaded('roles') || $this->relationLoaded('permissions'), function () {
                return $this->canManageUsers();
            }),

            // Audit information
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
