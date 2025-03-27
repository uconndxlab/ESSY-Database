@extends('layouts.app')

@section('content')

    <h1>Report Details</h1>

    <div class="accordion mb-4" id="accordionExample">
        <div class="accordion-item">
          <h2 class="accordion-header" id="headingOne">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
              Report Information
            </button>
          </h2>
          <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
            <div class="accordion-body">
                <p><strong>Start Date:</strong> {{ $report->StartDate }}</p>
                <p><strong>End Date:</strong> {{ $report->EndDate }}</p>
                <p><strong>Status:</strong> {{ $report->Status }}</p>
                <p><strong>Progress:</strong> {{ $report->Progress }}%</p>
                <p><strong>Finished:</strong> {{ $report->Finished ? 'Yes' : 'No' }}</p>
                <p><strong>Response ID:</strong> {{ $report->ResponseId }}</p>
                <p><strong>Recipient Name:</strong> {{ $report->RecipientFirstName }} {{ $report->RecipientLastName }}</p>
                <p><strong>Email:</strong> {{ $report->RecipientEmail }}</p>
                <p><strong>Location:</strong> {{ $report->LocationLatitude }}, {{ $report->LocationLongitude }}</p>
                <p><strong>Recorded Date:</strong> {{ $report->RecordedDate }}</p>
                <p><strong>Distribution Channel:</strong> {{ $report->DistributionChannel }}</p>
                <p><strong>User Language:</strong> {{ $report->UserLanguage }}</p>
                <p><strong>External Reference:</strong> {{ $report->ExternalReference }}</p>
                <p><strong>INITIALS:</strong> {{ $report->INITIALS }}</p>
            </div>
          </div>
        </div>
    </div>


    <div class="accordion mb-4" id="accordionExample">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
            Academic Skills (AS)
          </button>
        </h2>
        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
          <div class="accordion-body">
                <p><strong>AS DOMAIN:</strong> {{ $report->AS_DOMAIN }}</p>
                <p><strong>AS READING:</strong> {{ $report->AS_READING }}</p>
                <p><strong>AS WRITING:</strong> {{ $report->AS_WRITING }}</p>
                <p><strong>AS MATH:</strong> {{ $report->AS_MATH }}</p>
                <p><strong>AS ENGAGE:</strong> {{ $report->AS_ENGAGE }}</p>
                <p><strong>AS PLAN:</strong> {{ $report->AS_PLAN }}</p>
                <p><strong>AS TURNIN:</strong> {{ $report->AS_TURNIN }}</p>
                <p><strong>AS INTEREST:</strong> {{ $report->AS_INTEREST }}</p>
                <p><strong>AS PERSIST:</strong> {{ $report->AS_PERSIST }}</p>
                <p><strong>AS INITIATE:</strong> {{ $report->AS_INITIATE }}</p>
                <p><strong>AS DIRECTIONS 2:</strong> {{ $report->AS_DIRECTIONS2 }}</p>
            </div>
          </div>
        </div>
    </div>

    <div class="accordion mb-4" id="accordionExample">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
            Behavioral Information (BEH)
          </button>
        </h2>
        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
          <div class="accordion-body">
            <p><strong>BEH DOMAIN:</strong> {{ $report->BEH_DOMAIN }}</p>
            <p><strong>BEH CLASSEXPECT CL1:</strong> {{ $report->BEH_CLASSEXPECT_CL1 }}</p>
            <p><strong>BEH IMPULSE:</strong> {{ $report->BEH_IMPULSE }}</p>
            <p><strong>BEH DESTRUCT:</strong> {{ $report->BEH_DESTRUCT }}</p>
            <p><strong>BEH PHYSAGGRESS:</strong> {{ $report->BEH_PHYSAGGRESS }}</p>
            <p><strong>BEH SNEAK:</strong> {{ $report->BEH_SNEAK }}</p>
            <p><strong>BEH VERBAGGRESS:</strong> {{ $report->BEH_VERBAGGRESS }}</p>
            <p><strong>BEH BULLY:</strong> {{ $report->BEH_BULLY }}</p>
            </div>
          </div>
        </div>
    </div>

    <div class="accordion mb-4" id="accordionExample">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
            DEM Information
          </button>
        </h2>
        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
          <div class="accordion-body">
            <p><strong>DEM GRADE:</strong> {{ $report->DEM_GRADE }}</p>
            <p><strong>DEM AGE:</strong> {{ $report->DEM_AGE }}</p>
            <p><strong>DEM GENDER:</strong> {{ $report->DEM_GENDER }}</p>
            <p><strong>DEM GENDER 8 TEXT:</strong> {{ $report->DEM_GENDER_8_TEXT }}</p>
            <p><strong>DEM LANG:</strong> {{ $report->DEM_LANG }}</p>
            <p><strong>DEM LANG 9 TEXT:</strong> {{ $report->DEM_LANG_9_TEXT }}</p>
            <p><strong>DEM ETHNIC:</strong> {{ $report->DEM_ETHNIC }}</p>
            <p><strong>DEM RACE:</strong> {{ $report->DEM_RACE }}</p>
            <p><strong>DEM RACE 14 TEXT:</strong> {{ $report->DEM_RACE_14_TEXT }}</p>
            <p><strong>DEM IEP:</strong> {{ $report->DEM_IEP }}</p>
            <p><strong>DEM 504:</strong> {{ $report->DEM_504 }}</p>
            <p><strong>DEM CI:</strong> {{ $report->DEM_CI }}</p>
            <p><strong>DEM ELL:</strong> {{ $report->DEM_ELL }}</p>
            </div>
          </div>
        </div>
    </div>

    <div class="accordion mb-4" id="accordionExample">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="true" aria-controls="collapseFive">
            Emotional Web Being (EWB)
          </button>
        </h2>
        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
          <div class="accordion-body">
            <p><strong>EWB GROWTH:</strong> {{ $report->EWB_GROWTH }}</p>
            <p><strong>EWB CONFIDENT 1:</strong> {{ $report->EWB_CONFIDENT_1 }}</p>
            <p><strong>EWB POSITIVE 1:</strong> {{ $report->EWB_POSITIVE_1 }}</p>
            <p><strong>EWB CLINGY:</strong> {{ $report->EWB_CLINGY }}</p>
            </div>
          </div>
        </div>
    </div>

    <div class="accordion mb-4" id="accordionExample">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingOne">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="true" aria-controls="collapseSix">
            Miscellaneous
          </button>
        </h2>
        <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
          <div class="accordion-body">
            <p><strong>SEW DOMAIN:</strong> {{ $report->SEW_DOMAIN }}</p>
            <p><strong>PH2 DOMAIN:</strong> {{ $report->PH2_DOMAIN }}</p>
            <p><strong>SOS2 DOMAIN:</strong> {{ $report->SOS2_DOMAIN }}</p>
            <p><strong>ATT C DOMAIN:</strong> {{ $report->ATT_C_DOMAIN }}</p>
            <p><strong>CONF GATE1:</strong> {{ $report->CONF_GATE1 }}</p>
            
            <p><strong>SS ADULTSCOMM 1:</strong> {{ $report->SS_ADULTSCOMM_1 }}</p>
        
            <p><strong>PH ARTICULATE:</strong> {{ $report->PH_ARTICULATE }}</p>
            <p><strong>SSOS ACTIVITY3 1:</strong> {{ $report->SSOS_ACTIVITY3_1 }}</p>
            <p><strong>SIB PUNITIVE:</strong> {{ $report->SIB_PUNITIVE }}</p>
            <p><strong>RELATION TIME:</strong> {{ $report->RELATION_TIME }}</p>
            <p><strong>RELATION AMOUNT:</strong> {{ $report->RELATION_AMOUNT }}</p>
            <p><strong>RELATION CLOSE:</strong> {{ $report->RELATION_CLOSE }}</p>
            <p><strong>RELATION CONFLICT:</strong> {{ $report->RELATION_CONFLICT }}</p>
            <p><strong>CONF ALL:</strong> {{ $report->CONF_ALL }}</p>
            </div>
          </div>
        </div>
    </div>

    <a href="{{ url('/') }}" class="btn btn-primary">Back to Reports List</a>
@endsection
