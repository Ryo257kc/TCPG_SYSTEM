<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use setasign\Fpdi\Tcpdf\Fpdi;

class YearEndAdjustmentV2Controller extends Controller
{
    public function index(Request $request): View
    {
        $targetYear = (int) $request->query('target_year', date('Y'));
        if ($targetYear < 2000 || $targetYear > 2100) {
            $targetYear = (int) date('Y');
        }

        $rows = [];
        $statusCounts = [
            'draft' => 0,
            'submitted' => 0,
            'returned' => 0,
            'confirmed' => 0,
            'reflected' => 0,
            'excluded' => 0,
            'retired' => 0,
            'other' => 0,
        ];

        $tableExists = Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications');

        if ($tableExists) {
            $applications = DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_year_end_applications')
                ->where('target_year', $targetYear)
                ->orderBy('staff_id')
                ->get();

            $staffDetails = $this->staffListDetails($applications->pluck('staff_id')->all());

            foreach ($applications as $application) {
                $status = trim((string) ($application->status ?? ''));
                if ($status === '') {
                    $status = 'draft';
                }

                if (array_key_exists($status, $statusCounts)) {
                    $statusCounts[$status]++;
                } else {
                    $statusCounts['other']++;
                }

                $staffId = trim((string) ($application->staff_id ?? ''));
                $staffDetail = $staffDetails[$staffId] ?? ['staff_name' => '', 'nyu_date' => '', 'tai_date' => ''];
                $rows[] = [
                    'application_id' => (string) ($application->application_id ?? ''),
                    'staff_id' => $staffId,
                    'staff_name' => $staffDetail['staff_name'],
                    'nyu_date' => $staffDetail['nyu_date'],
                    'tai_date' => $staffDetail['tai_date'],
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'can_delete' => $status === 'draft',
                    'year_end_adjustment' => $this->bitLabel($application->year_end_adjustment ?? null),
                    'personal_info_changed' => $this->bitLabel($application->personal_info_changed ?? null),
                    'dependents_changed' => $this->bitLabel($application->dependents_changed ?? null),
                    'insurance_deduction_changed' => $this->bitLabel($application->insurance_deduction_changed ?? null),
                    'housing_loan_changed' => $this->bitLabel($application->housing_loan_changed ?? null),
                    'previous_job_withholding_changed' => $this->bitLabel($application->previous_job_withholding_changed ?? null),
                    'special_collection_requested' => $this->bitLabel($application->special_collection_requested ?? null),
                    'submitted_at' => $this->dateLabel($application->submitted_at ?? null),
                    'confirmed_at' => $this->dateLabel($application->confirmed_at ?? null),
                    'reflected_at' => $this->dateLabel($application->reflected_at ?? null),
                ];
            }
        }

        return view('admin_v2.work.year_end_adjustments.index', [
            'targetYear' => $targetYear,
            'yearOptions' => range((int) date('Y') - 1, (int) date('Y') + 1),
            'tableExists' => $tableExists,
            'rows' => $rows,
            'statusCounts' => $statusCounts,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(int $applicationId): View
    {
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications'), 404);

        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $targetYear = (int) ($application->target_year ?? date('Y'));
        $staffId = trim((string) ($application->staff_id ?? ''));

        return view('admin_v2.work.year_end_adjustments.show', [
            'applicationId' => $applicationId,
            'targetYear' => $targetYear,
            'application' => $this->formatApplication($application),
            'staff' => $this->staffDetail($staffId),
            'nenTyo' => $this->nenTyoDetail($application, $staffId, $targetYear),
            'nenTyoSummaryGroups' => $this->nenTyoSummaryGroups(),
            'nenTyoLabels' => $this->nenTyoLabels(),
            'fuyoRows' => $this->fuyoRows($staffId, $targetYear),
            'hokenRows' => $this->hokenRows($staffId, $targetYear),
        ]);
    }

    public function hokenPreview(int $applicationId)
    {
        [$application, $staffId, $targetYear] = $this->yearEndApplicationContext($applicationId);

        $templatePath = $this->hokenPdfTemplatePath($targetYear);
        $staff = $this->staffDetail($staffId);
        $hokenRows = $this->hokenRows($staffId, $targetYear);

        $previewDir = storage_path("app/year_end/previews/{$targetYear}/{$staffId}");
        if (!is_dir($previewDir)) {
            mkdir($previewDir, 0775, true);
        }

        $outputPath = $previewDir . '\\hoken_koujyo_shinkoku.pdf';

        $pdf = new Fpdi('P', 'mm');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pageCount = $pdf->setSourceFile($templatePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($pageNo === 1) {
                $pdf->SetTextColor(0, 0, 180);
                $pdf->SetFont('kozminproregular', '', 8);
                $this->writePdfText($pdf, 18, 14, "{$targetYear}年");
                $this->writePdfText($pdf, 42, 14, $staffId);
                $this->writePdfText($pdf, 62, 14, (string) ($staff['staff_name'] ?? ''));

                $y = 62;
                foreach (array_slice($hokenRows, 0, 8) as $row) {
                    $this->writePdfText($pdf, 18, $y, (string) ($row['insurance_company'] ?? ''), 28);
                    $this->writePdfText($pdf, 54, $y, (string) ($row['insurance_type'] ?? ''), 24);
                    $this->writePdfText($pdf, 84, $y, (string) ($row['category'] ?? ''), 18);
                    $this->writePdfText($pdf, 110, $y, (string) ($row['policy_holder_name'] ?? ''), 22);
                    $this->writePdfText($pdf, 138, $y, (string) ($row['beneficiary_name'] ?? ''), 22);
                    $this->writePdfText($pdf, 170, $y, (string) ($row['declared_amount'] ?? ''), 18);
                    $y += 8;
                }
            }
        }

        $pdf->Output($outputPath, 'F');

        return response()->file($outputPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="hoken_koujyo_shinkoku_' . $targetYear . '_' . $staffId . '.pdf"',
        ]);
    }
    public function kisoPreview(int $applicationId)
    {
        return $this->templatePreview($applicationId, 'kiso_koujyo_shinkoku', 'kiso_koujyo_shinkoku');
    }

    public function fuyoPreview(int $applicationId)
    {
        return $this->templatePreview($applicationId, 'fuyo_koujyo_shinkoku', 'fuyo_koujyo_shinkoku');
    }

    public function gensenBoPreview(int $applicationId)
    {
        return $this->templatePreview($applicationId, 'gensen_tyoushu_bo', 'gensen_tyoushu_bo');
    }

    public function gensenHyouPreview(int $applicationId)
    {
        return $this->templatePreview($applicationId, 'gensen_tyoushu_hyou', 'gensen_tyoushu_hyou');
    }

    private function templatePreview(int $applicationId, string $templateKey, string $outputKey)
    {
        [$application, $staffId, $targetYear] = $this->yearEndApplicationContext($applicationId);
        $templatePath = $this->yearEndPdfTemplatePath($targetYear, $templateKey);
        $staff = $this->staffDetail($staffId);
        $nenTyo = $this->nenTyoDetail($application, $staffId, $targetYear);

        $previewDir = storage_path("app/year_end/previews/{$targetYear}/{$staffId}");
        if (!is_dir($previewDir)) {
            mkdir($previewDir, 0775, true);
        }

        $outputPath = $previewDir . '\\' . $outputKey . '.pdf';
        $this->copyPdfTemplateToPreview(
            $templatePath,
            $outputPath,
            function (Fpdi $pdf, int $pageNo) use ($templateKey, $staffId, $targetYear, $staff, $nenTyo): void {
                if ($pageNo !== 1) {
                    return;
                }

                $this->writeYearEndTemplateHeader($pdf, $templateKey, $staffId, $targetYear, $staff, $nenTyo);
                if ($templateKey === 'gensen_tyoushu_bo') {
                    $this->writeGensenBoPreview($pdf, $staffId, $targetYear, $staff, $nenTyo);
                }
                if ($templateKey === 'gensen_tyoushu_hyou') {
                    $this->writeGensenHyouPreview($pdf, $staff, $nenTyo);
                }
                if ($templateKey === 'kiso_koujyo_shinkoku') {
                    $this->writeKisoPreview($pdf, $nenTyo);
                }
                if ($templateKey === 'fuyo_koujyo_shinkoku') {
                    $this->writeFuyoPreview($pdf, $staffId, $targetYear);
                }
            }
        );

        return response()->file($outputPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $outputKey . '_' . $targetYear . '_' . $staffId . '.pdf"',
        ]);
    }

    private function copyPdfTemplateToPreview(string $templatePath, string $outputPath, ?callable $writer = null): void
    {
        $pdf = new Fpdi('P', 'mm');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pageCount = $pdf->setSourceFile($templatePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            if ($writer) {
                $writer($pdf, $pageNo);
            }
        }

        $pdf->Output($outputPath, 'F');
    }

    private function writeYearEndTemplateHeader(Fpdi $pdf, string $templateKey, string $staffId, int $targetYear, array $staff, array $nenTyo): void
    {
        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));
        $yearEnd = trim((string) ($nenTyo['year_end'] ?? ''));

        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 8);

        if ($templateKey === 'fuyo_koujyo_shinkoku') {
            $this->writeFuyoHeader($pdf, $staff);
            return;
        }

        $positions = [
            'kiso_koujyo_shinkoku' => [18, 14, 42, 14, 62, 14, 122, 14],
            'fuyo_koujyo_shinkoku' => [18, 14, 42, 14, 62, 14, 122, 14],
            'gensen_tyoushu_bo' => [16, 12, 36, 12, 56, 12, 112, 12],
            'gensen_tyoushu_hyou' => [16, 12, 36, 12, 56, 12, 112, 12],
        ];
        [$yearX, $yearY, $idX, $idY, $nameX, $nameY, $companyX, $companyY] = $positions[$templateKey] ?? [18, 14, 42, 14, 62, 14, 122, 14];

        $this->writePdfText($pdf, $yearX, $yearY, "{$targetYear}年");
        $this->writePdfText($pdf, $idX, $idY, $staffId);
        $this->writePdfText($pdf, $nameX, $nameY, $staffName, 36);
        $this->writePdfText($pdf, $companyX, $companyY, $companyName, 36);

        if ($yearEnd !== '') {
            $this->writePdfText($pdf, $companyX, $companyY + 5, $yearEnd, 18);
        }
    }

    private function writeFuyoHeader(Fpdi $pdf, array $staff): void
    {
        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $staffFuri = trim((string) ($staff['staff_name_furi'] ?? ''));
        $address = trim((string) ($staff['address'] ?? ''));
        $postNum = preg_replace('/\D+/', '', trim((string) ($staff['post_num'] ?? ''))) ?? '';
        if ($postNum !== '') {
            $postNum = str_pad($postNum, 7, '0', STR_PAD_LEFT);
        }
        $birthday = $this->formatPdfJapaneseDate($staff['birthday'] ?? null);
        $myNumber = trim((string) ($staff['my_number'] ?? ''));
        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));
        $companyNumber = trim((string) ($staff['corporate_number'] ?? ''));
        $companyAddress = trim((string) ($staff['company_address'] ?? ''));
        $headHouse = trim((string) ($staff['head_house'] ?? ''));
        $houseRelationship = trim((string) ($staff['relationship'] ?? ''));
        $spouse = trim((string) ($staff['spouse'] ?? ''));
        $taxAmount = trim((string) ($staff['tax_amount'] ?? ''));
        $textColor = [0, 0, 180];

        // 会社欄
        $this->writePdfTextSized($pdf, 62, 15, $companyName, 7, 42);
        $this->writePdfDigitsSized($pdf, 60, 27, $companyNumber, 4.4, 7);
        $this->writePdfWrappedTextSized($pdf, 61, 34, $companyAddress, 58, 4.0, 2, 7);

        // 本人欄
        $this->writePdfTextSized($pdf, 142, 11.5, $staffFuri, 7, 28);
        $this->writePdfTextSized($pdf, 142, 17, $staffName, 7, 28);

        // 本人欄：生年月日の元号などを隠す枠。調整中は色付き、確定後は [255, 255, 255] にする。
        $birthdayEraseColor = [255, 240, 120];
        $staffBirthdayEraseX = 209.0;
        $staffBirthdayEraseY = 10.0;
        $staffBirthdayEraseW = 30.0;
        $staffBirthdayEraseH = 4.8;
        $this->fillPdfRect($pdf, $staffBirthdayEraseX, $staffBirthdayEraseY, $staffBirthdayEraseW, $staffBirthdayEraseH, $birthdayEraseColor);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $this->writePdfTextSized($pdf, 210, 12, $birthday, 7, 28);

        $this->writePdfTextSized($pdf, 210, 18, $headHouse, 7, 22);
        $this->writePdfTextSized($pdf, 210, 25, $houseRelationship, 7, 12);
        $this->writePdfDigitsSized($pdf, 136, 26, $myNumber, 4.5, 7);

        // 本人欄：郵便番号（左3桁・右4桁）
        if ($postNum !== '') {
            $this->writePdfTextSized($pdf, 148, 31, substr($postNum, 0, 3), 7, 6);
            $this->writePdfTextSized($pdf, 160, 31, substr($postNum, 3), 7, 8);
        }
        $this->writePdfWrappedTextSized($pdf, 142, 35, $address, 58, 4.0, 2, 7);

        // 本人欄：配偶者の有無
        $circle = '〇';
        if ($spouse === '1') {
            $this->writePdfTextSized($pdf, 234.0, 34.0, $circle, 11, 4);
        } elseif ($spouse === '0' || $spouse === '2') {
            $this->writePdfTextSized($pdf, 266.0, 34.0, $circle, 11, 4);
        }

        // 本人欄：甲欄
        if (str_contains($taxAmount, '甲')) {
            $this->writePdfTextSized($pdf, 283.0, 44.0, $circle, 11, 4);
        }
    }
    private function writeFuyoPreview(Fpdi $pdf, string $staffId, int $targetYear): void
    {
        $rows = $this->fuyoRows($staffId, $targetYear);
        $spouseRows = [];
        $dependentRows = [];
        $under16Rows = [];

        foreach ($rows as $row) {
            $relationship = trim((string) ($row['fuyo_relationship'] ?? ''));
            $age = $this->ageAtYearEnd($row['fuyo_birthday'] ?? null, $targetYear);
            $isTarget = ((string) ($row['deduction_target'] ?? '')) === '1';

            $spouseRelationships = ['夫', '妻', '配偶者'];
            if (in_array($relationship, $spouseRelationships, true)) {
                $spouseRows[] = $row;
                continue;
            }

            if (!$isTarget) {
                continue;
            }

            if ($age < 16) {
                $under16Rows[] = $row;
            } else {
                $dependentRows[] = $row;
            }
        }

        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 7);

        // A欄：源泉控除対象配偶者
        foreach (array_slice($spouseRows, 0, 1) as $index => $row) {
            $this->writeFuyoPersonRow($pdf, $row, 'spouse', $index);
        }

        // B欄：控除対象扶養親族（16歳以上）
        foreach (array_slice($dependentRows, 0, 4) as $index => $row) {
            $this->writeFuyoPersonRow($pdf, $row, 'dependent', $index);
        }

        // 16歳未満の扶養親族欄
        foreach (array_slice($under16Rows, 0, 2) as $index => $row) {
            $this->writeFuyoPersonRow($pdf, $row, 'under16', $index);
        }
    }

    private function writeFuyoPersonRow(Fpdi $pdf, array $row, string $section, int $index): void
    {
        $name = (string) ($row['fuyo_name'] ?? '');
        $furi = (string) ($row['fuyo_name_furi'] ?? '');
        $relationship = (string) ($row['fuyo_relationship'] ?? '');
        $relationshipLabel = $relationship === '妻' ? '' : $relationship;
        $income = $this->formatPdfMoney($row['fuyo_shunyu'] ?? null);
        $address = (string) ($row['fuyo_address'] ?? '');
        $failure = (string) ($row['failure_judgment'] ?? '');
        $checkMark = '✓';
        $textColor = [0, 0, 180];

        // 扶養欄：生年月日の元号などを隠す枠。調整中は色付き、確定後は [255, 255, 255] にする。
        $birthdayEraseColor = [255, 240, 120];

        if ($section === 'spouse') {
            // A欄：源泉控除対象配偶者
            $rowOffset = $index * 11.4;
            $this->writePdfTextSized($pdf, 40.0, 58.5 + $rowOffset, $furi, 7, 24);
            $this->writePdfTextSized($pdf, 42.0, 65.0 + $rowOffset, $name, 7, 24);
            $this->writePdfDigitsSized($pdf, 72.0, 60.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.45, 7);
            $this->writePdfTextSized($pdf, 83.0, 63.0 + $rowOffset, $relationshipLabel, 7, 10);

            // A欄：生年月日の元号などを隠す枠
            $this->fillPdfRect($pdf, 98.5, 62.5 + $rowOffset, 31.0, 5.0, $birthdayEraseColor);
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
            $this->writePdfTextSized($pdf, 99.0, 65.0 + $rowOffset, $this->formatPdfJapaneseDate($row['fuyo_birthday'] ?? null), 7, 18);

            $this->writePdfTextRightSized($pdf, 155.0, 63.0 + $rowOffset, $income, 7, 16);
            if (trim($failure) !== '') {
                $this->writePdfTextSized($pdf, 132.0, 61.0 + $rowOffset, $checkMark, 11, 4);
                $this->writePdfTextSized($pdf, 173.0, 63.0 + $rowOffset, $failure, 7, 10);
            }
            $this->writePdfWrappedTextSized($pdf, 203.0, 60.0 + $rowOffset, $address, 28, 3.7, 2, 7);
            return;
        }

        if ($section === 'under16') {
            // 16歳未満の扶養親族欄
            $rowOffset = $index * 11.7;
            $this->writePdfTextSized($pdf, 39.0, 178.5 + $rowOffset, $furi, 7, 24);
            $this->writePdfTextSized($pdf, 42.0, 182.0 + $rowOffset, $name, 7, 24);
            $this->writePdfDigitsSized($pdf, 69.0, 181.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.45, 7);
            $this->writePdfTextSized($pdf, 121.0, 181.0 + $rowOffset, $relationshipLabel, 7, 10);

            // 16歳未満欄：生年月日の元号などを隠す枠
            $this->fillPdfRect($pdf, 127.5, 178.5 + $rowOffset, 31.0, 5.0, $birthdayEraseColor);
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
            $this->writePdfTextSized($pdf, 128.0, 181.0 + $rowOffset, $this->formatPdfJapaneseDate($row['fuyo_birthday'] ?? null), 7, 18);

            $this->writePdfTextRightSized($pdf, 235.0, 181.0 + $rowOffset, $income, 7, 16);
            $this->writePdfWrappedTextSized($pdf, 148.0, 180.0 + $rowOffset, $address, 36, 3.7, 2, 7);
            return;
        }

        // B欄：控除対象扶養親族（16歳以上）
        $rowOffset = $index * 11.4;
        $this->writePdfTextSized($pdf, 40.0, 70.5 + $rowOffset, $furi, 7, 24);
        $this->writePdfTextSized($pdf, 42.0, 78.0 + $rowOffset, $name, 7, 24);
        $this->writePdfDigitsSized($pdf, 72.0, 74.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.45, 7);
        $this->writePdfTextSized($pdf, 78.0, 80.0 + $rowOffset, $relationshipLabel, 7, 10);

        // B欄：生年月日の元号などを隠す枠
        $this->fillPdfRect($pdf, 98.5, 77.5 + $rowOffset, 31.0, 5.0, $birthdayEraseColor);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $this->writePdfTextSized($pdf, 99.0, 80.0 + $rowOffset, $this->formatPdfJapaneseDate($row['fuyo_birthday'] ?? null), 7, 18);

        $this->writePdfTextRightSized($pdf, 155.0, 75.0 + $rowOffset, $income, 7, 16);
        if (trim($failure) !== '') {
            $this->writePdfTextSized($pdf, 35.0, 127.0 + $rowOffset, $checkMark, 11, 4);
            $this->writePdfTextSized($pdf, 135.0, 135.0 + $rowOffset, $failure, 7, 10);
        }
        $this->writePdfWrappedTextSized($pdf, 203.0, 73.0 + $rowOffset, $address, 28, 3.7, 2, 7);
    }
    private function writeKisoPreview(Fpdi $pdf, array $nenTyo): void
    {
        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 8);

        $rows = [
            ['kyuyo_teate_sum', 86, 68],
            ['shotoku_deduction', 124, 68],
            ['bonus_kyuyo_sum', 160, 68],
            ['kiso_bunrui', 189, 96],
            ['kiso_koujyo', 214, 105],
            ['haigu_shotoku', 166, 100],
            ['haigu_shotoku_sum', 198, 100],
            ['haigu_bunrui', 226, 104],
            ['haigu_toku_deduction', 251, 110],
            ['haigu_toku_deduction_amo', 274, 110],
            ['tyosei_koujyo_select', 117, 184],
            ['tyosei_koujyo', 184, 184],
        ];

        foreach ($rows as [$key, $x, $y]) {
            $this->writePdfTextRight($pdf, $x, $y, (string) ($nenTyo[$key] ?? ''), 22);
        }
    }
    private function writeGensenHyouPreview(Fpdi $pdf, array $staff, array $nenTyo): void
    {
        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $address = trim((string) ($staff['address'] ?? $staff['staff_address'] ?? $staff['add'] ?? ''));

        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 8);

        $this->writePdfText($pdf, 34, 31, $address, 70);
        $this->writePdfText($pdf, 34, 41, $staffName, 38);

        $this->writePdfTextRight($pdf, 83, 65, (string) ($nenTyo['bonus_kyuyo_sum'] ?? ''), 18);
        $this->writePdfTextRight($pdf, 121, 65, (string) ($nenTyo['shotoku_deduction'] ?? ''), 18);
        $this->writePdfTextRight($pdf, 159, 65, (string) ($nenTyo['shotoku_deduction_sum'] ?? ''), 18);
        $this->writePdfTextRight($pdf, 197, 65, (string) ($nenTyo['nentyo_nen_tax'] ?? ''), 18);

        $this->writePdfTextRight($pdf, 72, 87, (string) ($nenTyo['kyu_syaho_fee_kou'] ?? ''), 18);
        $this->writePdfTextRight($pdf, 109, 87, (string) ($nenTyo['seimei_fee_kou'] ?? ''), 18);
        $this->writePdfTextRight($pdf, 146, 87, (string) ($nenTyo['jishun_fee_kou'] ?? ''), 18);
        $this->writePdfTextRight($pdf, 184, 87, (string) ($nenTyo['jyu_kari_kou'] ?? ''), 18);

        $this->writePdfTextRight($pdf, 61, 112, (string) ($nenTyo['haigu_umu'] ?? ''), 8);
        $this->writePdfTextRight($pdf, 82, 112, (string) ($nenTyo['toku_fu'] ?? ''), 8);
        $this->writePdfTextRight($pdf, 103, 112, (string) ($nenTyo['rou_dou'] ?? ''), 8);
        $this->writePdfTextRight($pdf, 124, 112, (string) ($nenTyo['rou_dou_gai'] ?? ''), 8);
        $this->writePdfTextRight($pdf, 145, 112, (string) ($nenTyo['fuyo_ta'] ?? ''), 8);
        $this->writePdfTextRight($pdf, 166, 112, (string) ($nenTyo['dependent_under_16'] ?? ''), 8);
        $this->writePdfTextRight($pdf, 187, 112, (string) ($nenTyo['shougai_ta'] ?? ''), 8);
    }
    private function writeGensenBoPreview(Fpdi $pdf, string $staffId, int $targetYear, array $staff, array $nenTyo): void
    {
        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));
        $payrollRows = $this->yearEndPayrollLedgerRows($staffId, $targetYear, $companyName);

        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 7);

        $y = 47.0;
        foreach (array_slice($payrollRows, 0, 14) as $row) {
            $this->writePdfText($pdf, 25, $y, $row['month'], 16);
            $this->writePdfTextRight($pdf, 49, $y, $row['fuyo_sum'], 10);
            $this->writePdfTextRight($pdf, 67, $y, $row['bonus_amount'], 18);
            $this->writePdfTextRight($pdf, 88, $y, $row['taxation_sum'], 20);
            $this->writePdfTextRight($pdf, 114, $y, $row['syaho_sum'], 20);
            $this->writePdfTextRight($pdf, 139, $y, $row['syaho_deduction_sum'], 20);
            $this->writePdfTextRight($pdf, 163, $y, $row['income_tax'], 18);
            $this->writePdfText($pdf, 170, $y, $companyName, 24);
            $y += 9.45;
        }
    }

    /** @return list<array<string, string>> */
    private function yearEndPayrollLedgerRows(string $staffId, int $targetYear, string $companyName): array
    {
        if (!Schema::connection('sqlsrv_payroll')->hasTable('mx_kyuyo_shou')) {
            return [];
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_staff_id', $staffId)
            ->whereYear('supply_month', $targetYear)
            ->orderBy('supply_month')
            ->orderBy('bonus')
            ->get(['supply_month', 'fuyo_sum', 'bonus', 'bonus_amo', 'taxation_sum', 'syaho_sum', 'syaho_deduction_sum', 'income_tax'])
            ->map(function ($row) use ($companyName): array {
                $isBonus = (int) ($row->bonus ?? 0) === 1;
                return [
                    'month' => $this->pdfDateMonthLabel($row->supply_month ?? null),
                    'fuyo_sum' => $this->pdfNumber($row->fuyo_sum ?? 0),
                    'bonus_amount' => $isBonus ? $this->pdfNumber($row->bonus_amo ?? $row->taxation_sum ?? 0) : '',
                    'taxation_sum' => $this->pdfNumber($row->taxation_sum ?? 0),
                    'syaho_sum' => $this->pdfNumber($row->syaho_sum ?? 0),
                    'syaho_deduction_sum' => $this->pdfNumber($row->syaho_deduction_sum ?? 0),
                    'income_tax' => $this->pdfNumber($row->income_tax ?? 0),
                    'company_name' => $companyName,
                ];
            })
            ->all();
    }

    private function pdfDateMonthLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable((string) $value))->format('Y/m/d');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function pdfNumber(mixed $value): string
    {
        $number = $this->money($value);
        if (abs($number) < 0.00001) {
            return '0';
        }

        return number_format($number, 0);
    }

    /** @param list<array<string, string>> $rows */
    private function sumPdfRows(array $rows, string $key): string
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += $this->money($row[$key] ?? 0);
        }

        return number_format($total, 0);
    }

    private function writePdfTextRight(Fpdi $pdf, float $rightX, float $y, string $text, int $maxWidth = 0): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($maxWidth > 0 && function_exists('mb_strimwidth')) {
            $text = mb_strimwidth($text, 0, $maxWidth, '...', 'UTF-8');
        }

        $width = $pdf->GetStringWidth($text);
        $pdf->Text($rightX - $width, $y, $text);
    }

    private function formatPdfMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format($this->money($value), 0);
    }

    private function formatPdfDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable((string) $value))->format('Y/m/d');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }
    private function formatPdfJapaneseDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $date = new \DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            return trim((string) $value);
        }

        $year = (int) $date->format('Y');
        $era = '西暦';
        $eraYear = $year;
        if ($date >= new \DateTimeImmutable('2019-05-01')) {
            $era = '令和';
            $eraYear = $year - 2018;
        } elseif ($date >= new \DateTimeImmutable('1989-01-08')) {
            $era = '平成';
            $eraYear = $year - 1988;
        } elseif ($date >= new \DateTimeImmutable('1926-12-25')) {
            $era = '昭和';
            $eraYear = $year - 1925;
        }

        return $era . $eraYear . '年' . (int) $date->format('n') . '月' . (int) $date->format('j') . '日';
    }

    private function writePdfDigits(Fpdi $pdf, float $x, float $y, string $text, float $pitch): void
    {
        $digits = preg_replace('/\D+/', '', $text) ?? '';
        if ($digits === '') {
            return;
        }

        foreach (str_split($digits) as $index => $char) {
            $pdf->Text($x + ($index * $pitch), $y, $char);
        }
    }
    public function hokenCertificatePreview(int $applicationId, int $hokenNo): View
    {
        [$application, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);
        $row = $this->hokenRowOrFail($hokenNo, $staffId, $targetYear);
        $path = trim((string) ($row->certificate_file_path ?? ''));
        abort_if($path === '', 404);

        $isExternal = preg_match('/^https?:\/\//', $path) === 1;
        $fileUrl = '';
        $extension = '';

        if ($isExternal) {
            $fileUrl = $path;
            $urlPath = (string) parse_url($path, PHP_URL_PATH);
            $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
        } else {
            $relativePath = ltrim(str_replace('\\', '/', $path), '/');
            $fullPath = public_path($relativePath);
            abort_unless(is_file($fullPath), 404);
            $fileUrl = asset($relativePath);
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        }

        return view('admin_v2.work.year_end_adjustments.certificate_preview', [
            'fileUrl' => $fileUrl,
            'fileName' => trim((string) ($row->certificate_original_name ?? '')),
            'extension' => $extension,
            'isImage' => in_array($extension, ['jpg', 'jpeg', 'png'], true),
            'isPdf' => $extension === 'pdf',
            'hokenNo' => $hokenNo,
            'targetYear' => $targetYear,
        ]);
    }

    public function createTargets(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'target_year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $targetYear = (int) $values['target_year'];
        if (!Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications')) {
            return redirect()
                ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
                ->with('status', '年末調整申請テーブルが見つかりません。');
        }

        $staffRows = $this->activeStaffRows();
        $nenTyoNoMap = $this->nenTyoNoMap($targetYear);
        $created = 0;
        $skipped = 0;
        $linked = 0;

        foreach ($staffRows as $row) {
            $staffId = trim((string) ($row->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $nenTyoNo = $nenTyoNoMap[$staffId] ?? null;
            $existing = DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_year_end_applications')
                ->where('staff_id', $staffId)
                ->where('target_year', $targetYear)
                ->first(['application_id', 'nen_tyo_no']);

            if ($existing) {
                if ($nenTyoNo !== null && empty($existing->nen_tyo_no)) {
                    DB::connection('sqlsrv_payroll')
                        ->table('dbo.staff_year_end_applications')
                        ->where('application_id', $existing->application_id)
                        ->update(['nen_tyo_no' => $nenTyoNo]);
                    $linked++;
                }

                $skipped++;
                continue;
            }

            DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_year_end_applications')
                ->insert([
                    'staff_id' => $staffId,
                    'target_year' => $targetYear,
                    'nen_tyo_no' => $nenTyoNo,
                    'status' => 'draft',
                ]);

            $created++;
        }

        return redirect()
            ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
            ->with('status', "{$targetYear}年の対象者を作成しました。追加 {$created}件 / 作成済 {$skipped}件 / 年調リンク補完 {$linked}件");
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'application_id' => ['required', 'integer'],
            'target_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'status' => ['required', 'string'],
        ]);

        $targetYear = (int) $values['target_year'];
        $status = trim((string) $values['status']);
        if (!array_key_exists($status, $this->statusOptions())) {
            return redirect()
                ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
                ->with('status', '選択された状態が正しくありません。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', (int) $values['application_id'])
            ->where('target_year', $targetYear)
            ->update(['status' => $status]);

        return redirect()
            ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
            ->with('status', '年調対象者の状態を更新しました。');
    }
    public function deleteTarget(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'application_id' => ['required', 'integer'],
            'target_year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $targetYear = (int) $values['target_year'];
        if (!Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications')) {
            return redirect()
                ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
                ->with('status', '年末調整申請テーブルが見つかりません。');
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', (int) $values['application_id'])
            ->where('target_year', $targetYear)
            ->first(['application_id', 'staff_id', 'status']);

        if (!$row) {
            return redirect()
                ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
                ->with('status', '削除対象が見つかりません。');
        }

        $status = trim((string) ($row->status ?? 'draft'));
        if ($status !== '' && $status !== 'draft') {
            return redirect()
                ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
                ->with('status', '提出済以降の対象者は削除できません。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', (int) $row->application_id)
            ->delete();

        return redirect()
            ->route('admin.work.year_end_adjustments', ['target_year' => $targetYear])
            ->with('status', "{$targetYear}年の対象者からスタッフID {$row->staff_id} を削除しました。");
    }

    public function calculateSingle(Request $request, int $applicationId): RedirectResponse
    {
        [$application, $staffId, $targetYear] = $this->yearEndApplicationContext($applicationId);

        $nenTyo = $this->findOrCreateNenTyoRow($application, $staffId, $targetYear);
        if ((int) ($nenTyo->edit_lock ?? 0) === 1) {
            return redirect()
                ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
                ->with('status', '処理済のため再計算できません。');
        }

        $payload = $this->calculateYearEndPayload($nenTyo, $staffId, $targetYear);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', (int) $nenTyo->nen_tyo_no)
            ->update($payload);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->update(['nen_tyo_no' => (int) $nenTyo->nen_tyo_no]);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '年末調整を1件再計算しました。');
    }

    /**
     * @return array{0:object,1:string,2:int}
     */
    private function yearEndApplicationContext(int $applicationId): array
    {
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications'), 404);
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('mx_nen_tyo'), 404);

        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();

        abort_unless($application, 404);

        $staffId = trim((string) ($application->staff_id ?? ''));
        $targetYear = (int) ($application->target_year ?? date('Y'));
        abort_if($staffId === '' || $targetYear < 2000 || $targetYear > 2100, 404);

        return [$application, $staffId, $targetYear];
    }

    private function findOrCreateNenTyoRow(object $application, string $staffId, int $targetYear): object
    {
        $nenTyoNo = (int) ($application->nen_tyo_no ?? 0);
        $query = DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo');

        if ($nenTyoNo > 0) {
            $row = $query->where('nen_tyo_no', $nenTyoNo)->first();
            if ($row) {
                return $row;
            }
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('staff_id', $staffId)
            ->whereYear('year_end', $targetYear)
            ->orderBy('nen_tyo_no')
            ->first();

        if ($row) {
            return $row;
        }

        $newNo = (int) DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->insertGetId([
                'staff_id' => $staffId,
                'fuyo_deduction_report' => 1,
                'year_end' => sprintf('%04d-12-31', $targetYear),
                'nen_tyo_false' => 0,
                'edit_lock' => 0,
            ], 'nen_tyo_no');

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $newNo)
            ->first();
    }

    /** @return array<string, mixed> */
    private function calculateYearEndPayload(object $nenTyo, string $staffId, int $targetYear): array
    {
        $payroll = $this->yearEndPayrollTotals($staffId, $targetYear);
        $dependents = $this->yearEndDependentTotals($staffId, $targetYear);
        $retired = $this->isYearEndRetired($staffId);

        $zenShotoku = $this->money($nenTyo->zen_shotoku ?? 0);
        $zenSyaho = $this->money($nenTyo->zen_syaho_kou ?? 0);
        $zenKyuyoTax = $this->money($nenTyo->zen_kyuyo_tax ?? 0);
        $zenBonusTax = $this->money($nenTyo->zen_bonus_tax ?? 0);

        $kyuyoTeateSum = $payroll['salary_taxable'] + $zenShotoku;
        $bonusEtc = $payroll['bonus_taxable'];
        $bonusKyuyoSum = $kyuyoTeateSum + $bonusEtc;
        $kyuyoTeateTax = $payroll['salary_income_tax'] + $zenKyuyoTax;
        $bonusTax = $payroll['bonus_income_tax'] + $zenBonusTax;
        $siharaiShotoku = $kyuyoTeateTax + $bonusTax;
        $kyuSyahoFeeKou = $payroll['syaho_sum'] + $zenSyaho;

        $kisoKoujyo = $this->money($nenTyo->kiso_koujyo ?? 0);
        if ($kisoKoujyo <= 0) {
            $kisoKoujyo = 480000;
        }

        $haiguTokuDeduction = $this->money($nenTyo->haigu_toku_deduction ?? 0);
        $shinSyahoFeeKou = $this->money($nenTyo->shin_syaho_fee_kou ?? 0);
        $shunKigyouFeeKou = $this->money($nenTyo->shun_kigyou_fee_kou ?? 0);
        $seimeiFeeKou = $this->money($nenTyo->seimei_fee_kou ?? 0);
        $jishunFeeKou = $this->money($nenTyo->jishun_fee_kou ?? 0);
        $tyoseiKoujyo = $this->money($nenTyo->tyosei_koujyo ?? 0);
        $jyuKariKou = $this->money($nenTyo->jyu_kari_kou ?? 0);

        $deductionSum = $dependents['deduction_sum'];
        $shotokuDeduction = $this->salaryIncomeAfterDeduction($bonusKyuyoSum, $targetYear);
        $shotokuDeductionSum = $kyuSyahoFeeKou
            + $shinSyahoFeeKou
            + $shunKigyouFeeKou
            + $seimeiFeeKou
            + $jishunFeeKou
            + $deductionSum
            + $haiguTokuDeduction
            + $kisoKoujyo;

        $saKazeiShotoku = max(0, (int) floor(($shotokuDeduction - $tyoseiKoujyo - $shotokuDeductionSum) / 1000) * 1000);
        $sanshutuShotoku = $this->calculatedIncomeTax($saKazeiShotoku);
        $nentyoShotokuAmo = max(0, $sanshutuShotoku - $jyuKariKou);
        $nentyoNenTax = (int) floor(($nentyoShotokuAmo * 1.021) / 100) * 100;
        $saExcess = $nentyoNenTax - $siharaiShotoku;

        $fuyoReport = (int) ($nenTyo->fuyo_deduction_report ?? 1) === 1;
        $taxAmount = trim((string) ($nenTyo->tax_amount ?? ''));
        if ($taxAmount === '乙欄') {
            $kisoKoujyo = 0;
            $deductionSum = 0;
            $kyuSyahoFeeKou = 0;
            $shotokuDeductionSum = 0;
            $saKazeiShotoku = 0;
            $sanshutuShotoku = 0;
            $nentyoShotokuAmo = 0;
            $nentyoNenTax = $siharaiShotoku;
            $saExcess = 0;
        } elseif (!$fuyoReport || $retired) {
            $kisoKoujyo = 0;
            $deductionSum = 0;
            $shotokuDeduction = 0;
            $shotokuDeductionSum = 0;
            $saKazeiShotoku = 0;
            $sanshutuShotoku = 0;
            $nentyoShotokuAmo = 0;
            $nentyoNenTax = 0;
            $saExcess = $retired ? 0 : -$siharaiShotoku;
        }

        return [
            'kyuyo_teate_sum' => $kyuyoTeateSum,
            'bonus_etc' => $bonusEtc,
            'bonus_kyuyo_sum' => $bonusKyuyoSum,
            'kyuyo_teate_tax' => $kyuyoTeateTax,
            'bonus_tax' => $bonusTax,
            'siharai_shotoku' => $siharaiShotoku,
            'kyu_syaho_fee_kou' => $kyuSyahoFeeKou,
            'toku_fu' => $dependents['toku_fu'],
            'rou_dou' => $dependents['rou_dou'],
            'rou_dou_gai' => $dependents['rou_dou_gai'],
            'fuyo_ta' => $dependents['fuyo_ta'],
            'shougai_dou_toku' => $dependents['shougai_dou_toku'],
            'shougai_toku' => $dependents['shougai_toku'],
            'shougai_ta' => $dependents['shougai_ta'],
            'dependent_under_16' => $dependents['dependent_under_16'],
            'deduction_sum' => $deductionSum,
            'kiso_koujyo' => $kisoKoujyo,
            'shotoku_deduction' => $shotokuDeduction,
            'shotoku_deduction_sum' => $shotokuDeductionSum,
            'sa_kazei_shotoku' => $saKazeiShotoku,
            'sanshutu_shotoku' => $sanshutuShotoku,
            'nentyo_shotoku_amo' => $nentyoShotokuAmo,
            'nentyo_nen_tax' => $nentyoNenTax,
            'sa_excess' => $saExcess,
        ];
    }

    /** @return array{salary_taxable:float,bonus_taxable:float,salary_income_tax:float,bonus_income_tax:float,syaho_sum:float} */
    private function yearEndPayrollTotals(string $staffId, int $targetYear): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_staff_id', $staffId)
            ->whereYear('supply_month', $targetYear)
            ->get(['bonus', 'taxation_sum', 'income_tax', 'syaho_sum']);

        $totals = [
            'salary_taxable' => 0.0,
            'bonus_taxable' => 0.0,
            'salary_income_tax' => 0.0,
            'bonus_income_tax' => 0.0,
            'syaho_sum' => 0.0,
        ];

        foreach ($rows as $row) {
            $isBonus = (int) ($row->bonus ?? 0) === 1;
            $taxable = $this->money($row->taxation_sum ?? 0);
            $incomeTax = $this->money($row->income_tax ?? 0);
            if ($isBonus) {
                $totals['bonus_taxable'] += $taxable;
                $totals['bonus_income_tax'] += $incomeTax;
            } else {
                $totals['salary_taxable'] += $taxable;
                $totals['salary_income_tax'] += $incomeTax;
            }
            $totals['syaho_sum'] += $this->money($row->syaho_sum ?? 0);
        }

        return $totals;
    }

    /** @return array<string, int|float> */
    private function yearEndDependentTotals(string $staffId, int $targetYear): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->get();

        $totals = [
            'toku_fu' => 0,
            'rou_dou' => 0,
            'rou_dou_gai' => 0,
            'fuyo_ta' => 0,
            'shougai_dou_toku' => 0,
            'shougai_toku' => 0,
            'shougai_ta' => 0,
            'dependent_under_16' => 0,
            'deduction_sum' => 0.0,
        ];

        foreach ($rows as $row) {
            $target = (int) ($row->deduction_target ?? 0) === 1;
            $relationship = trim((string) ($row->fuyo_relationship ?? ''));
            $age = $this->ageAtYearEnd($row->fuyo_birthday ?? null, $targetYear);
            $failure = trim((string) ($row->failure_judgment ?? ''));
            $kyojyu = trim((string) ($row->kyojyu ?? ''));

            if ($target && $age < 16) {
                $totals['dependent_under_16']++;
            }

            if (!$target) {
                continue;
            }

            if (($relationship === '夫' || $relationship === '妻')) {
                $totals['deduction_sum'] += 380000;
                continue;
            }

            if ($age >= 19 && $age < 23) {
                $totals['toku_fu']++;
                $totals['deduction_sum'] += 630000;
            } elseif ($age >= 70) {
                if ($kyojyu === '同居') {
                    $totals['rou_dou']++;
                    $totals['deduction_sum'] += 580000;
                } else {
                    $totals['rou_dou_gai']++;
                    $totals['deduction_sum'] += 480000;
                }
            } elseif (($age >= 16 && $age <= 18) || ($age >= 23 && $age < 70)) {
                $totals['fuyo_ta']++;
                $totals['deduction_sum'] += 380000;
            }

            if (in_array($failure, ['A1', 'A2', '1級', '2級'], true)) {
                if ($kyojyu === '同居') {
                    $totals['shougai_dou_toku']++;
                    $totals['deduction_sum'] += 750000;
                } else {
                    $totals['shougai_toku']++;
                    $totals['deduction_sum'] += 400000;
                }
            } elseif ($failure !== '') {
                $totals['shougai_ta']++;
                $totals['deduction_sum'] += 270000;
            }
        }

        return $totals;
    }

    private function salaryIncomeAfterDeduction(float $income, int $targetYear): int
    {
        if ($income <= 0) {
            return 0;
        }

        if ($targetYear >= 2025) {
            if ($income <= 650999) {
                return 0;
            }
            if ($income <= 1899999) {
                return (int) ($income - 650000);
            }
            if ($income <= 3599999) {
                return (int) floor($income * 0.7 - 80000);
            }
            if ($income <= 6599999) {
                return (int) floor($income * 0.8 - 440000);
            }
            if ($income <= 8499999) {
                return (int) floor($income * 0.9 - 1100000);
            }
            return (int) ($income - 1950000);
        }

        if ($income <= 550999) {
            return 0;
        }
        if ($income <= 1618999) {
            return (int) ($income - 550000);
        }
        if ($income <= 1619999) {
            return (int) floor($income * 0.6 + 97600);
        }
        if ($income <= 1621999) {
            return (int) floor($income * 0.6 + 98000);
        }
        if ($income <= 1623999) {
            return (int) floor($income * 0.6 + 98800);
        }
        if ($income <= 1627999) {
            return (int) floor($income * 0.6 + 99600);
        }
        if ($income <= 1799999) {
            return (int) floor($income * 0.6 + 100000);
        }
        if ($income <= 3599999) {
            return (int) floor($income * 0.7 - 80000);
        }
        if ($income <= 6599999) {
            return (int) floor($income * 0.8 - 440000);
        }
        if ($income <= 8499999) {
            return (int) floor($income * 0.9 - 1100000);
        }
        return (int) ($income - 1950000);
    }

    private function calculatedIncomeTax(float $taxable): int
    {
        if ($taxable <= 0) {
            return 0;
        }
        if ($taxable <= 1950000) {
            return (int) floor($taxable * 0.05);
        }
        if ($taxable <= 3300000) {
            return (int) floor($taxable * 0.1 - 97500);
        }
        if ($taxable <= 6950000) {
            return (int) floor($taxable * 0.2 - 427500);
        }
        if ($taxable <= 9000000) {
            return (int) floor($taxable * 0.23 - 636000);
        }
        if ($taxable <= 18000000) {
            return (int) floor($taxable * 0.33 - 1536000);
        }
        if ($taxable <= 40000000) {
            return (int) floor($taxable * 0.4 - 2796000);
        }
        return (int) floor($taxable * 0.45 - 4796000);
    }

    private function ageAtYearEnd(mixed $birthday, int $targetYear): int
    {
        if ($birthday === null || $birthday === '') {
            return 0;
        }

        try {
            $birth = new \DateTimeImmutable((string) $birthday);
            $yearEnd = new \DateTimeImmutable(sprintf('%04d-12-31', $targetYear));
            return (int) $birth->diff($yearEnd)->y;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function isYearEndRetired(string $staffId): bool
    {
        if (!Schema::connection('sqlsrv')->hasTable('mx_staffs')) {
            return false;
        }

        $taiDate = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $staffId)
            ->value('tai_date');

        return trim((string) ($taiDate ?? '')) !== '';
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }
    public function createHoken(Request $request, int $applicationId): RedirectResponse
    {
        [$application, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);

        $values = $this->validateHokenValues($request);
        $payload = $this->hokenPayload($values);
        $payload['insurance_staff_no'] = $staffId;
        $payload['insurance_year'] = sprintf('%04d-12-31', $targetYear);
        $payload['checked_flag'] = 0;

        $hokenNo = (int) DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->insertGetId($payload, 'hoken_no');

        if ($hokenNo > 0 && $request->hasFile('certificate_file')) {
            $this->saveHokenCertificate($request, $hokenNo, $staffId, $targetYear);
        }

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $application->application_id])
            ->with('status', '保険情報を追加しました。');
    }

    public function updateHoken(Request $request, int $applicationId, int $hokenNo): RedirectResponse
    {
        [$application, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);
        $this->hokenRowOrFail($hokenNo, $staffId, $targetYear);

        $values = $this->validateHokenValues($request);
        $payload = $this->hokenPayload($values);

        if ($request->hasFile('certificate_file')) {
            $payload += $this->storeHokenCertificate($request, $hokenNo, $staffId, $targetYear);
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('hoken_no', $hokenNo)
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear)
            ->update($payload);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $application->application_id])
            ->with('status', '保険情報を保存しました。');
    }

    public function deleteHoken(Request $request, int $applicationId, int $hokenNo): RedirectResponse
    {
        [$application, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);
        $row = $this->hokenRowOrFail($hokenNo, $staffId, $targetYear);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('hoken_no', $hokenNo)
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear)
            ->delete();

        $path = trim((string) ($row->certificate_file_path ?? ''));
        if ($path !== '' && preg_match('/^https?:\/\//', $path) !== 1) {
            $fullPath = public_path(ltrim($path, '/'));
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $application->application_id])
            ->with('status', '保険情報を削除しました。');
    }

    /**
     * @return array{0:object,1:string,2:int}
     */
    private function hokenApplicationContext(int $applicationId): array
    {
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications'), 404);
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('mx_hoken'), 404);

        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();

        abort_unless($application, 404);

        $staffId = trim((string) ($application->staff_id ?? ''));
        $targetYear = (int) ($application->target_year ?? date('Y'));
        abort_if($staffId === '' || $targetYear < 2000 || $targetYear > 2100, 404);

        return [$application, $staffId, $targetYear];
    }

    private function hokenRowOrFail(int $hokenNo, string $staffId, int $targetYear): object
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('hoken_no', $hokenNo)
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear)
            ->first();

        abort_unless($row, 404);

        return $row;
    }

    /** @return array<string, mixed> */
    private function validateHokenValues(Request $request): array
    {
        return $request->validate([
            'insurance_company' => ['nullable', 'string', 'max:50'],
            'insurance_type' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:20'],
            'applied_system' => ['nullable', 'string', 'max:20'],
            'declared_amount' => ['nullable', 'numeric'],
            'insurance_period' => ['nullable', 'string', 'max:10'],
            'policy_holder_name' => ['nullable', 'string', 'max:20'],
            'beneficiary_name' => ['nullable', 'string', 'max:20'],
            'beneficiary_relationship' => ['nullable', 'string', 'max:10'],
            'pension_payment_start_date' => ['nullable', 'date'],
            'year_end_insurance_note' => ['nullable', 'string'],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
            'certificate_file.max' => '証明書は10MB以内で添付してください。',
        ]);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function hokenPayload(array $values): array
    {
        $date = trim((string) ($values['pension_payment_start_date'] ?? ''));

        return [
            'insurance_company' => $this->nullableText($values['insurance_company'] ?? null),
            'insurance_type' => $this->nullableText($values['insurance_type'] ?? null),
            'category' => $this->nullableText($values['category'] ?? null),
            'applied_system' => $this->nullableText($values['applied_system'] ?? null),
            'declared_amount' => $this->nullableMoney($values['declared_amount'] ?? null),
            'insurance_period' => $this->nullableText($values['insurance_period'] ?? null),
            'policy_holder_name' => $this->nullableText($values['policy_holder_name'] ?? null),
            'beneficiary_name' => $this->nullableText($values['beneficiary_name'] ?? null),
            'beneficiary_relationship' => $this->nullableText($values['beneficiary_relationship'] ?? null),
            'pension_payment_start_date' => $date !== '' ? date('Y-m-d', strtotime($date)) : null,
            'year_end_insurance_note' => $this->nullableText($values['year_end_insurance_note'] ?? null),
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : null;
    }

    private function nullableMoney(mixed $value): mixed
    {
        $text = str_replace(',', '', trim((string) ($value ?? '')));
        return $text !== '' ? $text : null;
    }

    private function saveHokenCertificate(Request $request, int $hokenNo, string $staffId, int $targetYear): void
    {
        $payload = $this->storeHokenCertificate($request, $hokenNo, $staffId, $targetYear);
        if ($payload === []) {
            return;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('hoken_no', $hokenNo)
            ->update($payload);
    }

    /** @return array<string, mixed> */
    private function storeHokenCertificate(Request $request, int $hokenNo, string $staffId, int $targetYear): array
    {
        $file = $request->file('certificate_file');
        if (!$file || !$file->isValid()) {
            return [];
        }

        $directory = public_path("uploads/year_end/{$targetYear}/{$staffId}/insurance");
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());
        $baseName = 'hoken_' . $hokenNo . '_' . date('YmdHis');

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            $fileName = $baseName . '.pdf';
            $file->move($directory, $fileName);
        } else {
            $fileName = $baseName . '.jpg';
            $targetPath = $directory . DIRECTORY_SEPARATOR . $fileName;
            if (!$this->storeCompressedHokenImage($file->getRealPath(), $targetPath)) {
                $fallbackExtension = in_array($extension, ['jpg', 'jpeg', 'png'], true) ? $extension : 'jpg';
                $fileName = $baseName . '.' . $fallbackExtension;
                $file->move($directory, $fileName);
            }
        }

        return [
            'certificate_file_path' => "uploads/year_end/{$targetYear}/{$staffId}/insurance/{$fileName}",
            'certificate_original_name' => function_exists('mb_substr') ? mb_substr($file->getClientOriginalName(), 0, 255) : substr($file->getClientOriginalName(), 0, 255),
            'certificate_uploaded_at' => now(),
        ];
    }

    private function storeCompressedHokenImage(string $sourcePath, string $targetPath): bool
    {
        if (!function_exists('imagejpeg') || !function_exists('imagecreatetruecolor')) {
            return false;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return false;
        }

        [$width, $height, $type] = $info;
        if ($width <= 0 || $height <= 0) {
            return false;
        }

        $source = null;
        if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
            $source = @imagecreatefromjpeg($sourcePath);
        } elseif ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $source = @imagecreatefrompng($sourcePath);
        }

        if (!$source) {
            return false;
        }

        $maxSide = 1600;
        $scale = min(1, $maxSide / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        if (!$canvas) {
            imagedestroy($source);
            return false;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = imagejpeg($canvas, $targetPath, 78);
        imagedestroy($canvas);
        imagedestroy($source);

        return $saved;
    }
    private function yearEndPdfTemplatePath(int $targetYear, string $templateKey): string
    {
        $fileName = $templateKey . '.pdf';
        $candidates = [
            storage_path("app/templates/year_end/{$targetYear}-{$fileName}"),
            storage_path("app/templates/year_end/2025-{$fileName}"),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        abort(404, '年末調整帳票テンプレートが見つかりません。');
    }
    private function hokenPdfTemplatePath(int $targetYear): string
    {
        $candidates = [
            storage_path("app/templates/year_end/{$targetYear}-hoken_koujyo_shinkoku.pdf"),
            storage_path('app/templates/year_end/2025-hoken_koujyo_shinkoku.pdf'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        abort(404, '保険料控除申告書テンプレートが見つかりません。');
    }

    private function fillPdfRect(Fpdi $pdf, float $x, float $y, float $w, float $h, array $rgb): void
    {
        $pdf->SetFillColor((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        $pdf->Rect($x, $y, $w, $h, 'F');
    }

    private function writePdfTextRightSized(Fpdi $pdf, float $rightX, float $y, string $text, int $fontSize, int $maxWidth = 0): void
    {
        $pdf->SetFont('kozminproregular', '', $fontSize);
        $this->writePdfTextRight($pdf, $rightX, $y, $text, $maxWidth);
        $pdf->SetFont('kozminproregular', '', 7);
    }

    private function writePdfDigitsSized(Fpdi $pdf, float $x, float $y, string $text, float $pitch, int $fontSize): void
    {
        $pdf->SetFont('kozminproregular', '', $fontSize);
        $this->writePdfDigits($pdf, $x, $y, $text, $pitch);
        $pdf->SetFont('kozminproregular', '', 7);
    }

    private function writePdfWrappedTextSized(Fpdi $pdf, float $x, float $y, string $text, int $maxWidth, float $lineHeight, int $maxLines, int $fontSize): void
    {
        $pdf->SetFont('kozminproregular', '', $fontSize);
        $this->writePdfWrappedText($pdf, $x, $y, $text, $maxWidth, $lineHeight, $maxLines);
        $pdf->SetFont('kozminproregular', '', 7);
    }
    private function writePdfTextSized(Fpdi $pdf, float $x, float $y, string $text, int $fontSize, int $maxWidth = 0): void
    {
        $pdf->SetFont('kozminproregular', '', $fontSize);
        $this->writePdfText($pdf, $x, $y, $text, $maxWidth);
        $pdf->SetFont('kozminproregular', '', 7);
    }
    private function writePdfWrappedText(Fpdi $pdf, float $x, float $y, string $text, int $maxWidth, float $lineHeight = 4.0, int $maxLines = 2): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($maxWidth <= 0 || !function_exists('mb_strimwidth')) {
            $pdf->Text($x, $y, $text);
            return;
        }

        $remaining = $text;
        for ($line = 0; $line < $maxLines && $remaining !== ''; $line++) {
            $suffix = $line === $maxLines - 1 ? '...' : '';
            $part = mb_strimwidth($remaining, 0, $maxWidth, $suffix, 'UTF-8');
            $pdf->Text($x, $y + ($line * $lineHeight), $part);
            $remaining = mb_substr($remaining, mb_strlen(str_replace($suffix, '', $part), 'UTF-8'), null, 'UTF-8');
        }
    }
    private function writePdfText(Fpdi $pdf, float $x, float $y, string $text, int $maxWidth = 0): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($maxWidth > 0 && function_exists('mb_strimwidth')) {
            $text = mb_strimwidth($text, 0, $maxWidth, '...', 'UTF-8');
        }

        $pdf->Text($x, $y, $text);
    }

    private function activeStaffRows(): Collection
    {
        if (!Schema::connection('sqlsrv')->hasTable('mx_staffs')) {
            return collect();
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id', 'staff_name', 'tai_date', 'staff_division', 'employment'])
            ->where(function ($query): void {
                $query->whereNull('tai_date')
                    ->orWhere(DB::raw("LTRIM(RTRIM(CAST(tai_date AS nvarchar(50))))"), '=', '')
                    ->orWhereDate('tai_date', '>=', date('Y-m-d'));
            })
            ->where(function ($query): void {
                $query->whereNull('staff_division')
                    ->orWhere(DB::raw("CAST(staff_division AS nvarchar(100))"), 'not like', '%業務委託%');
            })
            ->orderBy('staff_id')
            ->get();
    }

    /** @return array<string, string> */
    private function formatApplication(object $application): array
    {
        $status = trim((string) ($application->status ?? ''));
        if ($status === '') {
            $status = 'draft';
        }

        return [
            'application_id' => (string) ($application->application_id ?? ''),
            'staff_id' => trim((string) ($application->staff_id ?? '')),
            'target_year' => (string) ($application->target_year ?? ''),
            'nen_tyo_no' => (string) ($application->nen_tyo_no ?? ''),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'year_end_adjustment' => $this->bitLabel($application->year_end_adjustment ?? null),
            'personal_info_changed' => $this->bitLabel($application->personal_info_changed ?? null),
            'dependents_changed' => $this->bitLabel($application->dependents_changed ?? null),
            'insurance_deduction_changed' => $this->bitLabel($application->insurance_deduction_changed ?? null),
            'housing_loan_changed' => $this->bitLabel($application->housing_loan_changed ?? null),
            'previous_job_withholding_changed' => $this->bitLabel($application->previous_job_withholding_changed ?? null),
            'special_collection_requested' => $this->bitLabel($application->special_collection_requested ?? null),
            'submitted_at' => $this->dateLabel($application->submitted_at ?? null),
            'confirmed_at' => $this->dateLabel($application->confirmed_at ?? null),
            'reflected_at' => $this->dateLabel($application->reflected_at ?? null),
        ];
    }

    /** @return array<string, string> */
    private function staffDetail(string $staffId): array
    {
        if ($staffId === '' || !Schema::connection('sqlsrv')->hasTable('mx_staffs')) {
            return [];
        }

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $staffId)
            ->first();

        if (!$row) {
            return [];
        }

        $detail = $this->objectToArray($row);
        $section = trim((string) ($detail['section'] ?? ''));
        $storeCode = $section !== '' && ctype_digit($section) ? str_pad($section, 3, '0', STR_PAD_LEFT) : $section;
        if ($storeCode !== '' && Schema::connection('sqlsrv')->hasTable('mx_stores') && Schema::connection('sqlsrv')->hasTable('mx_companies')) {
            $company = DB::connection('sqlsrv')
                ->table('dbo.mx_stores as s')
                ->leftJoin('dbo.mx_companies as c', 's.company_id', '=', 'c.company_id')
                ->where('s.store_code', $storeCode)
                ->first([
                    'c.company_id',
                    'c.company_name',
                    'c.company_address',
                    'c.corporate_number',
                ]);

            if ($company) {
                $detail['company_id'] = trim((string) ($company->company_id ?? ''));
                $detail['company_name'] = trim((string) ($company->company_name ?? ''));
                $detail['company_address'] = trim((string) ($company->company_address ?? ''));
                $detail['corporate_number'] = trim((string) ($company->corporate_number ?? ''));
            }
        }

        return $detail;
    }

    /** @return array<string, string> */
    private function nenTyoDetail(object $application, string $staffId, int $targetYear): array
    {
        if (!Schema::connection('sqlsrv_payroll')->hasTable('mx_nen_tyo')) {
            return [];
        }

        $query = DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo');
        $nenTyoNo = (int) ($application->nen_tyo_no ?? 0);
        if ($nenTyoNo > 0) {
            $query->where('nen_tyo_no', $nenTyoNo);
        } else {
            $query->where('staff_id', $staffId)->whereYear('year_end', $targetYear);
        }

        $row = $query->first();
        return $row ? $this->objectToArray($row) : [];
    }

    /** @return list<array<string, string>> */
    private function fuyoRows(string $staffId, int $targetYear): array
    {
        if ($staffId === '' || !Schema::connection('sqlsrv_payroll')->hasTable('mx_fuyo')) {
            return [];
        }

        $columns = Schema::connection('sqlsrv_payroll')->getColumnListing('mx_fuyo');
        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId);

        if (in_array('registration_date', $columns, true)) {
            $query->whereYear('registration_date', $targetYear)->orderBy('registration_date');
        }

        return $query->orderBy('fuyo_no')->get()->map(fn($row): array => $this->objectToArray($row))->all();
    }

    /** @return list<array<string, string>> */
    private function hokenRows(string $staffId, int $targetYear): array
    {
        if ($staffId === '' || !Schema::connection('sqlsrv_payroll')->hasTable('mx_hoken')) {
            return [];
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear)
            ->orderBy('hoken_no')
            ->get()
            ->map(fn($row): array => $this->objectToArray($row))
            ->all();
    }

    /** @return array<string, array<string, string>> */
    private function nenTyoSummaryGroups(): array
    {
        return [
            '本人関係' => [
                'minor' => '未成年者',
                'foreigner' => '外国人',
                'death_retire' => '死亡退職',
                'disaster' => '災害者',
                'tax_amount' => '税額欄',
                'hon_shougai_toku' => '本人 特別障害者',
                'hon_shougai_ta' => '本人 一般障害者',
                'ippan_kafu' => '一般寡婦',
                'toku_kafu' => '特別寡婦',
                'kafu' => '寡婦',
                'student' => '勤労学生',
                'halfway' => '中途',
                'futu_tyoushu' => '普通徴収',
            ],
            '配偶者関係' => [
                'haigu_umu' => '配偶者有無',
                'haigu_kou_umu' => '配偶者控除有無',
                'haigu_bunrui' => '配偶者控除分類',
                'haigu_shotoku' => '配偶者所得',
                'haigu_shotoku_sum' => '配偶者合計所得',
                'haigu_toku_deduction' => '配偶者特別控除',
                'haigu_toku_deduction_amo' => '配偶者特別控除額',
            ],
            '扶養関係' => [
                'toku_fu' => '特定扶養親族',
                'rou_dou' => '老人 同居',
                'rou_dou_gai' => '老人 同居以外',
                'fuyo_ta' => 'その他扶養親族',
                'shougai_dou_toku' => '同居特別障害者',
                'shougai_toku' => '特別障害者',
                'shougai_ta' => 'その他障害者',
                'dependent_under_16' => '16歳未満扶養親族',
                'tyosei_koujyo_select' => '所得金額調整控除',
            ],
            '給与・税額' => [
                'kyuyo_teate_sum' => '給与・手当等',
                'bonus_etc' => '賞与等',
                'bonus_kyuyo_sum' => '計',
                'shotoku_deduction' => '所得控除後',
                'sa_kazei_shotoku' => '差引課税所得',
                'kyuyo_teate_tax' => '給与手当税額',
                'bonus_tax' => '賞与税額',
                'nentyo_shotoku_amo' => '年調所得税額',
                'nentyo_nen_tax' => '年調年税額',
                'sa_excess' => '差引過不足',
            ],
            '社保・控除' => [
                'kyu_syaho_fee_kou' => '給与社会保険料控除',
                'shin_syaho_fee_kou' => '申告社会保険料控除',
                'shun_kigyou_fee_kou' => '小規模共済掛金控除',
                'seimei_fee_kou' => '生命保険料控除',
                'jishun_fee_kou' => '地震保険料控除',
                'deduction_sum' => '控除額合計',
                'kiso_bunrui' => '基礎控除分類',
                'kiso_koujyo' => '基礎控除',
                'tyosei_koujyo' => '調整控除',
                'shotoku_deduction_sum' => '所得控除合計',
            ],
            '前職情報' => [
                'zen_shotoku' => '前職所得',
                'zen_syaho_kou' => '前職社会保険',
                'zen_kyuyo_tax' => '前職給与税',
                'zen_bonus_tax' => '前職賞与税',
                'zen_tai_date' => '前職退職日',
                'zen_syamei' => '前職会社名',
                'zen_add' => '前職住所',
            ],
            '住宅・定額減税' => [
                'jyu_kari_kou' => '住宅借入控除',
                'jyu_kojyo_kubun' => '住宅控除区分',
                'toku_kubun' => '特定取得区分',
                'koujyo_kubun_no' => '控除区分番号',
                'teigakugenzai_max' => '定額減税上限',
                'teigakugenzai_sum' => '定額減税額',
            ],
            '処理' => [
                'nen_tyo_no' => '年調No',
                'year_end' => '対象年',
                'fuyo_deduction_report' => '扶養控除申告',
                'nen_tyo_false' => '年調しない',
            ],
        ];
    }
    /** @return array<string, string> */
    private function nenTyoLabels(): array
    {
        $labels = [];
        foreach ($this->nenTyoSummaryGroups() as $items) {
            $labels += $items;
        }

        return $labels + [
            'nen_tyo_no' => '年調No',
            'staff_id' => 'スタッフID',
            'fuyo_deduction_report' => '扶養控除申告書',
            'year_end' => '対象年',
            'zen_add' => '前職追加',
            'haigu_shotoku' => '配偶者所得',
            'shun_kigyou_fee_kou' => '小規模企業共済等掛金控除',
            'haigu_toku_deduction_amo' => '配偶者特別控除額',
            'kyu_fee_siharai' => '旧保険料支払',
            'kigyou_kyousai' => '企業共済',
            'koku_nenkin' => '国民年金',
            'minor' => '未成年',
            'foreigner' => '外国人',
            'disaster' => '災害者',
            'hon_shougai_toku' => '本人特別障害',
            'hon_shougai_ta' => '本人その他障害',
            'ippan_kafu' => '一般寡婦',
            'toku_kafu' => '特別寡婦',
            'kafu' => '寡婦',
            'student' => '勤労学生',
            'halfway' => '中途',
            'toku_fu' => '特定扶養',
            'rou_dou' => '老人扶養同居',
            'rou_dou_gai' => '老人扶養同居外',
            'shougai_dou_toku' => '同居特別障害',
            'shougai_toku' => '特別障害',
            'shougai_ta' => 'その他障害',
            'jyu_kojyo_kubun' => '住宅控除区分',
            'toku_kubun' => '特別区分',
            'koujyo_kubun_no' => '控除区分No',
            'tyosei_koujyo_select' => '調整控除選択',
        ];
    }
    /** @return array<string, int> */
    private function nenTyoNoMap(int $targetYear): array
    {
        if (!Schema::connection('sqlsrv_payroll')->hasTable('mx_nen_tyo')) {
            return [];
        }

        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->select(['nen_tyo_no', 'staff_id'])
            ->whereYear('year_end', $targetYear)
            ->orderBy('staff_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $staffId = trim((string) ($row->staff_id ?? ''));
            $nenTyoNo = (int) ($row->nen_tyo_no ?? 0);
            if ($staffId !== '' && $nenTyoNo > 0 && !array_key_exists($staffId, $map)) {
                $map[$staffId] = $nenTyoNo;
            }
        }

        return $map;
    }

    /** @return array<string, int> */
    private function fuyoCounts(int $targetYear): array
    {
        if (!Schema::connection('sqlsrv_payroll')->hasTable('mx_fuyo')) {
            return [];
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->select('staff_id', DB::raw('COUNT(*) as row_count'))
            ->whereYear('registration_date', $targetYear)
            ->groupBy('staff_id')
            ->get()
            ->mapWithKeys(static function ($row): array {
                return [trim((string) ($row->staff_id ?? '')) => (int) ($row->row_count ?? 0)];
            })
            ->all();
    }

    /** @return array<string, int> */
    private function hokenCounts(int $targetYear): array
    {
        if (!Schema::connection('sqlsrv_payroll')->hasTable('mx_hoken')) {
            return [];
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->select('insurance_staff_no', DB::raw('COUNT(*) as row_count'))
            ->whereYear('insurance_year', $targetYear)
            ->groupBy('insurance_staff_no')
            ->get()
            ->mapWithKeys(static function ($row): array {
                return [trim((string) ($row->insurance_staff_no ?? '')) => (int) ($row->row_count ?? 0)];
            })
            ->all();
    }

    /**
     * @param array<int, mixed> $staffIds
     * @return array<string, array{staff_name:string,nyu_date:string,tai_date:string}>
     */
    private function staffListDetails(array $staffIds): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string) $value), $staffIds))));
        if ($staffIds === [] || !Schema::connection('sqlsrv')->hasTable('mx_staffs')) {
            return [];
        }

        $details = [];
        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereIn('staff_id', $staffIds)
            ->get(['staff_id', 'staff_name', 'nyu_date', 'tai_date']);

        foreach ($rows as $row) {
            $staffId = trim((string) ($row->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $details[$staffId] = [
                'staff_name' => trim((string) ($row->staff_name ?? '')),
                'nyu_date' => $this->valueLabel($row->nyu_date ?? '', 'nyu_date'),
                'tai_date' => $this->valueLabel($row->tai_date ?? '', 'tai_date'),
            ];
        }

        return $details;
    }

    /** @return array<string, string> */
    private function objectToArray(object $row): array
    {
        $values = [];
        foreach (get_object_vars($row) as $key => $value) {
            $values[$key] = $this->valueLabel($value, $key);
        }

        return $values;
    }

    private function valueLabel(mixed $value, string $key = ''): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y/m/d');
        }

        $text = trim((string) $value);
        if ($this->isDateOnlyColumn($key) && preg_match('/^\d{4}-\d{2}-\d{2}/', $text) === 1) {
            return str_replace('-', '/', substr($text, 0, 10));
        }

        if ($this->isDisplayNumber($text, $key)) {
            $number = (float) str_replace(',', '', $text);
            if (abs($number - round($number)) < 0.00001) {
                return number_format($number, 0);
            }

            return rtrim(rtrim(number_format($number, 4, '.', ','), '0'), '.');
        }

        return $text;
    }

    private function isDisplayNumber(string $text, string $key): bool
    {
        if ($text === '' || preg_match('/^-?(?:\d+(?:\.\d+)?|\.\d+)$/', $text) !== 1) {
            return false;
        }

        $key = strtolower($key);
        foreach (['id', 'no', 'code', 'year', 'date', 'birthday', 'birth', 'zip', 'tel', 'phone'] as $part) {
            if (str_contains($key, $part)) {
                return false;
            }
        }

        return true;
    }

    private function isDateOnlyColumn(string $key): bool
    {
        $key = strtolower($key);
        return str_ends_with($key, '_date')
            || str_contains($key, 'birthday')
            || str_contains($key, 'birth')
            || in_array($key, ['registration_date', 'nyu_date', 'tai_date'], true);
    }

    /** @return array<string, string> */
    private function statusOptions(): array
    {
        return [
            'draft' => '下書き',
            'submitted' => '提出済',
            'returned' => '差戻し',
            'confirmed' => '確認済',
            'reflected' => '反映済',
            'excluded' => '対象外',
            'retired' => '退職済',
        ];
    }

    private function statusLabel(string $status): string
    {
        return $this->statusOptions()[$status] ?? $status;
    }

    private function bitLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return ((int) $value) === 1 ? 'あり' : 'なし';
    }

    private function dateLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable((string) $value))->format('Y/m/d H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
