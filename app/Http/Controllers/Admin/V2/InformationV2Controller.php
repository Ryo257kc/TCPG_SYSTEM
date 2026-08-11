<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Support\ParsedInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InformationV2Controller extends Controller
{
    public function index(): View
    {
        $messages = DB::connection('sqlsrv')
            ->table('dbo.mx_message')
            ->select([
                'mesa_no',
                'mase_date',
                'destination',
                'title',
                'contents',
                'me_check',
            ])
            ->orderByDesc('mase_date')
            ->orderByDesc('mesa_no')
            ->get()
            ->map(fn($row): array => [
                'mesa_no' => (int) ($row->mesa_no ?? 0),
                'mase_date' => ParsedInput::date($row->mase_date) ?? '',
                'mase_date_label' => ParsedInput::dateDisplay($row->mase_date),
                'destination' => trim((string) ($row->destination ?? '')),
                'title' => trim((string) ($row->title ?? '')),
                'contents' => trim((string) ($row->contents ?? '')),
                'me_check' => (int) ($row->me_check ?? 0) !== 0,
            ])
            ->all();

        return view('admin_v2.work.information.index', [
            'messages' => $messages,
            'destinationOptions' => ['info', 'All'],
            'defaultDate' => now()->format('Y-m-d'),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mesa_no' => ['nullable', 'integer'],
            'mase_date' => ['nullable', 'date'],
            'destination' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:20'],
            'contents' => ['nullable', 'string'],
        ]);

        $payload = [
            'mase_date' => $validated['mase_date'] ?? now()->format('Y-m-d'),
            'destination' => trim((string) $validated['destination']),
            'title' => trim((string) $validated['title']),
            'contents' => trim((string) ($validated['contents'] ?? '')),
            'me_check' => $request->boolean('me_check') ? 1 : 0,
        ];

        $messageNo = (int) ($validated['mesa_no'] ?? 0);

        if ($messageNo > 0) {
            DB::connection('sqlsrv')
                ->table('dbo.mx_message')
                ->where('mesa_no', $messageNo)
                ->update($payload);
        } else {
            DB::connection('sqlsrv')
                ->table('dbo.mx_message')
                ->insert($payload);
        }

        return redirect()
            ->route('admin.work.information')
            ->with('status', '保存しました。');
    }

}
