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
 * 個人情報変更申請（氏名・住所・世帯主・通勤経路・扶養増減）。年末調整の「本人情報」
 * セクションと同じ考え方で、年中いつでも利用できる。氏名・住所・世帯主・通勤経路は
 * mx_staffs（履歴を持たない単一マスタ）が対象のため、いったんstaff_profile_requestsへ
 * ステージングし、事務所確認後にmx_staffsへ反映する。扶養増減はmx_fuyo
 * （registration_dateで年ごとに行が分かれる）へ直接書き込む（年末調整の
 * YearEndApplicationController::updateDependents()と同じ理由・同じロジック）。
 */
class ProfileRequestController extends Controller
{
    use HandlesStaffPortalContext;

    public function __construct(
        private readonly CertificateFileService $certificateFileService,
    ) {}

    private const EDITABLE_STATUSES = ['draft', 'returned'];

    /**
     * 配偶者はmx_fuyoに続柄「夫/妻/配偶者」の行として保存する（年末調整機能と同じ判定）。
     * このフォームの扶養一覧には出さない（配偶者は年末調整側で扱う）。
     */
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
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->whereYear('registration_date', $targetYear)
            ->whereNotIn('fuyo_relationship', self::SPOUSE_RELATIONSHIPS)
            ->orderBy('fuyo_no')
            ->get()
            ->map(fn($row): array => (array) $row)
            ->all();

