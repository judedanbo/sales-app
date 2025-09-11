<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => CategoryResource::collection($this->collection),
            'meta' => [
                'total_count' => $this->collection->count(),
                'filters_applied' => [
                    'parent_id' => $request->get('parent_id'),
                    'is_active' => $request->get('is_active'),
                    'search' => $request->get('search'),
                    'depth' => $request->get('depth'),
                ],
                'sorting' => [
                    'sort_by' => $request->get('sort_by', 'sort_order'),
                    'sort_direction' => $request->get('sort_direction', 'asc'),
                ],
                'view_type' => $request->get('view_type', 'list'),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Categories retrieved successfully',
        ];
    }
}
