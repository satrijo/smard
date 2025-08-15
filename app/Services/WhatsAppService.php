<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $apiUrl;
    private $defaultNumber;
    private $group;
    private $enabled;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url');
        $this->defaultNumber = config('whatsapp.default_number');
        $this->group = config('whatsapp.group');
        $this->enabled = config('whatsapp.enabled');
    }

    /**
     * Send WhatsApp message
     */
    public function sendMessage($message, $number = null, $group = null)
    {
        // Check if WhatsApp is enabled
        if (!$this->enabled) {
            Log::info('WhatsApp notifications are disabled');
            return true;
        }

        try {
            $response = Http::withoutVerifying()->timeout(30)->post($this->apiUrl, [
                'message' => $message,
                'number' => $number ?? $this->defaultNumber,
                'group' => $group ?? $this->group,
            ]);

            $responseData = $response->json();
            
            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
                Log::info('WhatsApp message sent successfully', [
                    'message' => $message,
                    'number' => $number ?? $this->defaultNumber,
                    'response' => $responseData
                ]);
                return true;
            } else {
                // Check if it's a connection issue
                if (isset($responseData['message']) && str_contains($responseData['message'], 'belum terkoneksi')) {
                    Log::warning('WhatsApp gateway not connected', [
                        'message' => $message,
                        'number' => $number ?? $this->defaultNumber,
                        'response' => $responseData
                    ]);
                } else {
                    Log::error('Failed to send WhatsApp message', [
                        'message' => $message,
                        'number' => $number ?? $this->defaultNumber,
                        'response' => $responseData,
                        'status' => $response->status()
                    ]);
                }
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending WhatsApp message', [
                'message' => $message,
                'number' => $number ?? $this->defaultNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send aerodrome warning message
     */
    public function sendAerodromeWarning($warning)
    {
        $message = $this->formatWarningMessage($warning);
        return $this->sendMessage($message);
    }

    /**
     * Send aerodrome warning cancellation message
     */
    public function sendCancellationMessage($cancellationWarning, $originalWarning)
    {
        $message = $this->formatCancellationMessage($cancellationWarning, $originalWarning);
        return $this->sendMessage($message);
    }

    /**
     * Format warning message for WhatsApp
     */
    private function formatWarningMessage($warning)
    {
        // Ambil langsung dari database tanpa parsing ulang
        // Gunakan getRawOriginal untuk memastikan data asli dari database
        $sandi = $warning->getRawOriginal('preview_message');
        $terjemahan = $warning->getRawOriginal('translation_message');
        
        // Split terjemahan berdasarkan koma dan format sebagai list
        $terjemahanList = $this->formatTranslationAsList($terjemahan);
        
        return "*AERODROME WARNING*\n\n" .
               "*" . $sandi . "*\n\n" .
               $terjemahanList;
    }

    /**
     * Format cancellation message for WhatsApp
     */
    private function formatCancellationMessage($cancellationWarning, $originalWarning)
    {
        // Ambil langsung dari database tanpa parsing ulang
        // Gunakan getRawOriginal untuk memastikan data asli dari database
        $sandi = $cancellationWarning->getRawOriginal('preview_message');
        $terjemahan = $cancellationWarning->getRawOriginal('translation_message');
        
        // Split terjemahan berdasarkan koma dan format sebagai list
        $terjemahanList = $this->formatTranslationAsList($terjemahan);
        
        return "*PEMBATALAN AERODROME WARNING*\n\n" .
               "*" . $sandi . "*\n\n" .
               $terjemahanList;
    }

    /**
     * Format translation as list based on comma separation
     */
    private function formatTranslationAsList($translation)
    {
        // Split by comma and clean up each part
        $parts = array_map('trim', explode(',', $translation));
        
        // Filter out empty parts and format as list
        $listItems = [];
        foreach ($parts as $part) {
            if (!empty($part)) {
                $listItems[] = "- " . $part;
            }
        }
        
        return implode("\n", $listItems);
    }
}
