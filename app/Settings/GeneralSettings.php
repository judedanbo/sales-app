<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    // Application Information
    public string $app_name;
    public string $app_description;
    public string $app_version;
    public string $company_name;
    public string $company_address;
    public string $company_phone;
    public string $company_email;
    public string $company_website;
    
    // Business Settings
    public string $business_registration_number;
    public string $tax_identification_number;
    public string $currency_code;
    public string $currency_symbol;
    public string $timezone;
    public string $date_format;
    public string $time_format;
    
    // System Settings
    public bool $maintenance_mode;
    public string $maintenance_message;
    public bool $debug_mode;
    public string $log_level;
    
    // Feature Flags
    public bool $enable_user_registration;
    public bool $enable_email_verification;
    public bool $enable_two_factor_auth;
    public bool $enable_api_access;
    public bool $enable_file_uploads;
    
    // Pagination & Limits
    public int $items_per_page;
    public int $max_file_upload_size; // in MB
    public int $session_timeout; // in minutes
    public int $max_login_attempts;
    
    // Backup Settings
    public bool $auto_backup_enabled;
    public string $backup_frequency; // daily, weekly, monthly
    public int $backup_retention_days;
    
    public static function group(): string
    {
        return 'general';
    }
    
    public static function encrypted(): array
    {
        return [];
    }
    
    /**
     * Get formatted currency
     */
    public function formatCurrency(float $amount): string
    {
        return $this->currency_symbol . number_format($amount, 2);
    }
    
    /**
     * Check if feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $property = 'enable_' . $feature;
        return property_exists($this, $property) ? $this->$property : false;
    }
    
    /**
     * Get max file upload size in bytes
     */
    public function getMaxFileUploadSizeInBytes(): int
    {
        return $this->max_file_upload_size * 1024 * 1024;
    }
}