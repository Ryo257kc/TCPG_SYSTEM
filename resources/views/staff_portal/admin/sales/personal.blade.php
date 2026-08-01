<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 個人売上</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">個人売上</h2>
            </div>

            <form method="get" class="filter-row">
                <label for="month">対象月</label>
                <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                <label for="staff_id">名前</label>
                <select id="staff_id" name="staff_id">
                    <option value="" @selected($selectedStaffId==='' )></option>
                    @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($staff['staff_id']===$selectedStaffId)>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
                    @endforeach
                </select>
                <button type="submit">表示</button>
            </form>

            @if ($errorMessage !== '')
            <div class="status">{{ $errorMessage }}</div>
            @endif

            @if ($selectedStaffId !== '')
            <div class="section-head">
                <h3>店舗売上一覧</h3>
                <div class="name-meta">名前：{{ $selectedStaffName }}</div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>店舗</th>
                            <th>保険</th>
                            <th>自費</th>
                            <th>合計</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($storeRows as $row)
                        <tr>
                            <td>{{ $row['store_name'] }}</td>
                            <td class="num">{{ number_format($row['insurance_amount']) }}</td>
                            <td class="num">{{ number_format($row['private_amount']) }}</td>
                            <td class="num">{{ number_format($row['total_amount']) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td>売上なし</td>
                            <td class="num">0</td>
                            <td class="num">0</td>
                            <td class="num">0</td>
                        </tr>
                        @endforelse
                        <tr>
                            <td>往診</td>
                            <td class="num"></td>
                            <td class="num"></td>
                            <td class="num">{{ number_format($houseCall['total'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td><b>合計</b></td>
                            <td class="num"><b>{{ number_format($storeTotals['insurance']) }}</b></td>
                            <td class="num"><b>{{ number_format($storeTotals['private']) }}</b></td>
                            <td class="num"><b>{{ number_format(($storeTotals['total'] ?? 0) + ($houseCall['total'] ?? 0)) }}</b></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="note">※往診売上はリアルタイムではありません。</p>

            <h3>メニュー別一覧</h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>メニュー</th>
                            <th>件数</th>
                            <th>自費合計</th>
                            <th>さくら歩合対象額</th>
                            <th>ひなた歩合対象額</th>
                            <th>店舗</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['menu_name'] }}</td>
                            <td>{{ number_format($row['visit_count']) }}</td>
                            <td class="num">{{ number_format($row['private_total']) }}</td>
                            <td class="num">{{ number_format($row['sakura_incentive_total']) }}</td>
                            <td class="num">{{ number_format($row['hinata_incentive_total']) }}</td>
                            <td>{{ $row['store_name'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td>売上なし</td>
                            <td>0</td>
                            <td class="num">0</td>
                            <td class="num">0</td>
                            <td class="num">0</td>
                            <td>-</td>
                        </tr>
                        @endforelse
                        <tr>
                            <td><b>合計</b></td>
                            <td><b>{{ number_format((int) $rows->sum('visit_count')) }}</b></td>
                            <td class="num"><b>{{ number_format((int) $rows->sum('private_total')) }}</b></td>
                            <td class="num"><b>{{ number_format((int) $rows->sum('sakura_incentive_total')) }}</b></td>
                            <td class="num"><b>{{ number_format((int) $rows->sum('hinata_incentive_total')) }}</b></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif

            <div>
                <a href="{{ route('store.sales') }}" class="btn btn_back">戻る</a>
            </div>
        </section>


    </main>
</body>

</html>
