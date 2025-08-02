<?php

namespace Tests\Unit;

use App\Console\Commands\ImportDecisionRules;
use App\Models\DecisionRule;
use App\ValueObjects\ImportResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportDecisionRulesCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilePath = storage_path('app/test_decision_rules.xlsx');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        parent::tearDown();
    }

    /** @test */
    public function it_can_import_decision_rules_from_excel_file()
    {
        // Create test Excel file
        $this->createTestExcelFile();

        // Run import command
        $exitCode = Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        // Assert command succeeded
        $this->assertEquals(0, $exitCode);

        // Assert decision rules were created with corrected field names
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_READ', // Corrected from AS_READING
            'frequency' => 'Almost Always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always demonstrates strong reading skills.'
        ]);

        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_B_IMPULSE_CL2', // Corrected from BEH_IMPULSE
            'frequency' => 'Sometimes',
            'domain' => 'Behavior',
            'decision_text' => 'The student sometimes shows impulsive behavior.'
        ]);
    }

    /** @test */
    public function it_can_update_existing_decision_rules()
    {
        // Create existing decision rule with corrected field name
        DecisionRule::create([
            'item_code' => 'A_READ', // Use corrected field name
            'frequency' => 'Almost Always',
            'domain' => 'Academic Skills',
            'decision_text' => 'Old text'
        ]);

        // Create test Excel file with updated text
        $this->createTestExcelFile();

        // Run import command
        Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        // Assert decision rule was updated with corrected field name
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_READ', // Corrected field name
            'frequency' => 'Almost Always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always demonstrates strong reading skills.'
        ]);

        // Assert only one record exists (updated, not duplicated)
        $this->assertEquals(1, DecisionRule::where('item_code', 'A_READ')
            ->where('frequency', 'Almost Always')->count());
    }

    /** @test */
    public function it_handles_missing_file_gracefully()
    {
        $exitCode = Artisan::call('essy:import-decision-rules', [
            'file' => 'nonexistent.xlsx'
        ]);

        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('File not found', $output);
    }

    /** @test */
    public function it_handles_missing_decision_rules_sheet()
    {
        // Create Excel file without Decision Rules sheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Wrong Sheet');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);

        $exitCode = Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Decision Rules', $output);
    }

    /** @test */
    public function it_validates_excel_structure()
    {
        // Create Excel file with invalid structure (missing frequency columns)
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Decision Rules');
        
        // Set invalid headers
        $sheet->setCellValue('A39', 'Question Column');
        $sheet->setCellValue('B39', 'Invalid Column');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);

        $exitCode = Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        $this->assertEquals(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('No frequency columns found', $output);
    }

    /** @test */
    public function it_maps_domains_correctly_from_item_codes()
    {
        $this->createTestExcelFile();

        Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        // Test various domain mappings with corrected field names
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_READ', // Corrected from AS_READING
            'domain' => 'Academic Skills'
        ]);

        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_B_IMPULSE_CL2', // Corrected from BEH_IMPULSE
            'domain' => 'Behavior'
        ]);

        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_S_CONFIDENT_CL2', // Corrected from EWB_CONFIDENT
            'domain' => 'Social-Emotional Well-being'
        ]);
    }

    /** @test */
    public function it_skips_empty_rows_and_cells()
    {
        // Create Excel file with some empty cells
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Decision Rules');
        
        // Set headers
        $sheet->setCellValue('A39', 'Question Column');
        $sheet->setCellValue('B39', 'Qualtrics Column');
        $sheet->setCellValue('C39', 'Almost Always');
        $sheet->setCellValue('D39', 'Sometimes');
        
        // Set data with empty cells
        $sheet->setCellValue('A40', 'Meets grade-level expectations for reading skills.');
        $sheet->setCellValue('B40', 'AS_READING');
        $sheet->setCellValue('C40', 'Good reading skills');
        // D40 is empty
        
        $sheet->setCellValue('A41', 'Some question text'); // Question text
        $sheet->setCellValue('B41', ''); // Empty Qualtrics Column
        $sheet->setCellValue('C41', 'Should be skipped');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);

        Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        // Should only have one record (the valid one) with corrected field name
        $this->assertEquals(1, DecisionRule::count());
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_READ', // Corrected from AS_READING
            'frequency' => 'Almost Always'
        ]);
    }

    /** @test */
    public function it_provides_progress_reporting()
    {
        $this->createTestExcelFile();

        Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        $output = Artisan::output();
        
        // Check for progress indicators
        $this->assertStringContainsString('Starting import', $output);
        $this->assertStringContainsString('Loading Excel file', $output);
        $this->assertStringContainsString('Processing', $output);
        $this->assertStringContainsString('Import Complete', $output);
        $this->assertStringContainsString('Imported:', $output);
    }

    /** @test */
    public function it_handles_malformed_data_gracefully()
    {
        // Create Excel file with some problematic data
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Decision Rules');
        
        // Set headers
        $sheet->setCellValue('A39', 'Question Column');
        $sheet->setCellValue('B39', 'Qualtrics Column');
        $sheet->setCellValue('C39', 'Almost Always');
        
        // Set valid data
        $sheet->setCellValue('A40', 'Meets grade-level expectations for reading skills.');
        $sheet->setCellValue('B40', 'AS_READING');
        $sheet->setCellValue('C40', 'Good reading skills');
        
        // Set row with empty item code (should cause error)
        $sheet->setCellValue('A41', 'Some question text');
        $sheet->setCellValue('B41', ''); // Empty Qualtrics Column
        $sheet->setCellValue('C41', 'Should cause error');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);

        $exitCode = Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        // Should complete successfully even with some errors since valid data was imported
        $this->assertEquals(0, $exitCode); // Exit code 0 since some data was successfully imported
        
        $output = Artisan::output();
        $this->assertStringContainsString('Errors:', $output);
        $this->assertStringContainsString('Row 41: Item code is empty in Qualtrics Column', $output);
        
        // Valid data should still be imported with corrected field name
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'A_READ', // Corrected from AS_READING
            'frequency' => 'Almost Always'
        ]);
    }

    /**
     * Create a test Excel file with sample decision rules data
     */
    private function createTestExcelFile(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Decision Rules');
        
        // Set headers in row 39
        $sheet->setCellValue('A39', 'Question Column');
        $sheet->setCellValue('B39', 'Qualtrics Column');
        $sheet->setCellValue('C39', 'Almost Always');
        $sheet->setCellValue('D39', 'Frequently');
        $sheet->setCellValue('E39', 'Sometimes');
        $sheet->setCellValue('F39', 'Occasionally');
        $sheet->setCellValue('G39', 'Almost Never');
        
        // Set sample data starting from row 40
        $sheet->setCellValue('A40', 'Meets grade-level expectations for reading skills.');
        $sheet->setCellValue('B40', 'AS_READING');
        $sheet->setCellValue('C40', 'The student almost always demonstrates strong reading skills.');
        $sheet->setCellValue('D40', 'The student frequently demonstrates good reading skills.');
        $sheet->setCellValue('E40', 'The student sometimes shows reading difficulties.');
        
        $sheet->setCellValue('A41', 'Exhibits impulsivity.');
        $sheet->setCellValue('B41', 'BEH_IMPULSE');
        $sheet->setCellValue('C41', 'The student almost always shows excellent impulse control.');
        $sheet->setCellValue('E41', 'The student sometimes shows impulsive behavior.');
        $sheet->setCellValue('G41', 'The student almost never shows impulse control.');
        
        $sheet->setCellValue('A42', 'Displays confidence in self.');
        $sheet->setCellValue('B42', 'EWB_CONFIDENT');
        $sheet->setCellValue('C42', 'The student almost always appears confident.');
        $sheet->setCellValue('D42', 'The student frequently shows confidence.');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);
    }
}