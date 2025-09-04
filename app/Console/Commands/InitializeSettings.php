<?php

namespace App\Console\Commands;

use App\Settings\GeneralSettings;
use App\Settings\InventorySettings;
use App\Settings\MailSettings;
use App\Settings\SalesSettings;
use Illuminate\Console\Command;

class InitializeSettings extends Command
{
    protected $signature = 'settings:init';
    protected $description = 'Initialize settings with default values';

    public function handle(): int
    {
        $this->info('Initializing settings with default values...');

        try {
            // Initialize General Settings
            $this->info('Initializing General Settings...');
            $generalSettings = new GeneralSettings([
                'app_name' => config('app.name', 'Sales Application'),
                'app_description' => 'School supplies and uniform sales management system',
                'app_version' => '1.0.0',
                'company_name' => 'Your Company Name',
                'company_address' => 'Your Company Address',
                'company_phone' => '+1234567890',
                'company_email' => 'info@company.com',
                'company_website' => 'https://company.com',
                'business_registration_number' => '',
                'tax_identification_number' => '',
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'timezone' => config('app.timezone', 'UTC'),
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
                'maintenance_mode' => false,
                'maintenance_message' => 'We are currently performing maintenance. Please check back soon.',
                'debug_mode' => config('app.debug', false),
                'log_level' => 'info',
                'enable_user_registration' => true,
                'enable_email_verification' => false,
                'enable_two_factor_auth' => false,
                'enable_api_access' => true,
                'enable_file_uploads' => true,
                'items_per_page' => 25,
                'max_file_upload_size' => 10,
                'session_timeout' => 120,
                'max_login_attempts' => 5,
                'auto_backup_enabled' => true,
                'backup_frequency' => 'daily',
                'backup_retention_days' => 30,
            ]);
            $generalSettings->save();

            // Initialize Mail Settings
            $this->info('Initializing Mail Settings...');
            $mailSettings = new MailSettings([
                'smtp_host' => config('mail.mailers.smtp.host', 'localhost'),
                'smtp_port' => (int) config('mail.mailers.smtp.port', 587),
                'smtp_username' => config('mail.mailers.smtp.username', '') ?? '',
                'smtp_password' => config('mail.mailers.smtp.password', '') ?? '',
                'smtp_encryption' => config('mail.mailers.smtp.encryption', 'tls'),
                'from_email' => config('mail.from.address', 'noreply@company.com'),
                'from_name' => config('mail.from.name', $generalSettings->company_name),
                'use_html_emails' => true,
                'default_template' => 'default',
                'queue_emails' => true,
                'queue_connection' => config('queue.default', 'sync'),
                'send_low_stock_alerts' => true,
                'send_receipt_emails' => false,
                'send_backup_notifications' => true,
                'admin_email' => 'admin@company.com',
                'backup_notification_emails' => json_encode(['admin@company.com']),
            ]);
            $mailSettings->save();

            // Initialize Sales Settings
            $this->info('Initializing Sales Settings...');
            $salesSettings = new SalesSettings([
                'default_payment_method' => 'cash',
                'allow_partial_payments' => false,
                'require_customer_info' => false,
                'auto_generate_receipt_numbers' => true,
                'receipt_number_prefix' => 'RCP-',
                'receipt_number_length' => 6,
                'allow_discounts' => false,
                'max_discount_percentage' => 0.0,
                'require_discount_approval' => true,
                'show_taxes' => true,
                'default_tax_rate' => 0.0,
                'tax_name' => 'VAT',
                'print_receipts_automatically' => true,
                'email_receipts' => false,
                'receipt_template' => 'default',
                'receipt_footer_text' => 'Thank you for your purchase!',
                'show_barcode_on_receipt' => false,
                'show_qr_code_on_receipt' => false,
                'max_sale_amount' => 10000.00,
                'max_items_per_sale' => 100,
                'validate_stock_before_sale' => true,
                'allow_negative_stock' => false,
                'require_cashier_approval_above_limit' => true,
                'cashier_approval_limit' => 1000.00,
                'allow_returns' => true,
                'return_period_days' => 30,
                'require_receipt_for_return' => true,
                'allow_refunds' => true,
                'require_manager_approval_for_refunds' => true,
                'enable_school_pricing' => true,
                'require_school_selection' => true,
                'validate_class_requirements' => true,
                'bulk_discount_threshold' => 10,
                'bulk_discount_percentage' => 0.0,
                'track_sales_by_cashier' => true,
                'track_sales_by_school' => true,
                'track_sales_by_category' => true,
                'generate_daily_reports' => true,
                'default_report_format' => 'pdf',
            ]);
            $salesSettings->save();

            // Initialize Inventory Settings
            $this->info('Initializing Inventory Settings...');
            $inventorySettings = new InventorySettings([
                'track_inventory' => true,
                'allow_negative_stock' => false,
                'auto_deduct_on_sale' => true,
                'require_stock_confirmation' => false,
                'stock_valuation_method' => 'FIFO',
                'enable_low_stock_alerts' => true,
                'alert_method' => 'email',
                'default_reorder_level' => 10,
                'critical_stock_level' => 5,
                'low_stock_notification_emails' => json_encode(['manager@company.com']),
                'auto_generate_purchase_orders' => false,
                'default_reorder_quantity' => 50,
                'reorder_multiplier' => 1.5,
                'suggest_reorder_quantities' => true,
                'lead_time_days' => 7,
                'require_stock_counts' => true,
                'stock_count_frequency_days' => 90,
                'lock_stock_during_count' => true,
                'variance_tolerance_percentage' => 5.0,
                'require_approval_for_adjustments' => true,
                'auto_generate_skus' => true,
                'sku_prefix' => 'SKU-',
                'sku_length' => 8,
                'require_barcodes' => false,
                'barcode_format' => 'CODE128',
                'multi_location_inventory' => false,
                'default_location' => 'Main Warehouse',
                'track_bin_locations' => false,
                'require_location_on_transactions' => false,
                'track_cost_prices' => true,
                'update_cost_on_purchase' => true,
                'calculate_landed_costs' => false,
                'default_costing_method' => 'average',
                'alert_on_negative_margins' => true,
                'require_product_categories' => true,
                'enable_product_attributes' => true,
                'track_serial_numbers' => false,
                'track_batch_numbers' => false,
                'track_expiry_dates' => false,
                'generate_inventory_reports' => true,
                'report_frequency' => 'weekly',
                'track_fast_slow_moving' => true,
                'fast_moving_threshold_days' => 30,
                'slow_moving_threshold_days' => 180,
            ]);
            $inventorySettings->save();

            $this->info('Settings initialized successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to initialize settings: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}