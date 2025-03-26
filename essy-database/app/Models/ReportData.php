<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'StartDate', 'EndDate', 'Status', 'IPAddress', 'Progress', 'Duration', 'Finished',
        'RecordedDate', 'ResponseId', 'RecipientLastName', 'RecipientFirstName', 'RecipientEmail', 
        'ExternalReference', 'LocationLatitude', 'LocationLongitude', 'DistributionChannel', 
        'UserLanguage', 'INITIALS', 'AS_DOMAIN', 'BEH_DOMAIN', 'SEW_DOMAIN', 'PH2_DOMAIN', 
        'SOS2_DOMAIN', 'ATT_C_DOMAIN', 'CONF_GATE1', 'AS_READING', 'AS_WRITING', 'AS_MATH', 
        'AS_ENGAGE', 'AS_PLAN', 'AS_TURNIN', 'AS_INTEREST', 'AS_PERSIST', 'AS_INITIATE', 
        'EWB_GROWTH', 'AS_DIRECTIONS2', 'BEH_CLASSEXPECT_CL1', 'BEH_IMPULSE', 'SS_ADULTSCOMM_1', 
        'EWB_CONFIDENT_1', 'EWB_POSITIVE_1', 'PH_ARTICULATE', 'SSOS_ACTIVITY3_1', 'EWB_CLINGY', 
        'BEH_DESTRUCT', 'BEH_PHYSAGGRESS', 'BEH_SNEAK', 'BEH_VERBAGGRESS', 'BEH_BULLY', 
        'SIB_PUNITIVE', 'BEH_CLASSEXPECT_CL2', 'SSOS_NBHDSTRESS_1', 'SSOS_FAMSTRESS_1', 
        'AMN_HOUSING_1', 'SS_CONNECT', 'SS_PROSOCIAL', 'SS_PEERCOMM', 'EWB_CONTENT', 
        'SIB_FRIEND', 'SIB_ADULT', 'SEW_SCHOOLCONNECT', 'SSOS_BELONG2', 'EWB_NERVOUS', 
        'EWB_SAD', 'EWB_ACHES', 'EWB_CONFIDENT_2', 'EWB_POSITIVE_2', 'SS_ADULTSCOMM_2', 
        'SSOS_ACTIVITY3_2', 'AMN_RESOURCE', 'SSOS_RECIPROCAL', 'SSOS_POSADULT', 
        'SSOS_ADULTBEST', 'SSOS_TALK', 'SSOS_FAMILY', 'SSOS_ROUTINE', 'AMN_HOUSING_2', 
        'SSOS_FAMSTRESS_2', 'SSOS_NBHDSTRESS_2', 'AMN_CLOTHES', 'AMN_HYGEINE', 
        'AMN_HUNGER', 'SSOS_ACTIVITY3', 'PH_SIGHT', 'PH_HEAR', 'PH_PARTICIPATE', 
        'AMN_HYGIENE', 'AMN_ORAL', 'AMN_PHYS', 'PH_RESTED1', 'BEH_SH', 'EWB_REGULATE', 
        'EWB_WITHDRAW', 'SIB_EXCLUDE', 'SIB_BULLIED', 'RELATION_TIME', 'RELATION_AMOUNT', 
        'RELATION_CLOSE', 'RELATION_CONFLICT', 'CONF_ALL', 'DEM_GRADE', 'DEM_AGE', 
        'DEM_GENDER', 'DEM_GENDER_8_TEXT', 'DEM_LANG', 'DEM_LANG_9_TEXT', 'DEM_ETHNIC', 
        'DEM_RACE', 'DEM_RACE_14_TEXT', 'DEM_IEP', 'DEM_504', 'DEM_CI', 'DEM_ELL'
    ];
}