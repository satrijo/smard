<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;
use App\Models\AerodromeWarning;

class ResendWhatsAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:resend {warning_id : ID of the warning to resend} {--type=warning : Type of message (warning/cancellation)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resend WhatsApp notification for a specific warning';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $warningId = $this->argument('warning_id');
        $type = $this->option('type');
        
        $this->info("Resending WhatsApp notification for warning ID: {$warningId}");
        
        try {
            $warning = AerodromeWarning::findOrFail($warningId);
            $whatsappService = new WhatsAppService();
            
            if ($type === 'cancellation') {
                // For cancellation, we need to find the original warning
                $originalWarning = AerodromeWarning::where('sequence_number', '<', $warning->sequence_number)
                    ->where('airport_code', $warning->airport_code)
                    ->whereDate('created_at', $warning->created_at->toDateString())
                    ->orderBy('sequence_number', 'desc')
                    ->first();
                
                if (!$originalWarning) {
                    $this->error('❌ Original warning not found for cancellation');
                    return 1;
                }
                
                $result = $whatsappService->sendCancellationMessage($warning, $originalWarning);
                $this->info("Sending cancellation message for warning AD WRNG {$originalWarning->sequence_number}");
            } else {
                $result = $whatsappService->sendAerodromeWarning($warning);
                $this->info("Sending warning message for AD WRNG {$warning->sequence_number}");
            }
            
            if ($result) {
                $this->info('✅ WhatsApp message sent successfully!');
            } else {
                $this->error('❌ Failed to send WhatsApp message');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
