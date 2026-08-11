<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Admin\V2\Concerns\HandlesStaffRequestReview;
use App\Http\Controllers\Controller;
use App\Services\YearEnd\CertificateFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 個人情報変更申請（氏名・住所・世帯主・通勤経路・扶養増減）の事務所側レビュー画面。
 * 年末調整の「本人情報」セクションで確立した「現在の登録（マスタ）／申告内容（訂正可）／
 * 証憑」の横並び比較・その場訂正パターンをそのまま踏襲する。
 */
class ProfileRequestV2Controller extends Controller
{
    use HandlesStaffRequestReview;

    public function __construct(
        private readonly CertificateFileService $certificateFileService,
    ) {}

    private const SPOUSE_RELATIONSHIPS = ['夫', '妻', '配偶者'];

    private function requestTableName(): string
    {
        return 'dbo.staff_profile_requests';
    }

    public function index(Request $request): View
    {
        $statusFilter = trim((string) $request->query('status', ''));

        $query = DB::connection('sqlsrv_payroll')->table('dbo.staff_profile_requests');
        if ($statusFilter !== '' && array_key_exists($statusFilter, $this->statusOptions())) {
            $query->where('status', $statusFilter);
        }

        $requests = $query->orderByDesc('request_id')->get();
        $staffDetails = $this->staffListDetails($requests->pluck('staff_id')->all());

        $rows = $requests->map(function ($row) use ($staffDetails): array {
            $staffId = trim((string) ($row->staff_id ?? ''));
            $status = trim((string) ($row->status ?? '')) ?: 'draft';

            return [
                'request_id' => (string) $row->request_id,
                'staff_id' => $staffId,
                'staff_name' => $staffDetails[$staffId]['staff_name'] ?? '',
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'submitted_at' => $this->dateLabel($row->submitted_at ?? null),
                'confirmed_at' => $this->dateLabel($row->confirmed_at ?? null),
                'reflected_at' => $this->dateLabel($row->reflected_at ?? null),
            ];
        })->all();

        return view('admin_v2.work.profile_requests.index', [
            'rows' => $rows,
            'statusOptions' => $this->statusOptions(),
            'selectedStatus' => $statusFilter,
        ]);
    }

    public function show(int $requestId): View
    {
        $requestRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        $staffId = trim((string) ($requestRow->staff_id ?? ''));
        $targetYear = (int) date('Y');

        return view('admin_v2.work.profile_requests.show', [
            'requestId' => $requestId,
            'request' => $this->formatRequest($requestRow),
            'staff' => $this->staffDetail($staffId),
            'dependentRows' => $this->fuyoRows($staffId, $targetYear),
        ]);
    }

