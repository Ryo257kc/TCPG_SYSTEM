<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AddressController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $keyword = trim((string) $request->query('q', ''));
        $selectedCategory = trim((string) $request->query('category', ''));
        $selectedContactPerson = trim((string) $request->query('contact_person', ''));
        $selectedOnly = (string) $request->query('selected_only', '') === '1';

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_addressees')
            ->select([
                'shipping_no',
                'full_name',
                'full_name_kana',
                'office_name',
                'company_name',
                'postal_code',
                'prefecture',
                'city',
                'address1',
                'building_name',
                'honorific',
                'is_selected',
                'category',
                'contact_person',
            ]);

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword): void {
                $query->where('full_name', 'like', '%' . $keyword . '%')
                    ->orWhere('full_name_kana', 'like', '%' . $keyword . '%')
                    ->orWhere('company_name', 'like', '%' . $keyword . '%')
                    ->orWhere('office_name', 'like', '%' . $keyword . '%')
                    ->orWhere('address1', 'like', '%' . $keyword . '%')
                    ->orWhere('building_name', 'like', '%' . $keyword . '%');
            });
        }

        if ($selectedCategory !== '') {
            $query->where('category', $selectedCategory);
        }

        if ($selectedContactPerson !== '') {
            $query->where('contact_person', $selectedContactPerson);
        }

        if ($selectedOnly) {
            $query->where(function ($query): void {
                $query->where('is_selected', true)
                    ->orWhere('is_selected', 1);
            });
        }

        $addressRows = $query
            ->orderBy('category')
            ->orderBy('full_name_kana')
            ->orderBy('shipping_no')
            ->get()
            ->map(fn($row): array => [
                'shipping_no' => (string) ($row->shipping_no ?? ''),
                'full_name' => trim((string) ($row->full_name ?? '')),
                'full_name_kana' => trim((string) ($row->full_name_kana ?? '')),
                'office_name' => trim((string) ($row->office_name ?? '')),
                'company_name' => trim((string) ($row->company_name ?? '')),
                'postal_code' => trim((string) ($row->postal_code ?? '')),
                'prefecture' => trim((string) ($row->prefecture ?? '')),
                'city' => trim((string) ($row->city ?? '')),
                'address1' => trim((string) ($row->address1 ?? '')),
                'building_name' => trim((string) ($row->building_name ?? '')),
                'honorific' => trim((string) ($row->honorific ?? '')),
                'is_selected' => (bool) ($row->is_selected ?? false) ? '1' : '',
                'category' => trim((string) ($row->category ?? '')),
                'contact_person' => trim((string) ($row->contact_person ?? '')),
            ])
            ->all();

        $categoryOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_addressees')
            ->select(['category'])
            ->whereNotNull('category')
            ->orderBy('category')
            ->get()
            ->map(fn($row): string => trim((string) ($row->category ?? '')))
            ->filter(fn(string $category): bool => $category !== '')
            ->unique()
            ->values()
            ->all();

        $contactPersonOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_addressees')
            ->select(['contact_person'])
            ->whereNotNull('contact_person')
            ->orderBy('contact_person')
            ->get()
            ->map(fn($row): string => trim((string) ($row->contact_person ?? '')))
            ->filter(fn(string $contactPerson): bool => $contactPerson !== '')
            ->unique()
            ->values()
            ->all();

        return view('staff_portal.office.office_menu.address.index', $this->commonViewData($request, [
            'keyword' => $keyword,
            'selectedCategory' => $selectedCategory,
            'selectedContactPerson' => $selectedContactPerson,
            'selectedOnly' => $selectedOnly,
            'categoryOptions' => $categoryOptions,
            'contactPersonOptions' => $contactPersonOptions,
            'addressRows' => $addressRows,
        ]));
    }
}
