<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BatchDownloadJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'status',
        'total_reports',
        'processed_reports',
        'file_path',
        'error_message',
        'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime'
    ];

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->total_reports === null || $this->total_reports === 0) {
            return 0;
        }

        return round(($this->processed_reports / $this->total_reports) * 100, 2);
    }

    /**
     * Check if the job is in progress.
     */
    public function isInProgress()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if the job is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the job has failed.
     */
    public function hasFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Get the download URL if the job is completed.
     */
    public function getDownloadUrl()
    {
        if ($this->isCompleted() && $this->file_path) {
            return route('batch-downloads.download', $this->id);
        }

        return null;
    }
}