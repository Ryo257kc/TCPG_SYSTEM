<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\YearEnd\CertificateFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * 入社手続き申請（住所・連絡先・振込口座・通勤経路・扶養・マイナンバー証憑）。
 * mx_staffs.employment='採用予定'の新入社スタッフ向け。個人情報変更申請
 * （ProfileRequestController）と同じ「ステージング→事務所確認→反映」の考え方だが、
 * 氏名・生年月日・世帯主はこの機能では扱わない（訂正は個人情報変更申請の方で行う）。
 * マイナンバーは証憑ファイルの保存のみで、数値そのものは保存しない
 * （マイナンバー証憑を開けるルートは事務所側にのみ用意し、スタッフ側には作らない）。
 */
class OnboardingRequestController extends Controller
{
    use HandlesStaffPortalContext;

    public function __construct(
        private readonly CertificateFileService $certificateFileService,
    ) {}

    private const EDITABLE_STATUSES = ['draft', 'returned'];

    private const SPOUSE_RELATIONSHIPS = ['夫', '妻', '配偶者'];

    private const STATUS_LABELS = [
        'draft' => '下書き',
        'submitted' => '提出済',
        'returned' => '差戻し',
        'confirmed' => '確認済',
        'reflected' => '反映済',
    ];

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }
        $staffRow = $this->staffPortalStaffRow($staffId) ?? [];

        $requestRow = $this->findOrCreateActiveRequest($staffId);
        $targetYear = (int) date('Y');

        $dependentRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->whereNotIn('fuyo_relationship', self::SPOUSE_RELATIONSHIPS)
            ->orderBy('fuyo_no')
            ->get()
            ->map(fn($row): array => (array) $row)
            ->all();

        return view('staff_portal.onboarding_request.index', $this->commonViewData($request, [
            'staffId' => $staffId,
            'requestRow' => $requestRow,
            'editable' => in_array($requestRow['status'], self::EDITABLE_STATUSES, true),
            'statusLabel' => self::STATUS_LABELS[$requestRow['status']] ?? $requestRow['status'],
            'currentStaffName' => trim((string) ($staffRow['staff_name'] ?? '')),
            'currentBirthday' => substr((string) ($staffRow['birthday'] ?? ''), 0, 10),
            'currentAddress' => trim((string) ($staffRow['address'] ?? '')),
            'currentAddressFuri' => trim((string) ($staffRow['address_furi'] ?? '')),
            'currentHomeTel' => trim((string) ($staffRow['home_tel'] ?? '')),
            'currentMobileTel' => trim((string) ($staffRow['mobile_tel'] ?? '')),
            'currentBankName' => trim((string) ($staffRow['bank_name_1'] ?? '')),
            'currentBankBranch' => trim((string) ($staffRow['bank_branch_1'] ?? '')),
            'currentAccountType' => trim((string) ($staffRow['account_type'] ?? '')),
            'currentAccountNum' => trim((string) ($staffRow['account_num'] ?? '')),
            'currentCarKm' => trim((string) ($staffRow['car_km'] ?? '')),
            'currentTrafficDay' => trim((string) ($staffRow['traffic_day'] ?? '')),
            'currentTrafficDayTuika' => trim((string) ($staffRow['traffic_day_tuika'] ?? '')),
            'dependentRows' => $dependentRows,
        ]));
    }

    public function updateBasicInfo(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $requestRow = $this->findOrCreateActiveRequest($staffId);
        $blocked = $this->blockIfNotEditable($requestRow);
        if ($blocked !== null) {
            return $blocked;
        }

        $validated = $request->validate([
            'new_address' => ['required', 'string', 'max:255'],
            'new_address_furi' => ['nullable', 'string', 'max:255'],
            'new_home_tel' => ['nullable', 'string', 'max:50'],
            'new_mobile_tel' => ['nullable', 'string', 'max:50'],
            'new_bank_name' => ['nullable', 'string', 'max:100'],
            'new_bank_branch' => ['nullable', 'string', 'max:100'],
            'new_account_type' => ['nullable', 'string', 'max:50'],
            'new_account_num' => ['nullable', 'string', 'max:50'],
            'new_car_km' => ['nullable', 'string', 'max:100'],
            'new_traffic_day' => ['nullable', 'string', 'max:100'],
            'new_traffic_day_tuika' => ['nullable', 'string', 'max:100'],
        ]);

        $values = $validated;

        $addressFile = $request->file('address_certificate_file');
        if ($addressFile !== null && $addressFile->isValid()) {
            $this->deleteCertificateIfPresent($requestRow['address_certificate_file_path'] ?? null);
            $stored = $this->certificateFileService->store($addressFile, "onboarding/{$staffId}", 'jusho_' . date('YmdHis'));
            $values['address_certificate_file_path'] = $stored['path'];
            $values['address_certificate_original_name'] = $stored['original_name'];
            $values['address_certificate_uploaded_at'] = $stored['uploaded_at'];
        }

        $mynumberFile = $request->file('mynumber_certificate_file');
        if ($mynumberFile !== null && $mynumberFile->isValid()) {
            $this->deleteCertificateIfPresent($requestRow['mynumber_certificate_file_path'] ?? null);
            $stored = $this->certificateFileService->store($mynumberFile, "onboarding/{$staffId}", 'mynumber_' . date('YmdHis'));
            $values['mynumber_certificate_file_path'] = $stored['path'];
            $values['mynumber_certificate_original_name'] = $stored['original_name'];
            $values['mynumber_certificate_uploaded_at'] = $stored['uploaded_at'];
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_onboarding_requests')
            ->where('request_id', $requestRow['request_id'])
            ->update($values);

        return redirect()->route('onboarding_request')->with('statusMessage', '申告内容を保存しました。');
    }

    /**
     * mx_fuyoへ直接update/insert。他機能（個人情報変更申請・年末調整）と同じ理由・同じロジック。
     */
    public function updateDependents(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $requestRow = $this->findOrCreateActiveRequest($staffId);
        $blocked = $this->blockIfNotEditable($requestRow);
        if ($blocked !== null) {
            return $blocked;
        }

        $targetYear = (int) date('Y');

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
            $certificate = $this->resolveDependentCertificateFields($request, "fuyo.{$fuyoNo}.failure_certificate_file", $hasDisability, $existingRow, $staffId, 'fuyo_' . $fuyoNo);

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
            $certificate = $this->resolveDependentCertificateFields($request, "add.{$slotNo}.failure_certificate_file", $hasDisability, null, $staffId, 'fuyo_add_' . $slotNo);

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

        if ($anyChange) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_onboarding_requests')
                ->where('request_id', $requestRow['request_id'])
                ->update(['updated_at' => now()]);
        }

        return redirect()->route('onboarding_request')->with('statusMessage', '扶養の申告内容を保存しました。');
    }

    public function submit(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $requestRow = $this->findOrCreateActiveRequest($staffId);
        $blocked = $this->blockIfNotEditable($requestRow);
        if ($blocked !== null) {
            return $blocked;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_onboarding_requests')
            ->where('request_id', $requestRow['request_id'])
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

        return redirect()->route('onboarding_request')->with('statusMessage', '提出しました。事務所の確認をお待ちください。');
    }

    private function deleteCertificateIfPresent(?string $path): void
    {
        if (!empty($path)) {
            $this->certificateFileService->delete($path);
        }
    }

    /**
     * 扶養親族の障害者手帳証憑。「障害あり」チェックがなければ証憑は不要（既存があれば消す）。
     * チェックがあれば、新規アップロードか対象行に既存の証憑のどちらかが必須。
     *
     * @return array<string, mixed>
     */
    private function resolveDependentCertificateFields(Request $request, string $fileFieldPath, bool $hasDisability, ?object $previousRow, string $staffId, string $baseName): array
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
                "onboarding/{$staffId}/fuyo",
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

    private function blockIfNotEditable(array $requestRow): ?RedirectResponse
    {
        if (in_array($requestRow['status'], self::EDITABLE_STATUSES, true)) {
            return null;
        }

        return redirect()->route('onboarding_request')->with('errorMessage', '提出済みのため編集できません。');
    }

    /**
     * reflected以外の直近の申請があればそれを返す。無ければ新規draftを作る
     * （個人情報変更申請のfindOrCreateActiveRequest()と同じ形）。
     *
     * @return array<string, mixed>
     */
    private function findOrCreateActiveRequest(string $staffId): array
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_onboarding_requests')
            ->where('staff_id', $staffId)
            ->where('status', '!=', 'reflected')
            ->orderByDesc('request_id')
            ->first();

        if ($row !== null) {
            return (array) $row;
        }

        $requestId = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_onboarding_requests')
            ->insertGetId([
                'staff_id' => $staffId,
                'status' => 'draft',
            ], 'request_id');

        return (array) DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_onboarding_requests')
            ->where('request_id', $requestId)
            ->first();
    }
}
