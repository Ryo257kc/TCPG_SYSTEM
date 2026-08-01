<?php

namespace App\Http\Controllers\StaffPortal\home_visit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonthlyVisitController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $target_month = trim((string) $request->query('target_month', now()->format('Y-m')));
        $switch_type = trim((string) $request->query('switch_type', '1'));
        $daily_report_store_name = trim((string) $request->query('daily_report_store_name', ''));
        $billing_staff = trim((string) $request->query('billing_staff', ''));
        $daily_report_facility_name = trim((string) $request->query('daily_report_facility_name', ''));
        $patient_name = trim((string) $request->query('patient_name', ''));
        $patient_id = trim((string) $request->query('patient_id', ''));

        try {
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $target_month = now()->format('Y-m');
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        }

        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();
        $days = range(1, $targetMonthStart->daysInMonth);

        $baseQuery = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->join('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->leftJoin('dbo.mx_staffs as billing_staff_master', 'billing_staff_master.staff_id', '=', 'k.billing_staff')
            ->leftJoin('dbo.mx_staffs as daily_report_billing_staff_master', 'daily_report_billing_staff_master.staff_id', '=', 'n.daily_report_billing_staff')
            ->where(function ($query): void {
                $query->where('n.is_no_treatment', 0)
                    ->orWhereNull('n.is_no_treatment');
            });

        if ($switch_type === '3') {
            $baseQuery
                ->whereBetween('n.target_month', [
                    $targetMonthStart->format('Y-m-d'),
                    $targetMonthEnd->format('Y-m-d'),
                ])
                ->where('n.is_ma_treatment', 1);
        } else {
            $baseQuery->whereBetween('n.treatment_date', [
                $targetMonthStart->format('Y-m-d'),
                $targetMonthEnd->format('Y-m-d'),
            ]);
        }

        if ($switch_type === '4') {
            $baseQuery->where(function ($query): void {
                $query->whereNull('n.daily_report_store_name')
                    ->orWhere('n.daily_report_store_name', '');
            });
        }

        if ($switch_type !== '3' && $daily_report_store_name !== '') {
            $baseQuery->where('n.daily_report_store_name', $daily_report_store_name);
        }

        if ($switch_type !== '3' && $billing_staff !== '') {
            $baseQuery->where('k.billing_staff', $billing_staff);
        }

        if ($switch_type !== '3' && $daily_report_facility_name !== '') {
            $baseQuery->where('n.daily_report_facility_name', $daily_report_facility_name);
        }

        if ($patient_name !== '') {
            $baseQuery->where('k.patient_name', 'like', '%' . $patient_name . '%');
        }

        if ($switch_type !== '3' && $patient_id !== '') {
            $baseQuery->where('n.patient_id', $patient_id);
        }

        $recordsQuery = (clone $baseQuery)
            ->select([
                'n.*',
                'k.patient_name',
                'k.consent_category',
                'k.consent_date',
                'k.standard_distance',
                'k.billing_staff',
                'billing_staff_master.staff_name as billing_staff_staff_name',
                'daily_report_billing_staff_master.display_name_ja as daily_report_billing_staff_staff_name',
            ])
            ->orderBy('k.consent_date')
            ->orderByDesc('billing_staff_master.staff_name')
            ->orderBy('k.patient_name');

        if ($switch_type === '3') {
            $recordsQuery->orderBy('n.ma_applied_at');
        } else {
            $recordsQuery->orderBy('n.treatment_date');
        }

        $records = $recordsQuery
            ->orderBy('n.daily_report_id')
            ->get();

        $rows = $records
            ->groupBy(fn($record): string => implode('|', [
                (string) $record->patient_id,
                (string) $record->daily_report_store_name,
                (string) $record->daily_report_facility_name,
            ]))
            ->map(function ($group) use ($days, $switch_type): object {
                $first = $group->first();
                $day_values = array_fill_keys($days, '');

                foreach ($group as $record) {
                    $countDate = $switch_type === '3' ? $record->ma_applied_at : $record->treatment_date;
                    if (empty($countDate)) {
                        continue;
                    }

                    $day = (int) Carbon::parse($countDate)->format('j');

                    if ($switch_type === '2') {
                        $symbol = trim((string) ($record->receipt_home_visit_distance_ma ?? ''));
                        if ($symbol === '') {
                            $symbol = trim((string) ($record->receipt_home_visit_distance ?? ''));
                        }

                        $day_values[$day] = $symbol;
                        continue;
                    }

                    $day_values[$day] = (string) (((int) ($day_values[$day] ?: 0)) + 1);
                }

                return (object) [
                    'target_month' => $first->target_month,
                    'patient_id' => $first->patient_id,
                    'patient_name' => $first->patient_name,
                    'consent_category' => $first->consent_category,
                    'consent_date' => $first->consent_date,
                    'standard_distance' => $first->standard_distance,
                    'daily_report_facility_name' => $first->daily_report_facility_name,
                    'billing_staff' => $first->billing_staff,
                    'billing_staff_staff_name' => $first->billing_staff_staff_name,
                    'daily_report_store_name' => $first->daily_report_store_name,
                    'day_values' => $day_values,
                    'total_count' => $switch_type === '3'
                        ? $group->filter(fn($record): bool => !empty($record->ma_applied_at))->count()
                        : $group->count(),
                ];
            })
            ->values();

        $selectedDetails = collect();
        if ($patient_id !== '') {
            $selectedDetails = $records
                ->where('patient_id', is_numeric($patient_id) ? (int) $patient_id : $patient_id)
                ->values();
        }

        $optionBaseQuery = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->join('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->whereBetween('n.treatment_date', [
                $targetMonthStart->format('Y-m-d'),
                $targetMonthEnd->format('Y-m-d'),
            ]);

        $daily_report_store_name_options = (clone $optionBaseQuery)
            ->whereNotNull('n.daily_report_store_name')
            ->where('n.daily_report_store_name', '<>', '')
            ->distinct()
            ->orderBy('n.daily_report_store_name')
            ->pluck('n.daily_report_store_name')
            ->toArray();

        $billing_staff_options = $this->homeVisitStaffOptions($targetMonthStart);

        $daily_report_facility_name_options = (clone $optionBaseQuery)
            ->whereNotNull('n.daily_report_facility_name')
            ->where('n.daily_report_facility_name', '<>', '')
            ->distinct()
            ->orderBy('n.daily_report_facility_name')
            ->pluck('n.daily_report_facility_name')
            ->toArray();

        return view('staff_portal.home_visit.monthly_visit.index', $this->commonViewData($request, [
            'target_month' => $target_month,
            'switch_type' => $switch_type,
            'daily_report_store_name' => $daily_report_store_name,
            'billing_staff' => $billing_staff,
            'daily_report_facility_name' => $daily_report_facility_name,
            'patient_name' => $patient_name,
            'patient_id' => $patient_id,
            'days' => $days,
            'rows' => $rows,
            'selectedDetails' => $selectedDetails,
            'daily_report_store_name_options' => $daily_report_store_name_options,
            'billing_staff_options' => $billing_staff_options,
            'daily_report_facility_name_options' => $daily_report_facility_name_options,
        ]));
    }

    public function print(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $target_month = trim((string) $request->query('target_month', now()->format('Y-m')));
        $print_type = trim((string) $request->query('print_type', '1'));
        $daily_report_store_name = trim((string) $request->query('daily_report_store_name', ''));
        $billing_staff = trim((string) $request->query('billing_staff', ''));
        $daily_report_facility_name = trim((string) $request->query('daily_report_facility_name', ''));
        $patient_name = trim((string) $request->query('patient_name', ''));
        $patient_id = trim((string) $request->query('patient_id', ''));

        try {
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $target_month = now()->format('Y-m');
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        }

        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();

        if ($print_type === '3') {
            $dailySummaryQuery = DB::connection('sqlsrv')
                ->table('dbo.hv_nippou as n')
                ->whereBetween('n.target_month', [
                    $targetMonthStart->format('Y-m-d'),
                    $targetMonthEnd->format('Y-m-d'),
                ])
                ->whereNotNull('n.added_at')
                ->where(function ($query): void {
                    $query->where('n.is_no_treatment', 0)
                        ->orWhereNull('n.is_no_treatment');
                });

            if ($daily_report_store_name !== '') {
                $dailySummaryQuery->where('n.daily_report_store_name', $daily_report_store_name);
            }

            if ($billing_staff !== '') {
                $dailySummaryQuery->where('n.daily_report_billing_staff', $billing_staff);
            }

            if ($daily_report_facility_name !== '') {
                $dailySummaryQuery->where('n.daily_report_facility_name', $daily_report_facility_name);
            }

            $dailySummaryRows = $dailySummaryQuery
                ->select([
                    'n.daily_report_store_name',
                    'n.daily_report_facility_name',
                    'n.receipt_home_visit_distance',
                    'n.added_at',
                ])
                ->get()
                ->map(function ($row): object {
                    return (object) [
                        'daily_report_store_name' => trim((string) ($row->daily_report_store_name ?? '')),
                        'daily_report_facility_name' => trim((string) ($row->daily_report_facility_name ?? '')),
                        'added_at_day' => Carbon::parse($row->added_at)->format('d'),
                        'mark' => trim((string) ($row->receipt_home_visit_distance ?? '')),
                    ];
                })
                ->groupBy(fn(object $row): string => implode('|', [
                    $row->daily_report_store_name,
                    $row->added_at_day,
                    $row->mark,
                    $row->daily_report_facility_name,
                ]))
                ->map(function ($group): object {
                    $first = $group->first();

                    return (object) [
                        'daily_report_store_name' => $first->daily_report_store_name,
                        'added_at_day' => $first->added_at_day,
                        'mark' => $first->mark,
                        'daily_report_facility_name' => $first->daily_report_facility_name,
                        'count' => $group->count(),
                    ];
                })
                ->sortBy([
                    ['daily_report_store_name', 'asc'],
                    ['added_at_day', 'asc'],
                    ['mark', 'asc'],
                    ['daily_report_facility_name', 'asc'],
                ])
                ->values();

            return view('staff_portal.home_visit.monthly_visit.print', $this->commonViewData($request, [
                'target_month' => $target_month,
                'targetMonthStart' => $targetMonthStart,
                'print_type' => $print_type,
                'records' => collect(),
                'dailySummaryRows' => $dailySummaryRows,
                'patient_id' => $patient_id,
            ]));
        }

        $query = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->join('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->leftJoin('dbo.mx_staffs as daily_report_billing_staff_master', 'daily_report_billing_staff_master.staff_id', '=', 'n.daily_report_billing_staff')
            ->where(function ($query) use ($targetMonthStart, $targetMonthEnd): void {
                $query->where(function ($query) use ($targetMonthStart, $targetMonthEnd): void {
                    $query->whereBetween('n.target_month', [
                        $targetMonthStart->format('Y-m-d'),
                        $targetMonthEnd->format('Y-m-d'),
                    ])
                        ->whereNotNull('n.daily_report_billing_staff')
                        ->where('n.daily_report_billing_staff', '<>', '')
                        ->where(function ($query): void {
                            $query->where('n.is_no_treatment', 0)
                                ->orWhereNull('n.is_no_treatment');
                        })
                        ->whereNotNull('n.daily_report_address')
                        ->where('n.daily_report_address', '<>', '')
                        ->where('n.daily_report_store_name', '<>', '社内施術');
                })->orWhere(function ($query) use ($targetMonthStart, $targetMonthEnd): void {
                    $query->whereBetween('n.target_month', [
                        $targetMonthStart->format('Y-m-d'),
                        $targetMonthEnd->format('Y-m-d'),
                    ])
                        ->whereNotNull('n.daily_report_facility_name')
                        ->where('n.daily_report_facility_name', '<>', '')
                        ->whereNotNull('n.daily_report_billing_staff')
                        ->where('n.daily_report_billing_staff', '<>', '')
                        ->where(function ($query): void {
                            $query->where('n.is_receipt_home_visit', '<>', 0)
                                ->orWhere(function ($query): void {
                                    $query->where(function ($query): void {
                                        $query->where('n.is_no_treatment', 0)
                                            ->orWhereNull('n.is_no_treatment');
                                    })
                                        ->where(function ($query): void {
                                            $query->where('n.is_receipt_home_visit', 0)
                                                ->orWhereNull('n.is_receipt_home_visit');
                                        });
                                });
                        });
                });
            });

        if ($daily_report_store_name !== '') {
            $query->where('n.daily_report_store_name', $daily_report_store_name);
        }

        if ($billing_staff !== '') {
            $query->where('n.daily_report_billing_staff', $billing_staff);
        }

        if ($daily_report_facility_name !== '') {
            $query->where('n.daily_report_facility_name', $daily_report_facility_name);
        }

        if ($patient_name !== '') {
            $query->where('k.patient_name', 'like', '%' . $patient_name . '%');
        }

        if ($print_type === '1') {
            if ($patient_id !== '') {
                $query->where('n.patient_id', $patient_id);
            } elseif ($patient_name === '') {
                $query->whereRaw('1 = 0');
            }
        }

        $records = $query
            ->select([
                'n.target_month',
                'n.patient_id',
                'n.daily_report_store_name',
                'k.patient_name',
                'n.daily_report_address',
                'n.daily_report_billing_staff',
                'n.daily_report_facility_name',
                'n.start_point_address',
                'n.is_receipt_home_visit',
                'n.added_at',
                'daily_report_billing_staff_master.staff_name',
                'n.is_no_treatment',
            ])
            ->orderBy('k.patient_name')
            ->orderBy('n.added_at')
            ->orderBy('n.daily_report_id')
            ->get()
            ->map(function ($row): object {
                $isReceiptHomeVisit = (int) ($row->is_receipt_home_visit ?? 0) !== 0;
                $isNoTreatment = (int) ($row->is_no_treatment ?? 0) !== 0;
                $dailyReportFacilityName = trim((string) ($row->daily_report_facility_name ?? ''));
                $dailyReportBillingStaff = trim((string) ($row->daily_report_billing_staff ?? ''));
                $mark = '';

                if ($isReceiptHomeVisit && $dailyReportFacilityName !== '') {
                    $mark = '◎';
                } elseif (!$isNoTreatment && !$isReceiptHomeVisit && $dailyReportFacilityName !== '' && $dailyReportBillingStaff !== '') {
                    $mark = '〇';
                }

                return (object) [
                    'target_month' => $row->target_month,
                    'patient_id' => $row->patient_id,
                    'daily_report_store_name' => $row->daily_report_store_name,
                    'patient_name' => $row->patient_name,
                    'daily_report_address' => trim((string) ($row->daily_report_address ?? '')),
                    'daily_report_billing_staff' => $row->daily_report_billing_staff,
                    'daily_report_facility_name' => $row->daily_report_facility_name,
                    'start_point_address' => $row->start_point_address,
                    'is_receipt_home_visit' => $row->is_receipt_home_visit,
                    'added_at' => $row->added_at,
                    'staff_name' => $row->staff_name,
                    'is_no_treatment' => $row->is_no_treatment,
                    'mark' => $mark,
                ];
            })
            ->filter(fn(object $row): bool => $row->mark !== '' || $row->daily_report_address !== '')
            ->values();

        return view('staff_portal.home_visit.monthly_visit.print', $this->commonViewData($request, [
            'target_month' => $target_month,
            'targetMonthStart' => $targetMonthStart,
            'print_type' => $print_type,
            'records' => $records,
            'patient_id' => $patient_id,
        ]));
    }

    public function update(Request $request, string $daily_report_id): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $validated = $request->validate([
            'treatment_date' => ['nullable', 'date'],
            'added_at' => ['nullable', 'date'],
            'ma_applied_at' => ['nullable', 'date'],
            'is_ma_treatment' => ['nullable', 'boolean'],
            'is_deformity_manual_therapy' => ['nullable', 'boolean'],
            'is_treatment_report_submitted' => ['nullable', 'boolean'],
            'is_receipt_home_visit' => ['nullable', 'boolean'],
            'daily_report_facility_name' => ['nullable', 'string', 'max:50'],
            'daily_report_billing_staff' => ['nullable', 'string', 'max:10'],
            'receipt_home_visit_distance' => ['nullable', 'string', 'max:10'],
            'receipt_home_visit_distance_ma' => ['nullable', 'string', 'max:10'],
            'home_visit_start_point' => ['nullable'],
            'daily_report_town_name' => ['nullable', 'string', 'max:50'],
            'start_point_address' => ['nullable', 'string', 'max:200'],
            'daily_report_address' => ['nullable', 'string', 'max:200'],
        ]);

        DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->where('daily_report_id', $daily_report_id)
            ->update([
                'treatment_date' => $validated['treatment_date'] ?? null,
                'added_at' => $validated['added_at'] ?? null,
                'ma_applied_at' => $validated['ma_applied_at'] ?? null,
                'is_ma_treatment' => $request->boolean('is_ma_treatment') ? 1 : 0,
                'is_deformity_manual_therapy' => $request->boolean('is_deformity_manual_therapy') ? 1 : 0,
                'is_treatment_report_submitted' => $request->boolean('is_treatment_report_submitted') ? 1 : 0,
                'is_receipt_home_visit' => $request->boolean('is_receipt_home_visit') ? 1 : 0,
                'daily_report_facility_name' => $validated['daily_report_facility_name'] ?? null,
                'daily_report_billing_staff' => $validated['daily_report_billing_staff'] ?? null,
                'receipt_home_visit_distance' => $validated['receipt_home_visit_distance'] ?? null,
                'receipt_home_visit_distance_ma' => $validated['receipt_home_visit_distance_ma'] ?? null,
                'home_visit_start_point' => $validated['home_visit_start_point'] ?? null,
                'daily_report_town_name' => $validated['daily_report_town_name'] ?? null,
                'start_point_address' => $validated['start_point_address'] ?? null,
                'daily_report_address' => $validated['daily_report_address'] ?? null,
            ]);

        return redirect()
            ->route('home_visit.monthly_visit', [
                'target_month' => $request->input('target_month'),
                'switch_type' => $request->input('switch_type'),
                'daily_report_store_name' => $request->input('daily_report_store_name'),
                'billing_staff' => $request->input('billing_staff'),
                'daily_report_facility_name' => $request->input('daily_report_facility_name'),
                'patient_name' => $request->input('patient_name'),
                'patient_id' => $request->input('patient_id'),
            ])
            ->with('status', '保存しました。');
    }
}
