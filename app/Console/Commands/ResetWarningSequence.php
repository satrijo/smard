<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AerodromeWarning;
use Illuminate\Support\Facades\DB;

class ResetWarningSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warnings:reset-sequence {--airport=WAHL : Airport code to reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset warning sequence numbers for a specific airport';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $airportCode = $this->option('airport');
        
        if (!$this->confirm("Are you sure you want to reset sequence numbers for airport {$airportCode}?")) {
            $this->info('Operation cancelled.');
            return;
        }
        
        $this->info("Resetting sequence numbers for airport {$airportCode}...");
        
        // Get all warnings for the airport, ordered by creation date
        $warnings = AerodromeWarning::where('airport_code', $airportCode)
            ->orderBy('created_at', 'asc')
            ->get();
        
        if ($warnings->isEmpty()) {
            $this->warn("No warnings found for airport {$airportCode}.");
            return;
        }
        
        DB::beginTransaction();
        
        try {
            $sequenceNumber = 1;
            
            foreach ($warnings as $warning) {
                $oldSequence = $warning->sequence_number;
                $newWarningNumber = AerodromeWarning::generateWarningNumber($sequenceNumber);
                
                $warning->update([
                    'sequence_number' => $sequenceNumber,
                    'warning_number' => $newWarningNumber
                ]);
                
                $this->info("Updated warning ID {$warning->id}: sequence {$oldSequence} → {$sequenceNumber}, warning number → {$newWarningNumber}");
                
                $sequenceNumber++;
            }
            
            DB::commit();
            $this->info("Successfully reset sequence numbers for {$airportCode}. Total warnings updated: " . ($sequenceNumber - 1));
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error resetting sequence numbers: " . $e->getMessage());
        }
    }
}
