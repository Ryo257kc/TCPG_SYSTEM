<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\YearEndAdjustment\YearEndCalculationService;
use App\Services\YearEnd\CertificateFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use setasign\Fpdi\Tcpdf\Fpdi;

class YearEndAdjustmentV2Controller extends Controller
{
    public function __construct(
        private readonly YearEndCalculationService $calculationService,
        private readonly CompanyV2Service $companyService,
        private readonly CertificateFileService $certificateFileService,
    ) {}

    /**
     * 配偶者はmx_fuyoに続柄「夫/妻/配偶者」の行として保存する（帳票生成側の
     * 配偶者判定・スタッフ側YearEndApplicationControllerと同じ条件）。
     */
    private const SPOUSE_RELATIONSHIPS = ['夫', '妻', '配偶者'];

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

                // 下書きは「対象者作成直後で未着手」と「入力途中でまだ提出前」の両方を含み
                // 一覧上で見分けがつかなかったため、いずれかのセクションを一度でも保存済み
                // （＝changed系カラムのどれかがNULLでない）かどうかで下書きの内訳を分ける。
                $hasStartedAnySection = $application->personal_info_changed !== null
                    || $application->dependents_changed !== null
                    || $application->spouse_changed !== null
                    || $application->insurance_deduction_changed !== null
                    || $application->housing_loan_changed !== null
                    || $application->previous_job_withholding_changed !== null;
                $statusLabel = $this->statusLabel($status);
                $statusBadgeKey = $status;
                if ($status === 'draft') {
                    $statusLabel .= $hasStartedAnySection ? '（作業中）' : '（未着手）';
                    $statusBadgeKey = $hasStartedAnySection ? 'draft-started' : 'draft-empty';
                }

                $rows[] = [
                    'application_id' => (string) ($application->application_id ?? ''),
                    'staff_id' => $staffId,
                    'staff_name' => $staffDetail['staff_name'],
                    'nyu_date' => $staffDetail['nyu_date'],
                    'tai_date' => $staffDetail['tai_date'],
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'status_badge_key' => $statusBadgeKey,
                    'can_delete' => $status === 'draft',
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

        $companyRows = $this->companyService->list('')['rows'] ?? [];
        $companyOptions = array_values(array_filter(array_map(
            static fn(array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn(array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));

        return view('admin_v2.work.year_end_adjustments.index', [
            'targetYear' => $targetYear,
            'yearOptions' => range((int) date('Y') - 1, (int) date('Y') + 1),
            'tableExists' => $tableExists,
            'rows' => $rows,
            'statusCounts' => $statusCounts,
            'statusOptions' => $this->statusOptions(),
            'companyOptions' => $companyOptions,
            'bulkReportOptions' => $this->bulkReportOptions(),
        ]);
    }

    /** @return array<string, string> テンプレートキー => 帳票名。まとめて出力ドロップダウンの選択肢（正本）。 */
    private function bulkReportOptions(): array
    {
        return [
            'fuyo_koujyo_shinkoku' => '扶養控除申告書',
            'kiso_koujyo_shinkoku' => '基礎控除申告書',
            'gensen_tyoushu_bo' => '源泉徴収簿',
            'gensen_tyoushu_hyou' => '源泉徴収票',
            'hoken_koujyo_shinkoku' => '保険料控除申告書',
        ];
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
            'kisoBracketOptions' => $this->kisoBracketOptionsForView($targetYear),
            'fuyoRows' => $this->fuyoRows($staffId, $targetYear),
            'hokenRows' => $this->hokenRows($staffId, $targetYear),
            'spouseFuyoRow' => $spouseFuyoRow = $this->spouseFuyoRow($staffId, $targetYear),
            'spouseComputedIncomePreview' => $spouseFuyoRow !== null && $spouseFuyoRow['fuyo_shunyu'] !== null
                ? $this->calculationService->salaryIncomeAfterDeduction((float) $spouseFuyoRow['fuyo_shunyu'], $targetYear)
                : null,
        ]);
    }

    /**
     * 配偶者はmx_fuyoに続柄「夫/妻/配偶者」の行として保存されている（スタッフ側と同じ判定）。
     * ステージングを廃止したため、実データから直接取得する。
     *
     * @return array<string, mixed>|null
     */
    private function spouseFuyoRow(string $staffId, int $targetYear): ?array
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->whereIn('fuyo_relationship', self::SPOUSE_RELATIONSHIPS)
            ->first();

        return $row !== null ? (array) $row : null;
    }

    /**
     * kiso_bunrui/haigu_bunruiの選択肢の表示用データ。選択肢の値は細かい所得区分の
     * ラベル（旧Access運用に合わせてkiso_bunruiにそのまま保存する文字列）、
     * 表示にはA/B/C・①②③の記号も金額と一緒に添える。
     *
     * @return array<int, array{label: string, amount: int, kisoBunruiSymbol: string, haiguBunruiSymbol: string}>
     */
    private function kisoBracketOptionsForView(int $targetYear): array
    {
        return array_map(fn(array $row) => [
            'label' => $row['label'],
            'amount' => $row['amount'],
            'kisoBunruiSymbol' => $row['symbol'],
            'haiguBunruiSymbol' => match ($row['symbol']) {
                'A' => '①',
                'B' => '②',
                'C' => '③',
                default => '',
            },
        ], $this->calculationService->kisoBracketOptions($targetYear));
    }

    /**
     * 「計算結果サマリー」欄（mx_nen_tyo）の手入力保存。給与画面と同じ「編集ボタン→保存」の
     * 方式で使う想定。nenTyoSummaryGroups()に載っている列だけを更新対象として受け付ける
     * （nen_tyo_no/year_endは行の識別に関わるためnenTyoSummaryGroups()に含めていない＝常に
     * 読み取り専用）。処理済（edit_lock=1）の場合は保存させない。
     */
    public function updateNenTyo(Request $request, int $applicationId)
    {
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications'), 404);
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('mx_nen_tyo'), 404);

        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $nenTyoNo = (int) ($application->nen_tyo_no ?? 0);
        if ($nenTyoNo <= 0) {
            return response()->json(['ok' => false, 'message' => '年調レコードがまだ作成されていません。'], 422);
        }

        $nenTyoRow = DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo')->where('nen_tyo_no', $nenTyoNo)->first();
        abort_unless($nenTyoRow, 404);

        if ((int) ($nenTyoRow->edit_lock ?? 0) === 1) {
            return response()->json(['ok' => false, 'message' => '処理済のため編集できません。'], 422);
        }

        $editableKeys = [];
        foreach ($this->nenTyoSummaryGroups() as $group) {
            foreach (array_keys($group) as $key) {
                $editableKeys[] = $key;
            }
        }

        $values = (array) $request->input('values', []);
        $payload = [];
        foreach ($editableKeys as $key) {
            if (array_key_exists($key, $values)) {
                $value = trim((string) $values[$key]);
                $payload[$key] = $value !== '' ? $value : null;
            }
        }

        if ($payload === []) {
            return response()->json(['ok' => false, 'message' => '更新項目がありません。'], 422);
        }

        DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo')->where('nen_tyo_no', $nenTyoNo)->update($payload);