    public function updatePersonalInfoStaging(Request $request, int $requestId): RedirectResponse
    {
        $requestRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        $staffId = trim((string) ($requestRow->staff_id ?? ''));

        $values = $request->validate([
            'new_staff_name' => ['nullable', 'string', 'max:50'],
            'new_staff_name_furi' => ['nullable', 'string', 'max:50'],
            'new_address' => ['nullable', 'string', 'max:255'],
            'new_address_furi' => ['nullable', 'string', 'max:255'],
            'setai_nushi' => ['nullable', 'string', 'max:50'],
            'setai_zoku_gara' => ['nullable', 'string', 'max:20'],
            'new_car_km' => ['nullable', 'string', 'max:100'],
            'new_traffic_day' => ['nullable', 'string', 'max:100'],
            'new_traffic_day_tuika' => ['nullable', 'string', 'max:100'],
            'name_change_certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'address_change_certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'commute_change_certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'name_change_certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
            'address_change_certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
            'commute_change_certificate_file.mimes' => '証明書はPDF、JPG、PNGで添付してください。HEICは使用できません。',
        ]);

        $payload = [
            'new_staff_name' => $this->nullableText($values['new_staff_name'] ?? null),
            'new_staff_name_furi' => $this->nullableText($values['new_staff_name_furi'] ?? null),
            'new_address' => $this->nullableText($values['new_address'] ?? null),
            'new_address_furi' => $this->nullableText($values['new_address_furi'] ?? null),
            'setai_nushi' => $this->nullableText($values['setai_nushi'] ?? null),
            'setai_zoku_gara' => $this->nullableText($values['setai_zoku_gara'] ?? null),
            'new_car_km' => $this->nullableText($values['new_car_km'] ?? null),
            'new_traffic_day' => $this->nullableText($values['new_traffic_day'] ?? null),
            'new_traffic_day_tuika' => $this->nullableText($values['new_traffic_day_tuika'] ?? null),
        ];

        foreach ([
            'name_change_certificate_file' => ['name_change_certificate', 'shimei'],
            'address_change_certificate_file' => ['address_change_certificate', 'jusho'],
            'commute_change_certificate_file' => ['commute_change_certificate', 'tsukin'],
        ] as $fieldName => [$columnPrefix, $baseName]) {
            $file = $request->file($fieldName);
            if ($file === null || !$file->isValid()) {
                continue;
            }

            $existingPath = trim((string) ($requestRow->{$columnPrefix . '_file_path'} ?? ''));
            if ($existingPath !== '') {
                $this->certificateFileService->delete($existingPath);
            }
            $stored = $this->certificateFileService->store($file, "profile/{$staffId}/personal_info", $baseName . '_' . date('YmdHis'));
            $payload[$columnPrefix . '_file_path'] = $stored['path'];
            $payload[$columnPrefix . '_original_name'] = $stored['original_name'];
            $payload[$columnPrefix . '_uploaded_at'] = $stored['uploaded_at'];
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->update($payload);

        return redirect()
            ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
            ->with('status', '申告内容を修正しました。');
    }

    public function updateFuyoRow(Request $request, int $requestId, int $fuyoNo): RedirectResponse
    {
        $requestRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        $staffId = trim((string) ($requestRow->staff_id ?? ''));
        $targetYear = (int) date('Y');

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
            $stored = $this->certificateFileService->store($file, "profile/{$staffId}/fuyo", 'fuyo_' . $fuyoNo . '_' . date('YmdHis'));
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
            ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
            ->with('status', '扶養情報を保存しました。');
    }

    public function confirmApplication(int $requestId): RedirectResponse
    {
        $requestRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        if (trim((string) $requestRow->status) !== 'submitted') {
            return redirect()
                ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
                ->with('status', '提出済みの申請のみ確認済みにできます。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'staff_snapshot_json' => json_encode($this->staffSnapshot(trim((string) $requestRow->staff_id))),
            ]);

        return redirect()
            ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
            ->with('status', '確認済みにしました。');
    }

    /**
     * reflectApplication()が上書きする項目だけを対象に、確認時点のmx_staffs値を保存する。
     * 確認〜反映の間に別の操作でマスタが変わっていたら反映を止めるための比較用スナップショット。
     *
     * @return array<string, mixed>
     */
    private function staffSnapshot(string $staffId): array
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $staffId)
            ->first(['staff_name', 'staff_name_furi', 'address', 'address_furi', 'head_house', 'relationship', 'car_km', 'traffic_day', 'traffic_day_tuika']);

