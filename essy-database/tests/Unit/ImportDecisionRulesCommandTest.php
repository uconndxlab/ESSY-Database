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

        // Assert decision rules were created
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'AS_READING',
            'frequency' => 'Almost Always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always demonstrates strong reading skills.'
        ]);

        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'BEH_IMPULSE',
            'frequency' => 'Sometimes',
            'domain' => 'Behavior',
            'decision_text' => 'The student sometimes shows impulsive behavior.'
        ]);
    }

    /** @test */
    public function it_can_update_existing_decision_rules()
    {
        // Create existing decision rule
        DecisionRule::create([
            'item_code' => 'AS_READING',
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

        // Assert decision rule was updated
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'AS_READING',
            'frequency' => 'Almost Always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always demonstrates strong reading skills.'
        ]);

        // Assert only one record exists (updated, not duplicated)
        $this->assertEquals(1, DecisionRule::where('item_code', 'AS_READING')
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

        // Test various domain mappings
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'AS_READING',
            'domain' => 'Academic Skills'
        ]);

        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'BEH_IMPULSE',
            'domain' => 'Behavior'
        ]);

        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'EWB_CONFIDENT',
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
        $sheet->setCellValue('B39', 'Almost Always');
        $sheet->setCellValue('C39', 'Sometimes');
        
        // Set data with empty cells
        $sheet->setCellValue('A40', 'AS_READING');
        $sheet->setCellValue('B40', 'Good reading skills');
        // C40 is empty
        
        $sheet->setCellValue('A41', ''); // Empty item code
        $sheet->setCellValue('B41', 'Should be skipped');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);

        Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        // Should only have one record (the valid one)
        $this->assertEquals(1, DecisionRule::count());
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'AS_READING',
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
        $sheet->setCellValue('B39', 'Almost Always');
        
        // Set valid data
        $sheet->setCellValue('A40', 'AS_READING');
        $sheet->setCellValue('B40', 'Good reading skills');
        
        // Set row with empty item code (should cause error)
        $sheet->setCellValue('A41', '');
        $sheet->setCellValue('B41', 'Should cause error');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);

        $exitCode = Artisan::call('essy:import-decision-rules', [
            'file' => $this->testFilePath
        ]);

        // Should complete successfully even with some errors since valid data was imported
        $this->assertEquals(0, $exitCode); // Exit code 0 since some data was successfully imported
        
        $output = Artisan::output();
        $this->assertStringContainsString('Errors:', $output);
        $this->assertStringContainsString('Row 41: Item code is empty', $output);
        
        // Valid data should still be imported
        $this->assertDatabaseHas('decision_rules', [
            'item_code' => 'AS_READING',
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
        $sheet->setCellValue('B39', 'Almost Always');
        $sheet->setCellValue('C39', 'Frequently');
        $sheet->setCellValue('D39', 'Sometimes');
        $sheet->setCellValue('E39', 'Occasionally');
        $sheet->setCellValue('F39', 'Almost Never');
        
        // Set sample data starting from row 40
        $sheet->setCellValue('A40', 'AS_READING');
        $sheet->setCellValue('B40', 'The student almost always demonstrates strong reading skills.');
        $sheet->setCellValue('C40', 'The student frequently demonstrates good reading skills.');
        $sheet->setCellValue('D40', 'The student sometimes shows reading difficulties.');
        
        $sheet->setCellValue('A41', 'BEH_IMPULSE');
        $sheet->setCellValue('B41', 'The student almost always shows excellent impulse control.');
        $sheet->setCellValue('D41', 'The student sometimes shows impulsive behavior.');
        $sheet->setCellValue('F41', 'The student almost never shows impulse control.');
        
        $sheet->setCellValue('A42', 'EWB_CONFIDENT');
        $sheet->setCellValue('B42', 'The student almost always appears confident.');
        $sheet->setCellValue('C42', 'The student frequently shows confidence.');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testFilePath);
    }
}