<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\BatchDownloadJob;
use Carbon\Carbon;

class CleanupBatchDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch-downloads:cleanup {--days=7 : Number of days to keep completed downloads}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old batch download files and database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up batch downloads older than {$days} days...");

        // Find old completed or failed jobs
        $oldJobs = BatchDownloadJob::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['completed', 'failed'])
            ->get();

        $deletedFiles = 0;
        $deletedRecords = 0;

        foreach ($oldJobs as $job) {
            // Delete the file if it exists
            if ($job->file_path && Storage::exists($job->file_path)) {
                Storage::delete($job->file_path);
                $deletedFiles++;
                $this->line("Deleted file: {$job->file_path}");
            }

            // Delete the database record
            $job->delete();
            $deletedRecords++;
        }

        $this->info("Cleanup completed:");
        $this->info("- Deleted {$deletedFiles} files");
        $this->info("- Deleted {$deletedRecords} database records");

        return Command::SUCCESS;
    }
}
