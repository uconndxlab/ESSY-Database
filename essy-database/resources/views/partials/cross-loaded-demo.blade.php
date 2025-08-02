{{-- 
    Demonstration of refactored cross-loaded domain logic using new services
    This shows how the template would be simplified using the new service classes
--}}

@php
    use App\Services\CrossLoadedDomainService;
    use App\Services\ReportTemplateHelper;
    
    // Initialize services (in real implementation, these would be injected)
    $crossLoadedService = app(CrossLoadedDomainService::class);
    $templateHelper = app(ReportTemplateHelper::class);
    
    // Get concern domains using the enhanced model method
    $concernDomains = $report->getConcernDomains();
    
    // Get fields that need dagger symbols
    $daggerFields = $crossLoadedService->getFieldsRequiringDagger($concernDomains);
    
    // Get domain indicators configuration
    $domainIndicators = $templateHelper->getDomainIndicators();
@endphp

<h3>Cross-Loaded Domain Processing Demo</h3>

<div class="concern-domains">
    <h4>Domains of Concern:</h4>
    @if(empty($concernDomains))
        <p>No domains identified as areas of concern.</p>
    @else
        <ul>
            @foreach($concernDomains as $domain)
                <li>{{ $domain }}</li>
            @endforeach
        </ul>
    @endif
</div>

<div class="dagger-fields">
    <h4>Fields Requiring Dagger Symbol (†):</h4>
    @if(empty($daggerFields))
        <p>No cross-loaded items require dagger symbols.</p>
    @else
        <ul>
            @foreach($daggerFields as $field => $value)
                <li>{{ $field }}</li>
            @endforeach
        </ul>
    @endif
</div>

{{-- Example of processing Academic Skills domain --}}
@if(in_array('Academic Skills', $concernDomains))
    <div class="academic-skills-section">
        <h4>Academic Skills (Processed with New Services)</h4>
        
        @php
            $academicResult = $templateHelper->processItemsForDomain(
                'Academic Skills', 
                $domainIndicators['Academic Skills'], 
                $report
            );
        @endphp
        
        @if($academicResult->hasErrors())
            <div class="errors">
                <strong>Processing Errors:</strong>
                <ul>
                    @foreach($academicResult->errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <table class="domain-results">
            <thead>
                <tr>
                    <th>Strengths</th>
                    <th>Monitor</th>
                    <th>Concerns</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @foreach($academicResult->strengths as $item)
                            <div>{{ $item->getFormattedText() }}</div>
                        @endforeach
                    </td>
                    <td>
                        @foreach($academicResult->monitor as $item)
                            <div>{{ $item->getFormattedText() }}</div>
                        @endforeach
                    </td>
                    <td>
                        @foreach($academicResult->concerns as $item)
                            <div>{{ $item->getFormattedText() }}</div>
                        @endforeach
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endif

{{-- Example of safe field access --}}
<div class="safe-field-access">
    <h4>Safe Field Access Examples:</h4>
    <ul>
        <li>Student reads at grade level: {{ $report->safeGetAttribute('A_READ', 'Not assessed') }}</li>
        <li>Student writes at grade level: {{ $report->safeGetAttribute('A_WRITE', 'Not assessed') }}</li>
        <li>Articulates clearly (Academic): {{ $crossLoadedService->safeGetFieldValue($report, 'A_P_S_ARTICULATE_CL1') ?? 'Not assessed' }}</li>
        <li>Articulates clearly (Physical): {{ $crossLoadedService->safeGetFieldValue($report, 'A_P_S_ARTICULATE_CL2') ?? 'Not assessed' }}</li>
    </ul>
</div>

{{-- Configuration validation status --}}
<div class="validation-status">
    <h4>Configuration Validation:</h4>
    @php
        $validationResult = $crossLoadedService->validateCrossLoadedConfiguration();
    @endphp
    
    <p>Status: <strong>{{ $validationResult->isValid ? 'Valid' : 'Invalid' }}</strong></p>
    
    @if($validationResult->hasErrors())
        <div class="validation-errors">
            <strong>Errors:</strong>
            <ul>
                @foreach($validationResult->errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    @if($validationResult->hasWarnings())
        <div class="validation-warnings">
            <strong>Warnings:</strong>
            <ul>
                @foreach($validationResult->warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<style>
    .domain-results {
        width: 100%;
        border-collapse: collapse;
        margin: 1em 0;
    }
    
    .domain-results th,
    .domain-results td {
        border: 1px solid #ccc;
        padding: 8px;
        vertical-align: top;
    }
    
    .domain-results th {
        background-color: #f5f5f5;
        font-weight: bold;
    }
    
    .validation-errors {
        color: #d32f2f;
        margin: 0.5em 0;
    }
    
    .validation-warnings {
        color: #f57c00;
        margin: 0.5em 0;
    }
    
    .concern-domains,
    .dagger-fields,
    .safe-field-access,
    .validation-status {
        margin: 1em 0;
        padding: 1em;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
</style>