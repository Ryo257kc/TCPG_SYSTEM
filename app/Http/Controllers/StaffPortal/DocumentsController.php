<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentsController extends Controller
{
    use HandlesStaffPortalContext;

    // 以前はpublic/document/配下に置いて認証無しで直接URLアクセスできる状態だった
    // （storage/app/templates/documents/へ移設、2026-09-06）。ファイル名を直接受け取らず
    // 許可リストのキー経由にすることで、パストラバーサルを防ぐ。
    private const DOWNLOADABLE_FILES = [
        'pg_pledge' => 'PG-誓約書.pdf',
        'tc_pledge' => 'TC-誓約書.pdf',
        'pg_guarantor' => 'PG-身元保証引受書.pdf',
        'tc_guarantor' => 'TC-身元保証引受書.pdf',
        'commute_route' => '通勤手段経路申請書.pdf',
        'fuyo_koujyo_shinkoku' => '扶養控除申告書.pdf',
    ];

    public function index(Request $request): RedirectResponse|View
    {
        return view('staff_portal.admin.documents.index', $this->commonViewData($request, []));
    }

    public function download(string $fileKey): BinaryFileResponse
    {
        abort_unless(array_key_exists($fileKey, self::DOWNLOADABLE_FILES), 404);

        $fileName = self::DOWNLOADABLE_FILES[$fileKey];
        $path = storage_path('app/templates/documents/' . $fileName);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
