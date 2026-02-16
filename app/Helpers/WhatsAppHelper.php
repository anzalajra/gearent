<?php

namespace App\Helpers;

use App\Models\Setting;

class WhatsAppHelper
{
    public static function getLink(?string $phone, string $message): string
    {
        // Clean phone number
        $phone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
        
        // Ensure international format (assuming ID 62 for now if starts with 0)
        if (!empty($phone) && str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // WhatsApp Click to Chat format: https://wa.me/<number>?text=<urlencodedtext>
        $encodedMessage = urlencode($message);
        
        if (empty($phone)) {
            return "https://wa.me/?text={$encodedMessage}";
        }
        
        return "https://wa.me/{$phone}?text={$encodedMessage}";
    }

    public static function parseTemplate(string $templateKey, array $data): string
    {
        // Get template from settings, default to empty string
        $template = Setting::get($templateKey, '');
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $template = str_replace("[{$key}]", $value, $template);
        }
        
        return $template;
    }
}
