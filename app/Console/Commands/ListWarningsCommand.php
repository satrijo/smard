<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AerodromeWarning;

class ListWarningsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:warnings {--limit=10 : Number of warnings to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all warnings in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $warnings = AerodromeWarning::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        $this->info("=== LATEST WARNINGS ===");
        
        foreach ($warnings as $warning) {
            $isCancellation = str_contains($warning->getRawOriginal('preview_message') ?? '', 'CNL AD WRNG');
            $type = $isCancellation ? 'CANCELLATION' : 'WARNING';
            
            $this->info("ID: {$warning->id} | Seq: {$warning->sequence_number} | Type: {$type}");
            $this->info("Preview: " . substr($warning->getRawOriginal('preview_message') ?? '', 0, 50) . "...");
            $this->info("---");
        }
        
        return 0;
    }
}
