<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AerodromeWarning;

class UpdateExpiredWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warnings:update-expired {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update expired aerodrome warnings to EXPIRED status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get expired warnings that are still active
        $expiredWarnings = AerodromeWarning::active()
            ->where('end_time', '<=', now())
            ->get();

        if ($expiredWarnings->isEmpty()) {
            $this->info('✅ No expired warnings found');
            return 0;
        }

        $this->info("Found {$expiredWarnings->count()} expired warning(s):");
        
        foreach ($expiredWarnings as $warning) {
            $this->line("  • {$warning->warning_number} - {$warning->airport_code} (Expired: {$warning->end_time->format('Y-m-d H:i:s')})");
        }

        if ($isDryRun) {
            $this->info('💡 Run without --dry-run to actually update these warnings');
            return 0;
        }

        if ($this->confirm('Do you want to update these warnings to EXPIRED status?')) {
            $updatedCount = AerodromeWarning::updateExpiredWarnings();
            $this->info("✅ Successfully updated {$updatedCount} warning(s) to EXPIRED status");
        } else {
            $this->info('❌ Operation cancelled');
        }

        return 0;
    }
}