        return $row === null ? [] : (array) $row;
    }

    public function returnApplication(Request $request, int $requestId): RedirectResponse
    {
        $values = $request->validate([
            'return_note' => ['required', 'string', 'max:2000'],
        ]);

        $requestRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        if (trim((string) $requestRow->status) !== 'submitted') {
            return redirect()
                ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
                ->with('status', '提出済みの申請のみ差し戻せます。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->update([
                'status' => 'returned',
                'return_note' => $values['return_note'],
            ]);

        return redirect()
            ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
            ->with('status', '差し戻しました。');
    }

    /**
     * 確認済みの申請をmx_staffsへ反映する。氏名・住所・世帯主・通勤経路のみが対象
     * （扶養増減はスタッフ保存時に既にmx_fuyoへ反映済みのため、ここでは触らない）。
     */
    public function reflectApplication(int $requestId): RedirectResponse
    {
        $requestRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        if (trim((string) $requestRow->status) !== 'confirmed') {
            return redirect()
                ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
                ->with('status', '確認済みの申請のみ反映できます。');
        }

        $staffId = trim((string) $requestRow->staff_id);

        $confirmedSnapshot = json_decode((string) ($requestRow->staff_snapshot_json ?? ''), true) ?? [];
        if ($confirmedSnapshot !== $this->staffSnapshot($staffId)) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.staff_profile_requests')
                ->where('request_id', $requestId)
                ->update(['status' => 'submitted', 'confirmed_at' => null, 'staff_snapshot_json' => null]);

            return redirect()
                ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
                ->with('status', '確認後にスタッフのマスタ情報が変更されていたため反映を中止しました。内容を再確認し、確認し直してください。');
        }

        $staffUpdate = [];

        $newStaffName = trim((string) ($requestRow->new_staff_name ?? ''));
        if ($newStaffName !== '') {
            $staffUpdate['staff_name'] = $newStaffName;
            $staffUpdate['staff_name_furi'] = trim((string) ($requestRow->new_staff_name_furi ?? ''));
        }

        $newAddress = trim((string) ($requestRow->new_address ?? ''));
        if ($newAddress !== '') {
            $staffUpdate['address'] = $newAddress;
            $staffUpdate['address_furi'] = trim((string) ($requestRow->new_address_furi ?? ''));
        }

        if ($newStaffName !== '' || $newAddress !== '') {
            $staffUpdate['head_house'] = trim((string) ($requestRow->setai_nushi ?? ''));
            $staffUpdate['relationship'] = trim((string) ($requestRow->setai_zoku_gara ?? ''));
        }

        if (($requestRow->new_car_km ?? null) !== null) {
            $staffUpdate['car_km'] = trim((string) $requestRow->new_car_km);
        }
        if (($requestRow->new_traffic_day ?? null) !== null) {
            $staffUpdate['traffic_day'] = trim((string) $requestRow->new_traffic_day);
        }
        if (($requestRow->new_traffic_day_tuika ?? null) !== null) {
            $staffUpdate['traffic_day_tuika'] = trim((string) $requestRow->new_traffic_day_tuika);
        }

        if ($staffUpdate !== []) {
            DB::connection('sqlsrv')
                ->table('dbo.mx_staffs')
                ->where('staff_id', $staffId)
                ->update($staffUpdate);
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('request_id', $requestId)
            ->update([
                'status' => 'reflected',
                'reflected_at' => now(),
            ]);

        return redirect()
            ->route('admin.work.profile_requests.show', ['requestId' => $requestId])
            ->with('status', '実データへ反映しました。');
    }

    public function nameChangeCertificateFile(int $requestId)
    {
        return $this->streamCertificate($requestId, 'name_change_certificate');
    }

    public function addressChangeCertificateFile(int $requestId)
    {
        return $this->streamCertificate($requestId, 'address_change_certificate');
    }

    public function commuteChangeCertificateFile(int $requestId)
    {
        return $this->streamCertificate($requestId, 'commute_change_certificate');
    }

    /** @return array<string, mixed> */
    private function formatRequest(object $requestRow): array
    {
        $status = trim((string) ($requestRow->status ?? '')) ?: 'draft';

        return [
            'request_id' => (string) $requestRow->request_id,
            'staff_id' => trim((string) ($requestRow->staff_id ?? '')),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'new_staff_name' => trim((string) ($requestRow->new_staff_name ?? '')),
            'new_staff_name_furi' => trim((string) ($requestRow->new_staff_name_furi ?? '')),
            'new_address' => trim((string) ($requestRow->new_address ?? '')),
            'new_address_furi' => trim((string) ($requestRow->new_address_furi ?? '')),
            'setai_nushi' => trim((string) ($requestRow->setai_nushi ?? '')),
            'setai_zoku_gara' => trim((string) ($requestRow->setai_zoku_gara ?? '')),
            'new_car_km' => trim((string) ($requestRow->new_car_km ?? '')),
            'new_traffic_day' => trim((string) ($requestRow->new_traffic_day ?? '')),
            'new_traffic_day_tuika' => trim((string) ($requestRow->new_traffic_day_tuika ?? '')),
            'name_change_certificate_original_name' => trim((string) ($requestRow->name_change_certificate_original_name ?? '')),
            'address_change_certificate_original_name' => trim((string) ($requestRow->address_change_certificate_original_name ?? '')),
            'commute_change_certificate_original_name' => trim((string) ($requestRow->commute_change_certificate_original_name ?? '')),
            'submitted_at' => $this->dateLabel($requestRow->submitted_at ?? null),
            'confirmed_at' => $this->dateLabel($requestRow->confirmed_at ?? null),
            'reflected_at' => $this->dateLabel($requestRow->reflected_at ?? null),
            'return_note' => trim((string) ($requestRow->return_note ?? '')),
        ];
    }

}
