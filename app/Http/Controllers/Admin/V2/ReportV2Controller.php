<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Report\ReportV2SanteiCsvService;
use App\Services\Admin\V2\Report\ReportV2SanteiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportV2Controller extends Controller
{
    public function __construct(
        private readonly ReportV2SanteiService $santeiService,
        private readonly ReportV2SanteiCsvService $santeiCsvService,
    ) {
    }

    public function index(): View
    {
        $categories = [
            [
                'title' => '給与帳票',
                'description' => '毎月の給与確認や社保負担の確認に使う帳票です。',
                'items' => [
                    [
                        'title' => '振込先一覧',
                        'description' => '給与計算の振込先一覧を開きます。',
                        'status' => 'available',
                        'url' => route('admin.payroll.transfer-list'),
                        'action' => '開く',
                    ],
                    [
                        'title' => '賃金台帳',
                        'description' => '給与計算の賃金台帳を開きます。',
                        'status' => 'available',
                        'url' => route('admin.payroll.wage-ledger'),
                        'action' => '開く',
                    ],
                    [
                        'title' => '社保負担一覧',
                        'description' => '事業主負担と個人負担を確認する一覧です。',
                        'status' => 'planned',
                    ],
                    [
                        'title' => 'ベースアップ一覧',
                        'description' => 'ベースアップ対象者を確認する一覧です。',
                        'status' => 'planned',
                    ],
                ],
            ],
            [
                'title' => '申請帳票',
                'description' => '電子申請や提出用の一覧・CSVをまとめていく枠です。',
                'items' => [
                    [
                        'title' => '算定基礎一覧',
                        'description' => '算定基礎の対象者と算定結果を確認する一覧です。',
                        'status' => 'available',
                        'url' => route('admin.report.santei.index'),
                        'action' => '開く',
                    ],
                    [
                        'title' => '算定基礎 CSV',
                        'description' => '電子申請用の算定基礎 CSV を出力します。',
                        'status' => 'planned',
                    ],
                    [
                        'title' => '賞与支払届 CSV',
                        'description' => '電子申請用の賞与支払届 CSV を出力します。',
                        'status' => 'planned',
                    ],
                    [
                        'title' => '各種連絡票',
                        'description' => '今後追加する社保・労務の各種連絡票です。',
                        'status' => 'planned',
                    ],
                ],
            ],
            [
                'title' => '年度集計',
                'description' => '年替わりの集計や対象者抽出をまとめる枠です。',
                'items' => [
                    [
                        'title' => '年度更新集計表',
                        'description' => '年度更新に必要な集計を確認する一覧です。',
                        'status' => 'planned',
                    ],
                    [
                        'title' => '月変対象者一覧',
                        'description' => '月額変更の候補者を確認する一覧です。',
                        'status' => 'planned',
                    ],
                ],
            ],
        ];

        return view('admin_v2.report.index', [
            'categories' => $categories,
        ]);
    }

    public function santeiIndex(Request $request): View
    {
        $availableYears = $this->santeiService->availableYears();
        $selectedYear = (int) $request->query('year', $availableYears[0] ?? date('Y'));
        if ($availableYears !== [] && !in_array($selectedYear, $availableYears, true)) {
            $selectedYear = $availableYears[0];
        }

        $companyOptions = $this->santeiService->companyOptions($selectedYear);
        $selectedCompany = trim((string) $request->query('company', ''));
        if ($selectedCompany !== '' && !in_array($selectedCompany, $companyOptions, true)) {
            $selectedCompany = '';
        }

        return view('admin_v2.report.santei_clean', [
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'companyOptions' => $companyOptions,
            'selectedCompany' => $selectedCompany,
            'rows' => $this->santeiService->rows($selectedYear, $selectedCompany),
        ]);
    }

    public function santeiCsv(Request $request)
    {
        $availableYears = $this->santeiService->availableYears();
        $selectedYear = (int) $request->query('year', $availableYears[0] ?? date('Y'));
        if ($availableYears !== [] && !in_array($selectedYear, $availableYears, true)) {
            $selectedYear = $availableYears[0];
        }

        $companyOptions = $this->santeiService->companyOptions($selectedYear);
        $selectedCompany = trim((string) $request->query('company', ''));
        abort_if($selectedCompany === '' || !in_array($selectedCompany, $companyOptions, true), 404);

        $csv = $this->santeiCsvService->build($selectedYear, $selectedCompany, date('Ymd'));
        abort_if($csv === '', 404);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $bytes = mb_convert_encoding($csv, 'SJIS-win', 'UTF-8');

        return response()->streamDownload(
            static function () use ($bytes): void {
                echo $bytes;
            },
            'SHFD0006.CSV',
            [
                'Content-Type' => 'text/csv; charset=Shift_JIS',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }
}
