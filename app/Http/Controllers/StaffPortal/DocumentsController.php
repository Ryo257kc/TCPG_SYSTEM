<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DocumentsController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        return view('staff_portal.admin.documents.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
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
