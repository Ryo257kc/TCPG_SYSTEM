<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DocumentsController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        return view('staff_portal.admin.documents.index', [
            'displayName' => $this->resolveDisplayName($staffId),
        ]);
    }

    private function resolveDisplayName(string $staffId): string
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first(['staff_name']);

        $name = trim((string) ($row->staff_name ?? ''));

        return $name !== '' ? $name : $staffId;
    }
}
