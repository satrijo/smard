<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;

class TestWhatsAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test {--message= : Custom message to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp service functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing WhatsApp Service...');
        
        $whatsappService = new WhatsAppService();
        
        // Test custom message or default test message
        $message = $this->option('message') ?? 'Test message dari Aerodrome Warning System - ' . now()->format('d/m/Y H:i:s');
        
        $this->info("Sending message: {$message}");
        
        try {
            $result = $whatsappService->sendMessage($message);
            
            if ($result) {
                $this->info('✅ WhatsApp message sent successfully!');
            } else {
                $this->error('❌ Failed to send WhatsApp message');
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
        }
        
        return 0;
    }
}
