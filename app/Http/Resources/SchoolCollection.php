<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SchoolCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => SchoolResource::collection($this->collection),
            'meta' => [
                'total_count' => $this->collection->count(),
                'filters_applied' => [
                    'school_type' => $request->get('school_type'),
                    'status' => $request->get('status'),
                    'search' => $request->get('search'),
                    'board_affiliation' => $request->get('board_affiliation'),
                ],
                'sorting' => [
                    'sort_by' => $request->get('sort_by', 'school_name'),
                    'sort_direction' => $request->get('sort_direction', 'asc'),
                ],
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
            'message' => 'Schools retrieved successfully',
        ];
    }
}
