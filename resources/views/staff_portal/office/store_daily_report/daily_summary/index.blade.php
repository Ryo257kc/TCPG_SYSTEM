<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 日報一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<style>
    .monthly-window-input-title {
        font-weight: bolder;
        font-size: 14px;
        margin: 8px 0 4px;
        color: #1b06df;
    }
</style>


<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">日報一覧</h2>
            </div>
            <div class="content-head">
                <form id="daily-summary-filter-form" method="get" action="{{ route('office.store_daily_report.daily_summary') }}" class="filter-row">
                    <label for="target_month">日付</label>
                    <input id="target_month" type="month" name="target_month" value="{{ $targetMonth ?? now()->format('Y-m') }}">

                    <select id="store" name="日報集計店舗">
                        <option value="">-店舗選択-</option>
                        @foreach (($storeOptions ?? []) as $日報集計店舗)
                        <option value="{{ $日報集計店舗 }}" @selected(($selectedStore ?? '' )===$日報集計店舗)>{{ $日報集計店舗 }}</option>
                        @endforeach
                    </select>


                    <button type="submit" class="btn btn-primary">表示</button>

                    <a href="{{ route('office.store_daily_report.daily_summary') }}" class="btn margin_r20">クリア</a>


                    @if (($selectedStore ?? '') !== '')
                    <select id="print_type" name="print_type">
                        <option value="">-印刷選択-</option>
                        <option value="monthly_daily_report" @selected(($selectedPrintType ?? 'monthly_daily_report' )==='monthly_daily_report' )>月報</option>
                        <option value="monthly_window_report" @selected(($selectedPrintType ?? '' )==='monthly_window_report' )>月間日報</option>
                        <option value="teacher_monthly_report" @selected(($selectedPrintType ?? '' )==='teacher_monthly_report' )>先生別月報</option>
                        <option value="teacher_daily_report" @selected(($selectedPrintType ?? '' )==='teacher_daily_report' )>先生別日報</option>
                    </select>
                    <button
                        type="submit"
                        class="btn"
                        formaction="{{ route('office.store_daily_report.daily_summary.monthly_print') }}"
                        formtarget="_blank">印刷</button>
                    @endif
                </form>

                @if (($vault_name ?? '') !== '')

                @endif
                @if ($isDailySummaryMonthlyClosed ?? false)
                <div class="notice">月次処理済 {{ $dailySummaryMonthlyClosingProcessedAt }}</div>
                @else
                <button type="submit" class="btn apply" form="receipt-monthly-close-form" onclick="syncMonthlyWindowInputs(); return confirm('{{ $targetMonth }}月分の月次締め処理をします。該当月の編集はできなくなります。');">店舗月次処理</button>
                @endif

                @if($is_admin)
                <button type="submit" class="btn master" form="receipt-monthly-unlock-form" onclick="return confirm('{{ $targetMonth }}月分のレセ月次処理を解除します。該当月の編集ができるようになります。');">店舗月次解除</button>
                @endif
            </div>
            <div class="monthly-window-input-form">
                <div class="monthly-window-input-title">【月間日報入力】</div>
                <table class="data-table f_size12 monthly-window-input-table">
                    <colgroup>
                        <col style="width: 110px;">
                        <col style="width: 160px;">
                        <col style="width: 160px;">
                        <col style="width: 80px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>店舗</th>
                            <th>レセ負担金</th>
                            <th>保険負担</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>さくら</th>
                            <td><input type="text" name="sakura_receipt_burden_amount" value="{{ $monthlyWindowInput['sakura_receipt_burden_amount'] ?? '' }}" class="text-right monthly-window-input" form="daily-summary-filter-form" @readonly($isDailySummaryMonthlyClosed ?? false)></td>
                            <td class="text-right">{{ $monthlyWindowInput['sakura_insurance_burden_total'] ?? '' }}</td>
                            <td rowspan="2" class="text-center">
                                @if (!($isDailySummaryMonthlyClosed ?? false))
                                <button type="submit" class="btn save" form="monthly-window-save-form" onclick="syncMonthlyWindowInputs();">保存</button>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>ひなた</th>
                            <td><input type="text" name="hinata_receipt_burden_amount" value="{{ $monthlyWindowInput['hinata_receipt_burden_amount'] ?? '' }}" class="text-right monthly-window-input" form="daily-summary-filter-form" @readonly($isDailySummaryMonthlyClosed ?? false)></td>
                            <td class="text-right">{{ $monthlyWindowInput['hinata_insurance_burden_total'] ?? '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <form id="monthly-window-save-form" method="post" action="{{ route('office.store_daily_report.daily_summary.monthly_window_save') }}">
                @csrf
                <input type="hidden" name="target_month" value="{{ $targetMonth ?? now()->format('Y-m') }}">
                <input type="hidden" id="save_sakura_receipt_burden_amount" name="sakura_receipt_burden_amount" value="{{ $monthlyWindowInput['sakura_receipt_burden_amount'] ?? '' }}">
                <input type="hidden" id="save_hinata_receipt_burden_amount" name="hinata_receipt_burden_amount" value="{{ $monthlyWindowInput['hinata_receipt_burden_amount'] ?? '' }}">
            </form>
            <form id="receipt-monthly-close-form" method="post" action="{{ route('office.store_daily_report.daily_summary.monthly_close') }}">
                @csrf
                <input type="hidden" name="daily_summary_id" value="{{ ($dailySummaryRows[0]['daily_summary_id'] ?? '') }}">
                <input type="hidden" name="target_month" value="{{ $targetMonth ?? now()->format('Y-m') }}">
                <input type="hidden" id="close_sakura_receipt_burden_amount" name="sakura_receipt_burden_amount" value="{{ $monthlyWindowInput['sakura_receipt_burden_amount'] ?? '' }}">
                <input type="hidden" id="close_hinata_receipt_burden_amount" name="hinata_receipt_burden_amount" value="{{ $monthlyWindowInput['hinata_receipt_burden_amount'] ?? '' }}">
            </form>
            <script>
                function syncMonthlyWindowInputs() {
                    var sakura = document.querySelector('input[name="sakura_receipt_burden_amount"]');
                    var hinata = document.querySelector('input[name="hinata_receipt_burden_amount"]');
                    var closeSakura = document.getElementById('close_sakura_receipt_burden_amount');
                    var closeHinata = document.getElementById('close_hinata_receipt_burden_amount');
                    var saveSakura = document.getElementById('save_sakura_receipt_burden_amount');
                    var saveHinata = document.getElementById('save_hinata_receipt_burden_amount');
                    if (sakura && closeSakura) closeSakura.value = sakura.value;
                    if (hinata && closeHinata) closeHinata.value = hinata.value;
                    if (sakura && saveSakura) saveSakura.value = sakura.value;
                    if (hinata && saveHinata) saveHinata.value = hinata.value;
                }
            </script>
            <form id="receipt-monthly-unlock-form" method="post" action="{{ route('office.store_daily_report.daily_summary.monthly_unlock') }}">
                @csrf
                <input type="hidden" name="target_month" value="{{ $targetMonth ?? now()->format('Y-m') }}">
            </form>

            @if (session('statusMessage'))
            <div class="status-message status">{{ session('statusMessage') }}</div>
            @endif

            @if (session('errorMessage'))
            <div class="status-message error">{{ session('errorMessage') }}</div>
            @endif
            <div class="table-wrap f_size13">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 70px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 70px;">
                        <col style="width: 30px;">
                        <col style="width: 80px;">
                        <col style="width: 100px;">
                        <col style="width: 50px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>来院<br>人数</th>
                            <th>予約<br>人数</th>
                            <th>院内<br>人数</th>
                            <th>レセコン</th>
                            <th>先生別計</th>
                            <th>差額</th>
                            <th>交通事故</th>
                            <th>レジ</th>
                            <th>銀行預入<br>金額</th>
                            <th>誤差<br>チェック</th>
                            <th>確定</th>
                            <th>確定日</th>
                            <th>日報集計店舗</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($dailySummaryRows ?? []) as $row)
                        <tr>
                            <td>{{ $row['日付'] }}</td>
                            <td class="text-right">{{ $row['来院人数'] }}</td>
                            <td class="text-right">{{ $row['予約人数'] }}</td>
                            <td class="text-right">{{ $row['院内人数'] }}</td>
                            <td class="text-right">{{ $row['レセコン'] }}</td>
                            <td class="text-right">{{ $row['先生別計'] }}</td>
                            <td class="text-right">{{ $row['差額'] }}</td>
                            <td class="text-right">{{ $row['交通事故'] }}</td>
                            <td class="text-right">{{ $row['レジ'] }}</td>
                            <td class="text-right">{{ $row['銀行預入金額'] }}</td>
                            <td class="text-right">{{ $row['誤差チェック'] }}</td>
                            <td>{{ $row['確定'] }}</td>
                            <td>{{ $row['確定日'] }}</td>
                            <td>{{ $row['日報集計店舗'] }}</td>
                            <td>
                                <a
                                    href="{{ route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $row['daily_summary_id']]) }}"
                                    class="btn btn_small">詳細</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="15">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                <a href="{{ route('office.store_daily_report') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
