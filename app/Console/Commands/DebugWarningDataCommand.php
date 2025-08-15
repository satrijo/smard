<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AerodromeWarning;

class DebugWarningDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:warning-data {warning_id : ID of the warning to debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug warning data from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $warningId = $this->argument('warning_id');
        
        try {
            $warning = AerodromeWarning::findOrFail($warningId);
            
            $this->info("=== DEBUG WARNING DATA ===");
            $this->info("ID: " . $warning->id);
            $this->info("Sequence Number: " . $warning->sequence_number);
            $this->info("Warning Number: " . $warning->warning_number);
            $this->info("Status: " . $warning->status);
            $this->info("Source: " . $warning->source);
            $this->info("Intensity: " . $warning->intensity);
            $this->info("Created At: " . $warning->created_at);
            
            $this->info("\n=== PREVIEW MESSAGE ===");
            $this->info($warning->preview_message);
            
            $this->info("\n=== TRANSLATION MESSAGE ===");
            $this->info($warning->translation_message);
            
            $this->info("\n=== RAW DATABASE DATA ===");
            $this->info("preview_message: " . $warning->getRawOriginal('preview_message'));
            $this->info("translation_message: " . $warning->getRawOriginal('translation_message'));
            
            // Check if this is a cancellation
            $isCancellation = str_contains($warning->getRawOriginal('preview_message') ?? '', 'CNL AD WRNG');
            $this->info("\n=== IS CANCELLATION ===");
            $this->info($isCancellation ? 'YES' : 'NO');
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
        
        return 0;
    }
}