        return view('staff_portal.profile_request.index', $this->commonViewData($request, [
            'staffId' => $staffId,
            'requestRow' => $requestRow,
            'editable' => in_array($requestRow['status'], self::EDITABLE_STATUSES, true),
            'statusLabel' => self::STATUS_LABELS[$requestRow['status']] ?? $requestRow['status'],
            'currentStaffName' => trim((string) ($staffRow['staff_name'] ?? '')),
            'currentStaffNameFuri' => trim((string) ($staffRow['staff_name_furi'] ?? '')),
            'currentAddress' => trim((string) ($staffRow['address'] ?? '')),
            'currentAddressFuri' => trim((string) ($staffRow['address_furi'] ?? '')),
            'currentHeadHouse' => trim((string) ($staffRow['head_house'] ?? '')),
            'currentRelationship' => trim((string) ($staffRow['relationship'] ?? '')),
            'currentCarKm' => trim((string) ($staffRow['car_km'] ?? '')),
            'currentTrafficDay' => trim((string) ($staffRow['traffic_day'] ?? '')),
            'currentTrafficDayTuika' => trim((string) ($staffRow['traffic_day_tuika'] ?? '')),
            'dependentRows' => $dependentRows,
        ]));
    }

    public function updatePersonalInfo(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }
        $staffRow = $this->staffPortalStaffRow($staffId) ?? [];

        $requestRow = $this->findOrCreateActiveRequest($staffId);
        $blocked = $this->blockIfNotEditable($requestRow);
        if ($blocked !== null) {
            return $blocked;
        }

        $currentStaffName = trim((string) ($staffRow['staff_name'] ?? ''));
        $currentAddress = trim((string) ($staffRow['address'] ?? ''));
        $currentCarKm = trim((string) ($staffRow['car_km'] ?? ''));
        $currentTrafficDay = trim((string) ($staffRow['traffic_day'] ?? ''));
        $currentTrafficDayTuika = trim((string) ($staffRow['traffic_day_tuika'] ?? ''));

        $validated = $request->validate([
            'new_staff_name' => ['required', 'string', 'max:50'],
            'new_staff_name_furi' => ['nullable', 'string', 'max:50'],
            'new_address' => ['required', 'string', 'max:255'],
            'new_address_furi' => ['nullable', 'string', 'max:255'],
            'setai_nushi' => ['nullable', 'string', 'max:50'],
            'setai_zoku_gara' => ['nullable', 'string', 'max:20'],
            'new_car_km' => ['nullable', 'string', 'max:100'],
            'new_traffic_day' => ['nullable', 'string', 'max:100'],
            'new_traffic_day_tuika' => ['nullable', 'string', 'max:100'],
        ]);

        $values = [
            'new_staff_name' => $validated['new_staff_name'],
            'new_staff_name_furi' => $validated['new_staff_name_furi'] ?? null,
            'new_address' => $validated['new_address'],
            'new_address_furi' => $validated['new_address_furi'] ?? null,
            'setai_nushi' => $validated['setai_nushi'] ?? null,
            'setai_zoku_gara' => $validated['setai_zoku_gara'] ?? null,
            'new_car_km' => $validated['new_car_km'] ?? null,
            'new_traffic_day' => $validated['new_traffic_day'] ?? null,
            'new_traffic_day_tuika' => $validated['new_traffic_day_tuika'] ?? null,
        ];

        // 氏名・住所・通勤経路は、実際に値が変わった場合のみ証憑必須（同じ値の再確認だけなら不要）。
        $nameActuallyChanged = $validated['new_staff_name'] !== $currentStaffName;
        $addressActuallyChanged = $validated['new_address'] !== $currentAddress;
        $commuteActuallyChanged = ($validated['new_car_km'] ?? '') !== $currentCarKm
            || ($validated['new_traffic_day'] ?? '') !== $currentTrafficDay
            || ($validated['new_traffic_day_tuika'] ?? '') !== $currentTrafficDayTuika;

        if ($nameActuallyChanged) {
            $this->requireCertificate($request, 'name_change_certificate_file', (object) $requestRow, '氏名変更の証憑（戸籍謄本・婚姻届受理証明書等）を添付してください。', 'name_change_certificate_file_path');
            $values = array_merge($values, $this->resolveCertificateFields($request, 'name_change_certificate_file', (object) $requestRow, 'name_change_certificate', $staffId, 'shimei'));
        } else {
            $this->deleteCertificateIfPresent($requestRow['name_change_certificate_file_path'] ?? null);
            $values['name_change_certificate_file_path'] = null;
            $values['name_change_certificate_original_name'] = null;
            $values['name_change_certificate_uploaded_at'] = null;
        }

        if ($addressActuallyChanged) {
            $this->requireCertificate($request, 'address_change_certificate_file', (object) $requestRow, '住所変更の証憑（住民票等）を添付してください。', 'address_change_certificate_file_path');
            $values = array_merge($values, $this->resolveCertificateFields($request, 'address_change_certificate_file', (object) $requestRow, 'address_change_certificate', $staffId, 'jusho'));
        } else {
            $this->deleteCertificateIfPresent($requestRow['address_change_certificate_file_path'] ?? null);
            $values['address_change_certificate_file_path'] = null;
            $values['address_change_certificate_original_name'] = null;
            $values['address_change_certificate_uploaded_at'] = null;
        }

        if ($commuteActuallyChanged) {
            $this->requireCertificate($request, 'commute_change_certificate_file', (object) $requestRow, '通勤経路変更の証憑（定期券の写し等）を添付してください。', 'commute_change_certificate_file_path');
            $values = array_merge($values, $this->resolveCertificateFields($request, 'commute_change_certificate_file', (object) $requestRow, 'commute_change_certificate', $staffId, 'tsukin'));
        } else {
            $this->deleteCertificateIfPresent($requestRow['commute_change_certificate_file_path'] ?? null);
            $values['commute_change_certificate_file_path'] = null;
            $values['commute_change_certificate_original_name'] = null;
            $values['commute_change_certificate_uploaded_at'] = null;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestRow['request_id'])
            ->update($values);

        return redirect()->route('profile_request')->with('statusMessage', '本人情報の申告内容を保存しました。');
    }

    /**
     * mx_fuyoへ直接update/insert。扶養はregistration_dateで年ごとに行が分かれるため、
     * スタッフの直書きでも過去のデータは壊れない（年末調整と同じ理由）。deduction_targetの
     * 解除（対象外化）は既存のマスタ管理画面（事務所側）でのみ行う。
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
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
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
                ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
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
                ->table('dbo.staff_profile_requests')
                ->where('request_id', $requestRow['request_id'])
                ->update(['updated_at' => now()]);
        }

        return redirect()->route('profile_request')->with('statusMessage', '扶養の申告内容を保存しました。');
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
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestRow['request_id'])
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

        return redirect()->route('profile_request')->with('statusMessage', '提出しました。事務所の確認をお待ちください。');
    }

    private function deleteCertificateIfPresent(?string $path): void
    {
        if (!empty($path)) {
            $this->certificateFileService->delete($path);
        }
    }

    /**
     * 新しいファイルのアップロードも、対象行に既存の証憑もない場合は保存させない
     * （変更ありなのに証憑なしでの申告を防ぐ）。
     */
    private function requireCertificate(Request $request, string $fileFieldPath, ?object $previousRow, string $message, string $existingPathProperty): void
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
     * 証憑系（氏名変更・住所変更・通勤経路変更）共通の保存処理。$columnPrefixに応じて
     * {$columnPrefix}_file_path 等のキーで返す。新しいファイルがあれば対象行の既存ファイルを
     * 削除してから保存する。
     *
     * @return array<string, mixed>
     */
    private function resolveCertificateFields(Request $request, string $fileFieldPath, object $previousRow, string $columnPrefix, string $staffId, string $baseName): array
    {
        $file = $request->file($fileFieldPath);
        if ($file !== null && $file->isValid()) {
            $existingPath = $previousRow->{$columnPrefix . '_file_path'} ?? null;
            if (!empty($existingPath)) {
                $this->certificateFileService->delete($existingPath);
            }
            $stored = $this->certificateFileService->store(
                $file,
                "profile/{$staffId}/personal_info",
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
                "profile/{$staffId}/fuyo",
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

        return redirect()->route('profile_request')->with('errorMessage', '提出済みのため編集できません。');
    }

    /**
     * reflected以外の直近の申請があればそれを返す（draft/returnedなら編集可、
     * submitted/confirmedなら表示のみ）。無ければ新規draftを作る。年末調整の
     * findOrCreateApplication()と同じ形だが、target_yearの概念が無いため
     * 「未反映の申請は同時に1件まで」というルールでまとめる。
     *
     * @return array<string, mixed>
     */
    private function findOrCreateActiveRequest(string $staffId): array
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->where('status', '!=', 'reflected')
            ->orderByDesc('request_id')
            ->first();

        if ($row !== null) {
            return (array) $row;
        }

        $requestId = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->insertGetId([
                'staff_id' => $staffId,
                'status' => 'draft',
            ], 'request_id');

        return (array) DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->first();
    }
}
