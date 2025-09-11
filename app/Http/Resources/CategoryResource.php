<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'color' => $this->color,
            'icon' => $this->icon,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // Computed attributes
            'full_name' => $this->getFullNameAttribute(),
            'depth' => $this->getDepth(),
            'is_root' => $this->isRoot(),
            'is_leaf' => $this->isLeaf(),
            'has_children' => $this->hasChildren(),
            'breadcrumb' => $this->getBreadcrumb(),

            // Relationships (only when loaded)
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'children_count' => $this->whenCounted('children'),
            'active_children_count' => $this->whenCounted('activeChildren'),
            'products_count' => $this->whenCounted('products'),

            // Creator/updater information
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'updated_by' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
            ]),

            // Status information
            'status' => [
                'is_active' => $this->is_active,
                'label' => $this->is_active ? 'Active' : 'Inactive',
                'class' => $this->is_active ? 'success' : 'warning',
            ],
        ];
    }
}
