<?php

namespace App\Http\Controllers\Admin\V2\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * 「申請→事務所確認→反映」型の事務所側レビュー画面（個人情報変更申請・入社手続き申請）で
 * 一字一句同じだった補助メソッドをまとめたもの。年末調整（YearEndAdjustmentV2Controller）は
 * ステータス種別・扶養行の取得条件・スタッフ情報の項目が実際に異なるため対象外
 * （無理に共通化すると片方の都合で他方が壊れる）。
 *
 * 利用側は `private readonly CertificateFileService $certificateFileService` を持ち、
 * ステージングテーブル名を返す `requestTableName(): string` を実装すること。
 */
trait HandlesStaffRequestReview
{
    abstract private function requestTableName(): string;

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

    /** @return array<string, mixed> */
    private function staffDetail(string $staffId): array
    {
        if ($staffId === '') {
            return [];
        }

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first();

        return $row === null ? [] : (array) $row;
    }

    /** @return array<string, array{staff_name: string}> */
    private function staffListDetails(array $staffIds): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string) $value), $staffIds))));
        if ($staffIds === []) {
            return [];
        }

        $details = [];
        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_id))'), $staffIds)
            ->get(['staff_id', 'staff_name']);

        foreach ($rows as $row) {
            $staffId = trim((string) ($row->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }
            $details[$staffId] = ['staff_name' => trim((string) ($row->staff_name ?? ''))];
        }

        return $details;
    }

    /** @return list<array<string, mixed>> */
    private function fuyoRows(string $staffId, int $targetYear): array
    {
        if ($staffId === '') {
            return [];
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->whereYear('registration_date', $targetYear)
            ->whereNotIn('fuyo_relationship', self::SPOUSE_RELATIONSHIPS)
            ->orderBy('fuyo_no')
            ->get()
            ->map(fn($row): array => (array) $row)
            ->all();
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
        ];
    }

    private function statusLabel(string $status): string
    {
        return $this->statusOptions()[$status] ?? $status;
    }

    private function streamCertificate(int $requestId, string $columnPrefix)
    {
        $requestRow = DB::connection('sqlsrv_payroll')
            ->table($this->requestTableName())
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        $path = trim((string) ($requestRow->{$columnPrefix . '_file_path'} ?? ''));
        abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

        $downloadName = trim((string) ($requestRow->{$columnPrefix . '_original_name'} ?? '')) ?: basename($path);

        return $this->certificateFileService->response($path, $downloadName);
    }

    public function fuyoCertificateFile(int $requestId, int $fuyoNo)
    {
        $requestRow = DB::connection('sqlsrv_payroll')
            ->table($this->requestTableName())
            ->where('request_id', $requestId)
            ->first();
        abort_unless($requestRow, 404);

        $staffId = trim((string) ($requestRow->staff_id ?? ''));
        $targetYear = (int) date('Y');

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('fuyo_no', $fuyoNo)
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->whereYear('registration_date', $targetYear)
            ->first();
        abort_unless($row, 404);

        $path = trim((string) ($row->failure_certificate_file_path ?? ''));
        abort_if($path === '' || !$this->certificateFileService->exists($path), 404);

        $downloadName = trim((string) ($row->failure_certificate_original_name ?? '')) ?: basename($path);

        return $this->certificateFileService->response($path, $downloadName);
    }
}
