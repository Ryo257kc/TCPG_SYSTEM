<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 入金確定履歴</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])
        @include('shared.status_message')

        <section class="panel content-panel sales-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">入金確定履歴</h2>
            </div>

            <form method="get" action="{{ route('hv_office.payment_confirmed') }}" class="filter-row">
                <select name="year">
                    @for($y = 2020; $y <= (int) now()->format('Y'); $y++)
                    <option value="{{ $y }}" @selected((string) $year === (string) $y)>{{ $y }}年</option>
                    @endfor
                </select>
                <button type="submit" class="btn">表示</button>
            </form>

            <div class="table-wrap staff-viewport-list-wrap">
                @if($rows->count() === 0)
                <div class="empty">「{{ $year }}」のデータがありません。</div>
                @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>月度</th>
                            <th>入金管理　確定日</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        @php
                        $isConfirmed = (int) ($row->is_payment_confirmed ?? 0) === 1;
                        $monthLabel = \Carbon\Carbon::parse($row->target_month)->format('Y-m');
                        @endphp
                        <tr>
                            <td>{{ $monthLabel }}</td>
                            <td style="{{ $isConfirmed ? '' : 'background:#fff3cd;' }}">
                                {{ $isConfirmed && $row->payment_confirmed_at ? \Carbon\Carbon::parse($row->payment_confirmed_at)->format('Y-m-d H:i:s') : '' }}
                            </td>
                            <td>
                                @if($isConfirmed)
                                <form method="post" action="{{ route('hv_office.payment_confirmed.unconfirm') }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="target_month" value="{{ $monthLabel }}">
                                    <button type="submit" class="btn_small">解除</button>
                                </form>
                                @else
                                <form method="post" action="{{ route('hv_office.payment_confirmed.confirm') }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="target_month" value="{{ $monthLabel }}">
                                    <button type="submit" class="btn_small">確定</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
