# Batch Download Queue Implementation

This implementation converts the synchronous batch PDF download to use Laravel queued jobs to prevent UI timeouts.

## How it works

1. **User clicks "Download All PDFs"**: Instead of processing immediately, a `BatchDownloadJob` record is created and a `GenerateBatchPdfZip` job is queued.

2. **Real-time progress tracking**: The UI polls the server every 2 seconds to check job status and shows a progress bar.

3. **Background processing**: The queued job generates all PDFs and creates a ZIP file in the background.

4. **Download ready**: Once complete, the user sees a download button for the generated file.

## Key Components

### Models
- `BatchDownloadJob`: Tracks the status, progress, and file path of each download job

### Jobs  
- `GenerateBatchPdfZip`: Handles the actual PDF generation and ZIP creation in the background

### Controllers
- `BatchController::downloadZip()`: Creates job and queues it
- `BatchController::downloadJobStatus()`: Returns job status for AJAX polling
- `BatchController::downloadFile()`: Serves the completed download file

### Frontend
- Progress bar with real-time updates
- Automatic page refresh when job completes
- Error handling for failed jobs

## Queue Configuration

The system uses Laravel's database queue driver (configured in `config/queue.php`).

### Starting the Queue Worker

To process jobs, you need to run the queue worker:

```bash
php artisan queue:work
```

For production, use a process manager like Supervisor to keep the queue worker running.

### Maintenance

Clean up old download files and database records:

```bash
# Clean up files older than 7 days (default)
php artisan batch-downloads:cleanup

# Clean up files older than 3 days
php artisan batch-downloads:cleanup --days=3
```

## Database Schema

The `batch_download_jobs` table tracks:
- `batch_id`: The batch being processed
- `status`: pending, processing, completed, or failed  
- `total_reports`/`processed_reports`: Progress tracking
- `file_path`: Path to generated ZIP file
- `error_message`: Error details if job fails
- `completed_at`: Completion timestamp

## File Storage

Generated ZIP files are stored in `storage/app/batch-downloads/` and follow the naming pattern:
`batch_{batch_id}_{job_id}.zip`

## Error Handling

- If a job fails, the user sees an error message with a "Try Again" button
- Failed jobs are logged with error details in the database
- Temporary files are cleaned up even if the job fails
- The queue system will retry failed jobs based on Laravel's retry configuration