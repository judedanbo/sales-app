<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'status' => $this->status,
            'unit_price' => $this->unit_price,
            'unit_type' => $this->unit_type,
            'reorder_level' => $this->reorder_level,
            'tax_rate' => $this->tax_rate,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'color' => $this->color,
            'brand' => $this->brand,
            'barcode' => $this->barcode,
            'image_url' => $this->image_url,
            'gallery' => $this->gallery,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'tags' => $this->tags,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // Relationships (only when loaded)
            'inventory' => $this->whenLoaded('inventory', function () {
                return [
                    'id' => $this->inventory->id,
                    'quantity_on_hand' => $this->inventory->quantity_on_hand,
                    'quantity_available' => $this->inventory->quantity_available,
                    'quantity_reserved' => $this->inventory->quantity_reserved,
                    'minimum_stock_level' => $this->inventory->minimum_stock_level,
                    'maximum_stock_level' => $this->inventory->maximum_stock_level,
                    'reorder_point' => $this->inventory->reorder_point,
                    'reorder_quantity' => $this->inventory->reorder_quantity,
                    'is_low_stock' => $this->inventory->is_low_stock,
                    'is_out_of_stock' => $this->inventory->is_out_of_stock,
                    'stock_status' => $this->inventory->stock_status,
                    'last_stock_count' => $this->inventory->last_stock_count?->toISOString(),
                    'last_movement_at' => $this->inventory->last_movement_at?->toISOString(),
                ];
            }),

            'current_price' => $this->whenLoaded('currentPrice', function () {
                return [
                    'id' => $this->currentPrice->id,
                    'price' => $this->currentPrice->price,
                    'final_price' => $this->currentPrice->final_price,
                    'status' => $this->currentPrice->status,
                    'valid_from' => $this->currentPrice->valid_from?->toISOString(),
                    'valid_to' => $this->currentPrice->valid_to?->toISOString(),
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            'updater' => $this->whenLoaded('updater', function () {
                return [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                    'email' => $this->updater->email,
                ];
            }),

            // Counts (only when loaded)
            'prices_count' => $this->whenCounted('prices'),
            'class_requirements_count' => $this->whenCounted('classRequirements'),

            // Computed attributes
            'formatted_price' => '$'.number_format($this->unit_price, 2),
            'formatted_tax_rate' => $this->tax_rate.'%',
            'formatted_weight' => $this->weight ? $this->weight.' kg' : null,
            'low_stock' => $this->low_stock ?? false,
            'status_label' => ucfirst($this->status),
            'status_color' => match ($this->status) {
                'active' => 'green',
                'inactive' => 'gray',
                'discontinued' => 'red',
                default => 'gray'
            },
        ];
    }
}