        return response()->json(['ok' => true]);
    }

    /**
     * 年調計算結果（mx_nen_tyo.edit_lock）の確定／確定解除トグル。給与画面のconfirm/bonusConfirm
     * と同じ「今の値を反転させるだけ」の方式。これまでこの画面にはedit_lockを1にする操作が
     * 一切無く（新規作成時に0を入れるだけ）、既存の処理済みレコードはすべて旧システムからの
     * 移行データだった。
     */
    public function toggleNenTyoLock(int $applicationId)
    {
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications'), 404);
        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('mx_nen_tyo'), 404);

        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $nenTyoNo = (int) ($application->nen_tyo_no ?? 0);
        if ($nenTyoNo <= 0) {
            return response()->json(['ok' => false, 'message' => '年調レコードがまだ作成されていません。'], 422);
        }

        $nenTyoRow = DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo')->where('nen_tyo_no', $nenTyoNo)->first(['edit_lock']);
        abort_unless($nenTyoRow, 404);

        $next = ((int) ($nenTyoRow->edit_lock ?? 0)) === 1 ? 0 : 1;

        DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo')->where('nen_tyo_no', $nenTyoNo)->update(['edit_lock' => $next]);

        return response()->json(['ok' => true, 'edit_lock' => $next]);
    }

    // ==========================================================================
    // 保険料控除申告書 (hoken_koujyo_shinkoku) ここから
    // 座標調整中。行の書き方・区分ごとの合計欄はwriteHokenLifeInsuranceRow/writeHokenEarthquakeRow内。
    // ==========================================================================

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

            $this->writeHokenPreviewPage($pdf, $targetYear, $staff, $nenTyo, $grouped, $pageIndex);
        }

        $pdf->Output($outputPath, 'F');

        // 座標調整のたびにファイルは再生成しているが、キャッシュヘッダーが無いと
        // ブラウザ内蔵のPDFビューアが同一URLの古い表示を使い回し、座標を変えても
        // 見た目が変わらないことがあるため、明示的にキャッシュを無効化する。
        return response()->file($outputPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="hoken_koujyo_shinkoku_' . $targetYear . '_' . $staffId . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
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
     * 調整中は目印として赤字 [255, 0, 0] を使う。確定後は他帳票と同じ青
     * [0, 0, 180] に変更する。
     *
     * @param array<string, list<array<string, string>>> $grouped
     */
    private function writeHokenPreviewPage(Fpdi $pdf, int $targetYear, array $staff, array $nenTyo, array $grouped, int $pageIndex): void
    {
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('kozminproregular', '', 7);

        $this->writeHokenHeader($pdf, $targetYear, $staff);

        // 生命保険料控除：一般／介護医療／個人年金。それぞれ自分の上限行数ぶんだけこのページの区間を表示する。
        // 例：一般は4行/ページなので、2ページ目は5〜8件目を表示する。
        foreach (['general', 'nursing', 'pension'] as $section) {
            $capacity = self::HOKEN_ROW_CAPACITY[$section];
            $rows = array_slice($grouped[$section], $pageIndex * $capacity, $capacity);
            foreach ($rows as $index => $row) {
                $this->writeHokenLifeInsuranceRow($pdf, $row, $section, $index, $nenTyo);
            }
        }

        // 地震保険料控除：地震／旧長期損害保険
        foreach (['earthquake', 'old_long_term'] as $section) {
            $capacity = self::HOKEN_ROW_CAPACITY[$section];
            $rows = array_slice($grouped[$section], $pageIndex * $capacity, $capacity);
            foreach ($rows as $index => $row) {
                $this->writeHokenEarthquakeRow($pdf, $row, $section, $index, $nenTyo);
            }
        }

        // 社会保険料控除欄
        $socialCapacity = self::HOKEN_ROW_CAPACITY['social_insurance'];
        $socialRows = array_slice($grouped['social_insurance'], $pageIndex * $socialCapacity, $socialCapacity);
        foreach ($socialRows as $index => $row) {
            $this->writeHokenSocialInsuranceRow($pdf, $row, $index);
        }

        // 小規模企業共済等掛金控除欄と各「合計（控除額）」欄も、2ページ目以降だけ空欄だと見づらいため全ページに書く。
        $this->writeHokenSmallBusinessRow($pdf, $grouped['kikou'], 'kikou');
        $this->writeHokenSmallBusinessRow($pdf, $grouped['kigyo'], 'kigyo');
        $this->writeHokenSmallBusinessRow($pdf, $grouped['kojin'], 'kojin');
        $this->writeHokenSmallBusinessRow($pdf, $grouped['shinshin'], 'shinshin');

        // 各控除の「合計（控除額）」欄は、帳票側で再計算せず mx_nen_tyo の申告済み保存値を表示する。
        $this->writeHokenAggregateSection($pdf, $nenTyo);
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
     * 生命保険料控除：一般／介護医療／個人年金の各行を書く。
     * 扶養控除申告書（writeFuyoPersonRow）と同じ書き方：区分ごとにブロックを分け、
     * 各フィールドの行は「基準Y + $rowOffset」を呼び出しの中に直接書く。
     * 座標・フォントサイズ・文字間・最大幅がすべて1行で見えるので、他のフィールドを
     * 探さずにその行だけを個別に調整できる。行を増やす場合はHOKEN_ROW_CAPACITYも増やすこと。
     */
    private function writeHokenLifeInsuranceRow(Fpdi $pdf, array $row, string $section, int $index, array $nenTyo): void
    {
        $company = (string) ($row['insurance_company'] ?? '');
        $type = (string) ($row['insurance_type'] ?? '');
        $period = (string) ($row['insurance_period'] ?? '');
        $contractor = (string) ($row['policy_holder_name'] ?? '');
        $recipient = (string) ($row['beneficiary_name'] ?? '');
        $appliedSystem = trim((string) ($row['applied_system'] ?? ''));
        $amount = (string) ($row['declared_amount'] ?? '');

        // 一般生命
        if ($section === 'general') {
            $rowOffset = $index * 8.0;
            $this->writePdfWrappedTextSized($pdf, 22.0, 62.0 + $rowOffset, $company, 25, 3.0, 2, 7);
            $this->writePdfWrappedTextSized($pdf, 48.0, 62.0 + $rowOffset, $type, 11, 3.0, 2, 7);
            $this->writePdfTextSized($pdf, 63.0, 63.0 + $rowOffset, $period, 6.5, 16);
            $this->writePdfWrappedTextSized($pdf, 75.0, 63.0 + $rowOffset, $contractor, 18, 3.0, 2, 7);
            $this->writePdfWrappedTextSized($pdf, 94.0, 63.0 + $rowOffset, $recipient, 18, 3.0, 2, 7);
            // 「新・旧」は左右に印字されているため、Yは行のまま、Xだけ左(新)・右(旧)にずらす。
            if (str_contains($appliedSystem, '新')) {
                $this->drawPdfCircleMark($pdf, 125.5, 64.5 + $rowOffset, 1.8);
            } elseif (str_contains($appliedSystem, '旧')) {
                $this->drawPdfCircleMark($pdf, 129.0, 64.5 + $rowOffset, 1.8);
            }
            $this->writePdfTextRightSized($pdf, 148.0, 64.0 + $rowOffset, $amount, 8, 16);

            // 「(a)のうち新/旧保険料等の合計額」欄(A/B)と、その計算式Ⅰ・Ⅱの中間結果(①②③④)。
            // 区分の合計欄なので1回だけ書けばよい（1行目だけ書く）。
            if ($index === 0) {
                $a = $this->money($nenTyo['shin_seimei_fee'] ?? 0);
                $b = $this->money($nenTyo['kyu_seimei_fee'] ?? 0);
                $calc1 = $this->hokenLifeInsuranceCalc1($a);
                $calc2 = $this->hokenLifeInsuranceCalc2($b);
                $combined = min($calc1 + $calc2, 40000.0);
                $chosen = max($calc2, $combined);

                $this->writePdfTextRightSized($pdf, 56.0, 96.0, (string) ($nenTyo['shin_seimei_fee'] ?? ''), 7, 16); // A
                $this->writePdfTextRightSized($pdf, 56.0, 104.0, (string) ($nenTyo['kyu_seimei_fee'] ?? ''), 7, 16); // B
                $this->writePdfTextRightSized($pdf, 113.0, 96.0, number_format($calc1, 0), 7, 14); // Aを計算式Iに当てはめた額
                $this->writePdfTextRightSized($pdf, 155.0, 96.0, number_format($combined, 0), 7, 14); // 計(①+②)を最高40,000円で頭打ち
                $this->writePdfTextRightSized($pdf, 113.0, 104.0, number_format($calc2, 0), 7, 14); // Bを計算式IIに当てはめた額
                $this->writePdfTextRightSized($pdf, 155.0, 104.0, number_format($chosen, 0), 7, 14); // ②と③のいずれか大きい金額
            }
            return;
        }

        // 介護保険
        if ($section === 'nursing') {
            // 介護医療保険料には新・旧の区分が存在しないため丸印は出さない。
            $rowOffset = $index * 8.0;
            $this->writePdfWrappedTextSized($pdf, 22.0, 111.0 + $rowOffset, $company, 25, 3.0, 2, 7);
            $this->writePdfWrappedTextSized($pdf, 48.0, 111.0 + $rowOffset, $type, 11, 3.0, 2, 7);
            $this->writePdfTextSized($pdf, 63.0, 111.0 + $rowOffset, $period, 6.5, 16);
            $this->writePdfWrappedTextSized($pdf, 75.0, 111.0 + $rowOffset, $contractor, 18, 3.0, 2, 7);
            $this->writePdfWrappedTextSized($pdf, 94.0, 111.0 + $rowOffset, $recipient, 18, 3.0, 2, 7);
            $this->writePdfTextRightSized($pdf, 148.0, 111.0 + $rowOffset, $amount, 8, 16);

            // 「(a)の金額の合計額」欄(C)と、計算式Ⅰの中間結果(⑦)。区分の合計欄なので1行目だけ書く。
            if ($index === 0) {
                $c = $this->money($nenTyo['kaigo_fee'] ?? 0);
                $calc = $this->hokenLifeInsuranceCalc1($c);
                $this->writePdfTextRightSized($pdf, 56.0, 135.0, (string) ($nenTyo['kaigo_fee'] ?? ''), 7, 16); // C
                $this->writePdfTextRightSized($pdf, 155.0, 135.0, number_format($calc, 0), 7, 14); // ⑦
            }
            return;
        }

        // pension（個人年金）
        $rowOffset = $index * 6.0;
        $this->writePdfWrappedTextSized($pdf, 22.0, 141.0 + $rowOffset, $company, 25, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 48.0, 141.0 + $rowOffset, $type, 11, 3.0, 2, 7);
        $this->writePdfTextSized($pdf, 63.0, 141.0 + $rowOffset, $period, 6.5, 16);
        $this->writePdfWrappedTextSized($pdf, 75.0, 141.0 + $rowOffset, $contractor, 18, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 94.0, 141.0 + $rowOffset, $recipient, 18, 3.0, 2, 7);
        // 「新・旧」は左右に印字されているため、Yは行のまま、Xだけ左(新)・右(旧)にずらす。
        if (str_contains($appliedSystem, '新')) {
            $this->drawPdfCircleMark($pdf, 124.0, 141.0 + $rowOffset, 1.8);
        } elseif (str_contains($appliedSystem, '旧')) {
            $this->drawPdfCircleMark($pdf, 128.0, 141.0 + $rowOffset, 1.8);
        }
        $this->writePdfTextRightSized($pdf, 148.0, 141.0 + $rowOffset, $amount, 8, 16);
        $pensionStart = (string) ($row['pension_payment_start_date'] ?? '');
        $this->writePdfTextSized($pdf, 73.0, 145.0 + $rowOffset, $pensionStart, 6.5, 18);

        // 「(a)のうち新/旧保険料等の合計額」欄(D/E)と、計算式Ⅰ・Ⅱの中間結果。1行目だけ書く。
        if ($index === 0) {
            $d = $this->money($nenTyo['shin_nenkin_fee'] ?? 0);
            $e = $this->money($nenTyo['kyu_nenkin_fee'] ?? 0);
            $calc1 = $this->hokenLifeInsuranceCalc1($d);
            $calc2 = $this->hokenLifeInsuranceCalc2($e);
            $combined = min($calc1 + $calc2, 40000.0);
            $chosen = max($calc2, $combined);

            $this->writePdfTextRightSized($pdf, 56.0, 167.0, (string) ($nenTyo['shin_nenkin_fee'] ?? ''), 7, 16); // D
            $this->writePdfTextRightSized($pdf, 56.0, 175.0, (string) ($nenTyo['kyu_nenkin_fee'] ?? ''), 7, 16); // E
            $this->writePdfTextRightSized($pdf, 113.0, 167.0, number_format($calc1, 0), 7, 14); // ④(個人年金)
            $this->writePdfTextRightSized($pdf, 155.0, 167.0, number_format($combined, 0), 7, 14); // ⑥
            $this->writePdfTextRightSized($pdf, 113.0, 175.0, number_format($calc2, 0), 7, 14); // ⑤
            $this->writePdfTextRightSized($pdf, 155.0, 175.0, number_format($chosen, 0), 7, 14); // ⑧
        }
    }

    /**
     * 地震保険料控除：地震／旧長期損害保険の各行を書く。書き方はwriteHokenLifeInsuranceRowと同じ。
     */
    private function writeHokenEarthquakeRow(Fpdi $pdf, array $row, string $section, int $index, array $nenTyo): void
    {
        $company = (string) ($row['insurance_company'] ?? '');
        $type = (string) ($row['insurance_type'] ?? '');
        $period = (string) ($row['insurance_period'] ?? '');
        // 「保険等の対象となった家屋等に居住又は家財を利用している者等の氏名」に対応する専用列が
        // mx_hokenに無いため、いったん beneficiary_name を流用する。
        $residentName = (string) ($row['beneficiary_name'] ?? '');
        $amount = (string) ($row['declared_amount'] ?? '');

        if ($section === 'earthquake') {
            $rowOffset = $index * 7.0;
            $this->writePdfWrappedTextSized($pdf, 172.0, 65.0 + $rowOffset, $company, 20, 3.0, 2, 7);
            $this->writePdfWrappedTextSized($pdf, 190.0, 65.0 + $rowOffset, $type, 16, 3.0, 2, 7);
            $this->writePdfTextSized($pdf, 205.0, 65.0 + $rowOffset, $period, 6.5, 14);
            $this->writePdfWrappedTextSized($pdf, 224.0, 65.0 + $rowOffset, $residentName, 18, 3.0, 2, 7);
            // 区分欄。印字済みの「地震」という文字を囲む欄なので真円ではなく横長の楕円にする。
            $this->drawPdfEllipseMark($pdf, 240.0, 63.5 + $rowOffset, 4.0, 2.0);
            $this->writePdfTextRightSized($pdf, 260.0, 65.0 + $rowOffset, $amount, 8, 16);

            // 「Ⓐのうち地震保険料の金額の合計額」欄(Ⓑ)。区分の合計欄なので1行目だけ書く。
            if ($index === 0) {
                $this->writePdfTextRightSized($pdf, 268.0, 87.0, (string) ($nenTyo['jishin_fee'] ?? ''), 7, 16);
            }
            return;
        }

        // old_long_term（旧長期損害保険）
        $rowOffset = $index * 7.0;
        $this->writePdfWrappedTextSized($pdf, 172.0, 79.0 + $rowOffset, $company, 20, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 190.0, 79.0 + $rowOffset, $type, 16, 3.0, 2, 7);
        $this->writePdfTextSized($pdf, 205.0, 79.0 + $rowOffset, $period, 6.5, 14);
        $this->writePdfWrappedTextSized($pdf, 224.0, 79.0 + $rowOffset, $residentName, 18, 3.0, 2, 7);
        // 区分欄。印字済みの「旧長期」という文字を囲む欄（3文字分なので地震より横幅を広めに）。
        $this->drawPdfEllipseMark($pdf, 245.0, 77.5 + $rowOffset, 5.5, 2.0);
        $this->writePdfTextRightSized($pdf, 260.0, 79.0 + $rowOffset, $amount, 8, 16);

        // 「Ⓐのうち旧長期損害保険料の金額の合計額」欄(Ⓒ)。区分の合計欄なので1行目だけ書く。
        if ($index === 0) {
            $this->writePdfTextRightSized($pdf, 268.0, 88.0, (string) ($nenTyo['kyu_songai_fee'] ?? ''), 7, 16);
        }
    }

    /**
     * 計算式Ⅰ（新保険料等用）。テンプレート下部に印字されている表そのもの。
     * A、C又はDの金額に適用する。1円未満の端数は切り上げ（テンプレートの注記どおり）。
     */
    private function hokenLifeInsuranceCalc1(float $amount): float
    {
        if ($amount <= 20000.0) {
            return $amount;
        }
        if ($amount <= 40000.0) {
            return ceil($amount * 0.5 + 10000.0);
        }
        if ($amount <= 80000.0) {
            return ceil($amount * 0.25 + 20000.0);
        }

        return 40000.0;
    }

    /**
     * 計算式Ⅱ（旧保険料等用）。テンプレート下部に印字されている表そのもの。
     * B又はEの金額に適用する。1円未満の端数は切り上げ（テンプレートの注記どおり）。
     */
    private function hokenLifeInsuranceCalc2(float $amount): float
    {
        if ($amount <= 25000.0) {
            return $amount;
        }
        if ($amount <= 50000.0) {
            return ceil($amount * 0.5 + 12500.0);
        }
        if ($amount <= 100000.0) {
            return ceil($amount * 0.25 + 25000.0);
        }

        return 50000.0;
    }

    private function writeHokenSocialInsuranceRow(Fpdi $pdf, array $row, int $index): void
    {
        $type = (string) ($row['insurance_type'] ?? '');
        $payTo = (string) ($row['insurance_company'] ?? '');
        // 「保険料を負担することになっている人の氏名」に対応する専用列が mx_hoken に無いため、
        // いったん policy_holder_name を流用する。
        $payer = (string) ($row['policy_holder_name'] ?? '');
        $amount = (string) ($row['declared_amount'] ?? '');

        $rowOffset = $index * 7.0;
        $this->writePdfWrappedTextSized($pdf, 172.0, 131.0 + $rowOffset, $type, 20, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 195.0, 131.0 + $rowOffset, $payTo, 25, 3.0, 2, 7);
        $this->writePdfWrappedTextSized($pdf, 225.0, 131.0 + $rowOffset, $payer, 20, 3.0, 2, 7);
        $this->writePdfTextRightSized($pdf, 268.0, 131.0 + $rowOffset, $amount, 7, 20);
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
        $this->writePdfTextRightSized($pdf, 155.0, 193.0, $seimeiTotal, 8, 16);

        // 要確認：地震保険料控除額（mx_nen_tyo.jishun_fee_kou の保存値をそのまま表示）
        $jishunTotal = (string) ($nenTyo['jishun_fee_kou'] ?? '');
        $this->writePdfTextRightSized($pdf, 268.0, 114.0, $jishunTotal, 8, 16);

        // 社会保険料控除欄「合計（控除額）」
        $syahoTotal = (string) ($nenTyo['shin_syaho_fee_kou'] ?? '');
        $this->writePdfTextRightSized($pdf, 268.0, 147.0, $syahoTotal, 7, 20);

        // 小規模企業共済等掛金控除欄「合計（控除額）」
        $kyosaiTotal = (string) ($nenTyo['shun_kigyou_fee_kou'] ?? '');
        $this->writePdfTextRightSized($pdf, 268.0, 197.0, $kyosaiTotal, 7, 20);
    }

    // ==========================================================================
    // 保険料控除申告書 ここまで
    // ==========================================================================

    // ==========================================================================
    // 基礎控除申告書・扶養控除申告書・源泉徴収簿・源泉徴収票 の共通ルーティング
    // (kisoPreview/fuyoPreview/gensenBoPreview/gensenHyouPreview)
    // 4帳票とも同じ templatePreview() → copyPdfTemplateToPreview() を経由する。
    // 各帳票固有の座標書き込みは write*Header()/write*Preview() 側にある
    // （下の writeFuyoHeader からが扶養控除申告書、writeKisoPreview が基礎控除申告書、
    // 　writeGensenHyouPreview が源泉徴収票、writeGensenBoPreview が源泉徴収簿）。
    // ==========================================================================

    public function kisoPreview(int $applicationId)
    {
        return $this->templatePreview($applicationId, 'kiso_koujyo_shinkoku', 'kiso_koujyo_shinkoku');
    }

    public function fuyoPreview(int $applicationId)
    {
        return $this->templatePreview($applicationId, 'fuyo_koujyo_shinkoku', 'fuyo_koujyo_shinkoku');
    }

    /**
     * 対象年の全スタッフ分の指定帳票を、会社ごとにまとめて1つのPDFに連続出力する。
     * report=テンプレートキー（bulkReportOptions()の中から選ぶ）、company_idを指定すれば
     * その会社のスタッフだけに絞る。並び順は会社→スタッフID。
     *
     * 対応帳票を増やすときは、下のmatchに1行追加するだけでよい形にしてある
     * （帳票ごとの座標書き込みはwrite*Preview()側、ここは「誰のページを何枚生成するか」だけを扱う）。
     */
    public function bulkPreview(Request $request)
    {
        $targetYear = (int) $request->query('target_year', date('Y'));
        if ($targetYear < 2000 || $targetYear > 2100) {
            $targetYear = (int) date('Y');
        }
        $companyId = trim((string) $request->query('company_id', ''));
        $templateKey = trim((string) $request->query('report', ''));
        abort_unless(array_key_exists($templateKey, $this->bulkReportOptions()), 404);

        abort_unless(Schema::connection('sqlsrv_payroll')->hasTable('staff_year_end_applications'), 404);

        $applications = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('target_year', $targetYear)
            ->get();

        $targets = [];
        foreach ($applications as $application) {
            $staffId = trim((string) ($application->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staff = $this->staffDetail($staffId);
            $staffCompanyId = trim((string) ($staff['company_id'] ?? ''));
            if ($companyId !== '' && $staffCompanyId !== $companyId) {
                continue;
            }

            $targets[] = [
                'application' => $application,
                'staff_id' => $staffId,
                'staff' => $staff,
                'company_id' => $staffCompanyId,
            ];
        }

        usort(
            $targets,
            static fn(array $a, array $b): int => [$a['company_id'], $a['staff_id']] <=> [$b['company_id'], $b['staff_id']]
        );

        $templatePath = $this->yearEndPdfTemplatePath($targetYear, $templateKey);

        $pdf = new Fpdi('P', 'mm');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        foreach ($targets as $target) {
            // kiso/gensenBo/gensenHyouはnenTyo（mx_nen_tyoの保存値）が要る。fuyoは使わない。
            $nenTyo = $this->nenTyoDetail($target['application'], $target['staff_id'], $targetYear);

            if ($templateKey === 'hoken_koujyo_shinkoku') {
                // 保険料控除申告書だけは他の帳票と違い、テンプレート自体のページ数ではなく
                // 本人の保険料データ量でページ数が変わる（hokenPreview()と同じ理由）。
                // テンプレートの1ページ目を必要な枚数だけ複製する。
                $grouped = $this->groupHokenRows($this->hokenRows($target['staff_id'], $targetYear));
                $hokenPageCount = $this->hokenPageCount($grouped);
                $pdf->setSourceFile($templatePath);
                for ($pageIndex = 0; $pageIndex < $hokenPageCount; $pageIndex++) {
                    $templateId = $pdf->importPage(1);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                    $this->writeHokenPreviewPage($pdf, $targetYear, $target['staff'], $nenTyo, $grouped, $pageIndex);
                }
                continue;
            }

            $pageCount = $pdf->setSourceFile($templatePath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                if ($pageNo === 1) {
                    match ($templateKey) {
                        'fuyo_koujyo_shinkoku' => $this->writeFuyoPreview($pdf, $target['staff_id'], $targetYear, $target['staff']),
                        'kiso_koujyo_shinkoku' => $this->writeKisoPreview($pdf, $nenTyo, $target['staff_id'], $targetYear, $target['staff']),
                        'gensen_tyoushu_bo' => $this->writeGensenBoPreview($pdf, $target['staff_id'], $targetYear, $target['staff'], $nenTyo),
                        'gensen_tyoushu_hyou' => $this->writeGensenHyouPreview($pdf, $target['staff'], $nenTyo, $targetYear),
                    };
                }
            }
        }

        $previewDir = storage_path("app/year_end/previews/{$targetYear}");
        if (!is_dir($previewDir)) {
            mkdir($previewDir, 0775, true);
        }
        $outputPath = $previewDir . "\\{$templateKey}_bulk" . ($companyId !== '' ? "_{$companyId}" : '') . '.pdf';
        $pdf->Output($outputPath, 'F');

        return response()->file($outputPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $templateKey . '_bulk_' . $targetYear . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
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

                // 帳票ごとに write*Preview() が自分の write*Header() を自分で呼ぶ（中継ぎ
                // ディスパッチャは廃止。各帳票で座標を個別に合わせる以上、共通化する意味が
                // なく、かえって「どこでヘッダーを書いているか」を辿りにくくしていたため）。
                if ($templateKey === 'gensen_tyoushu_bo') {
                    $this->writeGensenBoPreview($pdf, $staffId, $targetYear, $staff, $nenTyo);
                }
                if ($templateKey === 'gensen_tyoushu_hyou') {
                    $this->writeGensenHyouPreview($pdf, $staff, $nenTyo, $targetYear);
                }
                if ($templateKey === 'kiso_koujyo_shinkoku') {
                    $this->writeKisoPreview($pdf, $nenTyo, $staffId, $targetYear, $staff);
                }
                if ($templateKey === 'fuyo_koujyo_shinkoku') {
                    $this->writeFuyoPreview($pdf, $staffId, $targetYear, $staff);
                }
            }
        );

        // hokenPreview()と同じ理由でキャッシュを無効化する（座標調整時にブラウザが古いPDFを使い回すのを防ぐ）。
        return response()->file($outputPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $outputKey . '_' . $targetYear . '_' . $staffId . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
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

    // ==========================================================================
    // 扶養控除申告書 (fuyo_koujyo_shinkoku) ここから
    // ==========================================================================

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
        $pdf->SetTextColor(255, 0, 0);

        // 会社欄
        $this->writePdfTextSized($pdf, 62, 15, $companyName, 11, 42);
        $this->writePdfDigitsSized($pdf, 60, 27, $companyNumber, 4.4, 8);
        $this->writePdfWrappedTextSized($pdf, 61, 34, $companyAddress, 58, 4.0, 2, 7);

        // 本人欄
        $this->writePdfTrackedTextSized($pdf, 142, 11.5, $staffFuri, 7, 1.4, 28);
        $this->writePdfTextSized($pdf, 142, 17, $staffName, 9, 28);

        // 本人欄：生年月日の元号などを隠す枠。調整中は色付き、確定後は [255, 255, 255] にする。
        $birthdayEraseColor = [255, 240, 120];
        $staffBirthdayEraseX = 204.5;
        $staffBirthdayEraseY = 12.0;
        $staffBirthdayEraseW = 40.0;
        $staffBirthdayEraseH = 4.4;
        $this->fillPdfRect($pdf, $staffBirthdayEraseX, $staffBirthdayEraseY, $staffBirthdayEraseW, $staffBirthdayEraseH, $birthdayEraseColor);
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
            $this->writePdfTextSized($pdf, 239.0, 33.0, $circle, 12, 4);
        }

        // 本人欄：甲欄
        if (str_contains($taxAmount, '甲')) {
            $this->writePdfTextSized($pdf, 253.0, 35.0, $circle, 11, 4);
        }
    }
    private function writeFuyoPreview(Fpdi $pdf, string $staffId, int $targetYear, array $staff): void
    {
        // 会社・本人欄はこの帳票専用のwriteFuyoHeader()で書く（中継ぎディスパッチャ廃止、
        // writeKisoPreview()と同じ理由）。
        $this->writeFuyoHeader($pdf, $staff);

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

        $pdf->SetTextColor(255, 0, 0);
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

        $pdf->SetTextColor(255, 0, 0);

        // 扶養欄：生年月日の元号などを隠す枠。調整中は色付き、確定後は [255, 255, 255] にする。
        $birthdayEraseColor = [255, 240, 120];

        if ($section === 'spouse') {
            // A欄：源泉控除対象配偶者
            $rowOffset = $index * 11.4;
            $this->writePdfTrackedTextSized($pdf, 40.0, 58.5 + $rowOffset, $furi, 7, 1.4, 24);
            $this->writePdfTextSized($pdf, 42.0, 65.0 + $rowOffset, $name, 8, 24);
            $this->writePdfDigitsSized($pdf, 72.0, 60.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.45, 8);
            $this->writePdfTextSized($pdf, 83.0, 63.0 + $rowOffset, $relationshipLabel, 8, 10);

            // A欄：生年月日の元号などを隠す枠
            $this->fillPdfRect($pdf, 90, 64.5 + $rowOffset, 31.0, 5.0, $birthdayEraseColor);
            $this->writePdfTextSized($pdf, 95.0, 65.0 + $rowOffset, $this->formatPdfJapaneseDate($row['fuyo_birthday'] ?? null), 8, 18);

            $this->writePdfTextRightSized($pdf, 160.0, 63.0 + $rowOffset, $income, 7, 16);

            $this->writePdfWrappedTextSized($pdf, 203.0, 60.0 + $rowOffset, $address, 28, 3.7, 2, 7);
            return;
        }

        if ($section === 'under16') {
            // 16歳未満の扶養親族欄
            $rowOffset = $index * 11.7;
            $this->writePdfTrackedTextSized($pdf, 39.0, 178.5 + $rowOffset, $furi, 7, 1.4, 24);
            $this->writePdfTextSized($pdf, 42.0, 182.0 + $rowOffset, $name, 8, 24);
            $this->writePdfDigitsSized($pdf, 69.0, 181.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.3, 8);
            $this->writePdfTextSized($pdf, 121.0, 181.0 + $rowOffset, $relationshipLabel, 7, 10);

            // 16歳未満欄：生年月日の元号などを隠す枠
            $this->fillPdfRect($pdf, 128, 179.5 + $rowOffset, 17.0, 5.4, $birthdayEraseColor);
            $this->writePdfTextSized($pdf, 128.0, 181.0 + $rowOffset, $this->formatPdfJapaneseDateShort($row['fuyo_birthday'] ?? null), 8, 18);

            $this->writePdfTextRightSized($pdf, 235.0, 181.0 + $rowOffset, $income, 7, 16);
            $this->writePdfWrappedTextSized($pdf, 146.0, 179.0 + $rowOffset, $address, 38, 3.7, 2, 7);
            return;
        }

        // B欄：控除対象扶養親族（16歳以上）
        $rowOffset = $index * 14.1;
        $this->writePdfTrackedTextSized($pdf, 40.0, 70.5 + $rowOffset, $furi, 7, 1.4, 24);
        $this->writePdfTextSized($pdf, 42.0, 78.0 + $rowOffset, $name, 8, 24);
        $this->writePdfDigitsSized($pdf, 72.0, 74.0 + $rowOffset, (string) ($row['fuyo_my_number'] ?? ''), 4.45, 8);
        $this->writePdfTextSized($pdf, 78.0, 80.0 + $rowOffset, $relationshipLabel, 8, 10);

        // B欄：生年月日の元号などを隠す枠
        $this->fillPdfRect($pdf, 90, 78.5 + $rowOffset, 31.0, 5.0, $birthdayEraseColor);
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

        $pdf->SetTextColor(255, 0, 0);

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

    // ==========================================================================
    // 扶養控除申告書 ここまで／基礎控除申告書 (kiso_koujyo_shinkoku) ここから
    // ==========================================================================

    /**
     * 基礎控除申告書：年・整理番号・あなたの氏名・給与の支払者名（左上の共通ヘッダー部分）。
     * 以前は4帳票共通のwriteYearEndTemplateHeader()内の配列にまとめられていて見つけにくかったため、
     * 他の帳票（writeFuyoHeader等）と同じく、この帳票専用の関数として分離している。
     */
    private function writeKisoHeader(Fpdi $pdf, string $staffId, int $targetYear, array $staff, string $yearEnd): void
    {
        // 色は帳票全体で1箇所（ここ）だけで決める。確定後にこの数値を黒等へ変えれば全体に反映される。
        $pdf->SetTextColor(255, 0, 0);

        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $staffFuri = trim((string) ($staff['staff_name_furi'] ?? ''));
        $address = trim((string) ($staff['address'] ?? ''));
        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));
        $companyNumber = trim((string) ($staff['corporate_number'] ?? ''));
        $companyAddress = trim((string) ($staff['company_address'] ?? ''));

        // $this->writePdfText($pdf, 18, 14, "{$targetYear}年");
        // $this->writePdfText($pdf, 42, 14, $staffId);

        // 給与の支払者欄（他の帳票と同じく、文字詰めは使わずwritePdfTextSizedの自然な文字送りに統一）
        $this->writePdfTextSized($pdf, 67, 14, $companyName, 11, 36);
        $this->writePdfDigitsSized($pdf, 66, 22, $companyNumber, 4.6, 10);
        $this->writePdfWrappedTextSized($pdf, 67, 29, $companyAddress, 55, 3.5, 2, 8);

        // あなた（本人）欄
        $this->writePdfTrackedTextSized($pdf, 147, 14, $staffFuri, 7, 1.2, 20);
        $this->writePdfTextSized($pdf, 147, 20, $staffName, 11, 36);
        $this->writePdfWrappedTextSized($pdf, 147, 28, $address, 55, 3.5, 2, 8);

        // if ($yearEnd !== '') {
        //     $this->writePdfText($pdf, 122, 19, $yearEnd, 18);
        // }
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
    private function writeKisoPreview(Fpdi $pdf, array $nenTyo, string $staffId, int $targetYear, array $staff): void
    {
        // 会社・本人欄はこの帳票専用のwriteKisoHeader()で書く（以前は全帳票共通の
        // ディスパッチャ経由で呼ばれていたが、実質どの帳票も専用関数に丸投げするだけの
        // 中継ぎで、帳票ごとに座標を合わせる必要がある以上ここに共通化する意味がなかった
        // ため廃止し、各帳票が自分のwrite*Preview()から直接自分のヘッダーを呼ぶ形に統一した）。
        $this->writeKisoHeader($pdf, $staffId, $targetYear, $staff, trim((string) ($nenTyo['year_end'] ?? '')));

        // 色はここ1箇所だけで決める（writeKisoHeaderと共通の値）。関数の途中で変えない。
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('kozminproregular', '', 8);

        // 基礎控除申告書（左パネル）。各行フォントサイズを個別に指定できるようwritePdfTextRightSizedを使う。
        $this->writePdfTextRightSized($pdf, 70, 52, (string) ($nenTyo['bonus_kyuyo_sum'] ?? ''), 8, 22); // ✔確認済み：(1)給与所得 の 収入金額
        $this->writePdfTextRightSized($pdf, 100, 52, (string) ($nenTyo['shotoku_deduction'] ?? ''), 8, 22); // 要確認：(1)給与所得 の 所得金額
        $this->writePdfTextRightSized($pdf, 100, 70, (string) ($nenTyo['shotoku_deduction'] ?? ''), 8, 22); // 要確認：あなたの本年中の合計所得金額の見積額
        $this->writePdfTextRightSized($pdf, 98, 86, $this->kisoBunruiDisplaySymbol((string) ($nenTyo['kiso_bunrui'] ?? ''), $targetYear), 9, 22); // ✔確認済み：区分Ⅰ「（左のA〜Cを記載）」枠
        $this->writePdfTextRightSized($pdf, 103, 104, (string) ($nenTyo['kiso_koujyo'] ?? ''), 8, 22); // 要確認：基礎控除の額

        // 配偶者控除等申告書（右パネル）
        // どちらもhaigu_shotoku（本人申告値）を使う。実運用では配偶者に他の所得があっても
        // 合算した金額をhaigu_shotokuへ直接手入力しているため、給与所得の所得金額欄と
        // 合計所得金額の見積額欄は同じ値でよい（2026年8月、ユーザー確認済み）。
        $this->writePdfTextRightSized($pdf, 190, 75, (string) ($nenTyo['haigu_shotoku'] ?? ''), 8, 22); // ✔確認済み：配偶者の(1)給与所得 の 所得金額
        $this->writePdfTextRightSized($pdf, 190, 90, (string) ($nenTyo['haigu_shotoku'] ?? ''), 8, 22); // ✔確認済み：配偶者の本年中の合計所得金額の見積額
        $this->writePdfTextRightSized($pdf, 272.5, 87, $this->haiguBunruiDisplaySymbol((string) ($nenTyo['haigu_bunrui'] ?? ''), $targetYear), 8, 22); // ✔確認済み：区分Ⅱ「①②③を記載」枠
        $this->writePdfTextRightSized($pdf, 265, 105, (string) ($nenTyo['haigu_deduction'] ?? ''), 8, 22); // ✔確認済み：配偶者控除の額
        $this->writePdfTextRightSized($pdf, 265, 116, (string) ($nenTyo['haigu_toku_deduction'] ?? ''), 8, 22); // ✔確認済み：配偶者特別控除の額

        // 所得金額調整控除申告書（下部）。ここは金額欄ではなく、適用の条件となった扶養家族の
        // 氏名・フリガナ・生年月日・マイナンバー・住所・所得金額を記載する欄。
        // 要確認：tyosei_koujyo_select（"扶養親族が23歳未満"等の長い説明文）を、幅22mmの
        // 小さい枠にそのまま流し込んでいたのが直近の「データ相違」の原因だったため削除。
        // 本来ここは適用理由（本人が特別障害者／同一生計配偶者が特別障害者／扶養親族が23歳未満）
        // のチェックマーク欄のはずだが、実物のどの位置にどの記号を書くか未確認のため保留。
        // $this->writePdfTextRightSized($pdf, 184, 184, (string) ($nenTyo['tyosei_koujyo'] ?? ''), 8, 22); // ✔確認済み：所得金額調整控除額

        // 要確認：適用の条件となった家族の特定。実データで確認できた区分は「扶養親族が23歳未満」の
        // みのため、現時点ではその場合（対象年12/31時点で23歳未満・控除対象の扶養親族）のみ対応。
        // 他の区分（本人が特別障害者／同一生計配偶者が特別障害者 等）は実データが無く未確認のため
        // 対象者の特定ロジックを追加していない。
        $adjustmentTargetRow = null;
        if (trim((string) ($nenTyo['tyosei_koujyo_select'] ?? '')) === '扶養親族が23歳未満') {
            foreach ($this->fuyoRows($staffId, $targetYear) as $row) {
                $relationship = trim((string) ($row['fuyo_relationship'] ?? ''));
                if (in_array($relationship, ['夫', '妻', '配偶者'], true)) {
                    continue;
                }
                if ((int) ($row['deduction_target'] ?? 0) !== 1) {
                    continue;
                }
                if ($this->calculationService->ageAtYearEnd($row['fuyo_birthday'] ?? null, $targetYear) < 23) {
                    $adjustmentTargetRow = $row;
                    break;
                }
            }
        }

        if ($adjustmentTargetRow !== null) {
            // 要確認：座標は仮置き。氏名・フリガナ・マイナンバー・住所・所得金額（収入金額）。
            $this->writePdfTrackedTextSized($pdf, 115.0, 190.0, (string) ($adjustmentTargetRow['fuyo_name_furi'] ?? ''), 7, 1.2, 20);
            $this->writePdfTextSized($pdf, 112.0, 194.0, (string) ($adjustmentTargetRow['fuyo_name'] ?? ''), 10, 24);
            $this->writePdfDigitsSized($pdf, 140.0, 185.0, (string) ($adjustmentTargetRow['fuyo_my_number'] ?? ''), 4.3, 10);
            $this->writePdfWrappedTextSized($pdf, 140.0, 195.0, (string) ($adjustmentTargetRow['fuyo_address'] ?? ''), 50, 3.5, 2, 7);
            $this->writePdfTextRightSized($pdf, 220.0, 196.0, $this->pdfNumber($adjustmentTargetRow['fuyo_shunyu'] ?? 0), 8, 22);

            // 生年月日：他の家族欄と同じく、印字済みの元号チェック欄が透けて見えないよう
            // 一旦マスク枠を敷いてから日付を書く（塗り色・枠サイズは仮置き）。
            $this->fillPdfRect($pdf, 192.0, 184.5, 40.0, 5.0, [255, 240, 120]);
            $this->writePdfTextSized($pdf, 194.0, 185.0, $this->formatPdfJapaneseDate($adjustmentTargetRow['fuyo_birthday'] ?? null), 8, 20);
        }

        // 要確認：配偶者控除等申告書（右パネル）に配偶者の氏名・フリガナ・個人番号・生年月日が
        // 一切書かれていなかった（金額欄だけ実装されていた）ため追加。mx_fuyoの続柄が
        // 夫/妻/配偶者の行を、扶養控除申告書(writeFuyoPreview)の配偶者判定と同じ条件で取得する。
        // 座標は仮置きなので実帳票を見ながら調整してください。
        $spouseRow = null;
        foreach ($this->fuyoRows($staffId, $targetYear) as $row) {
            if (in_array(trim((string) ($row['fuyo_relationship'] ?? '')), ['夫', '妻', '配偶者'], true)) {
                $spouseRow = $row;
                break;
            }
        }

        if ($spouseRow !== null) {
            $this->writePdfTrackedTextSized($pdf, 128.0, 53.0, (string) ($spouseRow['fuyo_name_furi'] ?? ''), 7, 1.2, 20);
            $this->writePdfTextSized($pdf, 128.0, 58.0, (string) ($spouseRow['fuyo_name'] ?? ''), 10, 24);
            $this->writePdfDigitsSized($pdf, 163.0, 48.0, (string) ($spouseRow['fuyo_my_number'] ?? ''), 4.3, 10);

            // 生年月日：扶養控除申告書と同じく、印字済みの元号チェック欄が透けて見えないよう
            // 一旦マスク枠を敷いてから日付を書く。fillPdfRectは塗りつぶし色しか変えないので
            // 文字色を戻す必要はない（関数先頭で設定した色のまま）。
            // 塗り色・枠サイズは仮置きなので調整してください。
            $this->fillPdfRect($pdf, 216.0, 47.5, 58.0, 5.0, [255, 240, 120]);
            $this->writePdfTextSized($pdf, 220.0, 48.0, $this->formatPdfJapaneseDate($spouseRow['fuyo_birthday'] ?? null), 8, 20);
        }
    }

    /**
     * mx_nen_tyo.kiso_bunrui に保存されている説明文（例:「655万円超900万円以下」、
     * 旧Access運用に合わせた細かい所得区分そのもの）を、帳票の枠に印字するA/B/Cの
     * 記号に変換する。保存値は細かい区分のまま変えない。年度によって細かい区分の
     * 行数が変わるため、文字列を直接パースせずServiceのkisoBunruiSymbolForLabel()で
     * その年の表と突き合わせて求める。
     */
    private function kisoBunruiDisplaySymbol(string $label, int $targetYear): string
    {
        return $this->calculationService->kisoBunruiSymbolForLabel($label, $targetYear);
    }

    /**
     * 配偶者控除等申告書「区分Ⅱ」用。mx_nen_tyo.haigu_bunruiに保存されている説明文
     * （区分Ⅰと全く同じ境界の細かい所得区分）を、①②③の記号に変換する。
     * 保存値はそのまま変えない（kisoBunruiDisplaySymbol()と同じ理由）。
     */
    private function haiguBunruiDisplaySymbol(string $label, int $targetYear): string
    {
        return match ($this->calculationService->kisoBunruiSymbolForLabel($label, $targetYear)) {
            'A' => '①',
            'B' => '②',
            'C' => '③',
            default => '',
        };
    }

    // ==========================================================================
    // 基礎控除申告書 ここまで／源泉徴収票 (gensen_tyoushu_hyou) ここから
    // ==========================================================================

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
    private function writeGensenHyouPreview(Fpdi $pdf, array $staff, array $nenTyo, int $targetYear): void
    {
        // 色は帳票全体で1箇所（ここ）だけで決める。確定後にこの数値を黒等へ変えれば全体に
        // 反映される（以前はここが中継ぎディスパッチャ側の青固定(0,0,180)、ここから下が
        // 別の色、と2箇所に分かれていた）。
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('kozminproregular', '', 8);

        // 表題「令和◯年分」（中継ぎディスパッチャ廃止、writeKisoPreview()と同じ理由でここに移動）。
        $this->writePdfText($pdf, 27, 7, "{$targetYear}年分");

        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $address = trim((string) ($staff['address'] ?? $staff['staff_address'] ?? $staff['add'] ?? ''));
        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));

        // ✔確認済み：支払を受ける者 住所又は居所／氏名
        $this->writePdfText($pdf, 30, 16, $address, 60);
        $this->writePdfText($pdf, 30, 27, $staffName, 30);

        // ✔確認済み：支払金額｜給与所得控除後の金額（調整控除後）｜所得控除の額の合計額｜源泉徴収税額
        $this->writePdfTextRightSized($pdf, 60, 38, (string) ($nenTyo['bonus_kyuyo_sum'] ?? ''), 8, 16);
        $this->writePdfTextRightSized($pdf, 95, 38, (string) ($nenTyo['shotoku_deduction'] ?? ''), 8, 16);
        $this->writePdfTextRightSized($pdf, 125, 38, (string) ($nenTyo['shotoku_deduction_sum'] ?? ''), 8, 16);
        $this->writePdfTextRightSized($pdf, 143, 38, (string) ($nenTyo['nentyo_nen_tax'] ?? ''), 8, 16);

        // ✔確認済み：社会保険料等の金額｜生命保険料の控除額｜地震保険料の控除額｜住宅借入金等特別控除の額
        $this->writePdfTextRightSized($pdf, 68, 68, (string) ($nenTyo['kyu_syaho_fee_kou'] ?? ''), 8, 16);
        $this->writePdfTextRightSized($pdf, 93, 68, (string) ($nenTyo['seimei_fee_kou'] ?? ''), 8, 16);
        $this->writePdfTextRightSized($pdf, 118, 68, (string) ($nenTyo['jishun_fee_kou'] ?? ''), 8, 16);
        $this->writePdfTextRightSized($pdf, 145, 68, (string) ($nenTyo['jyu_kari_kou'] ?? ''), 8, 16);

        // 要確認：控除対象扶養親族等の数（老人・特定・その他・障害者など）のサブ列。
        // 行はy≈50に修正したが、各サブ列の幅は概算のため実帳票で列を確認してください。
        $this->writePdfTextRightSized($pdf, 22, 50, (string) ($nenTyo['haigu_umu'] ?? ''), 8, 6);
        $this->writePdfTextRightSized($pdf, 38, 50, (string) ($nenTyo['toku_fu'] ?? ''), 8, 6);
        $this->writePdfTextRightSized($pdf, 46, 50, (string) ($nenTyo['rou_dou'] ?? ''), 8, 6);
        $this->writePdfTextRightSized($pdf, 58, 50, (string) ($nenTyo['rou_dou_gai'] ?? ''), 8, 6);
        $this->writePdfTextRightSized($pdf, 76, 50, (string) ($nenTyo['fuyo_ta'] ?? ''), 8, 6);
        $this->writePdfTextRightSized($pdf, 136, 50, (string) ($nenTyo['dependent_under_16'] ?? ''), 8, 6);
        $this->writePdfTextRightSized($pdf, 112, 50, (string) ($nenTyo['shougai_ta'] ?? ''), 8, 6);

        // ✔確認済み：支払者（会社）欄 氏名又は名称
        $this->writePdfText($pdf, 45, 200, $companyName, 55);
    }

    // ==========================================================================
    // 源泉徴収票 ここまで／源泉徴収簿 (gensen_tyoushu_bo) ここから
    // ==========================================================================

    /**
     * 源泉徴収簿：会社（支払者）欄・本人欄（左上の共通ヘッダー部分）。
     * 他の帳票（writeKisoHeader等）と同じく、この帳票専用の関数として分離している。
     * 座標は他帳票からの類推の仮置きなので実帳票で確認してください（要確認）。
     */
    private function writeGensenBoHeader(Fpdi $pdf, string $staffId, array $staff): void
    {
        // 要確認：会社名ではなく部署名（本人の所属店舗名）を書く欄だったため、
        // mx_stores.store_name を使う（staffDetail()側でsection→store_codeの
        // 紐付けから取得済み）。
        $departmentName = trim((string) ($staff['store_name'] ?? ''));
        $staffName = trim((string) ($staff['staff_name'] ?? ''));
        $staffFuri = trim((string) ($staff['staff_name_furi'] ?? ''));
        $birthday = $this->formatPdfJapaneseDate($staff['birthday'] ?? null);
        $address = trim((string) ($staff['address'] ?? ''));
        $postNum = preg_replace('/\D+/', '', trim((string) ($staff['post_num'] ?? ''))) ?? '';
        if ($postNum !== '') {
            $postNum = str_pad($postNum, 7, '0', STR_PAD_LEFT);
        }

        // 要確認：整理番号（スタッフID）｜部署名｜あなた（本人）氏名｜フリガナ｜
        // 生年月日｜郵便番号｜住所
        $this->writePdfTextSized($pdf, 270, 9, $staffId, 10);
        // 部署名は半角カタカナ（濁点付き）と全角漢字・ひらがなが混在する（例：「ひなた鍼灸ﾏｯｻｰｼﾞ」）。
        // 半角濁点だけ自然な文字送りだと浮いて見える問題は他の帳票のフリガナ欄と同じ原因なので、
        // ここもwritePdfTrackedTextSizedで統一する（漢字部分もピッチ固定になるが、混在文字列を
        // 自然送りと使い分けるより一貫している）。ピッチは仮置きなので調整してください。
        $this->writePdfTrackedTextSized($pdf, 62, 12, $departmentName, 8, 2.6, 40);
        $this->writePdfTextSized($pdf, 205, 9, $staffName, 9, 30);
        $this->writePdfTrackedTextSized($pdf, 205, 6.5, $staffFuri, 6, 1.2, 20);

        // 生年月日：他の帳票と同じく、印字済みの元号チェック欄が透けて見えないよう
        // 一旦マスク枠を敷いてから日付を書く。塗り色・枠サイズは仮置き。
        $this->fillPdfRect($pdf, 205.0, 12.9, 41.3, 3.3, [255, 240, 120]);
        $this->writePdfTextSized($pdf, 205, 13, $birthday, 8, 30);
        // 郵便番号：扶養控除申告書と同じく上3桁／下4桁で別の枠に分けて書く（文字詰めは不要、
        // 数字は半角カタカナの濁点のような浮きが出ないのでwritePdfTextSizedの自然送りでよい）。
        if ($postNum !== '') {
            $this->writePdfTextSized($pdf, 108, 7, substr($postNum, 0, 3), 7, 6);
            $this->writePdfTextSized($pdf, 120, 7, substr($postNum, 3), 7, 8);
        }
        $this->writePdfWrappedTextSized($pdf, 108, 12, $address, 55, 3.5, 2, 8);
    }

    private function writeGensenBoPreview(Fpdi $pdf, string $staffId, int $targetYear, array $staff, array $nenTyo): void
    {
        // 色は帳票全体で1箇所（ここ）だけで決める。確定後にこの数値を黒等へ変えれば全体に反映される。
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('kozminproregular', '', 7);

        $this->writeGensenBoHeader($pdf, $staffId, $staff);

        $companyName = trim((string) ($staff['company_name'] ?? $staff['company'] ?? ''));
        $payrollRows = $this->yearEndPayrollLedgerRows($staffId, $targetYear, $companyName);
        $salaryRows = array_values(array_filter($payrollRows, static fn(array $row): bool => !$row['is_bonus']));
        $bonusRows = array_values(array_filter($payrollRows, static fn(array $row): bool => $row['is_bonus']));

        // 要確認：給与欄（月ごと）。賞与とはブロックを分けて、別の行範囲に印字する。
        // 各行フォントサイズを個別に指定できるようwritePdfTextRightSized等のSized系に統一する。
        $y = 30.0;
        foreach (array_slice($salaryRows, 0, 12) as $row) {
            $this->writePdfTextRightSized($pdf, 36, $y, $row['month'], 7, 16);
            $this->writePdfTextRightSized($pdf, 109, $y, $row['fuyo_sum'], 7, 10);
            $this->writePdfTextRightSized($pdf, 58, $y, $row['taxation_sum'], 7, 20);
            $this->writePdfTextRightSized($pdf, 78, $y, $row['syaho_sum'], 7, 20);
            $this->writePdfTextRightSized($pdf, 99, $y, $row['syaho_deduction_sum'], 7, 20);
            $this->writePdfTextRightSized($pdf, 127, $y, $row['income_tax'], 7, 18);
            $this->writePdfTextRightSized($pdf, 157, $y, $row['income_tax'], 7, 18);
            // 要確認：年調過不足（実際に差額が発生し、この月の給与で加減した額）。座標は仮置き。
            $this->writePdfTextRightSized($pdf, 145, $y + 6, $row['adjustment_year_end'], 7, 18);
            $y += 10.4;
        }

        // 要確認：給与欄の合計行。実データが12月分まで揃っていないと表内のループのYがずれるため、
        // ループの$yに連動させず固定座標にする（合計欄は帳票上の位置が決まっているはず）。
        $salaryTotalY = 157.8;
        $this->writePdfTextRightSized($pdf, 60, $salaryTotalY, $this->sumPdfRowValues($salaryRows, 'taxation_sum'), 7, 20);
        $this->writePdfTextRightSized($pdf, 78, $salaryTotalY, $this->sumPdfRowValues($salaryRows, 'syaho_sum'), 7, 20);
        $this->writePdfTextRightSized($pdf, 99, $salaryTotalY, $this->sumPdfRowValues($salaryRows, 'syaho_deduction_sum'), 7, 20);
        $this->writePdfTextRightSized($pdf, 127, $salaryTotalY, $this->sumPdfRowValues($salaryRows, 'income_tax'), 7, 18);
        $this->writePdfTextRightSized($pdf, 157, $salaryTotalY, $this->sumPdfRowValues($salaryRows, 'income_tax'), 7, 18);

        // 要確認：賞与欄（給与ブロックとは別枠）。
        $y = 166.0;
        foreach (array_slice($bonusRows, 0, 4) as $row) {
            $this->writePdfTextRightSized($pdf, 36, $y, $row['month'], 7, 16);
            // $this->writePdfTextRightSized($pdf, 67, $y, $row['bonus_amount'], 7, 18);
            $this->writePdfTextRightSized($pdf, 58, $y, $row['taxation_sum'], 7, 20);
            $this->writePdfTextRightSized($pdf, 78, $y, $row['syaho_sum'], 7, 20);
            $this->writePdfTextRightSized($pdf, 99, $y, $row['syaho_deduction_sum'], 7, 20);
            $this->writePdfTextRightSized($pdf, 127, $y, $row['income_tax'], 7, 18);
            $this->writePdfTextRightSized($pdf, 157, $y, $row['income_tax'], 7, 18);
            // 要確認：賞与率欄（mx_kyuyo_shou.bonus_taxは列名に反して税率が入っている）。座標は仮置き。
            $this->writePdfTextRightSized($pdf, 127, $y - 3.5, $row['bonus_tax'], 6, 18);
            $y += 8.3;
        }

        // 要確認：賞与欄の合計行。給与欄と同じ理由でループの$yに連動させず固定座標にする。
        $bonusTotalY = 197.2;
        $this->writePdfTextRightSized($pdf, 58, $bonusTotalY, $this->sumPdfRowValues($bonusRows, 'taxation_sum'), 7, 20);
        $this->writePdfTextRightSized($pdf, 78, $bonusTotalY, $this->sumPdfRowValues($bonusRows, 'syaho_sum'), 7, 20);
        $this->writePdfTextRightSized($pdf, 99, $bonusTotalY, $this->sumPdfRowValues($bonusRows, 'syaho_deduction_sum'), 7, 20);
        $this->writePdfTextRightSized($pdf, 127, $bonusTotalY, $this->sumPdfRowValues($bonusRows, 'income_tax'), 7, 18);
        $this->writePdfTextRightSized($pdf, 157, $bonusTotalY, $this->sumPdfRowValues($bonusRows, 'income_tax'), 7, 18);

        // 要確認：税額表の甲欄／乙欄。tax_amountで判定し、該当しない方を「*****」で消す。
        // 座標は仮置き。
        $taxAmount = trim((string) ($nenTyo['tax_amount'] ?? ''));
        if ($taxAmount === '甲欄') {
            $this->writePdfTextSized($pdf, 7, 11.5, '****', 9);
        } elseif ($taxAmount === '乙欄') {
            $this->writePdfTextSized($pdf, 7, 10, '****', 9);
        }

        // 要確認：帳票の指示通り、印字済みの「有」「無」のどちらかを〇で囲む（楕円ではなく真円、
        // drawPdfCircleMark）。座標は仮置き（三項演算子の1つ目=「有」の位置、2つ目=「無」の位置。
        // 同じY上に並んでいる前提）。3つとも同じ形（判定→bool変数→drawPdfCircleMark）に揃える。
        // ・申告の有無：甲欄なら有（tax_amountで判定、上の甲乙判定と同じ考え方）
        $shinkokuAri = $taxAmount === '甲欄';
        $this->drawPdfCircleMark($pdf, $shinkokuAri ? 171.0 : 80.0, 52.0, 1.4);
        // ・控除対象配偶者の有無：配偶者控除（配偶者特別控除は含まない）があれば有
        $haiguKoujoAri = $this->money($nenTyo['haigu_deduction'] ?? 0) > 0;
        $this->drawPdfCircleMark($pdf, $haiguKoujoAri ? 180.0 : 189.0, 52.0, 1.3);
        // ・所得控除の有無：所得金額調整控除があれば有
        $shotokuKoujoAri = $this->money($nenTyo['tyosei_koujyo'] ?? 0) > 0;
        $this->drawPdfCircleMark($pdf, $shotokuKoujoAri ? 280.0 : 275.5, 88.0, 1.5);

        $this->writeGensenBoNenTyoPanel($pdf, $nenTyo);
    }

    /** 表示用にpdfNumber()で整形済みの行データ配列から、指定キーの合計を計算して整形して返す。 */
    private function sumPdfRowValues(array $rows, string $key): string
    {
        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += $this->money($row[$key] ?? 0);
        }

        return $this->pdfNumber($sum);
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
        // 色・フォントはwriteGensenBoPreview()側で1箇所だけ設定済み（呼び出し元）。
        // ここは以前の青色(0,0,180)時代の残骸のSetTextColorがコメントアウトのまま残っていて、
        // 直後のSetFontも二重指定になっていたため削除した。

        // 要確認：控除対象扶養親族の数等（右上）。gensenHyouの同じ欄と同じmx_nen_tyo列を使う。
        // 座標は仮置き。
        $this->writePdfTextRightSized($pdf, 289, 53, (string) ($nenTyo['haigu_umu'] ?? ''), 8, 6); //配偶者有無
        $this->writePdfTextRightSized($pdf, 206.5, 51, (string) ($nenTyo['toku_fu'] ?? ''), 6, 6); //特定扶養
        $this->writePdfTextRightSized($pdf, 214, 51, (string) ($nenTyo['rou_dou'] ?? ''), 6, 6); //老人同居
        $this->writePdfTextRightSized($pdf, 222, 51, (string) ($nenTyo['rou_dou_gai'] ?? ''), 6, 6); //老人別居
        $this->writePdfTextRightSized($pdf, 199, 51, (string) ($nenTyo['fuyo_ta'] ?? ''), 6, 6); //一般扶養
        // $this->writePdfTextRightSized($pdf, 240, 51, (string) ($nenTyo['dependent_under_16'] ?? ''), 6, 6); //16歳未満（R7の帳票にはなくなってる）
        $this->writePdfTextRightSized($pdf, 248, 51, (string) ($nenTyo['shougai_dou_toku'] ?? ''), 6, 6); //特別障害同居
        $this->writePdfTextRightSized($pdf, 240, 51, (string) ($nenTyo['shougai_toku'] ?? ''), 6, 6); //特別障害
        $this->writePdfTextRightSized($pdf, 231, 51, (string) ($nenTyo['shougai_ta'] ?? ''), 6, 6); //一般障害

        // 要確認：上の人数×単価の控除額（正本はYearEndCalculationService::dependentDeductionRates()）。
        // 万円単位で「63」のように表示する（63万円のつもりで630000とは書かない）。
        // 各人数欄のすぐ下に並べる想定で座標は仮置き。dependent_under_16とhaigu_umuは単価が無いので対象外。
        $dependentRates = $this->calculationService->dependentDeductionRates();
        $this->writePdfTextRightSized($pdf, 207, 59, $this->pdfManNumber(((int) ($nenTyo['toku_fu'] ?? 0)) * $dependentRates['toku_fu']), 7, 10);
        $this->writePdfTextRightSized($pdf, 214, 59, $this->pdfManNumber(((int) ($nenTyo['rou_dou'] ?? 0)) * $dependentRates['rou_dou']), 7, 10);
        $this->writePdfTextRightSized($pdf, 222, 59, $this->pdfManNumber(((int) ($nenTyo['rou_dou_gai'] ?? 0)) * $dependentRates['rou_dou_gai']), 7, 10);
        $this->writePdfTextRightSized($pdf, 199, 59, $this->pdfManNumber(((int) ($nenTyo['fuyo_ta'] ?? 0)) * $dependentRates['fuyo_ta']), 7, 10);
        $this->writePdfTextRightSized($pdf, 248, 59, $this->pdfManNumber(((int) ($nenTyo['shougai_dou_toku'] ?? 0)) * $dependentRates['shougai_dou_toku']), 7, 10);
        $this->writePdfTextRightSized($pdf, 240, 59, $this->pdfManNumber(((int) ($nenTyo['shougai_toku'] ?? 0)) * $dependentRates['shougai_toku']), 7, 10);
        $this->writePdfTextRightSized($pdf, 231, 59, $this->pdfManNumber(((int) ($nenTyo['shougai_ta'] ?? 0)) * $dependentRates['shougai_ta']), 7, 10);

        // ✔確認済み：給料・手当等｜賞与等｜計（各行：金額／税額）
        $this->writePdfTextRightSized($pdf, 245, 69, (string) ($nenTyo['kyuyo_teate_sum'] ?? ''), 7, 20); //[1]
        $this->writePdfTextRightSized($pdf, 278, 69, (string) ($nenTyo['kyuyo_teate_tax'] ?? ''), 7, 16); //[3]
        $this->writePdfTextRightSized($pdf, 245, 73, (string) ($nenTyo['bonus_etc'] ?? ''), 7, 20); //[4]
        $this->writePdfTextRightSized($pdf, 278, 73, (string) ($nenTyo['bonus_tax'] ?? ''), 7, 16); //[6]
        $this->writePdfTextRightSized($pdf, 245, 78, (string) ($nenTyo['bonus_kyuyo_sum'] ?? ''), 7, 20); //[7]
        $this->writePdfTextRightSized($pdf, 278, 78, (string) ($nenTyo['siharai_shotoku'] ?? ''), 7, 16); //[8]

        // 要確認：①③④⑥⑦⑧の2段目＝前職分の欄。前職の会社名・退職日・所得・社会保険料・
        // 給与税・賞与税。座標は仮置き。
        $this->writePdfTextSized($pdf, 245, 75.5, (string) ($nenTyo['zen_syamei'] ?? ''), 6, 30);
        $this->writePdfTextSized($pdf, 245, 79.5, $this->formatPdfJapaneseDate($nenTyo['zen_tai_date'] ?? null), 6, 30);
        $this->writePdfTextRightSized($pdf, 200, 75.5, (string) ($nenTyo['zen_shotoku'] ?? ''), 6, 16);
        $this->writePdfTextRightSized($pdf, 200, 79.5, (string) ($nenTyo['zen_syaho_kou'] ?? ''), 6, 16);
        $this->writePdfTextRightSized($pdf, 220, 75.5, (string) ($nenTyo['zen_kyuyo_tax'] ?? ''), 6, 16);
        $this->writePdfTextRightSized($pdf, 220, 79.5, (string) ($nenTyo['zen_bonus_tax'] ?? ''), 6, 16);

        // 要確認：給与所得控除後の給与等の金額（①④⑦と同じ列）｜所得金額調整控除額（右側の専用枠）
        $this->writePdfTextRightSized($pdf, 245, 82, (string) ($nenTyo['shotoku_deduction'] ?? ''), 7, 20); //[9]
        $this->writePdfTextRightSized($pdf, 245, 89, (string) ($nenTyo['tyosei_koujyo'] ?? ''), 7, 20); //[10]
        // [11] = [9]給与所得控除後の金額 - [10]所得金額調整控除額。専用の保存列は無く、
        // 両方とも既に上で表示している保存値どうしの単純な引き算なのでここで計算する。座標は仮置き。
        $this->writePdfTextRightSized(
            $pdf,
            245,
            93,
            $this->pdfNumber($this->money($nenTyo['shotoku_deduction'] ?? 0) - $this->money($nenTyo['tyosei_koujyo'] ?? 0)),
            7,
            20
        ); //[11]

        // 要確認：社会保険料等控除額（給与等からの控除分｜申告社会保険料｜申告小規模企業共済等掛金）
        $this->writePdfTextRightSized($pdf, 245, 98, (string) ($nenTyo['kyu_syaho_fee_kou'] ?? ''), 7, 20); //[12]
        $this->writePdfTextRightSized($pdf, 245, 101, (string) ($nenTyo['shin_syaho_fee_kou'] ?? ''), 7, 20); //[13]
        $this->writePdfTextRightSized($pdf, 245, 108, (string) ($nenTyo['shun_kigyou_fee_kou'] ?? ''), 7, 20); //[14]
        // 要確認：国民年金保険料等の金額。座標は仮置き。
        $this->writePdfTextRightSized($pdf, 245, 104.5, (string) ($nenTyo['koku_nenkin'] ?? ''), 7, 20);
        // 要確認：企業共済（kigyou_kyousai。shun_kigyou_fee_kouとは別枠で表示）。座標は仮置き。
        $this->writePdfTextRightSized($pdf, 270, 108, (string) ($nenTyo['kigyou_kyousai'] ?? ''), 7, 20);

        // 要確認：生命保険料の控除額｜地震保険料の控除額｜配偶者（特別）控除額｜
        // 扶養控除額及び障害者等の控除額の合計額｜基礎控除額｜所得控除額の合計額
        $this->writePdfTextRightSized($pdf, 245, 113, (string) ($nenTyo['seimei_fee_kou'] ?? ''), 7, 20); //[15]
        $this->writePdfTextRightSized($pdf, 245, 118, (string) ($nenTyo['jishun_fee_kou'] ?? ''), 7, 20); //[16]
        // 要確認：旧長期損害保険料（kyu_songai_fee、mx_deduction_shou由来）。座標は仮置き。
        $this->writePdfTextRightSized($pdf, 270, 118, (string) ($nenTyo['kyu_songai_fee'] ?? ''), 7, 20);
        // [17]配偶者控除・配偶者特別控除は同時に両方発生することが無いのでsumして1つの欄に表示する。
        $this->writePdfTextRightSized(
            $pdf,
            245,
            123,
            $this->pdfNumber($this->money($nenTyo['haigu_deduction'] ?? 0) + $this->money($nenTyo['haigu_toku_deduction'] ?? 0)),
            7,
            20
        ); //[17]
        // 要確認：配偶者の合計所得金額。座標は仮置き。haigu_shotoku_sumという専用カラムが
        // 別途あったが、実運用では配偶者の他の所得も合算した金額をそのままhaigu_shotokuに
        // 手入力しているとのことで、haigu_shotoku_sumは常に未入力（全レコードでゼロ）
        // だったため2026年8月に削除した。
        $this->writePdfTextRightSized($pdf, 200, 123, (string) ($nenTyo['haigu_shotoku'] ?? ''), 7, 20);
        $this->writePdfTextRightSized($pdf, 245, 128, (string) ($nenTyo['deduction_sum'] ?? ''), 7, 20); //[18]控除額合計
        $this->writePdfTextRightSized($pdf, 245, 133, (string) ($nenTyo['kiso_koujyo'] ?? ''), 7, 20); //[19]
        $this->writePdfTextRightSized($pdf, 245, 138, (string) ($nenTyo['shotoku_deduction_sum'] ?? ''), 7, 20); //[20]

        // 要確認：差引課税給与所得金額｜算出所得税額｜住宅借入金等特別控除額
        $this->writePdfTextRightSized($pdf, 245, 145, (string) ($nenTyo['sa_kazei_shotoku'] ?? ''), 7, 20); //[21]
        $this->writePdfTextRightSized($pdf, 280, 145, (string) ($nenTyo['sanshutu_shotoku'] ?? ''), 7, 20); //[22]
        $this->writePdfTextRightSized($pdf, 280, 150, (string) ($nenTyo['jyu_kari_kou'] ?? ''), 7, 20); //[23]

        // 要確認：年調所得税額｜年調年税額｜差引超過額又は不足額
        $this->writePdfTextRightSized($pdf, 280, 154, (string) ($nenTyo['nentyo_shotoku_amo'] ?? ''), 7, 20); //[24]
        $this->writePdfTextRightSized($pdf, 280, 161, (string) ($nenTyo['nentyo_nen_tax'] ?? ''), 7, 20); //[25]
        $this->writePdfTextRightSized($pdf, 280, 166, $this->pdfSignedNumber($nenTyo['sa_excess'] ?? 0), 7, 20); //[26]
        $this->writePdfTextRightSized($pdf, 280, 184, $this->pdfSignedNumber($nenTyo['sa_excess'] ?? 0), 7, 20); //[30]
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
            ->get(['supply_month', 'fuyo_sum', 'bonus', 'bonus_amo', 'bonus_tax', 'taxation_sum', 'syaho_sum', 'syaho_deduction_sum', 'income_tax', 'adjustment_year_end'])
            ->map(function ($row) use ($companyName): array {
                $isBonus = (int) ($row->bonus ?? 0) === 1;
                return [
                    'is_bonus' => $isBonus,
                    'month' => $this->pdfDateMonthDayLabel($row->supply_month ?? null),
                    'fuyo_sum' => $this->pdfNumber($row->fuyo_sum ?? 0),
                    'bonus_amount' => $isBonus ? $this->pdfNumber($row->bonus_amo ?? $row->taxation_sum ?? 0) : '',
                    // mx_kyuyo_shou.bonus_tax は列名に反して金額ではなく賞与の源泉税率（小数第3位まで）
                    // が入っている。PayrollV2BonusIncomeTaxCalcService::calcBonusKou()/calcBonusOtsu()
                    // が 'bonus_tax' => round($calc['rate'], 3) で保存している。
                    // pdfNumber()は0桁丸めの金額用フォーマットなので使わず、小数のまま表示する。
                    'bonus_tax' => $row->bonus_tax !== null ? number_format((float) $row->bonus_tax, 3) : '',
                    'taxation_sum' => $this->pdfNumber($row->taxation_sum ?? 0),
                    'syaho_sum' => $this->pdfNumber($row->syaho_sum ?? 0),
                    'syaho_deduction_sum' => $this->pdfNumber($row->syaho_deduction_sum ?? 0),
                    'income_tax' => $this->pdfNumber($row->income_tax ?? 0),
                    'adjustment_year_end' => $this->pdfSignedNumber($row->adjustment_year_end ?? 0),
                    'company_name' => $companyName,
                ];
            })
            ->all();
    }

    // ここから下は特定の帳票専用ではなく、fuyo/hoken/kiso/gensenHyou/gensenBoなど
    // 全帳票が共通で使うPDF書き込み・整形ヘルパー関数群（座標や業務データは持たない）。
    // 各帳票固有の座標・データはそれぞれの write*Preview()/write*Row() 側を参照すること。

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

    /**
     * 源泉徴収簿の支給月日欄用。「月」「日」の文字も「/」も出さず、数字だけにする。
     * 帳票の枠が月と日で分かれているため、間はスラッシュではなく空白で区切る（例：「3 25」）。
     */
    private function pdfDateMonthDayLabel(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $date = new \DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            return trim((string) $value);
        }

        return (int) $date->format('n') . '     ' . (int) $date->format('j');
    }

    private function pdfNumber(mixed $value): string
    {
        $number = $this->money($value);
        if (abs($number) < 0.00001) {
            return '0';
        }

        return number_format($number, 0);
    }

    /** 円の値を万円単位の数字だけで表示する（630000円→「63」）。0のときは空欄。 */
    private function pdfManNumber(mixed $value): string
    {
        $number = $this->money($value);
        if (abs($number) < 0.00001) {
            return '';
        }

        return number_format($number / 10000, 0);
    }

    /**
     * 過不足額など、マイナスを「-」ではなく会計表記の「△」で表示したい欄で使う。
     * 0のときは「0」ではなく空欄にする（過不足が無い月・年は書かない）。
     */
    private function pdfSignedNumber(mixed $value): string
    {
        $number = $this->money($value);
        if (abs($number) < 0.00001) {
            return '';
        }

        return $number < 0 ? '△' . number_format(abs($number), 0) : number_format($number, 0);
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
        } elseif ($this->certificateFileService->exists($path)) {
            $fileUrl = route('admin.work.year_end_adjustments.hoken.certificate_file', ['applicationId' => $applicationId, 'hokenNo' => $hokenNo]);
            $extension = $this->certificateFileService->extension($path);
        } else {
            // 旧保存方式（public_path直下）の残存ファイル向けフォールバック。
            // 新規アップロードは必ずlocalディスク（storage/app/private）へ保存される。
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

    public function hokenCertificateFile(int $applicationId, int $hokenNo)
    {
        [$application, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);
        $row = $this->hokenRowOrFail($hokenNo, $staffId, $targetYear);
        $path = trim((string) ($row->certificate_file_path ?? ''));
        abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

        $downloadName = trim((string) ($row->certificate_original_name ?? '')) ?: basename($path);

        return $this->certificateFileService->response($path, $downloadName);
    }

    /**
     * 氏名変更・住所変更の証憑はstaff_year_end_applications（引き続きステージング）から、
     * 障害者手帳の証憑はmx_nen_tyo（直書き化）から配信する。
     */
    public function personalInfoCertificateFile(int $applicationId, string $type)
    {
        if ($type === 'disability') {
            $application = DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_year_end_applications')
                ->where('application_id', $applicationId)
                ->first();
            abort_unless($application, 404);

            $nenTyo = $this->nenTyoRowForApplication($application);
            abort_unless($nenTyo, 404);

            $path = trim((string) ($nenTyo->disability_certificate_file_path ?? ''));
            abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

            $downloadName = trim((string) ($nenTyo->disability_certificate_original_name ?? '')) ?: basename($path);

            return $this->certificateFileService->response($path, $downloadName);
        }

        $columnPrefix = match ($type) {
            'address' => 'address_change_certificate',
            'name' => 'name_change_certificate',
            default => abort(404),
        };

        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $path = trim((string) ($application->{$columnPrefix . '_file_path'} ?? ''));
        abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

        $downloadName = trim((string) ($application->{$columnPrefix . '_original_name'} ?? '')) ?: basename($path);

        return $this->certificateFileService->response($path, $downloadName);
    }

    public function previousJobCertificateFile(int $applicationId)
    {
        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $nenTyo = $this->nenTyoRowForApplication($application);
        abort_unless($nenTyo, 404);

        $path = trim((string) ($nenTyo->previous_job_certificate_file_path ?? ''));
        abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

        $downloadName = trim((string) ($nenTyo->previous_job_certificate_original_name ?? '')) ?: basename($path);

        return $this->certificateFileService->response($path, $downloadName);
    }

    public function housingLoanCertificateFile(int $applicationId)
    {
        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $nenTyo = $this->nenTyoRowForApplication($application);
        abort_unless($nenTyo, 404);

        $path = trim((string) ($nenTyo->housing_loan_certificate_file_path ?? ''));
        abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

        $downloadName = trim((string) ($nenTyo->housing_loan_certificate_original_name ?? '')) ?: basename($path);

        return $this->certificateFileService->response($path, $downloadName);
    }

    /** 作成はしない（GETの証憑配信で副作用を起こさないため）。無ければnull。 */
    private function nenTyoRowForApplication(object $application): ?object
    {
        $nenTyoNo = (int) ($application->nen_tyo_no ?? 0);
        if ($nenTyoNo > 0) {
            $row = DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo')->where('nen_tyo_no', $nenTyoNo)->first();
            if ($row !== null) {
                return $row;
            }
        }

        $staffId = trim((string) ($application->staff_id ?? ''));
        $targetYear = (int) ($application->target_year ?? date('Y'));

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('staff_id', $staffId)
            ->whereYear('year_end', $targetYear)
            ->orderBy('nen_tyo_no')
            ->first();
    }

    /**
     * mx_fuyoの障害者手帳証憑を直接配信する（ステージング廃止に伴う置き換え）。
     * applicationIdから対象スタッフ・年を特定し、そのfuyo_noが同一スタッフ・年の
     * ものであることを確認してから配信する（他スタッフの証憑を覗けないように）。
     */
    public function fuyoCertificateFile(int $applicationId, int $fuyoNo)
    {
        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $staffId = trim((string) ($application->staff_id ?? ''));
        $targetYear = (int) ($application->target_year ?? date('Y'));

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('fuyo_no', $fuyoNo)
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->first();
        abort_unless($row, 404);

        $path = trim((string) ($row->failure_certificate_file_path ?? ''));
        abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

        $downloadName = trim((string) ($row->failure_certificate_original_name ?? '')) ?: basename($path);

        return $this->certificateFileService->response($path, $downloadName);
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

    public function confirmApplication(int $applicationId): RedirectResponse
    {
        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        if (trim((string) $application->status) !== 'submitted') {
            return redirect()
                ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
                ->with('status', '提出済みの申請のみ確認済みにできます。');
        }

        $staffId = trim((string) $application->staff_id);
        $targetYear = (int) ($application->target_year ?? date('Y'));

        // 保険料控除はステージングを介さずmx_hokenへ直書きされているため、確認済みボタンで
        // 対象スタッフ・年のmx_hoken行を一括でchecked_flag=1にする（既存の未使用カラムを再利用）。
        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear)
            ->update(['checked_flag' => 1]);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '確認済みにしました。');
    }

    public function returnApplication(Request $request, int $applicationId): RedirectResponse
    {
        $values = $request->validate([
            'return_note' => ['required', 'string', 'max:2000'],
        ]);

        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        if (trim((string) $application->status) !== 'submitted') {
            return redirect()
                ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
                ->with('status', '提出済みの申請のみ差し戻せます。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->update([
                'status' => 'returned',
                'return_note' => $values['return_note'],
            ]);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '差し戻しました。');
    }

    /**
     * 確認済みの申請を実データへ反映する。扶養・配偶者(mx_fuyo)・保険料控除(mx_hoken)・
     * 住宅ローン控除/前職/本人状況フラグ(mx_nen_tyo)は、スタッフの申告時点で既に実データへ
     * 直接書き込まれているため、ここでの反映対象はmx_staffs（氏名・住所・生年月日、
     * 履歴を持たない単一マスタのため唯一ステージングを経由する）のみ。
     */
    public function reflectApplication(int $applicationId): RedirectResponse
    {
        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        if (trim((string) $application->status) !== 'confirmed') {
            return redirect()
                ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
                ->with('status', '確認済みの申請のみ反映できます。');
        }

        $staffId = trim((string) $application->staff_id);
        $personalInfoChanged = (int) ($application->personal_info_changed ?? 0) === 1;
        $newAddress = trim((string) ($application->new_address ?? ''));
        $newStaffName = trim((string) ($application->new_staff_name ?? ''));

        if ($personalInfoChanged && ($newAddress !== '' || $newStaffName !== '')) {
            $staffUpdate = [];
            if ($newAddress !== '') {
                $staffUpdate['address'] = $newAddress;
                $staffUpdate['address_furi'] = trim((string) ($application->new_address_furi ?? ''));
            }
            if ($newStaffName !== '') {
                $staffUpdate['staff_name'] = $newStaffName;
                $staffUpdate['staff_name_furi'] = trim((string) ($application->new_staff_name_furi ?? ''));
            }
            if ($application->new_birthday !== null) {
                $staffUpdate['birthday'] = $application->new_birthday;
            }
            $staffUpdate['head_house'] = trim((string) ($application->setai_nushi ?? ''));
            $staffUpdate['relationship'] = trim((string) ($application->setai_zoku_gara ?? ''));

            DB::connection('sqlsrv')
                ->table('dbo.mx_staffs')
                ->where('staff_id', $staffId)
                ->update($staffUpdate);
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->update([
                'status' => 'reflected',
                'reflected_at' => now(),
            ]);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '実データへ反映しました。');
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
     * 住宅ローン控除をmx_nen_tyoへ直接保存する（保険と同じ、確認しながらその場で
     * 修正できる専用フォーム）。
     */
    public function updateHousingLoanNenTyo(Request $request, int $applicationId): RedirectResponse
    {
        [$application, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);
        $nenTyo = $this->findOrCreateNenTyoRow($application, $staffId, $targetYear);

        $values = $request->validate([
            'housing_loan_declared_amount' => ['nullable', 'numeric'],
            'jyu_kojyo_kubun' => ['nullable', 'string', 'max:50'],
            'toku_kubun' => ['nullable', 'string', 'max:50'],
            'koujyo_kubun_no' => ['nullable', 'string', 'max:50'],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
            'certificate_file.max' => '証明書は10MB以内で添付してください。',
        ]);

        $payload = [
            'jyu_kari_kou' => $this->nullableMoney($values['housing_loan_declared_amount'] ?? null),
            'jyu_kojyo_kubun' => $this->nullableText($values['jyu_kojyo_kubun'] ?? null),
            'toku_kubun' => $this->nullableText($values['toku_kubun'] ?? null),
            'koujyo_kubun_no' => $this->nullableText($values['koujyo_kubun_no'] ?? null),
        ];

        $file = $request->file('certificate_file');
        if ($file !== null && $file->isValid()) {
            $existingPath = trim((string) ($nenTyo->housing_loan_certificate_file_path ?? ''));
            if ($existingPath !== '') {
                $this->certificateFileService->delete($existingPath);
            }
            $stored = $this->certificateFileService->store(
                $file,
                "year_end/{$targetYear}/{$staffId}/housing_loan",
                'jyutaku_' . date('YmdHis'),
            );
            $payload['housing_loan_certificate_file_path'] = $stored['path'];
            $payload['housing_loan_certificate_original_name'] = $stored['original_name'];
            $payload['housing_loan_certificate_uploaded_at'] = $stored['uploaded_at'];
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $nenTyo->nen_tyo_no)
            ->update($payload);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '住宅ローン控除を保存しました。');
    }

    /**
     * 前職情報をmx_nen_tyoへ直接保存する（保険・住宅ローンと同じ専用フォーム）。
     */
    public function updatePreviousJobNenTyo(Request $request, int $applicationId): RedirectResponse
    {
        [$application, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);
        $nenTyo = $this->findOrCreateNenTyoRow($application, $staffId, $targetYear);

        $values = $request->validate([
            'zen_syamei' => ['nullable', 'string', 'max:60'],
            'zen_add' => ['nullable', 'string', 'max:120'],
            'zen_tai_date' => ['nullable', 'date'],
            'zen_shotoku' => ['nullable', 'numeric'],
            'zen_syaho_kou' => ['nullable', 'numeric'],
            'zen_kyuyo_tax' => ['nullable', 'numeric'],
            'zen_bonus_tax' => ['nullable', 'numeric'],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
            'certificate_file.max' => '証明書は10MB以内で添付してください。',
        ]);

        $zenTaiDate = trim((string) ($values['zen_tai_date'] ?? ''));

        $payload = [
            'zen_syamei' => $this->nullableText($values['zen_syamei'] ?? null),
            'zen_add' => $this->nullableText($values['zen_add'] ?? null),
            'zen_tai_date' => $zenTaiDate !== '' ? date('Y-m-d', strtotime($zenTaiDate)) : null,
            'zen_shotoku' => $this->nullableMoney($values['zen_shotoku'] ?? null),
            'zen_syaho_kou' => $this->nullableMoney($values['zen_syaho_kou'] ?? null),
            'zen_kyuyo_tax' => $this->nullableMoney($values['zen_kyuyo_tax'] ?? null),
            'zen_bonus_tax' => $this->nullableMoney($values['zen_bonus_tax'] ?? null),
        ];

        $file = $request->file('certificate_file');
        if ($file !== null && $file->isValid()) {
            $existingPath = trim((string) ($nenTyo->previous_job_certificate_file_path ?? ''));
            if ($existingPath !== '') {
                $this->certificateFileService->delete($existingPath);
            }
            $stored = $this->certificateFileService->store(
                $file,
                "year_end/{$targetYear}/{$staffId}/previous_job",
                'zenshoku_' . date('YmdHis'),
            );
            $payload['previous_job_certificate_file_path'] = $stored['path'];
            $payload['previous_job_certificate_original_name'] = $stored['original_name'];
            $payload['previous_job_certificate_uploaded_at'] = $stored['uploaded_at'];
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $nenTyo->nen_tyo_no)
            ->update($payload);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '前職情報を保存しました。');
    }

    /**
     * 本人情報（氏名・住所・生年月日・世帯主）のスタッフ申告内容を、事務所側でその場で
     * 訂正できるようにする。mx_staffsへは反映しない（それは既存のreflectApplication()の
     * 役目）。ここはstaff_year_end_applicationsのステージング値を直すだけ。
     */
    public function updatePersonalInfoStaging(Request $request, int $applicationId): RedirectResponse
    {
        $application = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
        abort_unless($application, 404);

        $staffId = trim((string) ($application->staff_id ?? ''));
        $targetYear = (int) ($application->target_year ?? date('Y'));

        $values = $request->validate([
            'new_staff_name' => ['nullable', 'string', 'max:50'],
            'new_staff_name_furi' => ['nullable', 'string', 'max:50'],
            'new_birthday' => ['nullable', 'date'],
            'new_address' => ['nullable', 'string', 'max:255'],
            'new_address_furi' => ['nullable', 'string', 'max:255'],
            'setai_nushi' => ['nullable', 'string', 'max:50'],
            'setai_zoku_gara' => ['nullable', 'string', 'max:20'],
            'name_change_certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'address_change_certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'name_change_certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
            'address_change_certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
        ]);

        $payload = [
            'new_staff_name' => $this->nullableText($values['new_staff_name'] ?? null),
            'new_staff_name_furi' => $this->nullableText($values['new_staff_name_furi'] ?? null),
            'new_birthday' => !empty($values['new_birthday']) ? $values['new_birthday'] : null,
            'new_address' => $this->nullableText($values['new_address'] ?? null),
            'new_address_furi' => $this->nullableText($values['new_address_furi'] ?? null),
            'setai_nushi' => $this->nullableText($values['setai_nushi'] ?? null),
            'setai_zoku_gara' => $this->nullableText($values['setai_zoku_gara'] ?? null),
        ];

        $nameFile = $request->file('name_change_certificate_file');
        if ($nameFile !== null && $nameFile->isValid()) {
            $existing = trim((string) ($application->name_change_certificate_file_path ?? ''));
            if ($existing !== '') {
                $this->certificateFileService->delete($existing);
            }
            $stored = $this->certificateFileService->store($nameFile, "year_end/{$targetYear}/{$staffId}/personal_info", 'shimei_' . date('YmdHis'));
            $payload['name_change_certificate_file_path'] = $stored['path'];
            $payload['name_change_certificate_original_name'] = $stored['original_name'];
            $payload['name_change_certificate_uploaded_at'] = $stored['uploaded_at'];
        }

        $addressFile = $request->file('address_change_certificate_file');
        if ($addressFile !== null && $addressFile->isValid()) {
            $existing = trim((string) ($application->address_change_certificate_file_path ?? ''));
            if ($existing !== '') {
                $this->certificateFileService->delete($existing);
            }
            $stored = $this->certificateFileService->store($addressFile, "year_end/{$targetYear}/{$staffId}/personal_info", 'jusho_' . date('YmdHis'));
            $payload['address_change_certificate_file_path'] = $stored['path'];
            $payload['address_change_certificate_original_name'] = $stored['original_name'];
            $payload['address_change_certificate_uploaded_at'] = $stored['uploaded_at'];
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->update($payload);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '本人情報の申告内容を修正しました。');
    }

    /**
     * 扶養情報をmx_fuyoへ直接保存する（保険と同じ、その場で訂正・保存できる専用フォーム）。
     */
    public function updateFuyoRow(Request $request, int $applicationId, int $fuyoNo): RedirectResponse
    {
        [, $staffId, $targetYear] = $this->hokenApplicationContext($applicationId);

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('fuyo_no', $fuyoNo)
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->first();
        abort_unless($row, 404);

        $values = $request->validate([
            'fuyo_name' => ['nullable', 'string', 'max:50'],
            'fuyo_name_furi' => ['nullable', 'string', 'max:50'],
            'fuyo_relationship' => ['nullable', 'string', 'max:50'],
            'fuyo_birthday' => ['nullable', 'date'],
            'fuyo_address' => ['nullable', 'string', 'max:255'],
            'fuyo_sex' => ['nullable', 'string', 'max:5'],
            'kyojyu' => ['nullable', 'string', 'max:5'],
            'fuyo_shunyu' => ['nullable', 'numeric'],
            'failure_notebook' => ['nullable', 'string', 'max:50'],
            'failure_judgment' => ['nullable', 'string', 'max:50'],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
            'certificate_file.max' => '証明書は10MB以内で添付してください。',
        ]);

        $payload = [
            'fuyo_name' => $this->nullableText($values['fuyo_name'] ?? null),
            'fuyo_name_furi' => $this->nullableText($values['fuyo_name_furi'] ?? null),
            'fuyo_relationship' => $this->nullableText($values['fuyo_relationship'] ?? null),
            'fuyo_birthday' => !empty($values['fuyo_birthday']) ? $values['fuyo_birthday'] : null,
            'fuyo_address' => $this->nullableText($values['fuyo_address'] ?? null),
            'fuyo_sex' => $this->nullableText($values['fuyo_sex'] ?? null),
            'kyojyu' => $this->nullableText($values['kyojyu'] ?? null),
            'fuyo_shunyu' => $this->nullableMoney($values['fuyo_shunyu'] ?? null),
            'deduction_target' => $request->boolean('deduction_target') ? 1 : 0,
            'widow' => $request->boolean('widow') ? 1 : 0,
            'failure_notebook' => $this->nullableText($values['failure_notebook'] ?? null),
            'failure_judgment' => $this->nullableText($values['failure_judgment'] ?? null),
        ];

        $file = $request->file('certificate_file');
        if ($file !== null && $file->isValid()) {
            $existingPath = trim((string) ($row->failure_certificate_file_path ?? ''));
            if ($existingPath !== '') {
                $this->certificateFileService->delete($existingPath);
            }
            $stored = $this->certificateFileService->store(
                $file,
                "year_end/{$targetYear}/{$staffId}/fuyo",
                'fuyo_' . $fuyoNo . '_' . date('YmdHis'),
            );
            $payload['failure_certificate_file_path'] = $stored['path'];
            $payload['failure_certificate_original_name'] = $stored['original_name'];
            $payload['failure_certificate_uploaded_at'] = $stored['uploaded_at'];
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('fuyo_no', $fuyoNo)
            ->where('staff_id', $staffId)
            ->update($payload);

        return redirect()
            ->route('admin.work.year_end_adjustments.show', ['applicationId' => $applicationId])
            ->with('status', '扶養情報を保存しました。');
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

    private function deleteHokenCertificateFile(string $path): void
    {
        $path = trim($path);
        if ($path === '' || preg_match('/^https?:\/\//', $path) === 1) {
            return;
        }

        if ($this->certificateFileService->exists($path)) {
            $this->certificateFileService->delete($path);
            return;
        }

        // 旧保存方式（public_path直下）の残存ファイル向けフォールバック。
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

        $stored = $this->certificateFileService->store(
            $file,
            "year_end/{$targetYear}/{$staffId}/insurance",
            'hoken_' . $hokenNo . '_' . date('YmdHis'),
        );

        return [
            'certificate_file_path' => $stored['path'],
            'certificate_original_name' => $stored['original_name'],
            'certificate_uploaded_at' => $stored['uploaded_at'],
        ];
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
     * チェック用の丸印を描画する。1文字だけを囲む「新・旧」欄などに使う。
     * 文字の「〇」はフォント次第で崩れて見えることがあるため、TCPDFのCircle()で真円を描く。
     * $x,$yは円の中心。$rgb省略時は現在のテキスト色を使う。
     */
    private function drawPdfCircleMark(Fpdi $pdf, float $x, float $y, float $radius, ?array $rgb = null): void
    {
        $rgb ??= [255, 0, 0];
        $pdf->SetDrawColor((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        $pdf->SetLineWidth(0.25);
        $pdf->Circle($x, $y, $radius, 0, 360, 'D');
    }

    /**
     * チェック用の楕円印を描画する。「地震」「旧長期」など複数文字の印字済みラベルを
     * 囲む欄で使う（横長になるため真円ではなく楕円が正しい）。$rx=横半径、$ry=縦半径。
     */
    private function drawPdfEllipseMark(Fpdi $pdf, float $x, float $y, float $rx, float $ry, ?array $rgb = null): void
    {
        $rgb ??= [255, 0, 0];
        $pdf->SetDrawColor((int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        $pdf->SetLineWidth(0.25);
        $pdf->Ellipse($x, $y, $rx, $ry, 0, 0, 360, 'D');
    }

    /**
     * フリガナ専用。半角カタカナ＋濁点(゛)は自然な文字送りだと濁点が離れて浮いて見えるため、
     * 1文字ずつ$pitchで詰めて描画する（2026年8月、扶養控除申告書で実際に確認済みの挙動）。
     * 氏名（漢字）や会社名はこの問題が起きないので writePdfTextSized を使うこと。
     */
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
            'personal_info_changed' => $this->bitLabel($application->personal_info_changed ?? null),
            'dependents_changed' => $this->bitLabel($application->dependents_changed ?? null),
            'insurance_deduction_changed' => $this->bitLabel($application->insurance_deduction_changed ?? null),
            'housing_loan_changed' => $this->bitLabel($application->housing_loan_changed ?? null),
            'previous_job_withholding_changed' => $this->bitLabel($application->previous_job_withholding_changed ?? null),
            'previous_job_already_submitted' => $this->bitLabel($application->previous_job_already_submitted ?? null),
            'special_collection_requested' => $this->bitLabel($application->special_collection_requested ?? null),
            'submitted_at' => $this->dateLabel($application->submitted_at ?? null),
            'confirmed_at' => $this->dateLabel($application->confirmed_at ?? null),
            'reflected_at' => $this->dateLabel($application->reflected_at ?? null),
            'new_address' => trim((string) ($application->new_address ?? '')),
            'new_address_furi' => trim((string) ($application->new_address_furi ?? '')),
            'new_staff_name' => trim((string) ($application->new_staff_name ?? '')),
            'new_staff_name_furi' => trim((string) ($application->new_staff_name_furi ?? '')),
            'new_birthday' => trim((string) ($application->new_birthday ?? '')),
            'setai_nushi' => trim((string) ($application->setai_nushi ?? '')),
            'setai_zoku_gara' => trim((string) ($application->setai_zoku_gara ?? '')),
            'address_change_certificate_original_name' => trim((string) ($application->address_change_certificate_original_name ?? '')),
            'name_change_certificate_original_name' => trim((string) ($application->name_change_certificate_original_name ?? '')),
            'return_note' => trim((string) ($application->return_note ?? '')),
            'spouse_changed' => $this->bitLabel($application->spouse_changed ?? null),
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
                    's.store_name',
                    'c.company_id',
                    'c.company_name',
                    'c.company_address',
                    'c.corporate_number',
                ]);

            if ($company) {
                $detail['store_name'] = trim((string) ($company->store_name ?? ''));
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
        $nenTyo = $row ? $this->objectToArray($row) : [];
        if ($nenTyo === []) {
            return [];
        }

        // 生命保険料・地震保険料の内訳（新旧生命保険・介護医療・新旧個人年金・地震・旧長期損害の
        // 各申告額）は mx_nen_tyo ではなく mx_deduction_shou（nen_tyo_noで1:1）に保存されている。
        // ここで合わせて取得し、以降は $nenTyo 配列から他の列と同じように参照できるようにする。
        if (Schema::connection('sqlsrv_payroll')->hasTable('mx_deduction_shou')) {
            $deductionRow = DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_deduction_shou')
                ->where('nen_tyo_no', (int) ($nenTyo['nen_tyo_no'] ?? 0))
                ->first();
            if ($deductionRow) {
                $nenTyo = array_merge($nenTyo, $this->objectToArray($deductionRow));
            }
        }

        return $nenTyo;
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
                'ippan_kafu' => '一般寡婦（旧・現在は使用しない）',
                'toku_kafu' => '特別寡婦（旧・現在は使用しない）',
                'hitori_oya' => 'ひとり親',
                'kafu' => '寡婦',
                'student' => '勤労学生',
                'halfway' => '中途',
                'futu_tyoushu' => '普通徴収',
                'fuyo_deduction_report' => '扶養控除申告',
                'nen_tyo_false' => '年調しない',
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
            '配偶者関係' => [
                'haigu_umu' => '配偶者有無',
                'haigu_kou_umu' => '配偶者控除有無',
                'haigu_bunrui' => '配偶者控除分類',
                'haigu_shotoku' => '配偶者所得',
                'haigu_deduction' => '配偶者控除',
                'haigu_toku_deduction' => '配偶者特別控除',
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
