<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gate1Report extends Model
{
    use HasFactory;

    protected $table = 'gate1_reports';

    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'batch_id',
        'excel_file_path',
        // Basic Qualtrics fields
        'StartDate',
        'EndDate',
        'Status',
        'IPAddress',
        'Progress',
        'Duration',
        'Finished',
        'RecordedDate',
        'ResponseId',
        'RecipientLastName',
        'RecipientFirstName',
        'RecipientEmail',
        'ExternalReference',
        'LocationLatitude',
        'LocationLongitude',
        'DistributionChannel',
        'UserLanguage',
        // Student and teacher info
        'FN_STUDENT',
        'LN_STUDENT',
        'FN_TEACHER',
        'LN_TEACHER',
        'SCHOOL',
        // Gate 1 Domain Ratings (the 6 broad screening domains)
        'A_DOMAIN',      // Academic Skills
        'ATT_DOMAIN',    // Attendance
        'B_DOMAIN',      // Behavior
        'P_DOMAIN',      // Physical Health
        'S_DOMAIN',      // Social & Emotional Well-Being
        'O_DOMAIN',      // Supports Outside of School
        // Gate 1 specific fields
        'COMMENTS_GATE1',
        'TIMING_GATE1_FirstClick',
        'TIMING_GATE1_LastClick',
        'TIMING_GATE1_PageSubmit',
        'TIMING_GATE1_ClickCount',
        // Demographics
        'DEM_RACE',
        'DEM_RACE_14_TEXT',
        'DEM_ETHNIC',
        'DEM_GENDER',
        'DEM_ELL',
        'DEM_IEP',
        'DEM_504',
        'DEM_CI',
        'DEM_GRADE',
        'DEM_CLASSTEACH',
        'SPEEDING_GATE1',
    ];
}

