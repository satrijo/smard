<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckWhatsAppStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check WhatsApp API connection status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking WhatsApp API Status...');
        
        $apiUrl = config('whatsapp.api_url');
        $defaultNumber = config('whatsapp.default_number');
        
        $this->info("API URL: {$apiUrl}");
        $this->info("Default Number: {$defaultNumber}");
        
        try {
            $response = Http::withoutVerifying()->timeout(10)->post($apiUrl, [
                'message' => 'Test koneksi WhatsApp API',
                'number' => $defaultNumber,
                'group' => config('whatsapp.group'),
            ]);
            
            $this->info("HTTP Status: " . $response->status());
            $this->info("Response Body: " . $response->body());
            
            if ($response->successful()) {
                $responseData = $response->json();
                $this->info("Response JSON: " . json_encode($responseData, JSON_PRETTY_PRINT));
                
                if (isset($responseData['status'])) {
                    if ($responseData['status'] === true) {
                        $this->info('✅ WhatsApp API is connected and working!');
                    } else {
                        $this->error('❌ WhatsApp API returned false status');
                        if (isset($responseData['message'])) {
                            $this->error('Message: ' . $responseData['message']);
                        }
                    }
                } else {
                    $this->warn('⚠️  Response does not contain status field');
                }
            } else {
                $this->error('❌ HTTP request failed with status: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
        }
        
        return 0;
    }
}
