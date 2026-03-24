<?php

namespace App\Services\Admin\V2\Report;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use ZipArchive;

class ReportV2LaborInsuranceExcelService
{
    private const TEMPLATE_PATH = 'app/templates/R6-rouho_nendo_template.xlsx';

    /** @var array<int,int> */
    private const REGULAR_ROW_MAP = [
        4 => 26,
        5 => 28,
        6 => 30,
        7 => 32,
        8 => 34,
        9 => 36,
        10 => 38,
        11 => 40,
        12 => 42,
        1 => 44,
        2 => 46,
        3 => 48,
    ];

    /** @var list<int> */
    private const BONUS_ROWS = [50, 52, 54];

    public function __construct(
        private readonly ReportV2LaborInsuranceService $laborInsuranceService,
    ) {
    }

    /**
     * @return array{path:string,download_name:string}
     */
    public function build(int $fiscalYear, string $companyName): array
    {
        $companyName = trim($companyName);
        if ($companyName === '') {
            throw new RuntimeException('Company is required.');
        }

        $templatePath = storage_path(self::TEMPLATE_PATH);
        if (!is_file($templatePath)) {
            throw new RuntimeException('Labor insurance template not found.');
        }

        $report = $this->laborInsuranceService->build($fiscalYear, $companyName);

        $loadableTemplatePath = $this->sanitizeTemplateForOutput($templatePath);
        $spreadsheet = IOFactory::load($loadableTemplatePath);
        $this->fillWorkbook($spreadsheet, $report, $fiscalYear);

        $tempDir = storage_path('app/tmp');
        if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Failed to create temp directory.');
        }

