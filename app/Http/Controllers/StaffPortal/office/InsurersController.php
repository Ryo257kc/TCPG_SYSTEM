<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InsurersController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        $search = trim((string) $request->query('search', ''));
        $depositNameFilter = trim((string) $request->query('deposit_name_display', 'all'));
        $activeTab = trim((string) $request->query('tab', 'all'));

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_insurers')
            ->select([
                'insurer_number',
                'insurer_name',
                'scheduled_payment_name',
                'scheduled_payment_name_2',
                'insurance_type',
                'subsidy_type',
                // 'insurance_kind',
            ]);

        if ($search !== '') {
            $rowsQuery->where(function ($query) use ($search): void {
                $query->where('insurer_number', 'like', $search . '%')
                    ->orWhere('insurer_name', 'like', '%' . $search . '%');
            });
        }

        if ($depositNameFilter === 'with') {
            $rowsQuery->where(function ($query): void {
                $query->where(function ($nested): void {
                    $nested->whereNotNull('scheduled_payment_name')
                        ->where('scheduled_payment_name', '<>', '');
                })->orWhere(function ($nested): void {
                    $nested->whereNotNull('scheduled_payment_name_2')
                        ->where('scheduled_payment_name_2', '<>', '');
                });
            });
        } elseif ($depositNameFilter === 'without') {
            $rowsQuery->where(function ($query): void {
                $query->where(function ($nested): void {
                    $nested->whereNull('scheduled_payment_name')
                        ->orWhere('scheduled_payment_name', '');
                })->orWhere(function ($nested): void {
                    $nested->whereNull('scheduled_payment_name_2')
                        ->orWhere('scheduled_payment_name_2', '');
                });
            });
        }

        if ($activeTab === 'general') {
            $rowsQuery
                ->where(function ($query): void {
                    $query->whereNull('insurance_type')
                        ->orWhere('insurance_type', '<>', '後期高齢');
                })
                ->where(function ($query): void {
                    $query->whereNull('insurance_type')
                        ->orWhere('insurance_type', '<>', '医療助成');
                });
        } elseif ($activeTab === 'elderly') {
            $rowsQuery->where('insurance_type', 'like', '後期高齢%');
        } elseif ($activeTab === 'medical_support') {
            $rowsQuery->where('insurance_type', 'like', '医療助成%');
        } elseif ($activeTab === 'incomplete') {
            $rowsQuery
                ->whereNotNull('insurer_number')
                ->where('insurer_number', '<>', '')
                ->where(function ($query): void {
                    $query->whereNull('insurer_name')
                        ->orWhere('insurer_name', '');
                });
        }

        $insurerRows = $rowsQuery
            ->orderBy('insurer_number')
            ->get()
            // ->map(static fn($row): array => [
            //     'insurer_number' => trim((string) ($row->insurer_number ?? '')),
            //     'insurer_name' => trim((string) ($row->insurer_name ?? '')),
            //     'scheduled_payment_name' => trim((string) ($row->scheduled_payment_name ?? '')),
            //     'scheduled_payment_name_2' => trim((string) ($row->scheduled_payment_name_2 ?? '')),
            //     'insurance_type' => trim((string) ($row->insurance_type ?? '')),
            //     'subsidy_type' => trim((string) ($row->subsidy_type ?? '')),
            //     // 'insurance_kind' => trim((string) ($row->insurance_kind ?? '')),
            // ])
            ->all();

        $scheduledPaymentNameOptions = collect(
            DB::connection('sqlsrv')
                ->table('dbo.mx_insurers')
                ->select(['scheduled_payment_name', 'scheduled_payment_name_2'])
                ->get()
        )
            ->flatMap(static fn($row): array => [
                trim((string) ($row->scheduled_payment_name ?? '')),
                trim((string) ($row->scheduled_payment_name_2 ?? '')),
            ])
            ->filter(static fn(string $value): bool => $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return view('staff_portal.office.receipt.insurers.index', $this->commonViewData($request, [
            'search' => $search,
            'depositNameFilter' => in_array($depositNameFilter, ['all', 'with', 'without'], true) ? $depositNameFilter : 'all',
            'activeTab' => in_array($activeTab, ['all', 'general', 'elderly', 'medical_support', 'incomplete'], true) ? $activeTab : 'all',
            'insurerRows' => $insurerRows,
            'scheduledPaymentNameOptions' => $scheduledPaymentNameOptions,
        ]));
    }

    public function save(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $data = $request->validate(
            [
                'original_insurer_number' => ['nullable', 'string', 'max:8'],
                'insurer_number' => ['required', 'string', 'size:8'],
                'insurer_name' => ['nullable', 'string', 'max:50'],
                'scheduled_payment_name' => ['nullable', 'string', 'max:50'],
                'scheduled_payment_name_2' => ['nullable', 'string', 'max:50'],
                'insurance_type' => ['nullable', 'string', 'max:20'],
                'subsidy_type' => ['nullable', 'string', 'max:30'],
            ],
            [
                'insurer_number.required' => '保険者番号を入力してください',
                'insurer_number.size' => '保険者番号は8桁で入力してください',
            ]
        );

        $originalInsurerNumber = trim((string) ($data['original_insurer_number'] ?? ''));
        $insurerNumber = trim((string) $data['insurer_number']);

        $payload = [
            'insurer_number' => $insurerNumber,
            'insurer_name' => trim((string) ($data['insurer_name'] ?? '')),
            'scheduled_payment_name' => trim((string) ($data['scheduled_payment_name'] ?? '')),
            'scheduled_payment_name_2' => trim((string) ($data['scheduled_payment_name_2'] ?? '')),
            'insurance_type' => trim((string) ($data['insurance_type'] ?? '')),
            'subsidy_type' => trim((string) ($data['subsidy_type'] ?? '')),
        ];

        $query = DB::connection('sqlsrv')->table('dbo.mx_insurers');

        if ($originalInsurerNumber !== '') {
            $existing = $query
                ->where('insurer_number', $originalInsurerNumber)
                ->first(['insurer_number']);

            if ($existing === null) {
                return back()->with('errorMessage', '更新対象の保険者が見つかりません');
            }

            if ($originalInsurerNumber !== $insurerNumber) {
                $duplicate = DB::connection('sqlsrv')
                    ->table('dbo.mx_insurers')
                    ->where('insurer_number', $insurerNumber)
                    ->exists();

                if ($duplicate) {
                    return back()->with('errorMessage', '同じ保険者番号が既に存在します');
                }
            }

            $query->where('insurer_number', $originalInsurerNumber)->update($payload);
        } else {
            $duplicate = DB::connection('sqlsrv')
                ->table('dbo.mx_insurers')
                ->where('insurer_number', $insurerNumber)
                ->exists();

            if ($duplicate) {
                return back()->with('errorMessage', '同じ保険者番号が既に存在します');
            }

            DB::connection('sqlsrv')->table('dbo.mx_insurers')->insert($payload);
        }

        return back()->with('successMessage', '保険者を保存しました');
    }

    public function delete(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $data = $request->validate([
            'original_insurer_number' => ['required', 'string', 'max:8'],
        ]);

        DB::connection('sqlsrv')
            ->table('dbo.mx_insurers')
            ->where('insurer_number', trim((string) $data['original_insurer_number']))
            ->delete();

        return back()->with('successMessage', '保険者を削除しました');
    }
}
