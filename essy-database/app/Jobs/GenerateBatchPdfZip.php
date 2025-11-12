<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Models\ReportData;
use App\Models\BatchDownloadJob;

class GenerateBatchPdfZip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $downloadJobId;

    /**
     * Create a new job instance.
     */
    public function __construct($batchId, $downloadJobId)
    {
        $this->batchId = $batchId;
        $this->downloadJobId = $downloadJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $downloadJob = BatchDownloadJob::find($this->downloadJobId);
            if (!$downloadJob) {
                return;
            }

            $downloadJob->update(['status' => 'processing']);

            $reports = ReportData::where('batch_id', $this->batchId)->get();

            if ($reports->isEmpty()) {
                $downloadJob->update([
                    'status' => 'failed',
                    'error_message' => 'No reports found for this batch.'
                ]);
                return;
            }

            $zipFileName = "batch_{$this->batchId}_{$downloadJob->id}.zip";
            $tempDir = storage_path("app/tmp-pdfs-" . Str::uuid());
            mkdir($tempDir, 0755, true);

            $downloadJob->update([
                'total_reports' => $reports->count(),
                'processed_reports' => 0
            ]);

            // Generate PDFs
            foreach ($reports as $index => $report) {
                $pdf = Pdf::loadView('print', compact('report'));
                $filename = "Report_{$report->FN_STUDENT}_{$report->LN_STUDENT}_{$report->id}.pdf";
                $pdfPath = $tempDir . '/' . $filename;
                $pdf->save($pdfPath);

                // Update progress
                $downloadJob->update(['processed_reports' => $index + 1]);
            }

            // Create ZIP file
            $zipPath = storage_path("app/batch-downloads/{$zipFileName}");
            
            // Ensure the batch-downloads directory exists
            if (!file_exists(storage_path('app/batch-downloads'))) {
                mkdir(storage_path('app/batch-downloads'), 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach (glob("$tempDir/*.pdf") as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                // Clean up temporary files
                collect(glob("$tempDir/*"))->each(fn($f) => unlink($f));
                rmdir($tempDir);

                $downloadJob->update([
                    'status' => 'completed',
                    'file_path' => "batch-downloads/{$zipFileName}",
                    'completed_at' => now()
                ]);
            } else {
                throw new \Exception('Failed to create ZIP file');
            }

        } catch (\Exception $e) {
            if (isset($downloadJob)) {
                $downloadJob->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            // Clean up temporary directory if it exists
            if (isset($tempDir) && is_dir($tempDir)) {
                collect(glob("$tempDir/*"))->each(fn($f) => unlink($f));
                rmdir($tempDir);
            }

            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        $downloadJob = BatchDownloadJob::find($this->downloadJobId);
        if ($downloadJob) {
            $downloadJob->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage()
            ]);
        }
    }
}