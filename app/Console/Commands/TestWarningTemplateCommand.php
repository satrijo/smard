<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;
use App\Models\AerodromeWarning;
use Carbon\Carbon;

class TestWarningTemplateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test-template {--type=warning : Type of message (warning/cancellation)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp warning message template with sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info("Testing WhatsApp {$type} template...");
        
        // Create sample warning data
        $sampleWarning = new AerodromeWarning([
            'airport_code' => 'WAHL',
            'warning_number' => 'AD WRNG 01',
            'sequence_number' => 1,
            'start_time' => Carbon::create(2025, 8, 13, 0, 0, 0),
            'end_time' => Carbon::create(2025, 8, 13, 2, 0, 0),
            'phenomena' => [
                [
                    'type' => 'VIS',
                    'details' => [
                        'visibility' => '3000',
                        'visibilityCause' => 'BR'
                    ]
                ]
            ],
            'source' => 'OBS',
            'intensity' => 'NC',
            'observation_time' => Carbon::create(2025, 8, 13, 0, 0, 0),
            'preview_message' => 'WAHL AD WRNG 01 VALID 130000/130200 VIS 3000M BR OBS AT 0000Z NC=',
            'translation_message' => 'AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 1, BERLAKU TANGGAL 13 AGUSTUS 2025, ANTARA PUKUL 00.00 - 02.00 UTC, VISIBILITY 3000 METER, TERJADI KABUT TIPIS (HALIMUN) / MIST, BERDASARKAN DATA OBSERVASI PUKUL 00.00 UTC, DIPRAKIRAKAN INTENSITAS AKAN TETAP TIDAK ADA PERUBAHAN.',
            'forecaster_name' => 'Rudi Hermawan',
            'forecaster_nip' => '198905152010011005',
            'status' => 'ACTIVE',
            'created_at' => Carbon::now(),
        ]);
        
        $whatsappService = new WhatsAppService();
        
        if ($type === 'cancellation') {
            // Create sample cancellation warning
            $sampleCancellation = new AerodromeWarning([
                'airport_code' => 'WAHL',
                'warning_number' => 'AD WRNG 02',
                'sequence_number' => 2,
                'start_time' => Carbon::create(2025, 8, 13, 1, 0, 0),
                'end_time' => Carbon::create(2025, 8, 13, 2, 0, 0),
                'phenomena' => [],
                'source' => 'FCST',
                'intensity' => 'NC',
                'preview_message' => 'WAHL AD WRNG 02 VALID 130100/130200 CNL AD WRNG 01 130000/130200=',
                'translation_message' => 'AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 2, BERLAKU TANGGAL 13 AGUSTUS 2025, ANTARA PUKUL 01.00 - 02.00 UTC, BAHWA PERINGATAN DINI CUACA NOMOR 1 (TANGGAL 13 AGUSTUS 2025, ANTARA PUKUL 00.00 - 02.00 UTC) TELAH BERAKHIR / DIBATALKAN.',
                'forecaster_name' => 'Rudi Hermawan',
                'forecaster_nip' => '198905152010011005',
                'status' => 'ACTIVE',
                'created_at' => Carbon::now(),
            ]);
            
            $result = $whatsappService->sendCancellationMessage($sampleCancellation, $sampleWarning);
            $this->info("Sending cancellation template test...");
        } else {
            $result = $whatsappService->sendAerodromeWarning($sampleWarning);
            $this->info("Sending warning template test...");
        }
        
        if ($result) {
            $this->info('✅ WhatsApp template test sent successfully!');
        } else {
            $this->error('❌ Failed to send WhatsApp template test');
        }
        
        return 0;
    }
}
