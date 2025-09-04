<?php

namespace Database\Seeders;

use App\Settings\GeneralSettings;
use App\Settings\InventorySettings;
use App\Settings\MailSettings;
use App\Settings\SalesSettings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // General Settings
        $generalSettings = app(GeneralSettings::class);
        $generalSettings->app_name = config('app.name', 'Sales Application');
        $generalSettings->app_description = 'School supplies and uniform sales management system';
        $generalSettings->app_version = '1.0.0';
        $generalSettings->company_name = 'Your Company Name';
        $generalSettings->company_address = 'Your Company Address';
        $generalSettings->company_phone = '+1234567890';
        $generalSettings->company_email = 'info@company.com';
        $generalSettings->company_website = 'https://company.com';
        $generalSettings->business_registration_number = '';
        $generalSettings->tax_identification_number = '';
        $generalSettings->currency_code = 'USD';
        $generalSettings->currency_symbol = '$';
        $generalSettings->timezone = config('app.timezone', 'UTC');
        $generalSettings->date_format = 'Y-m-d';
        $generalSettings->time_format = 'H:i:s';
        $generalSettings->maintenance_mode = false;
        $generalSettings->maintenance_message = 'We are currently performing maintenance. Please check back soon.';
        $generalSettings->debug_mode = config('app.debug', false);
        $generalSettings->log_level = 'info';
        $generalSettings->enable_user_registration = true;
        $generalSettings->enable_email_verification = false;
        $generalSettings->enable_two_factor_auth = false;
        $generalSettings->enable_api_access = true;
        $generalSettings->enable_file_uploads = true;
        $generalSettings->items_per_page = 25;
        $generalSettings->max_file_upload_size = 10; // 10MB
        $generalSettings->session_timeout = 120; // 2 hours
        $generalSettings->max_login_attempts = 5;
        $generalSettings->auto_backup_enabled = true;
        $generalSettings->backup_frequency = 'daily';
        $generalSettings->backup_retention_days = 30;
        $generalSettings->save();

        // Mail Settings
        $mailSettings = app(MailSettings::class);
        $mailSettings->smtp_host = config('mail.mailers.smtp.host', 'localhost');
        $mailSettings->smtp_port = config('mail.mailers.smtp.port', 587);
        $mailSettings->smtp_username = config('mail.mailers.smtp.username', '');
        $mailSettings->smtp_password = config('mail.mailers.smtp.password', '');
        $mailSettings->smtp_encryption = config('mail.mailers.smtp.encryption', 'tls');
        $mailSettings->from_email = config('mail.from.address', 'noreply@company.com');
        $mailSettings->from_name = config('mail.from.name', $generalSettings->company_name);
        $mailSettings->use_html_emails = true;
        $mailSettings->default_template = 'default';
        $mailSettings->queue_emails = true;
        $mailSettings->queue_connection = config('queue.default', 'sync');
        $mailSettings->send_low_stock_alerts = true;
        $mailSettings->send_receipt_emails = false;
        $mailSettings->send_backup_notifications = true;
        $mailSettings->admin_email = 'admin@company.com';
        $mailSettings->backup_notification_emails = json_encode(['admin@company.com']);
        $mailSettings->save();

        // Sales Settings
        $salesSettings = app(SalesSettings::class);
        $salesSettings->default_payment_method = 'cash';
        $salesSettings->allow_partial_payments = false;
        $salesSettings->require_customer_info = false;
        $salesSettings->auto_generate_receipt_numbers = true;
        $salesSettings->receipt_number_prefix = 'RCP-';
        $salesSettings->receipt_number_length = 6;
        $salesSettings->allow_discounts = false; // As per requirements: No discounts
        $salesSettings->max_discount_percentage = 0.0;
        $salesSettings->require_discount_approval = true;
        $salesSettings->show_taxes = true;
        $salesSettings->default_tax_rate = 0.0; // No tax by default
        $salesSettings->tax_name = 'VAT';
        $salesSettings->print_receipts_automatically = true;
        $salesSettings->email_receipts = false;
        $salesSettings->receipt_template = 'default';
        $salesSettings->receipt_footer_text = 'Thank you for your purchase!';
        $salesSettings->show_barcode_on_receipt = false;
        $salesSettings->show_qr_code_on_receipt = false;
        $salesSettings->max_sale_amount = 10000.00;
        $salesSettings->max_items_per_sale = 100;
        $salesSettings->validate_stock_before_sale = true;
        $salesSettings->allow_negative_stock = false;
        $salesSettings->require_cashier_approval_above_limit = true;
        $salesSettings->cashier_approval_limit = 1000.00;
        $salesSettings->allow_returns = true;
        $salesSettings->return_period_days = 30;
        $salesSettings->require_receipt_for_return = true;
        $salesSettings->allow_refunds = true;
        $salesSettings->require_manager_approval_for_refunds = true;
        $salesSettings->enable_school_pricing = true;
        $salesSettings->require_school_selection = true;
        $salesSettings->validate_class_requirements = true;
        $salesSettings->bulk_discount_threshold = 10;
        $salesSettings->bulk_discount_percentage = 0.0; // No discounts
        $salesSettings->track_sales_by_cashier = true;
        $salesSettings->track_sales_by_school = true;
        $salesSettings->track_sales_by_category = true;
        $salesSettings->generate_daily_reports = true;
        $salesSettings->default_report_format = 'pdf';
        $salesSettings->save();

        // Inventory Settings
        $inventorySettings = app(InventorySettings::class);
        $inventorySettings->track_inventory = true;
        $inventorySettings->allow_negative_stock = false;
        $inventorySettings->auto_deduct_on_sale = true;
        $inventorySettings->require_stock_confirmation = false;
        $inventorySettings->stock_valuation_method = 'FIFO';
        $inventorySettings->enable_low_stock_alerts = true;
        $inventorySettings->alert_method = 'email';
        $inventorySettings->default_reorder_level = 10;
        $inventorySettings->critical_stock_level = 5;
        $inventorySettings->low_stock_notification_emails = json_encode(['manager@company.com']);
        $inventorySettings->auto_generate_purchase_orders = false;
        $inventorySettings->default_reorder_quantity = 50;
        $inventorySettings->reorder_multiplier = 1.5;
        $inventorySettings->suggest_reorder_quantities = true;
        $inventorySettings->lead_time_days = 7;
        $inventorySettings->require_stock_counts = true;
        $inventorySettings->stock_count_frequency_days = 90;
        $inventorySettings->lock_stock_during_count = true;
        $inventorySettings->variance_tolerance_percentage = 5.0;
        $inventorySettings->require_approval_for_adjustments = true;
        $inventorySettings->auto_generate_skus = true;
        $inventorySettings->sku_prefix = 'SKU-';
        $inventorySettings->sku_length = 8;
        $inventorySettings->require_barcodes = false;
        $inventorySettings->barcode_format = 'CODE128';
        $inventorySettings->multi_location_inventory = false;
        $inventorySettings->default_location = 'Main Warehouse';
        $inventorySettings->track_bin_locations = false;
        $inventorySettings->require_location_on_transactions = false;
        $inventorySettings->track_cost_prices = true;
        $inventorySettings->update_cost_on_purchase = true;
        $inventorySettings->calculate_landed_costs = false;
        $inventorySettings->default_costing_method = 'average';
        $inventorySettings->alert_on_negative_margins = true;
        $inventorySettings->require_product_categories = true;
        $inventorySettings->enable_product_attributes = true;
        $inventorySettings->track_serial_numbers = false;
        $inventorySettings->track_batch_numbers = false;
        $inventorySettings->track_expiry_dates = false;
        $inventorySettings->generate_inventory_reports = true;
        $inventorySettings->report_frequency = 'weekly';
        $inventorySettings->track_fast_slow_moving = true;
        $inventorySettings->fast_moving_threshold_days = 30;
        $inventorySettings->slow_moving_threshold_days = 180;
        $inventorySettings->save();
    }
}