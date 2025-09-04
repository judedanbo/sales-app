<?php

namespace App\Settings;

use Illuminate\Support\Facades\App;

class SettingsHelper
{
    /**
     * Get general settings instance
     */
    public static function general(): GeneralSettings
    {
        return App::make(GeneralSettings::class);
    }
    
    /**
     * Get mail settings instance
     */
    public static function mail(): MailSettings
    {
        return App::make(MailSettings::class);
    }
    
    /**
     * Get sales settings instance
     */
    public static function sales(): SalesSettings
    {
        return App::make(SalesSettings::class);
    }
    
    /**
     * Get inventory settings instance
     */
    public static function inventory(): InventorySettings
    {
        return App::make(InventorySettings::class);
    }
    
    /**
     * Check if a feature is enabled
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return self::general()->isFeatureEnabled($feature);
    }
    
    /**
     * Format currency amount
     */
    public static function formatCurrency(float $amount): string
    {
        return self::general()->formatCurrency($amount);
    }
    
    /**
     * Get company information
     */
    public static function getCompanyInfo(): array
    {
        $general = self::general();
        
        return [
            'name' => $general->company_name,
            'address' => $general->company_address,
            'phone' => $general->company_phone,
            'email' => $general->company_email,
            'website' => $general->company_website,
            'registration_number' => $general->business_registration_number,
            'tax_number' => $general->tax_identification_number,
        ];
    }
    
    /**
     * Get currency settings
     */
    public static function getCurrencySettings(): array
    {
        $general = self::general();
        
        return [
            'code' => $general->currency_code,
            'symbol' => $general->currency_symbol,
        ];
    }
    
    /**
     * Check if amount requires approval
     */
    public static function requiresSalesApproval(float $amount): bool
    {
        return self::sales()->requiresApproval($amount);
    }
    
    /**
     * Check if stock is low
     */
    public static function isStockLow(int $currentStock, int $reorderLevel = null): bool
    {
        return self::inventory()->isStockLow($currentStock, $reorderLevel);
    }
    
    /**
     * Check if stock is critical
     */
    public static function isStockCritical(int $currentStock): bool
    {
        return self::inventory()->isStockCritical($currentStock);
    }
    
    /**
     * Generate next receipt number
     */
    public static function generateReceiptNumber(int $currentNumber): string
    {
        return self::sales()->generateReceiptNumber($currentNumber);
    }
    
    /**
     * Generate next SKU
     */
    public static function generateSku(int $currentNumber): string
    {
        return self::inventory()->generateSku($currentNumber);
    }
    
    /**
     * Get items per page for pagination
     */
    public static function getItemsPerPage(): int
    {
        return self::general()->items_per_page;
    }
    
    /**
     * Get max file upload size in bytes
     */
    public static function getMaxFileUploadSize(): int
    {
        return self::general()->getMaxFileUploadSizeInBytes();
    }
    
    /**
     * Get low stock notification emails
     */
    public static function getLowStockNotificationEmails(): array
    {
        return self::inventory()->getLowStockNotificationEmails();
    }
    
    /**
     * Get backup notification emails
     */
    public static function getBackupNotificationEmails(): array
    {
        return self::mail()->getBackupNotificationEmails();
    }
}