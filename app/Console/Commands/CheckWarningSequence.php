<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AerodromeWarning;

class CheckWarningSequence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warnings:check-sequence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check current warning sequence numbers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Current Aerodrome Warnings:');
        $this->info('==========================');
        
        $warnings = AerodromeWarning::orderBy('created_at', 'asc')->get();
        
        if ($warnings->isEmpty()) {
            $this->warn('No warnings found in database.');
            return;
        }
        
        $this->table(
            ['ID', 'Airport', 'Warning Number', 'Sequence', 'Created At (UTC)', 'Status'],
            $warnings->map(function ($warning) {
                return [
                    $warning->id,
                    $warning->airport_code,
                    $warning->warning_number,
                    $warning->sequence_number,
                    $warning->created_at->utc()->format('Y-m-d H:i:s'),
                    $warning->status
                ];
            })->toArray()
        );
        
        // Check next sequence number
        $nextSequence = AerodromeWarning::getNextSequenceNumber('WAHL');
        $this->info("\nNext sequence number for WAHL: {$nextSequence}");
        
        // Check UTC date
        $utcDate = now()->utc()->toDateString();
        $this->info("Current UTC date: {$utcDate}");
    }
}
