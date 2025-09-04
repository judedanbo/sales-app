<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MailSettings extends Settings
{
    // SMTP Configuration
    public string $smtp_host;
    public int $smtp_port;
    public string $smtp_username;
    public string $smtp_password;
    public string $smtp_encryption;
    
    // From Address Configuration
    public string $from_email;
    public string $from_name;
    
    // Email Templates
    public bool $use_html_emails;
    public string $default_template;
    
    // Queue Settings
    public bool $queue_emails;
    public string $queue_connection;
    
    // Notification Settings
    public bool $send_low_stock_alerts;
    public bool $send_receipt_emails;
    public bool $send_backup_notifications;
    
    // Alert Recipients
    public string $admin_email;
    public string $backup_notification_emails; // JSON array of emails
    
    public static function group(): string
    {
        return 'mail';
    }
    
    public static function encrypted(): array
    {
        return [
            'smtp_password',
        ];
    }
    
    /**
     * Get backup notification emails as array
     */
    public function getBackupNotificationEmails(): array
    {
        return json_decode($this->backup_notification_emails, true) ?: [];
    }
    
    /**
     * Set backup notification emails from array
     */
    public function setBackupNotificationEmails(array $emails): void
    {
        $this->backup_notification_emails = json_encode($emails);
    }
}