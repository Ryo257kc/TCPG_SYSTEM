<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\YearEndAdjustment\YearEndCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use setasign\Fpdi\Tcpdf\Fpdi;

class YearEndAdjustmentV2Controller extends Controller
{
    public function __construct(private readonly YearEndCalculationService $calculationService)
    {
    }

    private const FUYO_PDF_TEXT_COLOR = [255, 0, 0];
    private const HOKEN_PDF_TEXT_COLOR = [255, 0, 0];
    /** 保険料控除申告書：各欄が1ページに収まる行数（超過分は複製ページへ回す） */
    private const HOKEN_ROW_CAPACITY = [
        'general' => 4,
        'nursing' => 2,
        'pension' => 2,
        'earthquake' => 2,
        'old_long_term' => 2,
        'social_insurance' => 4,
    ];

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
            'fuyoRows' => $this->fuyoRows($staffId, $targetYear),
            'hokenRows' => $this->hokenRows($staffId, $targetYear),
        ]);
    }

    public function hokenPreview(int $applicationId)
    {
        [$application, $staffId, $targetYear] = $this->yearEndApplicationContext($applicationId);

        $templatePath = $this->yearEndPdfTemplatePath($targetYear, 'hoken_koujyo_shinkoku');
        $staff = $this->staffDetail($staffId);
        $nenTyo = $this->nenTyoDetail($application, $staffId, $targetYear);
        $grouped = $this->groupHokenRows($this->hokenRows($staffId, $targetYear));

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
        $pdf->setSourceFile($templatePath);

        // 1ページの各欄に収まる行数を超えた保険情報は、実務で保険料控除申告書を複数枚使うのに合わせて
        // 同じ様式のページを必要な枚数だけ複製し、超過分を後続ページへ回す（データを黙って切り捨てない）。
        $pageCount = $this->hokenPageCount($grouped);

        for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
            $templateId = $pdf->importPage(1);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            $this->writeHokenPreviewPage($pdf, $targetYear, $staff, $nenTyo, $grouped, $pageIndex, $pageCount);
        }

        $pdf->Output($outputPath, 'F');

        return response()->file($outputPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="hoken_koujyo_shinkoku_' . $targetYear . '_' . $staffId . '.pdf"',
        ]);
    }

    /**
     * mx_hoken.category の文字列でキーワード判定して区分する。
     * 「個人年金」と「個人型」、「年金」と「企業型年金／個人型年金」が紛らわしいため、
     * 小規模企業共済等掛金控除（機構／企業型／個人型／心身障害）を先に判定する。
     *
     * @param list<array<string, string>> $hokenRows
     * @return array<string, list<array<string, string>>>
     */
    private function groupHokenRows(array $hokenRows): array
    {
        $grouped = [
            'general' => [],
            'nursing' => [],
            'pension' => [],
            'earthquake' => [],
            'old_long_term' => [],
            'social_insurance' => [],
            'kikou' => [],
            'kigyo' => [],
            'kojin' => [],
            'shinshin' => [],
        ];

        foreach ($hokenRows as $row) {
            $category = trim((string) ($row['category'] ?? ''));

            if (str_contains($category, '旧長期')) {
                $grouped['old_long_term'][] = $row;
            } elseif (str_contains($category, '地震')) {
                $grouped['earthquake'][] = $row;
            } elseif (str_contains($category, '介護')) {
                $grouped['nursing'][] = $row;
            } elseif (str_contains($category, '社会保険')) {
                $grouped['social_insurance'][] = $row;
            } elseif (str_contains($category, '機構')) {
                $grouped['kikou'][] = $row;
            } elseif (str_contains($category, '企業型')) {
                $grouped['kigyo'][] = $row;
            } elseif (str_contains($category, '個人型')) {
                $grouped['kojin'][] = $row;
            } elseif (str_contains($category, '心身障害')) {
                $grouped['shinshin'][] = $row;
            } elseif (str_contains($category, '個人年金') || str_contains($category, '年金')) {
                $grouped['pension'][] = $row;
            } elseif (str_contains($category, '一般')) {
                $grouped['general'][] = $row;
            }
        }

        return $grouped;
    }

    /**
     * self::HOKEN_ROW_CAPACITY を超える区分があれば、その分だけページを増やす。
     *
     * @param array<string, list<array<string, string>>> $grouped
     */
    private function hokenPageCount(array $grouped): int
    {
        $pageCount = 1;
        foreach (self::HOKEN_ROW_CAPACITY as $section => $capacity) {
            $count = count($grouped[$section] ?? []);
            if ($count === 0) {
                continue;
            }
            $pageCount = max($pageCount, (int) ceil($count / $capacity));
        }

        return $pageCount;
    }

    /**
     * 保険料控除申告書プレビュー：1ページ分の描画。
     *
     * 座標はすべて仮値。実帳票（storage/app/templates/year_end/{年}-hoken_koujyo_shinkoku.pdf）を
     * 見ながら1項目ずつ調整する前提で、行ごとに座標・文字サイズ・文字間・最大幅をまとめて書く。
     * 調整中は目印として赤字 self::HOKEN_PDF_TEXT_COLOR を使う。確定後は他帳票と同じ青
     * [0, 0, 180] に変更する。
     *
     * @param array<string, list<array<string, string>>> $grouped
     */
    private function writeHokenPreviewPage(Fpdi $pdf, int $targetYear, array $staff, array $nenTyo, array $grouped, int $pageIndex, int $pageCount): void
    {
        $textColor = self::HOKEN_PDF_TEXT_COLOR;
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->SetFont('kozminproregular', '', 7);

        $this->writeHokenHeader($pdf, $targetYear, $staff);

        // 生命保険料控除：一般／介護医療／個人年金。それぞれ自分の上限行数ぶんだけこのページの区間を表示する。
        // 例：一般は4行/ページなので、2ページ目は5〜8件目を表示する。
        foreach (['general', 'nursing', 'pension'] as $section) {
            $capacity = self::HOKEN_ROW_CAPACITY[$section];
            $rows = array_slice($grouped[$section], $pageIndex * $capacity, $capacity);
            foreach ($rows as $index => $row) {
                $this->writeHokenLifeInsuranceRow($pdf, $row, $section, $index);
            }
        }

        // 地震保険料控除：地震／旧長期損害保険
        foreach (['earthquake', 'old_long_term'] as $section) {
            $capacity = self::HOKEN_ROW_CAPACITY[$section];
            $rows = array_slice($grouped[$section], $pageIndex * $capacity, $capacity);
            foreach ($rows as $index => $row) {
                $this->writeHokenEarthquakeRow($pdf, $row, $section, $index);
            }
        }

        // 社会保険料控除欄
        $socialCapacity = self::HOKEN_ROW_CAPACITY['social_insurance'];
        $socialRows = array_slice($grouped['social_insurance'], $pageIndex * $socialCapacity, $socialCapacity);
        foreach ($socialRows as $index => $row) {
            $this->writeHokenSocialInsuranceRow($pdf, $row, $index);
        }

        // 小規模企業共済等掛金控除欄と各「合計（控除額）」欄は帳票1枚につき1回だけの表示なので、最終ページにのみ書く。
        if ($pageIndex === $pageCount - 1) {
            $this->writeHokenSmallBusinessRow($pdf, $grouped['kikou'], 'kikou');
            $this->writeHokenSmallBusinessRow($pdf, $grouped['kigyo'], 'kigyo');
            $this->writeHokenSmallBusinessRow($pdf, $grouped['kojin'], 'kojin');
            $this->writeHokenSmallBusinessRow($pdf, $grouped['shinshin'], 'shinshin');

            // 各控除の「合計（控除額）」欄は、帳票側で再計算せず mx_nen_tyo の申告済み保存値を表示する。
            $this->writeHokenAggregateSection($pdf, $nenTyo);
        }
    }

    private function writeHokenHeader(Fpdi $pdf, int $targetYear, array $staff): void
    {
        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));
        $companyNumber = trim((string) ($staff['corporate_number'] ?? ''));
        $companyAddress = trim((string) ($staff['company_address'] ?? ''));
        $staffFuri = trim((string) ($staff['staff_name_furi'] ?? ''));
        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $address = trim((string) ($staff['address'] ?? ''));

        // タイトル行「令和◯年分」の年度部分
        // $this->writePdfTextSized($pdf, 150, 8, "{$targetYear}年分", 9, 20);

        // 給与の支払者欄（左上）
        $this->writePdfTextSized($pdf, 55, 22, $companyName, 11, 55);
        $this->writePdfDigitsSized($pdf, 55, 32, $companyNumber, 6.4, 11);
        $this->writePdfWrappedTextSized($pdf, 55, 40, $companyAddress, 55, 3.5, 2, 8);

        // あなた（本人）欄（右上）
        $this->writePdfTrackedTextSized($pdf, 165, 20, $staffFuri, 8, 1.2, 20);
        $this->writePdfTextSized($pdf, 165, 27, $staffName, 11, 40);
        $this->writePdfWrappedTextSized($pdf, 165, 37, $address, 55, 3.5, 2, 8);
    }

    /**
     * 生命保険料控除：一般／介護医療／個人年金の各行のY座標。
     * 開始位置+行間の計算式ではなく行ごとに明示することで、1行だけ個別調整できるようにしている。
     * 行を増やす場合は配列に1行足すだけでよい（HOKEN_ROW_CAPACITYの上限も合わせて増やすこと）。
     */
    private const HOKEN_LIFE_ROW_Y = [
        'general' => [63.0, 71.0, 79.0, 87.0],
        'nursing' => [111.0, 119.0],
        'pension' => [137.0, 147.0],
    ];

    private function writeHokenLifeInsuranceRow(Fpdi $pdf, array $row, string $section, int $index): void
    {
        $company = (string) ($row['insurance_company'] ?? '');
        $type = (string) ($row['insurance_type'] ?? '');
        $period = (string) ($row['insurance_period'] ?? '');
        $contractor = (string) ($row['policy_holder_name'] ?? '');
        $recipient = (string) ($row['beneficiary_name'] ?? '');
        $appliedSystem = trim((string) ($row['applied_system'] ?? ''));
        $amount = (string) ($row['declared_amount'] ?? '');

        $y = self::HOKEN_LIFE_ROW_Y[$section][$index] ?? null;
        if ($y === null) {
            return;
        }

        // 枠より長い文字は省略記号ではなく2行折り返しにする（保険会社名・種類が長いケース対策）。
        $this->writePdfWrappedTextSized($pdf, 22.0, $y, $company, 25, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 48.0, $y, $type, 11, 3.0, 2, 7);
        $this->writePdfTextSized($pdf, 63.0, $y, $period, 6.5, 16);
        $this->writePdfWrappedTextSized($pdf, 75.0, $y, $contractor, 18, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 94.0, $y, $recipient, 18, 3.0, 2, 7);

        // 新・旧の区分欄。介護医療保険料には新・旧の区分が存在しないため丸印を出さない。
        if ($section !== 'nursing') {
            if (str_contains($appliedSystem, '新')) {
                $this->drawPdfCircleMark($pdf, 124.0, $y - 1.5, 1.8);
            } elseif (str_contains($appliedSystem, '旧')) {
                $this->drawPdfCircleMark($pdf, 128.0, $y - 1.5, 1.8);
            }
        }

        $this->writePdfTextRightSized($pdf, 148.0, $y, $amount, 8, 16);

        if ($section === 'pension') {
            $pensionStart = (string) ($row['pension_payment_start_date'] ?? '');
            $this->writePdfTextSized($pdf, 73.0, $y + 4.0, $pensionStart, 6.5, 18);
        }
    }

    /**
     * 地震保険料控除：地震／旧長期損害保険の各行のY座標。個別調整できるよう行ごとに明示する。
     */
    private const HOKEN_EARTHQUAKE_ROW_Y = [
        'earthquake' => [65.0, 72.0],
        'old_long_term' => [79.0, 86.0],
    ];

    private function writeHokenEarthquakeRow(Fpdi $pdf, array $row, string $section, int $index): void
    {
        $company = (string) ($row['insurance_company'] ?? '');
        $type = (string) ($row['insurance_type'] ?? '');
        $period = (string) ($row['insurance_period'] ?? '');
        // 「保険等の対象となった家屋等に居住又は家財を利用している者等の氏名」に対応する専用列が
        // mx_hokenに無いため、いったん beneficiary_name を流用する。
        $residentName = (string) ($row['beneficiary_name'] ?? '');
        $amount = (string) ($row['declared_amount'] ?? '');

        $y = self::HOKEN_EARTHQUAKE_ROW_Y[$section][$index] ?? null;
        if ($y === null) {
            return;
        }

        $this->writePdfWrappedTextSized($pdf, 172.0, $y, $company, 20, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 190.0, $y, $type, 16, 3.0, 2, 7);
        $this->writePdfTextSized($pdf, 205.0, $y, $period, 6.5, 14);
        $this->writePdfWrappedTextSized($pdf, 224.0, $y, $residentName, 18, 3.0, 2, 7);

        // 区分欄（地震／旧長期）。文字の〇は楕円に見えるため、TCPDFの真円描画に変更している。
        if ($section === 'earthquake') {
            $this->drawPdfCircleMark($pdf, 240.0, $y - 1.5, 1.8);
        } else {
            $this->drawPdfCircleMark($pdf, 245.0, $y - 1.5, 1.8);
        }

        $this->writePdfTextRightSized($pdf, 260.0, $y, $amount, 8, 16);
    }

    /** 社会保険料控除欄の各行のY座標。個別調整できるよう行ごとに明示する。 */
    private const HOKEN_SOCIAL_ROW_Y = [131.0, 138.0, 145.0, 152.0];

    private function writeHokenSocialInsuranceRow(Fpdi $pdf, array $row, int $index): void
    {
        $type = (string) ($row['insurance_type'] ?? '');
        $payTo = (string) ($row['insurance_company'] ?? '');
        // 「保険料を負担することになっている人の氏名」に対応する専用列が mx_hoken に無いため、
        // いったん policy_holder_name を流用する。
        $payer = (string) ($row['policy_holder_name'] ?? '');
        $amount = (string) ($row['declared_amount'] ?? '');

        $y = self::HOKEN_SOCIAL_ROW_Y[$index] ?? null;
        if ($y === null) {
            return;
        }

        $this->writePdfWrappedTextSized($pdf, 172.0, $y, $type, 20, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 195.0, $y, $payTo, 25, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 225.0, $y, $payer, 20, 3.0, 2, 7);
        $this->writePdfTextRightSized($pdf, 268.0, $y, $amount, 7, 20);
    }

    /** @param list<array<string, string>> $rows */
    private function writeHokenSmallBusinessRow(Fpdi $pdf, array $rows, string $kind): void
    {
        if ($rows === []) {
            return;
        }

        // 帳票側の枠は種類ごとに1行だけしかないため、同じ種類が複数行あれば合算して1つの金額にする。
        $amount = 0.0;
        foreach ($rows as $row) {
            $amount += $this->money($row['declared_amount'] ?? 0);
        }

        // 小規模企業共済等掛金控除欄：種類は帳票に印字済みの4行固定。
        // 機構の共済契約 y=155／企業型年金 y=162／個人型年金 y=169／心身障害者扶養共済 y=176
        $rowY = [
            'kikou' => 155.0,
            'kigyo' => 162.0,
            'kojin' => 169.0,
            'shinshin' => 176.0,
        ];

        $this->writePdfTextRightSized($pdf, 288.0, $rowY[$kind], number_format($amount, 0), 7, 20);
    }

    private function writeHokenAggregateSection(Fpdi $pdf, array $nenTyo): void
    {
        // 要確認：生命保険料控除額（一般・介護医療・新旧個人年金から国税庁の計算式で算出した最終額。
        // 帳票側では再計算せず mx_nen_tyo.seimei_fee_kou の保存値をそのまま表示する）
        $seimeiTotal = (string) ($nenTyo['seimei_fee_kou'] ?? '');
        $this->writePdfTextRightSized($pdf, 148.0, 155.0, $seimeiTotal, 8, 16);

        // 要確認：地震保険料控除額（mx_nen_tyo.jishun_fee_kou の保存値をそのまま表示）
        $jishunTotal = (string) ($nenTyo['jishun_fee_kou'] ?? '');
        $this->writePdfTextRightSized($pdf, 260.0, 95.0, $jishunTotal, 8, 16);

        // 社会保険料控除欄「合計（控除額）」
        $syahoTotal = (string) ($nenTyo['shin_syaho_fee_kou'] ?? '');
        $this->writePdfTextRightSized($pdf, 268.0, 147.0, $syahoTotal, 7, 20);

        // 小規模企業共済等掛金控除欄「合計（控除額）」
        $kyosaiTotal = (string) ($nenTyo['shun_kigyou_fee_kou'] ?? '');
        $this->writePdfTextRightSized($pdf, 268.0, 195.0, $kyosaiTotal, 7, 20);
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

        if ($templateKey === 'gensen_tyoushu_hyou') {
            // この帳票は「支払を受ける者」欄の氏名・住所と「支払者」欄の会社名を
            // writeGensenHyouPreview() 側でまとめて書く。ここでは表題の「令和◯年分」だけ書く。
            $this->writePdfText($pdf, 27, 7, "{$targetYear}年分");
            return;
        }

        $positions = [
            'kiso_koujyo_shinkoku' => [18, 14, 42, 14, 62, 14, 122, 14],
            'fuyo_koujyo_shinkoku' => [18, 14, 42, 14, 62, 14, 122, 14],
            'gensen_tyoushu_bo' => [16, 12, 36, 12, 56, 12, 112, 12],
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
        $textColor = self::FUYO_PDF_TEXT_COLOR;
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

        // 会社欄
        $this->writePdfTextSized($pdf, 62, 15, $companyName, 11, 42);
        $this->writePdfDigitsSized($pdf, 60, 27, $companyNumber, 4.4, 8);
        $this->writePdfWrappedTextSized($pdf, 61, 34, $companyAddress, 58, 4.0, 2, 7);

        // 本人欄
        $this->writePdfTrackedTextSized($pdf, 142, 11.5, $staffFuri, 7, 1.4, 28);
        $this->writePdfTrackedTextSized($pdf, 142, 17, $staffName, 9, 3.0, 28);

        // 本人欄：生年月日の元号などを隠す枠。調整中は色付き、確定後は [255, 255, 255] にする。
        $birthdayEraseColor = [255, 240, 120];
        $staffBirthdayEraseX = 204.5;
        $staffBirthdayEraseY = 12.0;
        $staffBirthdayEraseW = 40.0;
        $staffBirthdayEraseH = 4.4;
        $this->fillPdfRect($pdf, $staffBirthdayEraseX, $staffBirthdayEraseY, $staffBirthdayEraseW, $staffBirthdayEraseH, $birthdayEraseColor);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $this->writePdfTextSized($pdf, 210, 13, $birthday, 7, 28);

        $this->writePdfTextSized($pdf, 210, 18, $headHouse, 8, 22);
        $this->writePdfTextSized($pdf, 210, 25, $houseRelationship, 8, 12);
        $this->writePdfDigitsSized($pdf, 136, 26, $myNumber, 4.5, 8);

        // 本人欄：郵便番号（左3桁・右4桁）
        if ($postNum !== '') {
            $this->writePdfTextSized($pdf, 148, 31, substr($postNum, 0, 3), 7, 6);
            $this->writePdfTextSized($pdf, 160, 31, substr($postNum, 3), 7, 8);
        }
        $this->writePdfWrappedTextSized($pdf, 142, 35, $address, 58, 4.0, 2, 7);

        // 本人欄：配偶者の有無
        $circle = '〇';
        if ($spouse === '1') {
            $this->writePdfTextSized($pdf, 233.0, 33.0, $circle, 12, 4);
        } elseif ($spouse === '0' || $spouse === '2') {
            $this->writePdfTextSized($pdf, 266.0, 33.0, $circle, 12, 4);
        }

        // 本人欄：甲欄
        if (str_contains($taxAmount, '甲')) {
            $this->writePdfTextSized($pdf, 253.0, 35.0, $circle, 11, 4);
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
            $age = $this->calculationService->ageAtYearEnd($row['fuyo_birthday'] ?? null, $targetYear);
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

        $textColor = self::FUYO_PDF_TEXT_COLOR;
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
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

        // C欄：障害者、寡婦、ひとり親又は勤労学生
        $this->writeFuyoDisabilitySection($pdf, $rows);
    }

    private function writeFuyoPersonRow(Fpdi $pdf, array $row, string $section, int $index): void
    {
        $name = (string) ($row['fuyo_name'] ?? '');
        $furi = (string) ($row['fuyo_name_furi'] ?? '');
        $relationship = (string) ($row['fuyo_relationship'] ?? '');
        $relationshipLabel = $relationship === '妻' ? '' : $relationship;
        $income = $this->formatPdfMoney($row['fuyo_shunyu'] ?? null);
        $address = (string) ($row['fuyo_address'] ?? '');

        $textColor = self::FUYO_PDF_TEXT_COLOR;
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

        // 扶養欄：生年月日の元号などを隠す枠。調整中は色付き、確定後は [255, 255, 255] にする。
        $birthdayEraseColor = [255, 240, 120];

        if ($section === 'spouse') {
            // A欄：源泉控除対象配偶者
            $rowOffset = $index * 11.4;
            $this->writePdfTrackedTextSized($pdf, 40.0, 58.5 + $rowOffset, $furi, 7, 1.4, 24);
            $this->writePdfTrackedTextSized($pdf, 42.0, 65.0 + $rowOffset, $name, 8, 3.0, 24);
            $this->writePdfDigitsSized($pdf, 72.0, 60.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.45, 8);
            $this->writePdfTextSized($pdf, 83.0, 63.0 + $rowOffset, $relationshipLabel, 8, 10);

            // A欄：生年月日の元号などを隠す枠
            $this->fillPdfRect($pdf, 90, 64.5 + $rowOffset, 31.0, 5.0, $birthdayEraseColor);
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
            $this->writePdfTextSized($pdf, 95.0, 65.0 + $rowOffset, $this->formatPdfJapaneseDate($row['fuyo_birthday'] ?? null), 8, 18);

            $this->writePdfTextRightSized($pdf, 155.0, 63.0 + $rowOffset, $income, 7, 16);

            $this->writePdfWrappedTextSized($pdf, 203.0, 60.0 + $rowOffset, $address, 28, 3.7, 2, 7);
            return;
        }

        if ($section === 'under16') {
            // 16歳未満の扶養親族欄
            $rowOffset = $index * 11.7;
            $this->writePdfTrackedTextSized($pdf, 39.0, 178.5 + $rowOffset, $furi, 7, 1.4, 24);
            $this->writePdfTrackedTextSized($pdf, 42.0, 182.0 + $rowOffset, $name, 8, 3.0, 24);
            $this->writePdfDigitsSized($pdf, 69.0, 181.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.3, 8);
            $this->writePdfTextSized($pdf, 121.0, 181.0 + $rowOffset, $relationshipLabel, 7, 10);

            // 16歳未満欄：生年月日の元号などを隠す枠
            $this->fillPdfRect($pdf, 128, 179.5 + $rowOffset, 17.0, 5.4, $birthdayEraseColor);
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
            $this->writePdfTextSized($pdf, 128.0, 181.0 + $rowOffset, $this->formatPdfJapaneseDateShort($row['fuyo_birthday'] ?? null), 8, 18);

            $this->writePdfTextRightSized($pdf, 235.0, 181.0 + $rowOffset, $income, 7, 16);
            $this->writePdfWrappedTextSized($pdf, 146.0, 179.0 + $rowOffset, $address, 38, 3.7, 2, 7);
            return;
        }

        // B欄：控除対象扶養親族（16歳以上）
        $rowOffset = $index * 14.1;
        $this->writePdfTrackedTextSized($pdf, 40.0, 70.5 + $rowOffset, $furi, 7, 1.4, 24);
        $this->writePdfTrackedTextSized($pdf, 42.0, 78.0 + $rowOffset, $name, 8, 3.0, 24);
        $this->writePdfDigitsSized($pdf, 72.0, 74.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.45, 8);
        $this->writePdfTextSized($pdf, 78.0, 80.0 + $rowOffset, $relationshipLabel, 8, 10);

        // B欄：生年月日の元号などを隠す枠
        $this->fillPdfRect($pdf, 90, 78.5 + $rowOffset, 31.0, 5.0, $birthdayEraseColor);
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $this->writePdfTextSized($pdf, 95.0, 80.0 + $rowOffset, $this->formatPdfJapaneseDate($row['fuyo_birthday'] ?? null), 8, 18);

        $this->writePdfTextRightSized($pdf, 160.0, 76.0 + $rowOffset, $income, 7, 16);

        $this->writePdfWrappedTextSized($pdf, 203.0, 73.0 + $rowOffset, $address, 28, 3.7, 2, 7);
    }
    private function writeFuyoDisabilitySection(Fpdi $pdf, array $rows): void
    {
        $disabledRows = [];
        $counts = [
            'same_household_special' => 0,
            'special' => 0,
            'general' => 0,
        ];

        foreach ($rows as $row) {
            $failure = trim((string) ($row['failure_judgment'] ?? ''));
            if ($failure === '') {
                continue;
            }

            $name = trim((string) ($row['fuyo_name'] ?? ''));
            $notebook = trim((string) ($row['failure_notebook'] ?? ''));
            $kyojyu = trim((string) ($row['kyojyu'] ?? ''));
            $isSpecial = in_array($failure, ['A1', 'A2', '1級', '2級'], true);

            if ($isSpecial && $kyojyu === '同居') {
                $counts['same_household_special']++;
            } elseif ($isSpecial) {
                $counts['special']++;
            } else {
                $counts['general']++;
            }

            $parts = array_values(array_filter([$name, $notebook, $failure], static fn($value): bool => trim((string) $value) !== ''));
            if ($parts !== []) {
                $disabledRows[] = implode('　', $parts);
            }
        }

        $total = array_sum($counts);
        if ($total <= 0) {
            return;
        }

        $textColor = self::FUYO_PDF_TEXT_COLOR;
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

        // C欄：障害者チェック
        $this->writePdfTextSized($pdf, 35.5, 127.0, 'レ', 9, 4);

        // C欄：障害者人数（同居特別、特別、その他）
        if ($counts['same_household_special'] > 0) {
            $this->writePdfTextRightSized($pdf, 101.5, 134.5, (string) $counts['same_household_special'], 7, 4);
        }
        if ($counts['special'] > 0) {
            $this->writePdfTextRightSized($pdf, 101.5, 140.5, (string) $counts['special'], 7, 4);
        }
        if ($counts['general'] > 0) {
            $this->writePdfTextRightSized($pdf, 101.5, 132.5, (string) $counts['general'], 7, 4);
        }

        // C欄：障害者又は勤労学生の内容（名前、障害手帳、等級）
        foreach (array_slice($disabledRows, 0, 2) as $index => $detail) {
            $this->writePdfTextSized($pdf, 132.0, 133.5 + ($index * 4.0), $detail, 7, 48);
        }
    }
    /**
     * 基礎控除申告書プレビュー。
     *
     * この帳票は「基礎控除申告書（左パネル）」と「配偶者控除等申告書（右パネル）」が
     * 1枚に同居しており、パネルの境界は概ね x=127 付近（左パネル x=15〜125／右パネル x=128〜285）。
     * 元のコードはこの境界を無視した座標になっており、値が隣のパネルの見出し文字に
     * 重なって表示される不具合があったため、テンプレートに実測グリッド（10mm刻み）を重ねて
     * 座標を確認済み。
     *
     * 以下のうち ✔確認済み は実測グリッドで枠内着地を確認したもの、要確認 は
     * パネル間の重なりは解消したが、枠の中心までは実測できていないもの（実帳票で最終確認推奨）。
     */
    private function writeKisoPreview(Fpdi $pdf, array $nenTyo): void
    {
        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 8);

        $rows = [
            // 基礎控除申告書（左パネル）
            ['kyuyo_teate_sum', 80, 58],          // ✔確認済み：(1)給与所得 の 収入金額
            ['shotoku_deduction', 118, 58],       // 要確認：(1)給与所得 の 所得金額
            ['bonus_kyuyo_sum', 118, 76],         // 要確認：あなたの本年中の合計所得金額の見積額（(1)と(2)の合計額）
            ['kiso_bunrui', 108, 90],             // ✔確認済み：区分Ⅰ「（左のA〜Cを記載）」枠
            ['kiso_koujyo', 108, 113],            // 要確認：基礎控除の額

            // 配偶者控除等申告書（右パネル）
            ['haigu_shotoku', 198, 101],          // 要確認：配偶者の(1)給与所得 の 所得金額
            ['haigu_shotoku_sum', 198, 109],      // 要確認：配偶者の本年中の合計所得金額の見積額（(1)と(2)の合計額）
            ['haigu_bunrui', 148, 113],           // 要確認：区分Ⅱ の区分（A/B/C）欄
            ['haigu_toku_deduction', 251, 110],   // ✔確認済み：配偶者控除の額
            ['haigu_toku_deduction_amo', 274, 110], // ✔確認済み：配偶者特別控除の額

            // 所得金額調整控除申告書（下部）
            ['tyosei_koujyo_select', 117, 184],   // ✔確認済み：所得金額調整控除 適用の有無
            ['tyosei_koujyo', 184, 184],          // ✔確認済み：所得金額調整控除額
        ];

        foreach ($rows as [$key, $x, $y]) {
            $this->writePdfTextRight($pdf, $x, $y, (string) ($nenTyo[$key] ?? ''), 22);
        }
    }
    /**
     * 源泉徴収票プレビュー（受給者交付用）。
     *
     * テンプレートは元々「税務署提出用（左）＋受給者交付用（右）」の横並び2部構成だったが、
     * 税務署提出は別途電子申告するため使わず、本人に渡す受給者交付用だけを切り出して
     * storage/app/templates/year_end/{年}-gensen_tyoushu_hyou.pdf を作り直した
     * （旧ファイルは同フォルダに .bak で残置）。そのため現在のテンプレートは148.5mm×210mmの
     * 単票で、以下の座標はその単票内の位置になる。
     *
     * 元のコードは実際の枠より大きくy座標がずれており（例：支払金額の行がy=65だが
     * 実際は y≈16、扶養親族等の数の行がy=112だが実際はy≈50）、テンプレートに実測グリッド
     * （10mm刻み）を重ねて座標を確認・修正済み。
     * ✔確認済み は実測グリッドで行の位置を確認したもの、要確認 は行は合わせたが
     * 細かい列幅までは実測できていないもの（実帳票で最終確認推奨）。
     */
    private function writeGensenHyouPreview(Fpdi $pdf, array $staff, array $nenTyo): void
    {
        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $address = trim((string) ($staff['address'] ?? $staff['staff_address'] ?? $staff['add'] ?? ''));
        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));

        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 8);

        // ✔確認済み：支払を受ける者 住所又は居所／氏名
        $this->writePdfText($pdf, 30, 16, $address, 60);
        $this->writePdfText($pdf, 30, 27, $staffName, 30);

        // ✔確認済み：支払金額｜給与所得控除後の金額（調整控除後）｜所得控除の額の合計額｜源泉徴収税額
        $this->writePdfTextRight($pdf, 60, 38, (string) ($nenTyo['bonus_kyuyo_sum'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 95, 38, (string) ($nenTyo['shotoku_deduction'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 125, 38, (string) ($nenTyo['shotoku_deduction_sum'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 143, 38, (string) ($nenTyo['nentyo_nen_tax'] ?? ''), 16);

        // ✔確認済み：社会保険料等の金額｜生命保険料の控除額｜地震保険料の控除額｜住宅借入金等特別控除の額
        $this->writePdfTextRight($pdf, 68, 68, (string) ($nenTyo['kyu_syaho_fee_kou'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 93, 68, (string) ($nenTyo['seimei_fee_kou'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 118, 68, (string) ($nenTyo['jishun_fee_kou'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 145, 68, (string) ($nenTyo['jyu_kari_kou'] ?? ''), 16);

        // 要確認：控除対象扶養親族等の数（老人・特定・その他・障害者など）のサブ列。
        // 行はy≈50に修正したが、各サブ列の幅は概算のため実帳票で列を確認してください。
        $this->writePdfTextRight($pdf, 22, 50, (string) ($nenTyo['haigu_umu'] ?? ''), 6);
        $this->writePdfTextRight($pdf, 38, 50, (string) ($nenTyo['toku_fu'] ?? ''), 6);
        $this->writePdfTextRight($pdf, 46, 50, (string) ($nenTyo['rou_dou'] ?? ''), 6);
        $this->writePdfTextRight($pdf, 58, 50, (string) ($nenTyo['rou_dou_gai'] ?? ''), 6);
        $this->writePdfTextRight($pdf, 76, 50, (string) ($nenTyo['fuyo_ta'] ?? ''), 6);
        $this->writePdfTextRight($pdf, 136, 50, (string) ($nenTyo['dependent_under_16'] ?? ''), 6);
        $this->writePdfTextRight($pdf, 112, 50, (string) ($nenTyo['shougai_ta'] ?? ''), 6);

        // ✔確認済み：支払者（会社）欄 氏名又は名称
        $this->writePdfText($pdf, 45, 200, $companyName, 55);
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

        $this->writeGensenBoNenTyoPanel($pdf, $nenTyo);
    }

    /**
     * 源泉徴収簿・右側「年調」計算結果欄（①〜㉖）。
     *
     * mx_nen_tyo の保存済み値をそのまま表示するだけで、帳票側では再計算しない。
     * 座標はテンプレートに実測グリッド（10mm刻み）を重ねて確認したが、①③④⑥⑦⑧の4箇所は
     * マーカーを実際に置いて着地を確認済み（✔確認済み）。それ以外はグリッドの目視で
     * 行位置を合わせただけなので、実帳票で最終確認してください（要確認）。
     * ⑪（給与所得控除後の給与等の金額・調整控除後）と㉗〜㉝（還付・徴収の内訳）は
     * 対応する mx_nen_tyo の保存列が見当たらなかったため未実装。
     */
    private function writeGensenBoNenTyoPanel(Fpdi $pdf, array $nenTyo): void
    {
        $pdf->SetTextColor(0, 0, 180);
        $pdf->SetFont('kozminproregular', '', 7);

        // ✔確認済み：給料・手当等｜賞与等｜計（各行：金額／税額）
        $this->writePdfTextRight($pdf, 225, 70, (string) ($nenTyo['kyuyo_teate_sum'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 288, 70, (string) ($nenTyo['kyuyo_teate_tax'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 225, 76, (string) ($nenTyo['bonus_etc'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 288, 76, (string) ($nenTyo['bonus_tax'] ?? ''), 16);
        $this->writePdfTextRight($pdf, 225, 82, (string) ($nenTyo['bonus_kyuyo_sum'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 288, 82, (string) ($nenTyo['siharai_shotoku'] ?? ''), 16);

        // 要確認：給与所得控除後の給与等の金額（①④⑦と同じ列）｜所得金額調整控除額（右側の専用枠）
        $this->writePdfTextRight($pdf, 230, 86, (string) ($nenTyo['shotoku_deduction'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 270, 91, (string) ($nenTyo['tyosei_koujyo'] ?? ''), 20);

        // 要確認：社会保険料等控除額（給与等からの控除分｜申告社会保険料｜申告小規模企業共済等掛金）
        $this->writePdfTextRight($pdf, 230, 101, (string) ($nenTyo['kyu_syaho_fee_kou'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 107, (string) ($nenTyo['shin_syaho_fee_kou'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 112, (string) ($nenTyo['shun_kigyou_fee_kou'] ?? ''), 20);

        // 要確認：生命保険料の控除額｜地震保険料の控除額｜配偶者（特別）控除額｜
        // 扶養控除額及び障害者等の控除額の合計額｜基礎控除額｜所得控除額の合計額
        $this->writePdfTextRight($pdf, 230, 117, (string) ($nenTyo['seimei_fee_kou'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 122, (string) ($nenTyo['jishun_fee_kou'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 127, (string) ($nenTyo['haigu_toku_deduction_amo'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 133, (string) ($nenTyo['deduction_sum'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 138, (string) ($nenTyo['kiso_koujyo'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 144, (string) ($nenTyo['shotoku_deduction_sum'] ?? ''), 20);

        // 要確認：差引課税給与所得金額｜算出所得税額｜住宅借入金等特別控除額
        $this->writePdfTextRight($pdf, 200, 148, (string) ($nenTyo['sa_kazei_shotoku'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 148, (string) ($nenTyo['sanshutu_shotoku'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 153, (string) ($nenTyo['jyu_kari_kou'] ?? ''), 20);

        // 要確認：年調所得税額｜年調年税額｜差引超過額又は不足額
        $this->writePdfTextRight($pdf, 230, 158, (string) ($nenTyo['nentyo_shotoku_amo'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 163, (string) ($nenTyo['nentyo_nen_tax'] ?? ''), 20);
        $this->writePdfTextRight($pdf, 230, 169, (string) ($nenTyo['sa_excess'] ?? ''), 20);
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

    private function formatPdfJapaneseDateShort(mixed $value): string
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
        $era = '';
        $eraYear = $year;
        if ($date >= new \DateTimeImmutable('2019-05-01')) {
            $era = 'R';
            $eraYear = $year - 2018;
        } elseif ($date >= new \DateTimeImmutable('1989-01-08')) {
            $era = 'H';
            $eraYear = $year - 1988;
        } elseif ($date >= new \DateTimeImmutable('1926-12-25')) {
            $era = 'S';
            $eraYear = $year - 1925;
        }

        return $era . $eraYear . '/' . (int) $date->format('n') . '/' . (int) $date->format('j');
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

        // スタッフ1人ずつ存在確認クエリを投げると人数分のラウンドトリップが発生するため、
        // その年の既存対象者をまとめて1回で取得してメモリ上で突き合わせる。
        $existingRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('target_year', $targetYear)
            ->get(['application_id', 'staff_id', 'nen_tyo_no'])
            ->keyBy(fn($row) => trim((string) $row->staff_id));

        $created = 0;
        $skipped = 0;
        $linked = 0;
        $insertRows = [];

        foreach ($staffRows as $row) {
            $staffId = trim((string) ($row->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $nenTyoNo = $nenTyoNoMap[$staffId] ?? null;
            $existing = $existingRows->get($staffId);

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

            $insertRows[] = [
                'staff_id' => $staffId,
                'target_year' => $targetYear,
                'nen_tyo_no' => $nenTyoNo,
                'status' => 'draft',
            ];
            $created++;
        }

        if ($insertRows !== []) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_year_end_applications')
                ->insert($insertRows);
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

        $payload = $this->calculationService->calculateYearEndPayload($nenTyo, $staffId, $targetYear);

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
        $existing = $this->hokenRowOrFail($hokenNo, $staffId, $targetYear);

        $values = $this->validateHokenValues($request);
        $payload = $this->hokenPayload($values);

        if ($request->hasFile('certificate_file')) {
            $newCertificate = $this->storeHokenCertificate($request, $hokenNo, $staffId, $targetYear);
            if ($newCertificate !== []) {
                // 差し替え前の証明書ファイルは孤児化してサーバー容量を無駄に使うため、
                // 新しいファイルを保存できた場合のみ古いファイルを削除する。
                $this->deleteHokenCertificateFile((string) ($existing->certificate_file_path ?? ''));
                $payload += $newCertificate;
            }
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

        $this->deleteHokenCertificateFile((string) ($row->certificate_file_path ?? ''));

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
    private function deleteHokenCertificateFile(string $path): void
    {
        $path = trim($path);
        if ($path === '' || preg_match('/^https?:\/\//', $path) === 1) {
            return;
        }

        $fullPath = public_path(ltrim($path, '/'));
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

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
    private function fillPdfRect(Fpdi $pdf, float $x, float $y, float $w, float $h, array $rgb): void
    {
        $pdf->SetFillColor((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        $pdf->Rect($x, $y, $w, $h, 'F');
    }

    /**
     * チェック用の丸印を描画する。文字の「〇」はフォント次第で楕円に見えることがあるため、
     * TCPDFのCircle()で真円を描く。$x,$yは円の中心。$rgb省略時は現在のテキスト色を使う。
     */
    private function drawPdfCircleMark(Fpdi $pdf, float $x, float $y, float $radius, ?array $rgb = null): void
    {
        $rgb ??= self::HOKEN_PDF_TEXT_COLOR;
        $pdf->SetDrawColor((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        $pdf->SetLineWidth(0.25);
        $pdf->Circle($x, $y, $radius, 0, 360, 'D');
    }

    private function writePdfTrackedTextSized(Fpdi $pdf, float $x, float $y, string $text, int|float $fontSize, float $pitch, int $maxChars = 0): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        if ($maxChars > 0 && function_exists('mb_substr')) {
            $text = mb_substr($text, 0, $maxChars, 'UTF-8');
        }

        $pdf->SetFont('kozminproregular', '', $fontSize);
        $chars = function_exists('mb_str_split') ? mb_str_split($text) : preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars ?: [] as $index => $char) {
            $pdf->Text($x + ($index * $pitch), $y, $char);
        }
        $pdf->SetFont('kozminproregular', '', 7);
    }
    private function writePdfTextRightSized(Fpdi $pdf, float $rightX, float $y, string $text, int|float $fontSize, int $maxWidth = 0): void
    {
        $pdf->SetFont('kozminproregular', '', $fontSize);
        $this->writePdfTextRight($pdf, $rightX, $y, $text, $maxWidth);
        $pdf->SetFont('kozminproregular', '', 7);
    }

    private function writePdfDigitsSized(Fpdi $pdf, float $x, float $y, string $text, float $pitch, int|float $fontSize): void
    {
        $pdf->SetFont('kozminproregular', '', $fontSize);
        $this->writePdfDigits($pdf, $x, $y, $text, $pitch);
        $pdf->SetFont('kozminproregular', '', 7);
    }

    private function writePdfWrappedTextSized(Fpdi $pdf, float $x, float $y, string $text, int $maxWidth, float $lineHeight, int $maxLines, int|float $fontSize): void
    {
        $pdf->SetFont('kozminproregular', '', $fontSize);
        $this->writePdfWrappedText($pdf, $x, $y, $text, $maxWidth, $lineHeight, $maxLines);
        $pdf->SetFont('kozminproregular', '', 7);
    }
    private function writePdfTextSized(Fpdi $pdf, float $x, float $y, string $text, int|float $fontSize, int $maxWidth = 0): void
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
