<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 返戻ノート</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .return-note-filter {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-start;
        }

        .return-note-filter fieldset {
            align-items: center;
            display: inline-flex;
            gap: 10px;
            margin: 0;
            width: auto;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">返戻ノート</h2>
            </div>

            @if (session('statusMessage'))
            <div class="status-message status">{{ session('statusMessage') }}</div>
            @endif

            @if (session('errorMessage'))
            <div class="status-message error">{{ session('errorMessage') }}</div>
            @endif

            <form method="get" action="{{ route('office.store_daily_report.return_note') }}" class="filter-row return-note-filter">
                <label>
                    施術年
                    <input type="number" name="target_year" value="{{ $targetYear }}" min="2000" max="2100" step="1">
                </label>

                <select name="返戻店舗">
                    <option value="">-店舗選択-</option>
                    @foreach (($storeOptions ?? []) as $返戻店舗)
                    <option value="{{ $返戻店舗 }}" @selected(($selectedStore ?? '' )===$返戻店舗)>{{ $返戻店舗 }}</option>
                    @endforeach
                </select>

                <fieldset>
                    <legend>入金フィルタ</legend>
                    @foreach (['' => '全て', 'unpaid' => '未入金', 'paid' => '入金済'] as $value => $label)
                    <label>
                        <input type="radio" name="payment_filter" value="{{ $value }}" @checked(($paymentFilter ?? '' )===$value)>
                        {{ $label }}
                    </label>
                    @endforeach
                </fieldset>

                <button type="submit" class="btn">表示</button>
            </form>

            <div class="table-wrap">
                <table class="data-table f_size12">
                    <colgroup>
                        <col style="width: 50px;">
                        <col style="width: 20px;">
                        <col style="width: 60px;">
                        <col style="width: 35px;">
                        <col style="width: 90px;">
                        <col style="width: 40px;">
                        <col style="width: 90px;">
                        <col style="width: 40px;">
                        <col style="width: 35px;">
                        <col style="width: 70px;">
                        <col style="width: 80px;">
                        <col style="width: 30px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>患者名</th>
                            <th>区分</th>
                            <th>返戻先保険者</th>
                            <th>施術月</th>
                            <th>返戻理由</th>
                            <th>金額</th>
                            <th>備考</th>
                            <th>返戻店舗</th>
                            <th>提出月</th>
                            <th>入金日</th>
                            <th>事務所備考</th>
                            <th>保存</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($returnNoteRows ?? []) as $row)
                        @php
                        $formId = 'return-note-save-' . $loop->index;
                        @endphp
                        <tr>
                            <td>{{ $row['患者名'] }}</td>
                            <td>{{ $row['本人区分'] }}</td>
                            <td>{{ $row['返戻先保険者'] }}</td>
                            <td>{{ $row['施術月'] }}</td>
                            <td>{{ $row['返戻理由'] }}</td>
                            <td class="text-right">{{ $row['金額'] }}</td>
                            <td>{{ $row['返戻備考'] }}</td>
                            <td>{{ $row['返戻店舗'] }}</td>
                            <td>{{ $row['提出月'] }}</td>
                            <td>
                                <form id="{{ $formId }}" method="post" action="{{ route('office.store_daily_report.return_note.save') }}">
                                    @csrf
                                    <input type="hidden" name="henrei_no" value="{{ $row['henrei_no'] }}">
                                    <input type="hidden" name="target_year" value="{{ $targetYear }}">
                                    <input type="hidden" name="返戻店舗" value="{{ $selectedStore }}">
                                    <input type="hidden" name="payment_filter" value="{{ $paymentFilter }}">
                                    <input type="date" name="入金日" value="{{ $row['original_入金日'] }}">
                                </form>
                            </td>
                            <td><input form="{{ $formId }}" type="text" name="事務所備考" value="{{ $row['事務所備考'] }}"></td>
                            <td><button form="{{ $formId }}" type="submit" class="btn_small">保存</button></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12">対象データがありません。</td>
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
