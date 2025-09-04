<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SalesSettings extends Settings
{
    // Sales Configuration
    public string $default_payment_method; // cash, card, cheque
    public bool $allow_partial_payments;
    public bool $require_customer_info;
    public bool $auto_generate_receipt_numbers;
    public string $receipt_number_prefix;
    public int $receipt_number_length;
    
    // Pricing & Discounts
    public bool $allow_discounts;
    public float $max_discount_percentage;
    public bool $require_discount_approval;
    public bool $show_taxes;
    public float $default_tax_rate;
    public string $tax_name; // VAT, GST, Sales Tax, etc.
    
    // Receipt Settings
    public bool $print_receipts_automatically;
    public bool $email_receipts;
    public string $receipt_template;
    public string $receipt_footer_text;
    public bool $show_barcode_on_receipt;
    public bool $show_qr_code_on_receipt;
    
    // Sales Limits & Validations
    public float $max_sale_amount;
    public int $max_items_per_sale;
    public bool $validate_stock_before_sale;
    public bool $allow_negative_stock;
    public bool $require_cashier_approval_above_limit;
    public float $cashier_approval_limit;
    
    // Return & Refund Settings
    public bool $allow_returns;
    public int $return_period_days;
    public bool $require_receipt_for_return;
    public bool $allow_refunds;
    public bool $require_manager_approval_for_refunds;
    
    // School-specific Settings
    public bool $enable_school_pricing;
    public bool $require_school_selection;
    public bool $validate_class_requirements;
    public float $bulk_discount_threshold; // minimum quantity for bulk discount
    public float $bulk_discount_percentage;
    
    // Reporting & Analytics
    public bool $track_sales_by_cashier;
    public bool $track_sales_by_school;
    public bool $track_sales_by_category;
    public bool $generate_daily_reports;
    public string $default_report_format; // pdf, excel, csv
    
    public static function group(): string
    {
        return 'sales';
    }
    
    public static function encrypted(): array
    {
        return [];
    }
    
    /**
     * Check if amount requires approval
     */
    public function requiresApproval(float $amount): bool
    {
        return $this->require_cashier_approval_above_limit && 
               $amount > $this->cashier_approval_limit;
    }
    
    /**
     * Calculate tax amount
     */
    public function calculateTax(float $amount): float
    {
        return $this->show_taxes ? ($amount * $this->default_tax_rate / 100) : 0.0;
    }
    
    /**
     * Get next receipt number
     */
    public function generateReceiptNumber(int $currentNumber): string
    {
        $nextNumber = $currentNumber + 1;
        return $this->receipt_number_prefix . 
               str_pad($nextNumber, $this->receipt_number_length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if bulk discount applies
     */
    public function qualifiesForBulkDiscount(int $quantity): bool
    {
        return $quantity >= $this->bulk_discount_threshold;
    }
    
    /**
     * Calculate bulk discount amount
     */
    public function calculateBulkDiscount(float $amount, int $quantity): float
    {
        return $this->qualifiesForBulkDiscount($quantity) 
            ? ($amount * $this->bulk_discount_percentage / 100) 
            : 0.0;
    }
}