<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportData extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'StartDate', 'EndDate', 'Status', 'IPAddress', 'Progress', 'Duration', 'Finished', 'RecordedDate',
        'ResponseId', 'RecipientLastName', 'RecipientFirstName', 'RecipientEmail', 'ExternalReference',
        'LocationLatitude', 'LocationLongitude', 'DistributionChannel', 'UserLanguage',
        'FN_STUDENT', 'LN_STUDENT', 'FN_TEACHER', 'LN_TEACHER', 'SCHOOL',
        'A_DOMAIN', 'ATT_DOMAIN', 'B_DOMAIN', 'P_DOMAIN', 'S_DOMAIN', 'O_DOMAIN',
        'COMMENTS_GATE1', 'TIMING_GATE1_FirstClick', 'TIMING_GATE1_LastClick', 'TIMING_GATE1_PageSubmit', 'TIMING_GATE1_ClickCount',
        'E_SHARM', 'E_BULLIED', 'E_EXCLUDE', 'E_WITHDRAW', 'E_REGULATE', 'E_RESTED',
        'COMMENTS_ESS', 'TIMING_ESS_FirstClick', 'TIMING_ESS_LastClick', 'TIMING_ESS_PageSubmit', 'TIMING_ESS_ClickCount',
        'A_READ', 'A_WRITE', 'A_MATH', 'A_P_ARTICULATE_CL1', 'A_S_ADULTCOMM_CL1', 'A_DIRECTIONS', 'A_INITIATE', 'A_PLANORG',
        'A_TURNIN', 'A_B_CLASSEXPECT_CL1', 'A_B_IMPULSE_CL1', 'A_ENGAGE', 'A_INTEREST', 'A_PERSIST', 'A_GROWTH',
        'A_S_CONFIDENT_CL1', 'A_S_POSOUT_CL1', 'A_S_O_ACTIVITY3_CL1',
        'COMMENTS_AS', 'TIMING_AS_FirstClick', 'TIMING_AS_LastClick', 'TIMING_AS_PageSubmit', 'TIMING_AS_ClickCount',
        'A_B_CLASSEXPECT_CL2', 'A_B_IMPULSE_CL2', 'B_CLINGY', 'B_SNEAK', 'BEH_VERBAGGRESS', 'BEH_PHYSAGGRESS',
        'B_DESTRUCT', 'B_BULLY', 'B_PUNITIVE', 'B_O_HOUSING_CL1', 'B_O_FAMSTRESS_CL1', 'B_O_NBHDSTRESS_CL1',
        'COMMENTS_BEH', 'TIMING_BEH_FirstClick', 'TIMING_BEH_LastClick', 'TIMING_BEH_PageSubmit', 'TIMING_BEH_ClickCount',
        'P_SIGHT', 'P_HEAR', 'A_P_ARTICULATE_CL2', 'A_ORAL', 'A_PHYS', 'P_PARTICIPATE', 'S_P_ACHES_CL1',
        'O_P_HUNGER_CL1', 'O_P_HYGEINE_CL1', 'O_P_CLOTHES_CL1', 'COMMENTS_PH', 'TIMING_PH_FirstClick',
        'TIMING_PH_LastClick', 'TIMING_PH_PageSubmit', 'TIMING_PH_ClickCount',
        'S_CONTENT', 'A_S_CONFIDENT_CL2', 'A_S_POSOUT_CL2', 'S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD',
        'S_SOCIALCONN', 'S_FRIEND', 'S_PROSOCIAL', 'S_PEERCOMM', 'A_S_ADULTCOMM_CL2',
        'S_POSADULT', 'S_SCHOOLCONN', 'S_COMMCONN', 'A_S_O_ACTIVITY_CL2',
        'COMMENTS_SEW', 'TIMING_SEW_FirstClick', 'TIMING_SEW_LastClick', 'TIMING_SEW_PageSubmit', 'TIMING_SEW_ClickCount',
        'O_RECIPROCAL', 'O_POSADULT', 'O_ADULTBEST', 'O_TALK', 'O_ROUTINE', 'O_FAMILY',
        'O_P_HUNGER_CL2', 'O_P_HYGIENE_CL2', 'O_P_CLOTHES_CL2', 'O_RESOURCE',
        'B_O_HOUSING_CL2', 'B_O_FAMSTRESS_CL2', 'B_O_NBHDSTRESS_CL2', 'A_S_O_ACTIVITY_CL3',
        'COMMENTS_SOS', 'TIMING_SOS_FirstClick', 'TIMING_SOS_LastClick', 'TIMING_SOS_PageSubmit', 'TIMING_SOS_ClickCount',
        'RELATION_CLOSE', 'RELATION_CONFLICT', 'COMMENTS_STR',
        'DEM_RACE', 'DEM_RACE_14_TEXT', 'DEM_ETHNIC', 'DEM_GENDER', 'DEM_ELL', 'DEM_IEP',
        'DEM_504', 'DEM_CI', 'DEM_GRADE', 'DEM_CLASSTEACH', 'SPEEDING_GATE1', 'SPEEDING_ESS', 'SPEEDING_GATE2', 'batch_id'
    ];
}
