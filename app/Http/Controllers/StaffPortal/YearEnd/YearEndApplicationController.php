<?php

namespace App\Http\Controllers\StaffPortal\YearEnd;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\Admin\V2\YearEndAdjustment\YearEndCalculationService;
use App\Services\YearEnd\CertificateFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class YearEndApplicationController extends Controller
{
    use HandlesStaffPortalContext;

    public function __construct(
        private readonly CertificateFileService $certificateFileService,
        private readonly YearEndCalculationService $calculationService,
    ) {}

    private const EDITABLE_STATUSES = ['draft', 'returned'];

    /**
     * 配偶者はmx_fuyoに続柄「夫/妻/配偶者」の行として保存する（既存の帳票生成コード
     * YearEndAdjustmentV2Controller::writeSpousePreview等と同じ判定条件）。
     * 扶養控除申告書側の一覧には出さず、配偶者セクションで別扱いする。
     */
    private const SPOUSE_RELATIONSHIPS = ['夫', '妻', '配偶者'];

    private const STATUS_LABELS = [
        'draft' => '下書き',
        'submitted' => '提出済',
        'returned' => '差戻し',
        'confirmed' => '確認済',
        'reflected' => '反映済',
        'excluded' => '対象外',
        'retired' => '退職済',
    ];

    public function index(Request $request): RedirectResponse|View
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, $staffRow, $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);

        $allFuyoRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->orderBy('fuyo_no')
            ->get()
            ->map(fn($row): array => (array) $row);

        $isSpouseRow = fn(array $row): bool => in_array(trim((string) ($row['fuyo_relationship'] ?? '')), self::SPOUSE_RELATIONSHIPS, true);

        $spouseFuyoRow = $allFuyoRows->first($isSpouseRow);
        $dependentRows = $allFuyoRows->reject($isSpouseRow)->values()->all();

        $nenTyoRow = $this->currentNenTyoRow($staffId, $targetYear, (int) ($application['nen_tyo_no'] ?? 0));

        $hokenRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear)
            ->orderBy('hoken_no')
            ->get()
            ->map(fn($row): array => (array) $row)
            ->all();

        $hasPreviousYearInsurance = $hokenRows === [] && DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear - 1)
            ->exists();

        return view('staff_portal.year_end.index', $this->commonViewData($request, [
            'staffId' => $staffId,
            'targetYear' => $targetYear,
            'application' => $application,
            'editable' => in_array($application['status'], self::EDITABLE_STATUSES, true),
            'statusLabel' => self::STATUS_LABELS[$application['status']] ?? $application['status'],
            'currentAddress' => trim((string) ($staffRow['address'] ?? '')),
            'currentAddressFuri' => trim((string) ($staffRow['address_furi'] ?? '')),
            'currentStaffName' => trim((string) ($staffRow['staff_name'] ?? '')),
            'currentStaffNameFuri' => trim((string) ($staffRow['staff_name_furi'] ?? '')),
            'currentBirthday' => substr((string) ($staffRow['birthday'] ?? ''), 0, 10),
            'currentHeadHouse' => trim((string) ($staffRow['head_house'] ?? '')),
            'currentRelationship' => trim((string) ($staffRow['relationship'] ?? '')),
            'dependentRows' => $dependentRows,
            'spouseFuyoRow' => $spouseFuyoRow,
            'hokenRows' => $hokenRows,
            'hasPreviousYearInsurance' => $hasPreviousYearInsurance,
            'currentNenTyo' => $nenTyoRow,
        ]));
    }

    /**
     * 前職の情報はmx_nen_tyo（zen_*列）へ直接書き込む。mx_nen_tyoは年調専用・年ごとに
     * 行が分かれるため（mx_staffs等と違い）、スタッフの直書きでも過去のデータは壊れない。
     * 「入社時に提出済み」フラグ自体は申告メタ情報のためstaff_year_end_applicationsに残す。
     */
    public function updatePreviousJob(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, , $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        $nenTyo = $this->findOrCreateNenTyoRow($staffId, $targetYear, (int) ($application['nen_tyo_no'] ?? 0));
        $previous = (object) $nenTyo;

        // 二段階の確認：①今年、前職はあるか ②あるなら、源泉徴収票は入社時に提出済みか。
        // 提出済みなら事務所側に既に証憑があるはずなので、スタッフに重複入力・再添付させない。
        $hasPreviousJob = $request->boolean('previous_job_withholding_changed');
        $alreadySubmitted = $request->boolean('previous_job_already_submitted');

        $nenTyoValues = [];

        if ($hasPreviousJob && !$alreadySubmitted) {
            $validated = $request->validate([
                'zen_syamei' => ['required', 'string', 'max:60'],
                'zen_add' => ['nullable', 'string', 'max:120'],
                'zen_tai_date' => ['nullable', 'date'],
                'zen_shotoku' => ['nullable', 'numeric', 'min:0'],
                'zen_syaho_kou' => ['nullable', 'numeric', 'min:0'],
                'zen_kyuyo_tax' => ['nullable', 'numeric', 'min:0'],
                'zen_bonus_tax' => ['nullable', 'numeric', 'min:0'],
            ]);

            $this->requireCertificate($request, 'previous_job_certificate_file', $previous, '前職の源泉徴収票を添付してください。', 'previous_job_certificate_file_path');
            $nenTyoValues = array_merge($nenTyoValues, $this->resolveCertificateFieldsFor($request, 'previous_job_certificate_file', $previous, 'previous_job_certificate', $staffId, $targetYear, 'zenshoku'));

            $nenTyoValues['zen_syamei'] = $validated['zen_syamei'];
            $nenTyoValues['zen_add'] = $validated['zen_add'] ?? null;
            $nenTyoValues['zen_tai_date'] = $validated['zen_tai_date'] ?? null;
            $nenTyoValues['zen_shotoku'] = $validated['zen_shotoku'] ?? null;
            $nenTyoValues['zen_syaho_kou'] = $validated['zen_syaho_kou'] ?? null;
            $nenTyoValues['zen_kyuyo_tax'] = $validated['zen_kyuyo_tax'] ?? null;
            $nenTyoValues['zen_bonus_tax'] = $validated['zen_bonus_tax'] ?? null;
        } else {
            // 前職なし、または「入社時に提出済み」を選んだ場合は詳細入力は不要
            $this->deleteCertificateIfPresent($previous->previous_job_certificate_file_path ?? null);

            $nenTyoValues['zen_syamei'] = null;
            $nenTyoValues['zen_add'] = null;
            $nenTyoValues['zen_tai_date'] = null;
            $nenTyoValues['zen_shotoku'] = null;
            $nenTyoValues['zen_syaho_kou'] = null;
            $nenTyoValues['zen_kyuyo_tax'] = null;
            $nenTyoValues['zen_bonus_tax'] = null;
            $nenTyoValues['previous_job_certificate_file_path'] = null;
            $nenTyoValues['previous_job_certificate_original_name'] = null;
            $nenTyoValues['previous_job_certificate_uploaded_at'] = null;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $nenTyo['nen_tyo_no'])
            ->update($nenTyoValues);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update([
                'previous_job_withholding_changed' => $hasPreviousJob,
                'previous_job_already_submitted' => $hasPreviousJob ? $alreadySubmitted : null,
            ]);

        return redirect()->route('year_end_adjustment')->with('statusMessage', '前職の申告内容を保存しました。');
    }

    /**
     * 住宅ローン控除：計算はしない。証憑（住宅借入金等特別控除申告書）に印字済みの
     * 控除額をそのまま転記させるだけ（2年目以降は税務署から送付される証憑に金額が
     * 印字済みのため）。mx_nen_tyo.jyu_kari_kouへ直接書き込む。
     */
    public function updateHousingLoan(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, , $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        $nenTyo = $this->findOrCreateNenTyoRow($staffId, $targetYear, (int) ($application['nen_tyo_no'] ?? 0));
        $previous = (object) $nenTyo;

        $changed = $request->boolean('housing_loan_changed');
        $nenTyoValues = [];

        if ($changed) {
            $validated = $request->validate([
                'housing_loan_declared_amount' => ['required', 'numeric', 'min:0'],
                'jyu_kojyo_kubun' => ['nullable', 'string', 'max:50'],
                'toku_kubun' => ['nullable', 'string', 'max:50'],
                'koujyo_kubun_no' => ['nullable', 'string', 'max:50'],
            ]);
            $nenTyoValues['jyu_kari_kou'] = $validated['housing_loan_declared_amount'];
            $nenTyoValues['jyu_kojyo_kubun'] = $validated['jyu_kojyo_kubun'] ?? null;
            $nenTyoValues['toku_kubun'] = $validated['toku_kubun'] ?? null;
            $nenTyoValues['koujyo_kubun_no'] = $validated['koujyo_kubun_no'] ?? null;

            $this->requireCertificate($request, 'housing_loan_certificate_file', $previous, '住宅ローン控除の証憑ファイルを添付してください。', 'housing_loan_certificate_file_path');
            $nenTyoValues = array_merge($nenTyoValues, $this->resolveCertificateFieldsFor($request, 'housing_loan_certificate_file', $previous, 'housing_loan_certificate', $staffId, $targetYear, 'jyutaku'));
        } else {
            $this->deleteCertificateIfPresent($previous->housing_loan_certificate_file_path ?? null);
            $nenTyoValues['jyu_kari_kou'] = null;
            $nenTyoValues['jyu_kojyo_kubun'] = null;
            $nenTyoValues['toku_kubun'] = null;
            $nenTyoValues['koujyo_kubun_no'] = null;
            $nenTyoValues['housing_loan_certificate_file_path'] = null;
            $nenTyoValues['housing_loan_certificate_original_name'] = null;
            $nenTyoValues['housing_loan_certificate_uploaded_at'] = null;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $nenTyo['nen_tyo_no'])
            ->update($nenTyoValues);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update(['housing_loan_changed' => $changed]);

        return redirect()->route('year_end_adjustment')->with('statusMessage', '住宅ローン控除の申告内容を保存しました。');
    }

    /**
     * 前年のmx_hoken（保険会社・種類・区分等）を当年分としてそのままコピーする
     * （mx_hokenへ直接INSERT）。証憑は年ごとに再確認が必要なため引き継がない
     * （保存時に改めて添付必須）。今年分のmx_hokenが未作成のときの初期値作成用。
     */
    public function copyPreviousYearInsurance(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, , $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        $previousYearRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear - 1)
            ->orderBy('hoken_no')
            ->get();

        if ($previousYearRows->isEmpty()) {
            return redirect()->route('year_end_adjustment')->with('errorMessage', '前年の保険料控除データが見つかりませんでした。');
        }

        $insertRows = $previousYearRows->map(fn($row): array => [
            'insurance_staff_no' => $staffId,
            'insurance_year' => sprintf('%04d-12-31', $targetYear),
            'insurance_company' => $row->insurance_company,
            'category' => $row->category,
            'applied_system' => $row->applied_system,
            'declared_amount' => $row->declared_amount,
            'insurance_type' => $row->insurance_type,
            'insurance_period' => $row->insurance_period,
            'policy_holder_name' => $row->policy_holder_name,
            'beneficiary_name' => $row->beneficiary_name,
            'beneficiary_relationship' => $row->beneficiary_relationship,
            'pension_payment_start_date' => $row->pension_payment_start_date,
            'year_end_insurance_note' => $row->year_end_insurance_note,
            'certificate_file_path' => null,
            'certificate_original_name' => null,
            'certificate_uploaded_at' => null,
            'checked_flag' => 0,
        ])->all();

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->insert($insertRows);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update(['insurance_deduction_changed' => 1]);

        return redirect()->route('year_end_adjustment')->with('statusMessage', '前年の保険料控除データを' . $previousYearRows->count() . '件コピーしました。内容を確認し、証憑を添付して保存してください。');
    }

    /**
     * mx_hokenへ直接update/insert/delete。保険は年調専用・年ごとに行が分かれる
     * テーブルのため、スタッフの直書きでも過去のデータは壊れない。事務所が確認すると
     * checked_flagが1になる（confirmApplication側）。変更するとchecked_flagは0に戻る。
     */
    public function updateInsurance(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, , $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        $existingRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_hoken')
            ->where('insurance_staff_no', $staffId)
            ->whereYear('insurance_year', $targetYear)
            ->get()
            ->keyBy('hoken_no');

        $fieldRules = [
            'insurance_company' => ['required', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:20'],
            'applied_system' => ['nullable', 'string', 'max:20'],
            'declared_amount' => ['nullable', 'numeric', 'min:0'],
            'insurance_type' => ['nullable', 'string', 'max:50'],
            'insurance_period' => ['nullable', 'string', 'max:10'],
            'policy_holder_name' => ['nullable', 'string', 'max:20'],
            'beneficiary_name' => ['nullable', 'string', 'max:20'],
            'beneficiary_relationship' => ['nullable', 'string', 'max:10'],
            'pension_payment_start_date' => ['nullable', 'date'],
            'year_end_insurance_note' => ['nullable', 'string', 'max:2000'],
        ];

        $fieldMessages = [
            'insurance_company.required' => '保険会社を入力してください。',
        ];

        $anyChange = false;

        $hokenInput = (array) $request->input('hoken', []);
        foreach ($hokenInput as $hokenNo => $row) {
            $hokenNo = (int) $hokenNo;
            $existingRow = $existingRows->get($hokenNo);
            if ($existingRow === null) {
                continue;
            }

            if (!empty($row['remove'])) {
                if ((int) ($existingRow->checked_flag ?? 0) === 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "hoken.{$hokenNo}.remove" => '事務所確認済みの保険は削除できません。事務所へご連絡ください。',
                    ]);
                }
                $this->certificateFileService->delete($existingRow->certificate_file_path);
                DB::connection('sqlsrv_payroll')
                    ->table('dbo.mx_hoken')
                    ->where('hoken_no', $hokenNo)
                    ->where('insurance_staff_no', $staffId)
                    ->delete();
                $anyChange = true;
                continue;
            }

            if (!isset($row['changed']) || $row['changed'] !== '1') {
                continue;
            }

            $validated = Validator::make($row, $fieldRules, $fieldMessages)->validate();
            $this->requireCertificate($request, "hoken.{$hokenNo}.certificate_file", $existingRow, '保険料控除の証憑ファイルを添付してください。');
            $certificate = $this->resolveCertificateFields($request, "hoken.{$hokenNo}.certificate_file", $existingRow, $staffId, $targetYear, 'hoken_' . $hokenNo);

            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_hoken')
                ->where('hoken_no', $hokenNo)
                ->where('insurance_staff_no', $staffId)
                ->update(array_merge($validated, $certificate, ['checked_flag' => 0]));
            $anyChange = true;
        }

        $addInput = (array) $request->input('hoken_add', []);
        foreach ($addInput as $slotNo => $row) {
            if (!isset($row['enabled']) || $row['enabled'] !== '1') {
                continue;
            }

            $validated = Validator::make($row, $fieldRules, $fieldMessages)->validate();
            $this->requireCertificate($request, "hoken_add.{$slotNo}.certificate_file", null, '保険料控除の証憑ファイルを添付してください。');
            $certificate = $this->resolveCertificateFields($request, "hoken_add.{$slotNo}.certificate_file", null, $staffId, $targetYear, 'hoken_add_' . $slotNo);

            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_hoken')
                ->insert(array_merge($validated, $certificate, [
                    'insurance_staff_no' => $staffId,
                    'insurance_year' => sprintf('%04d-12-31', $targetYear),
                    'checked_flag' => 0,
                ]));
            $anyChange = true;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update(['insurance_deduction_changed' => $anyChange ? 1 : 0]);

        return redirect()->route('year_end_adjustment')->with('statusMessage', '保険料控除の申告内容を保存しました。');
    }

    /**
     * 新しいファイルのアップロードも、対象行に既存の証憑もない場合は保存させない
     * （変更ありなのに証憑なしでの申告を防ぐ）。
     */
    private function requireCertificate(Request $request, string $fileFieldPath, ?object $previousRow, string $message, string $existingPathProperty = 'certificate_file_path'): void
    {
        $hasNewFile = $request->file($fileFieldPath) !== null;
        $hasExistingFile = $previousRow !== null && !empty($previousRow->{$existingPathProperty});

        if (!$hasNewFile && !$hasExistingFile) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $fileFieldPath => $message,
            ]);
        }
    }

    /**
     * 新しいファイルがアップロードされていればそれを保存し、対象行に既存の証憑があれば
     * 差し替え前に削除する。アップロードがなければ、対象行の証憑情報をそのまま返す
     * （file inputは値を復元できないため、UPDATE文で同じ値を書き戻す形になる）。
     *
     * @return array<string, mixed>
     */
    private function resolveCertificateFields(Request $request, string $fileFieldPath, ?object $previousRow, string $staffId, int $targetYear, string $baseName): array
    {
        $file = $request->file($fileFieldPath);
        if ($file !== null && $file->isValid()) {
            if ($previousRow !== null && !empty($previousRow->certificate_file_path)) {
                $this->certificateFileService->delete($previousRow->certificate_file_path);
            }
            $stored = $this->certificateFileService->store(
                $file,
                "year_end/{$targetYear}/{$staffId}/insurance",
                $baseName . '_' . date('YmdHis'),
            );

            return [
                'certificate_file_path' => $stored['path'],
                'certificate_original_name' => $stored['original_name'],
                'certificate_uploaded_at' => $stored['uploaded_at'],
            ];
        }

        if ($previousRow !== null) {
            return [
                'certificate_file_path' => $previousRow->certificate_file_path,
                'certificate_original_name' => $previousRow->certificate_original_name,
                'certificate_uploaded_at' => $previousRow->certificate_uploaded_at,
            ];
        }

        return [
            'certificate_file_path' => null,
            'certificate_original_name' => null,
            'certificate_uploaded_at' => null,
        ];
    }

    /**
     * 扶養親族の障害者手帳証憑。「障害あり」チェックがなければ証憑は不要（既存があれば消す）。
     * チェックがあれば、新規アップロードか対象行に既存の証憑のどちらかが必須。
     *
     * @return array<string, mixed>
     */
    private function resolveDependentCertificateFields(Request $request, string $fileFieldPath, bool $hasDisability, ?object $previousRow, string $staffId, int $targetYear, string $baseName): array
    {
        if (!$hasDisability) {
            if ($previousRow !== null && !empty($previousRow->failure_certificate_file_path)) {
                $this->certificateFileService->delete($previousRow->failure_certificate_file_path);
            }

            return [
                'failure_certificate_file_path' => null,
                'failure_certificate_original_name' => null,
                'failure_certificate_uploaded_at' => null,
            ];
        }

        $file = $request->file($fileFieldPath);
        if ($file !== null && $file->isValid()) {
            if ($previousRow !== null && !empty($previousRow->failure_certificate_file_path)) {
                $this->certificateFileService->delete($previousRow->failure_certificate_file_path);
            }
            $stored = $this->certificateFileService->store(
                $file,
                "year_end/{$targetYear}/{$staffId}/fuyo",
                $baseName . '_' . date('YmdHis'),
            );

            return [
                'failure_certificate_file_path' => $stored['path'],
                'failure_certificate_original_name' => $stored['original_name'],
                'failure_certificate_uploaded_at' => $stored['uploaded_at'],
            ];
        }

        if ($previousRow !== null && !empty($previousRow->failure_certificate_file_path)) {
            return [
                'failure_certificate_file_path' => $previousRow->failure_certificate_file_path,
                'failure_certificate_original_name' => $previousRow->failure_certificate_original_name,
                'failure_certificate_uploaded_at' => $previousRow->failure_certificate_uploaded_at,
            ];
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            $fileFieldPath => '障害者手帳の写しを添付してください。',
        ]);
    }

    /**
     * 配偶者はmx_fuyoに続柄「配偶者」の行として保存する（扶養親族と同じ仕組み）。
     * 収入→所得の変換は保存の瞬間に計算してmx_nen_tyo.haigu_umu/haigu_shotokuへ
     * 直接書き込む（ステージングが無くなったため、反映待ちにせずその場で計算する）。
     */
    public function updateSpouse(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, , $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        $currentSpouseRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->whereIn('fuyo_relationship', self::SPOUSE_RELATIONSHIPS)
            ->first();

        // 「変わった」（扶養親族の各行と同じ意味のトグル）がチェックされていなければ
        // 申告なし＝現状維持。
        $engaged = $request->boolean('spouse_engaged');

        if (!$engaged) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_year_end_applications')
                ->where('application_id', $application['application_id'])
                ->update(['spouse_changed' => false]);

            return redirect()->route('year_end_adjustment')->with('statusMessage', '配偶者の申告内容を保存しました。');
        }

        $validated = $request->validate([
            'fuyo_name' => ['required', 'string', 'max:50'],
            'fuyo_name_furi' => ['nullable', 'string', 'max:50'],
            'fuyo_birthday' => ['nullable', 'date'],
            'fuyo_address' => ['nullable', 'string', 'max:255'],
            'fuyo_shunyu' => ['required', 'numeric', 'min:0'],
            'next_year_fuyo_shunyu' => ['nullable', 'numeric', 'min:0'],
        ], [
            'fuyo_name.required' => '配偶者の氏名を入力してください。',
            'fuyo_shunyu.required' => '配偶者の年間収入見込みを入力してください。',
        ]);

        $deductionTarget = $request->boolean('deduction_target') ? 1 : 0;

        $fields = [
            'fuyo_name' => $validated['fuyo_name'],
            'fuyo_name_furi' => $validated['fuyo_name_furi'] ?? null,
            'fuyo_relationship' => '配偶者',
            'fuyo_birthday' => $validated['fuyo_birthday'] ?? null,
            'fuyo_address' => $validated['fuyo_address'] ?? null,
            'fuyo_shunyu' => $validated['fuyo_shunyu'],
            'deduction_target' => $deductionTarget,
        ];

        if ($currentSpouseRow !== null) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_fuyo')
                ->where('fuyo_no', $currentSpouseRow->fuyo_no)
                ->where('staff_id', $staffId)
                ->update($fields);
        } else {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_fuyo')
                ->insert(array_merge($fields, [
                    'staff_id' => $staffId,
                    'registration_date' => now(),
                    'widow' => 0,
                ]));
        }

        // 正本：ステージング時点の値を鵜呑みにせず、保存の瞬間にここで再計算する。
        $recomputedIncome = $deductionTarget === 1
            ? $this->calculationService->salaryIncomeAfterDeduction((float) $validated['fuyo_shunyu'], $targetYear)
            : null;

        $nenTyo = $this->findOrCreateNenTyoRow($staffId, $targetYear, (int) ($application['nen_tyo_no'] ?? 0));
        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $nenTyo['nen_tyo_no'])
            ->update([
                'haigu_umu' => $deductionTarget === 1 ? '〇' : null,
                'haigu_shotoku' => $recomputedIncome,
            ]);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update(['spouse_changed' => true]);

        // 翌年分もこの場で作成しておく（今年の内容をそのままコピー、収入見込みだけ
        // 変更予定があれば上書き）。翌年の扶養控除申告書・給与の源泉徴収税額表の
        // 扶養人数カウント（PayrollV2FuyoService、registration_dateの年で判定）が
        // 翌年の年調が始まる前から漏れなく動くようにするため。
        $nextYear = $targetYear + 1;
        $nextYearIncome = $validated['next_year_fuyo_shunyu'] ?? $validated['fuyo_shunyu'];
        $nextYearFields = array_merge($fields, ['fuyo_shunyu' => $nextYearIncome]);

        $nextYearSpouseRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $nextYear)
            ->whereIn('fuyo_relationship', self::SPOUSE_RELATIONSHIPS)
            ->first();

        if ($nextYearSpouseRow !== null) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_fuyo')
                ->where('fuyo_no', $nextYearSpouseRow->fuyo_no)
                ->where('staff_id', $staffId)
                ->update($nextYearFields);
        } else {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_fuyo')
                ->insert(array_merge($nextYearFields, [
                    'staff_id' => $staffId,
                    'registration_date' => sprintf('%04d-12-31', $nextYear),
                    'widow' => 0,
                ]));
        }

        return redirect()->route('year_end_adjustment')->with('statusMessage', '配偶者の申告内容を保存しました（翌年分の扶養データも作成しました）。');
    }

    /** 表示専用（見つからなければ空配列）。書き込み時はfindOrCreateNenTyoRow()を使う。 */
    /** @return array<string, mixed> */
    private function currentNenTyoRow(string $staffId, int $targetYear, int $nenTyoNo): array
    {
        $query = DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo');

        if ($nenTyoNo > 0) {
            $row = $query->where('nen_tyo_no', $nenTyoNo)->first();
            if ($row !== null) {
                return (array) $row;
            }
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('staff_id', $staffId)
            ->whereYear('year_end', $targetYear)
            ->orderBy('nen_tyo_no')
            ->first();

        return $row !== null ? (array) $row : [];
    }

    /**
     * mx_nen_tyoの当年行を取得、なければ作成する（admin側findOrCreateNenTyoRow()と同じ
     * ロジック）。スタッフが住宅ローン・前職・配偶者・本人状況フラグを直接書き込む際に使う。
     *
     * @return array<string, mixed>
     */
    private function findOrCreateNenTyoRow(string $staffId, int $targetYear, int $nenTyoNo): array
    {
        $query = DB::connection('sqlsrv_payroll')->table('dbo.mx_nen_tyo');

        if ($nenTyoNo > 0) {
            $row = $query->where('nen_tyo_no', $nenTyoNo)->first();
            if ($row !== null) {
                return (array) $row;
            }
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('staff_id', $staffId)
            ->whereYear('year_end', $targetYear)
            ->orderBy('nen_tyo_no')
            ->first();

        if ($row !== null) {
            return (array) $row;
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

        return (array) DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $newNo)
            ->first();
    }

    /**
     * 氏名・住所・生年月日・世帯主情報（mx_staffs.head_house/relationship。扶養控除
     * 申告書PDFもここから読んでいる）は引き続きstaff_year_end_applicationsへ
     * ステージングし、事務所確認後にmx_staffsへ反映する（履歴を持たない単一マスタのため）。
     * 本人状況フラグ（障害者・ひとり親・寡婦・勤労学生）は年調専用のmx_nen_tyoへ
     * その場で直接書き込む（同じ1回の送信で2箇所に書き込む）。
     */
    public function updatePersonalInfo(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, $staffRow, $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        $currentAddress = trim((string) ($staffRow['address'] ?? ''));
        $currentStaffName = trim((string) ($staffRow['staff_name'] ?? ''));

        $previousApp = (array) DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->first([
                'address_change_certificate_file_path', 'address_change_certificate_original_name', 'address_change_certificate_uploaded_at',
                'name_change_certificate_file_path', 'name_change_certificate_original_name', 'name_change_certificate_uploaded_at',
            ]);

        $nenTyo = $this->findOrCreateNenTyoRow($staffId, $targetYear, (int) ($application['nen_tyo_no'] ?? 0));
        $previousNenTyo = (object) $nenTyo;

        $changed = $request->boolean('personal_info_changed');
        $appValues = ['personal_info_changed' => $changed];
        $nenTyoValues = [];

        if ($changed) {
            $validated = $request->validate([
                'new_address' => ['required', 'string', 'max:255'],
                'new_address_furi' => ['nullable', 'string', 'max:255'],
                'new_staff_name' => ['required', 'string', 'max:50'],
                'new_staff_name_furi' => ['nullable', 'string', 'max:50'],
                'new_birthday' => ['nullable', 'date'],
                'setai_nushi' => ['nullable', 'string', 'max:50'],
                'setai_zoku_gara' => ['nullable', 'string', 'max:20'],
            ]);
            $appValues['new_address'] = $validated['new_address'];
            $appValues['new_address_furi'] = $validated['new_address_furi'] ?? null;
            $appValues['new_staff_name'] = $validated['new_staff_name'];
            $appValues['new_staff_name_furi'] = $validated['new_staff_name_furi'] ?? null;
            $appValues['new_birthday'] = $validated['new_birthday'] ?? null;
            // 世帯主情報はmx_staffs.head_house/relationshipが正本（扶養控除申告書PDFも
            // ここから読んでいる）。氏名・住所と同じくステージング経由でmx_staffsへ反映する。
            $appValues['setai_nushi'] = $validated['setai_nushi'] ?? null;
            $appValues['setai_zoku_gara'] = $validated['setai_zoku_gara'] ?? null;

            $nenTyoValues['hon_shougai_toku'] = $request->boolean('hon_shougai_toku') ? 1 : 0;
            $nenTyoValues['hon_shougai_ta'] = $request->boolean('hon_shougai_ta') ? 1 : 0;
            $nenTyoValues['hitori_oya'] = $request->boolean('hitori_oya') ? 1 : 0;
            $nenTyoValues['kafu'] = $request->boolean('kafu') ? 1 : 0;
            $nenTyoValues['student'] = $request->boolean('student') ? 1 : 0;

            // 住所・氏名は実際に値が変わった場合のみ証憑必須（同じ値を再確認しただけなら不要）。
            $addressActuallyChanged = $validated['new_address'] !== $currentAddress;
            $nameActuallyChanged = $validated['new_staff_name'] !== $currentStaffName;
            $hasDisability = (bool) $nenTyoValues['hon_shougai_toku'] || (bool) $nenTyoValues['hon_shougai_ta'];

            if ($addressActuallyChanged) {
                $this->requireCertificate($request, 'address_change_certificate_file', (object) $previousApp, '住所変更の証憑（住民票等）を添付してください。', 'address_change_certificate_file_path');
                $appValues = array_merge($appValues, $this->resolveCertificateFieldsFor($request, 'address_change_certificate_file', (object) $previousApp, 'address_change_certificate', $staffId, $targetYear, 'jusho'));
            } else {
                $this->deleteCertificateIfPresent($previousApp['address_change_certificate_file_path'] ?? null);
                $appValues['address_change_certificate_file_path'] = null;
                $appValues['address_change_certificate_original_name'] = null;
                $appValues['address_change_certificate_uploaded_at'] = null;
            }

            if ($nameActuallyChanged) {
                $this->requireCertificate($request, 'name_change_certificate_file', (object) $previousApp, '氏名変更の証憑（戸籍謄本・婚姻届受理証明書等）を添付してください。', 'name_change_certificate_file_path');
                $appValues = array_merge($appValues, $this->resolveCertificateFieldsFor($request, 'name_change_certificate_file', (object) $previousApp, 'name_change_certificate', $staffId, $targetYear, 'shimei'));
            } else {
                $this->deleteCertificateIfPresent($previousApp['name_change_certificate_file_path'] ?? null);
                $appValues['name_change_certificate_file_path'] = null;
                $appValues['name_change_certificate_original_name'] = null;
                $appValues['name_change_certificate_uploaded_at'] = null;
            }

            if ($hasDisability) {
                $this->requireCertificate($request, 'disability_certificate_file', $previousNenTyo, '障害者手帳の写しを添付してください。', 'disability_certificate_file_path');
                $nenTyoValues = array_merge($nenTyoValues, $this->resolveCertificateFieldsFor($request, 'disability_certificate_file', $previousNenTyo, 'disability_certificate', $staffId, $targetYear, 'shougai'));
            } else {
                $this->deleteCertificateIfPresent($previousNenTyo->disability_certificate_file_path ?? null);
                $nenTyoValues['disability_certificate_file_path'] = null;
                $nenTyoValues['disability_certificate_original_name'] = null;
                $nenTyoValues['disability_certificate_uploaded_at'] = null;
            }
        } else {
            $appValues['new_address'] = null;
            $appValues['new_address_furi'] = null;
            $appValues['new_staff_name'] = null;
            $appValues['new_staff_name_furi'] = null;
            $appValues['new_birthday'] = null;
            $appValues['setai_nushi'] = null;
            $appValues['setai_zoku_gara'] = null;

            $this->deleteCertificateIfPresent($previousApp['address_change_certificate_file_path'] ?? null);
            $this->deleteCertificateIfPresent($previousApp['name_change_certificate_file_path'] ?? null);
            $appValues['address_change_certificate_file_path'] = null;
            $appValues['address_change_certificate_original_name'] = null;
            $appValues['address_change_certificate_uploaded_at'] = null;
            $appValues['name_change_certificate_file_path'] = null;
            $appValues['name_change_certificate_original_name'] = null;
            $appValues['name_change_certificate_uploaded_at'] = null;

            $nenTyoValues['hon_shougai_toku'] = null;
            $nenTyoValues['hon_shougai_ta'] = null;
            $nenTyoValues['hitori_oya'] = null;
            $nenTyoValues['kafu'] = null;
            $nenTyoValues['student'] = null;

            $this->deleteCertificateIfPresent($previousNenTyo->disability_certificate_file_path ?? null);
            $nenTyoValues['disability_certificate_file_path'] = null;
            $nenTyoValues['disability_certificate_original_name'] = null;
            $nenTyoValues['disability_certificate_uploaded_at'] = null;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update($appValues);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->where('nen_tyo_no', $nenTyo['nen_tyo_no'])
            ->update($nenTyoValues);

        return redirect()->route('year_end_adjustment')->with('statusMessage', '本人情報の申告内容を保存しました。');
    }

    private function deleteCertificateIfPresent(?string $path): void
    {
        if (!empty($path)) {
            $this->certificateFileService->delete($path);
        }
    }

    /**
     * 証憑系（住所変更・氏名変更・障害者手帳・住宅ローン・前職）共通の保存処理。
     * $columnPrefixに応じて {$columnPrefix}_file_path 等のキーで返す。新しいファイルが
     * あれば対象行の既存ファイルを削除してから保存する（ステージングの「引き継ぎ」処理が
     * 無くなったため、差し替え時は呼び出し側でなくここで確実に消す）。
     *
     * @return array<string, mixed>
     */
    private function resolveCertificateFieldsFor(Request $request, string $fileFieldPath, object $previousRow, string $columnPrefix, string $staffId, int $targetYear, string $baseName): array
    {
        $file = $request->file($fileFieldPath);
        if ($file !== null && $file->isValid()) {
            $existingPath = $previousRow->{$columnPrefix . '_file_path'} ?? null;
            if (!empty($existingPath)) {
                $this->certificateFileService->delete($existingPath);
            }
            $stored = $this->certificateFileService->store(
                $file,
                "year_end/{$targetYear}/{$staffId}/personal_info",
                $baseName . '_' . date('YmdHis'),
            );

            return [
                $columnPrefix . '_file_path' => $stored['path'],
                $columnPrefix . '_original_name' => $stored['original_name'],
                $columnPrefix . '_uploaded_at' => $stored['uploaded_at'],
            ];
        }

        return [
            $columnPrefix . '_file_path' => $previousRow->{$columnPrefix . '_file_path'} ?? null,
            $columnPrefix . '_original_name' => $previousRow->{$columnPrefix . '_original_name'} ?? null,
            $columnPrefix . '_uploaded_at' => $previousRow->{$columnPrefix . '_uploaded_at'} ?? null,
        ];
    }

    /**
     * mx_fuyoへ直接update/insert。扶養は年調専用ではないが、registration_dateで
     * 年ごとに行が分かれるため、スタッフの直書きでも過去のデータは壊れない。
     * deduction_targetの解除（対象外化）も既存行の編集から行える
     * （他に事務所へ伝わる経路がないため、ここがスタッフからの唯一の申告手段）。
     */
    public function updateDependents(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, , $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        $existingRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->whereNotIn('fuyo_relationship', self::SPOUSE_RELATIONSHIPS)
            ->get()
            ->keyBy('fuyo_no');

        $fieldRules = [
            'fuyo_name' => ['required', 'string', 'max:50'],
            'fuyo_name_furi' => ['nullable', 'string', 'max:50'],
            'fuyo_relationship' => ['required', 'string', 'max:50'],
            'fuyo_address' => ['nullable', 'string', 'max:255'],
            'fuyo_birthday' => ['required', 'date'],
            'fuyo_sex' => ['nullable', 'string', 'max:5'],
            'kyojyu' => ['nullable', 'string', 'max:5'],
            'fuyo_shunyu' => ['nullable', 'numeric', 'min:0'],
            'failure_notebook' => ['nullable', 'string', 'max:50'],
            'failure_judgment' => ['nullable', 'string', 'max:50'],
        ];

        $fieldMessages = [
            'fuyo_name.required' => '氏名を入力してください。',
            'fuyo_relationship.required' => '続柄を入力してください。',
            'fuyo_birthday.required' => '生年月日を入力してください。',
        ];

        $anyChange = false;

        $fuyoInput = (array) $request->input('fuyo', []);
        foreach ($fuyoInput as $fuyoNo => $row) {
            $fuyoNo = (int) $fuyoNo;
            $existingRow = $existingRows->get($fuyoNo);
            if ($existingRow === null) {
                continue;
            }
            if (!isset($row['changed']) || $row['changed'] !== '1') {
                continue;
            }

            $validated = Validator::make($row, $fieldRules, $fieldMessages)->validate();
            $hasDisability = !empty($row['has_disability']);
            $certificate = $this->resolveDependentCertificateFields($request, "fuyo.{$fuyoNo}.failure_certificate_file", $hasDisability, $existingRow, $staffId, $targetYear, 'fuyo_' . $fuyoNo);

            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_fuyo')
                ->where('fuyo_no', $fuyoNo)
                ->where('staff_id', $staffId)
                ->update(array_merge($validated, $certificate, [
                    'deduction_target' => !empty($row['deduction_target']) ? 1 : 0,
                    'widow' => !empty($row['widow']) ? 1 : 0,
                ]));
            $anyChange = true;
        }

        $addInput = (array) $request->input('add', []);
        foreach ($addInput as $slotNo => $row) {
            if (!isset($row['enabled']) || $row['enabled'] !== '1') {
                continue;
            }

            $validated = Validator::make($row, $fieldRules, $fieldMessages)->validate();
            $hasDisability = !empty($row['has_disability']);
            $certificate = $this->resolveDependentCertificateFields($request, "add.{$slotNo}.failure_certificate_file", $hasDisability, null, $staffId, $targetYear, 'fuyo_add_' . $slotNo);

            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_fuyo')
                ->insert(array_merge($validated, $certificate, [
                    'staff_id' => $staffId,
                    'registration_date' => now(),
                    'deduction_target' => !empty($row['deduction_target']) ? 1 : 0,
                    'widow' => !empty($row['widow']) ? 1 : 0,
                ]));
            $anyChange = true;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update(['dependents_changed' => $anyChange ? 1 : 0]);

        return redirect()->route('year_end_adjustment')->with('statusMessage', '扶養の申告内容を保存しました。');
    }

    public function submit(Request $request): RedirectResponse
    {
        $context = $this->requireContext($request);
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$staffId, , $targetYear] = $context;

        $application = $this->findOrCreateApplication($staffId, $targetYear);
        $blocked = $this->blockIfNotEditable($application);
        if ($blocked !== null) {
            return $blocked;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $application['application_id'])
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

        return redirect()->route('year_end_adjustment')->with('statusMessage', '提出しました。事務所の確認をお待ちください。');
    }

    /** @return array{0: string, 1: array<string, mixed>, 2: int}|RedirectResponse */
    private function requireContext(Request $request): array|RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isAdmin($staffRow) && !$this->isYearEndAdjustmentPublished()) {
            abort(404);
        }

        return [$staffId, $staffRow ?? [], (int) date('Y')];
    }

    private function blockIfNotEditable(array $application): ?RedirectResponse
    {
        if (in_array($application['status'], self::EDITABLE_STATUSES, true)) {
            return null;
        }

        return redirect()->route('year_end_adjustment')->with('errorMessage', '提出済みのため編集できません。');
    }

    /** @return array<string, mixed> */
    private function findOrCreateApplication(string $staffId, int $targetYear): array
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('staff_id', $staffId)
            ->where('target_year', $targetYear)
            ->first();

        if ($row !== null) {
            return (array) $row;
        }

        $applicationId = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->insertGetId([
                'staff_id' => $staffId,
                'target_year' => $targetYear,
                'status' => 'draft',
            ], 'application_id');

        return (array) DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_year_end_applications')
            ->where('application_id', $applicationId)
            ->first();
    }
}
