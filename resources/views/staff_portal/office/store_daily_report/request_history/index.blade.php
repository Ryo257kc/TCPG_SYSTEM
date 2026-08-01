<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 修正依頼</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .request-history-filter {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-start;
        }

        .request-history-filter fieldset {
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
                <h2 class="content-title">修正依頼</h2>
            </div>

            @if (session('statusMessage'))
            <div class="status-message status">{{ session('statusMessage') }}</div>
            @endif

            @if (session('errorMessage'))
            <div class="status-message error">{{ session('errorMessage') }}</div>
            @endif

            <form method="get" action="{{ route('office.store_daily_report.request_history') }}" class="filter-row request-history-filter">
                <fieldset>
                    <legend>ステータス</legend>
                    @foreach (['' => '全て', '依頼中' => '依頼中', '処理済' => '処理済', '取下げ' => '取下げ'] as $value => $label)
                    <label>
                        <input type="radio" name="status_filter" value="{{ $value }}" @checked(($selectedStatus ?? '' )===$value)>
                        {{ $label }}
                    </label>
                    @endforeach
                </fieldset>
                <button type="submit" class="btn">表示</button>
            </form>

            <div class="table-wrap">
                <table class="data-table f_size14">
                    <colgroup>
                        <col style="width: 40px;">
                        <col style="width: 30px;">
                        <col style="width: 150px;">
                        <col style="width: 30px;">
                        <col style="width: 20px;">
                        <col style="width: 50px;">
                        <col style="width: 90px;">
                        <col style="width: 20px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>ステータス</th>
                            <th>対象日</th>
                            <th>内容</th>
                            <th>依頼日</th>
                            <th>依頼者</th>
                            <th>店舗</th>
                            <th>店舗非表示・事務所備忘用</th>
                            <th>保存</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($requestHistoryRows ?? []) as $row)
                        @php
                        $formId = 'request-history-save-' . $loop->index;
                        @endphp
                        <tr>
                            <td>
                                <form id="{{ $formId }}" method="post" action="{{ route('office.store_daily_report.request_history.save') }}">
                                    @csrf
                                    <input type="hidden" name="original_対象日" value="{{ $row['original_対象日'] }}">
                                    <input type="hidden" name="original_内容" value="{{ $row['内容'] }}">
                                    <input type="hidden" name="original_依頼日" value="{{ $row['original_依頼日'] }}">
                                    <input type="hidden" name="original_依頼者" value="{{ $row['依頼者'] }}">
                                    <input type="hidden" name="original_店舗" value="{{ $row['店舗'] }}">
                                    <select name="状況">
                                        @foreach (['依頼中', '処理済', '取下げ'] as $状況)
                                        <option value="{{ $状況 }}" @selected(($row['状況'] ?? '' )===$状況)>{{ $状況 }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>{{ $row['対象日'] }}</td>
                            <td>{{ $row['内容'] }}</td>
                            <td>{{ $row['依頼日'] }}</td>
                            <td>{{ $row['依頼者'] }}</td>
                            <td>{{ $row['店舗'] }}</td>
                            <td><input form="{{ $formId }}" type="text" name="事務所コメント" value="{{ $row['事務所コメント'] }}"></td>
                            <td><button form="{{ $formId }}" type="submit" class="btn_small">保存</button></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">対象データがありません。</td>
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
