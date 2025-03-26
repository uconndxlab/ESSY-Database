<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportData extends Model
{
    use HasFactory;

    protected $table = 'report_data';

    protected $fillable = [
        'StartDate', 'EndDate', 'Status', 'IPAddress', 'Progress', 'Duration', 'Finished', 'RecordedDate',
        'ResponseId', 'LocationLatitude', 'LocationLongitude', 'DistributionChannel', 'UserLanguage',
        'INITIALS', 'AS_DOMAIN', 'BEH_DOMAIN', 'SEW_DOMAIN', 'PH2_DOMAIN', 'SOS2_DOMAIN', 'ATT_C_DOMAIN',
        'CONF_GATE1', 'RELATION_TIME', 'RELATION_AMOUNT', 'RELATION_CLOSE', 'RELATION_CONFLICT',
        'Confidence_Level', 'TIMING_RELATION_A_First_Click', 'TIMING_RELATION_A_Last_Click',
        'TIMING_RELATION_A_Page_Submit', 'TIMING_RELATION_A_Click_Count'
    ];

    protected $casts = [
        'StartDate' => 'datetime',
        'EndDate' => 'datetime',
        'RecordedDate' => 'datetime',
        'Finished' => 'boolean',
    ];
}
