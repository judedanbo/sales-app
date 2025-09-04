<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InventorySettings extends Settings
{
    // Stock Management
    public bool $track_inventory;
    public bool $allow_negative_stock;
    public bool $auto_deduct_on_sale;
    public bool $require_stock_confirmation;
    public string $stock_valuation_method; // FIFO, LIFO, Average
    
    // Low Stock Alerts
    public bool $enable_low_stock_alerts;
    public string $alert_method; // email, notification, both
    public int $default_reorder_level;
    public int $critical_stock_level;
    public string $low_stock_notification_emails; // JSON array
    
    // Reorder Management
    public bool $auto_generate_purchase_orders;
    public int $default_reorder_quantity;
    public float $reorder_multiplier;
    public bool $suggest_reorder_quantities;
    public int $lead_time_days;
    
    // Stock Counting & Auditing
    public bool $require_stock_counts;
    public int $stock_count_frequency_days;
    public bool $lock_stock_during_count;
    public float $variance_tolerance_percentage;
    public bool $require_approval_for_adjustments;
    
    // Barcode & SKU Settings
    public bool $auto_generate_skus;
    public string $sku_prefix;
    public int $sku_length;
    public bool $require_barcodes;
    public string $barcode_format; // CODE128, EAN13, etc.
    
    // Location & Warehouse
    public bool $multi_location_inventory;
    public string $default_location;
    public bool $track_bin_locations;
    public bool $require_location_on_transactions;
    
    // Cost & Pricing
    public bool $track_cost_prices;
    public bool $update_cost_on_purchase;
    public bool $calculate_landed_costs;
    public string $default_costing_method; // standard, actual, average
    public bool $alert_on_negative_margins;
    
    // Categories & Classifications
    public bool $require_product_categories;
    public bool $enable_product_attributes;
    public bool $track_serial_numbers;
    public bool $track_batch_numbers;
    public bool $track_expiry_dates;
    
    // Reports & Analytics
    public bool $generate_inventory_reports;
    public string $report_frequency; // daily, weekly, monthly
    public bool $track_fast_slow_moving;
    public int $fast_moving_threshold_days;
    public int $slow_moving_threshold_days;
    
    public static function group(): string
    {
        return 'inventory';
    }
    
    public static function encrypted(): array
    {
        return [];
    }
    
    /**
     * Get low stock notification emails as array
     */
    public function getLowStockNotificationEmails(): array
    {
        return json_decode($this->low_stock_notification_emails, true) ?: [];
    }
    
    /**
     * Set low stock notification emails from array
     */
    public function setLowStockNotificationEmails(array $emails): void
    {
        $this->low_stock_notification_emails = json_encode($emails);
    }
    
    /**
     * Check if stock level is critical
     */
    public function isStockCritical(int $currentStock): bool
    {
        return $currentStock <= $this->critical_stock_level;
    }
    
    /**
     * Check if stock level is low
     */
    public function isStockLow(int $currentStock, int $reorderLevel = null): bool
    {
        $threshold = $reorderLevel ?: $this->default_reorder_level;
        return $currentStock <= $threshold;
    }
    
    /**
     * Calculate suggested reorder quantity
     */
    public function calculateReorderQuantity(int $currentStock, int $reorderLevel, int $averageUsage = null): int
    {
        if ($this->auto_generate_purchase_orders) {
            $baseQuantity = max($reorderLevel - $currentStock, $this->default_reorder_quantity);
            
            if ($averageUsage && $this->lead_time_days > 0) {
                $safetyStock = ceil($averageUsage * $this->lead_time_days * $this->reorder_multiplier);
                return max($baseQuantity, $safetyStock);
            }
            
            return $baseQuantity;
        }
        
        return $this->default_reorder_quantity;
    }
    
    /**
     * Generate next SKU
     */
    public function generateSku(int $currentNumber): string
    {
        if (!$this->auto_generate_skus) {
            return '';
        }
        
        $nextNumber = $currentNumber + 1;
        return $this->sku_prefix . str_pad($nextNumber, $this->sku_length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if variance is within tolerance
     */
    public function isVarianceWithinTolerance(int $systemStock, int $physicalStock): bool
    {
        if ($systemStock == 0) {
            return $physicalStock == 0;
        }
        
        $variance = abs($systemStock - $physicalStock);
        $variancePercentage = ($variance / $systemStock) * 100;
        
        return $variancePercentage <= $this->variance_tolerance_percentage;
    }
}