        $downloadName = sprintf(
            '%d_labor_insurance_%s.xlsx',
            $fiscalYear,
            $this->safeFilePart($companyName)
        );
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('labor_insurance_', true) . '.xlsx';

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'path' => $outputPath,
            'download_name' => $downloadName,
        ];
    }

    /**
     * @param array<string,mixed> $report
     */
    private function fillWorkbook(Spreadsheet $spreadsheet, array $report, int $fiscalYear): void
    {
        $settingsSheet = $this->sheet($spreadsheet, '設定シート（非表示）');
        $summarySheet = $this->sheet($spreadsheet, '算定基礎賃金集計表');

        $settingsSheet->setCellValue('C6', $fiscalYear + 1);

        /** @var array<string,mixed> $companyMeta */
        $companyMeta = $report['company_meta'] ?? [];
        $this->writeLaborInsuranceNumber(
            $summarySheet,
            (string) ($companyMeta['labor_insurance_number'] ?? '')
        );
        $summarySheet->setCellValue('BE8', (string) ($companyMeta['company_name'] ?? ''));
        $summarySheet->setCellValue('CE6', (string) ($companyMeta['tel'] ?? ''));
        $summarySheet->setCellValue('BE12', (string) ($companyMeta['company_address'] ?? ''));
        $summarySheet->setCellValue('CS9', (string) ($companyMeta['labor_install_category'] ?? ''));
        $summarySheet->setCellValue('CG10', '');

        // `申告書記入イメージ` 側は、現在見えている候補セルがラベル結合セルだったため、
        // 入力先が確定するまでここへの書き込みは止める。

        /** @var list<array<string,mixed>> $monthlyRows */
        $monthlyRows = $report['monthly_rows'] ?? [];
        $regularRows = [];
        $bonusRows = [];
        foreach ($monthlyRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (!empty($row['is_bonus'])) {
                $bonusRows[] = $row;
                continue;
            }

            $monthKey = (string) ($row['month_key'] ?? '');
            $month = (int) substr($monthKey, 5, 2);
            if ($month > 0) {
                $regularRows[$month] = $row;
            }
        }

        foreach (self::REGULAR_ROW_MAP as $month => $sheetRow) {
            $row = $regularRows[$month] ?? null;
            $this->writeMonthlyRow($summarySheet, $sheetRow, is_array($row) ? $row : null);
        }

        foreach (self::BONUS_ROWS as $index => $sheetRow) {
            $row = $bonusRows[$index] ?? null;
            $this->writeMonthlyRow($summarySheet, $sheetRow, is_array($row) ? $row : null);
        }

        /** @var list<array<string,string>> $executiveWorkers */
        $executiveWorkers = $report['executive_worker_summary'] ?? [];
        for ($i = 0; $i < 4; $i++) {
            $excelRow = 78 + $i;
            $worker = $executiveWorkers[$i] ?? null;
            $summarySheet->setCellValue('S' . $excelRow, (string) ($worker['staff_name'] ?? ''));
            $summarySheet->setCellValue('AB' . $excelRow, (string) ($worker['role_label'] ?? ''));
            $summarySheet->setCellValue('AJ' . $excelRow, (string) ($worker['employment_label'] ?? ''));
        }
    }

    /**
     * @param array<string,mixed>|null $row
     */
    private function writeMonthlyRow(Worksheet $sheet, int $sheetRow, ?array $row): void
    {
        $sheet->setCellValue('A' . $sheetRow, (string) ($row['label'] ?? ''));
        $sheet->setCellValue('O' . $sheetRow, $this->intValue($row['left_regular_count'] ?? null));
        $sheet->setCellValue('S' . $sheetRow, $this->intValue($row['left_regular_amount'] ?? null));
        $sheet->setCellValue('AB' . $sheetRow, $this->intValue($row['left_executive_count'] ?? null));
        $sheet->setCellValue('AF' . $sheetRow, $this->intValue($row['left_executive_amount'] ?? null));
        $sheet->setCellValue('AO' . $sheetRow, $this->intValue($row['left_temporary_count'] ?? null));
        $sheet->setCellValue('AS' . $sheetRow, $this->intValue($row['left_temporary_amount'] ?? null));
        $sheet->setCellValue('BB' . $sheetRow, $this->intValue($row['left_total_count'] ?? null));
        $sheet->setCellValue('BF' . $sheetRow, $this->intValue($row['left_total_amount'] ?? null));
        $sheet->setCellValue('BO' . $sheetRow, $this->intValue($row['right_regular_count'] ?? null));
        $sheet->setCellValue('BS' . $sheetRow, $this->intValue($row['right_regular_amount'] ?? null));
        $sheet->setCellValue('CB' . $sheetRow, $this->intValue($row['right_executive_count'] ?? null));
        $sheet->setCellValue('CF' . $sheetRow, $this->intValue($row['right_executive_amount'] ?? null));
    }

    private function intValue(mixed $value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }

        return (int) round((float) $value, 0);
    }

    private function sheet(Spreadsheet $spreadsheet, string $name): Worksheet
    {
        $sheet = $spreadsheet->getSheetByName($name);
        if (!$sheet instanceof Worksheet) {
            throw new RuntimeException(sprintf('Worksheet not found: %s', $name));
        }

        return $sheet;
    }

    /**
     */
    private function writeLaborInsuranceNumber(Worksheet $sheet, string $laborInsuranceNumber): void
    {
        $parts = $this->formatLaborInsuranceParts($laborInsuranceNumber);
        $manualDigits = $parts['prefecture'] . $parts['jurisdiction'] . $parts['office'] . $parts['base'];

        $slots = ['O9', 'Q9', 'S9', 'U9', 'W9', 'Y9', 'AA9', 'AC9', 'AE9', 'AG9', 'AI9'];
        foreach ($slots as $index => $cell) {
            $digit = substr($manualDigits, $index, 1);
            if ($digit === false) {
                $digit = '';
            }
            $sheet->setCellValueExplicit($cell, $digit, DataType::TYPE_STRING);
        }
    }

    /**
     * @return array{prefecture:string,jurisdiction:string,office:string,base:string}
     */
    private function formatLaborInsuranceParts(string $laborInsuranceNumber): array
    {
        $digits = preg_replace('/\D+/', '', $laborInsuranceNumber) ?? '';

        if (strlen($digits) >= 14) {
            return [
                'prefecture' => (string) substr($digits, 0, 2),
                'jurisdiction' => (string) substr($digits, 2, 1),
                'office' => str_pad((string) substr($digits, 3, 2), 2, '0', STR_PAD_LEFT),
                'base' => str_pad((string) substr($digits, 5, 6), 6, '0', STR_PAD_LEFT),
            ];
        }

        if (strlen($digits) === 12 && substr($digits, -3) === '000') {
            $core = substr($digits, 0, 9);

            return [
                'prefecture' => (string) substr($core, 0, 2),
                'jurisdiction' => (string) substr($core, 2, 1),
                'office' => str_pad((string) substr($core, 3, 1), 2, '0', STR_PAD_LEFT),
                'base' => str_pad((string) substr($core, 4, 5), 6, '0', STR_PAD_LEFT),
            ];
        }

        $core = substr(str_pad($digits, 9, '0', STR_PAD_LEFT), 0, 9);

        return [
            'prefecture' => (string) substr($core, 0, 2),
            'jurisdiction' => (string) substr($core, 2, 1),
            'office' => str_pad((string) substr($core, 3, 1), 2, '0', STR_PAD_LEFT),
            'base' => str_pad((string) substr($core, 4, 5), 6, '0', STR_PAD_LEFT),
        ];
    }

    private function safeFilePart(string $value): string
    {
        $value = preg_replace('/[\\\\\\/:*?"<>|]+/u', '_', $value) ?? 'labor_insurance';
        $value = trim($value, " .\t\n\r\0\x0B");

        return $value !== '' ? $value : 'labor_insurance';
    }

    private function sanitizeTemplateForOutput(string $templatePath): string
    {
        $tempDir = storage_path('app/tmp');
        if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Failed to create temp directory.');
        }

        $sanitizedPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('labor_template_clean_', true) . '.xlsx';
        if (!copy($templatePath, $sanitizedPath)) {
            throw new RuntimeException('Failed to copy labor insurance template.');
        }

        $zip = new ZipArchive();
        if ($zip->open($sanitizedPath) !== true) {
            throw new RuntimeException('Failed to open labor insurance template zip.');
        }

        $this->replaceZipEntry(
            $zip,
            'xl/worksheets/sheet1.xml',
            static fn (string $xml): string => preg_replace('/<legacyDrawing\b[^>]*\/>/u', '', $xml) ?? $xml
        );
        $this->replaceZipEntry(
            $zip,
            'xl/worksheets/sheet6.xml',
            static fn (string $xml): string => preg_replace('/<legacyDrawing\b[^>]*\/>/u', '', $xml) ?? $xml
        );
        $this->replaceZipEntry(
            $zip,
            'xl/worksheets/_rels/sheet1.xml.rels',
            static fn (string $xml): string => preg_replace(
                '/<Relationship\b[^>]*Type="http:\/\/schemas\.openxmlformats\.org\/officeDocument\/2006\/relationships\/vmlDrawing"[^>]*\/>/u',
                '',
                $xml
            ) ?? $xml
        );
        $this->replaceZipEntry(
            $zip,
            'xl/worksheets/_rels/sheet6.xml.rels',
            static fn (string $xml): string => preg_replace(
                '/<Relationship\b[^>]*Type="http:\/\/schemas\.openxmlformats\.org\/officeDocument\/2006\/relationships\/(?:vmlDrawing|comments)"[^>]*\/>/u',
                '',
                $xml
            ) ?? $xml
        );
        $this->replaceZipEntry(
            $zip,
            '[Content_Types].xml',
            static fn (string $xml): string => preg_replace(
                '/<Override\b[^>]*PartName="\/xl\/comments1\.xml"[^>]*\/>/u',
                '',
                $xml
            ) ?? $xml
        );

        $zip->deleteName('xl/comments1.xml');
        $zip->deleteName('xl/drawings/vmlDrawing1.vml');
        $zip->deleteName('xl/drawings/vmlDrawing2.vml');
        $zip->deleteName('xl/drawings/_rels/vmlDrawing1.vml.rels');
        $zip->deleteName('xl/drawings/_rels/vmlDrawing2.vml.rels');

        $zip->close();

        return $sanitizedPath;
    }

    private function replaceZipEntry(ZipArchive $zip, string $entryName, callable $mutator): void
    {
        $content = $zip->getFromName($entryName);
        if (!is_string($content)) {
            return;
        }

        $updated = $mutator($content);
        if (!is_string($updated) || $updated === $content) {
            return;
        }

        $zip->deleteName($entryName);
        $zip->addFromString($entryName, $updated);
    }
}
