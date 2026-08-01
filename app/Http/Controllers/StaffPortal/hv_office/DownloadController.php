<?php

namespace App\Http\Controllers\StaffPortal\hv_office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    use HandlesStaffPortalContext;

    // 一覧表示
    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $target_month = trim((string) $request->query('target_month', now()->format('Y-m')));
        $daily_report_store_name = trim((string) $request->query('daily_report_store_name', ''));
        $csv_type = $this->csvType($request);

        try {
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $target_month = now()->format('Y-m');
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        }
        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();

        $daily_report_store_name_options = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->whereBetween('target_month', [
                $targetMonthStart->format('Y-m-d'),
                $targetMonthEnd->format('Y-m-d'),
            ])
            ->whereNotNull('daily_report_store_name')
            ->where('daily_report_store_name', '<>', '')
            ->orderBy('daily_report_store_name')
            ->pluck('daily_report_store_name')
            ->map(fn($value): string => trim((string) $value))
            ->filter(fn(string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        $rows = collect();
        if ($csv_type === 'massage' || $daily_report_store_name !== '') {
            $rows = $this->downloadRowsQuery($targetMonthStart, $targetMonthEnd, $daily_report_store_name, $csv_type)
                // ->limit(50)
                ->get();
        }

        return view('staff_portal.hv_office.download.index', $this->commonViewData($request, [
            'target_month' => $target_month,
            'daily_report_store_name' => $daily_report_store_name,
            'csv_type' => $csv_type,
            'daily_report_store_name_options' => $daily_report_store_name_options,
            'rows' => $rows,
        ]));
    }

    // csv出力
    public function csv(Request $request): RedirectResponse|StreamedResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $target_month = trim((string) $request->query('target_month', now()->format('Y-m')));
        $daily_report_store_name = trim((string) $request->query('daily_report_store_name', ''));
        $csv_type = $this->csvType($request);

        try {
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            return redirect()->route('hv_office.download')->withErrors(['target_month' => '月度が正しくありません。']);
        }

        if ($csv_type === 'acupuncture' && $daily_report_store_name === '') {
            return redirect()->route('hv_office.download', [
                'target_month' => $target_month,
                'csv_type' => $csv_type,
            ])->withErrors(['daily_report_store_name' => '店舗を選択してください。']);
        }

        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();
        $rows = $this->downloadRowsQuery($targetMonthStart, $targetMonthEnd, $daily_report_store_name, $csv_type)->get();
        $csvTypeLabel = $csv_type === 'massage' ? 'マッサージ' : '鍼灸';
        $csvTypeFile = $csv_type === 'massage' ? 'massage' : 'acupuncture';
        $storePart = $csv_type === 'massage' ? '' : $daily_report_store_name . '-';
        $downloadNameUtf8 = $target_month . '-' . $storePart . $csvTypeLabel . 'レセデータ.csv';
        $downloadNameAscii = $target_month . '-' . $csvTypeFile . '-rese_data.csv';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($rows, $csv_type): void {
            echo $this->sjisCsvLine(['ID', '患者名', '申請日', '施術分類', '施術', '担当', '店舗', '施術期間自', '施術期間至']);

            foreach ($rows as $row) {
                $addedAt = $row->added_at ? Carbon::parse($row->added_at) : null;
                $receipt_home_visit_distance = $this->receiptHomeVisitDistance($row, $csv_type);

                echo $this->sjisCsvLine([
                    $row->common_id ?? '',
                    $row->patient_name ?? '',
                    $addedAt ? $addedAt->format('Y/m/d') : '',
                    $receipt_home_visit_distance,
                    $this->receiptTreatmentName($receipt_home_visit_distance),
                    $row->daily_report_billing_staff_display_name_ja ?: ($row->daily_report_billing_staff_staff_name ?? ''),
                    $row->daily_report_store_name ?? '',
                    $addedAt ? $addedAt->copy()->startOfMonth()->format('Y/m/d') : '',
                    $addedAt ? $addedAt->copy()->endOfMonth()->format('Y-m-d') : '',
                ]);
            }
        }, $downloadNameAscii, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => 'attachment; filename="' . $downloadNameAscii . '"; filename*=UTF-8\'\'' . rawurlencode($downloadNameUtf8),
            'Content-Transfer-Encoding' => 'binary',
        ]);
    }

    private function csvType(Request $request): string
    {
        $csv_type = trim((string) $request->query('csv_type', 'acupuncture'));
        if (!in_array($csv_type, ['acupuncture', 'massage'], true)) {
            return 'acupuncture';
        }

        return $csv_type;
    }

    private function downloadRowsQuery(Carbon $targetMonthStart, Carbon $targetMonthEnd, string $daily_report_store_name, string $csv_type)
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->leftJoin('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->leftJoin('dbo.mx_staffs as billing_staff', 'billing_staff.staff_id', '=', 'n.daily_report_billing_staff')
            ->select([
                'n.target_month',
                'n.added_at',
                'k.patient_name',
                'k.patient_id',
                'k.common_id',
                'k.facility_name',
                'n.daily_report_store_name',
                'n.staff_name',
                'n.receipt_home_visit_distance',
                'n.receipt_home_visit_distance_ma',
                'n.daily_report_billing_staff',
                'billing_staff.display_name_ja as daily_report_billing_staff_display_name_ja',
                'billing_staff.staff_name as daily_report_billing_staff_staff_name',
            ])
            ->whereBetween('n.target_month', [
                $targetMonthStart->format('Y-m-d'),
                $targetMonthEnd->format('Y-m-d'),
            ])
            ->where(function ($query): void {
                $query->where('n.is_no_treatment', 0)
                    ->orWhereNull('n.is_no_treatment');
            })
            ->where(function ($query): void {
                $query->where('k.is_receipt_import_excluded', 0)
                    ->orWhereNull('k.is_receipt_import_excluded');
            });

        if ($csv_type === 'acupuncture') {
            $query->where('n.daily_report_store_name', $daily_report_store_name)
                ->whereNotNull('n.receipt_home_visit_distance')
                ->where('n.receipt_home_visit_distance', '<>', '');
        } else {
            $query->whereNotNull('n.receipt_home_visit_distance_ma')
                ->where('n.receipt_home_visit_distance_ma', '<>', '');
        }

        return $query
            ->orderBy('k.common_id')
            ->orderBy('n.added_at');
    }

    private function receiptTreatmentName(string $receipt_home_visit_distance): string
    {
        return match ($receipt_home_visit_distance) {
            '〇' => '通所',
            '◎' => '通所(往療)',
            '①' => '施術1',
            '②' => '施術2',
            '③' => '施術3',
            '④' => '施術4',
            default => '',
        };
    }

    private function receiptHomeVisitDistance(object $row, string $csv_type): string
    {
        if ($csv_type === 'massage') {
            return trim((string) ($row->receipt_home_visit_distance_ma ?? ''));
        }

        return trim((string) ($row->receipt_home_visit_distance ?? ''));
    }

    private function sjisCsvLine(array $values): string
    {
        $escaped = array_map(function ($value): string {
            return '"' . str_replace('"', '""', (string) $value) . '"';
        }, $values);

        return mb_convert_encoding(implode(',', $escaped) . "\r\n", 'SJIS-win', 'UTF-8');
    }
}
