<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 仕訳帳CSV取込確認</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <style>
        .import-review-group {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .import-review-group-header {
            margin-bottom: 8px;
        }

        .import-review-group-header label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
        }

        .import-review-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .import-review-columns h4 {
            margin: 0 0 6px;
        }

        .import-review-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .import-review-diff {
            font-weight: bold;
            color: #c0392b;
        }
    </style>
</head>

<body>
    <section class="panel admin-viewport-panel">
        <h2>仕訳帳CSV取込の確認</h2>
        <p>{{ $summaryMessage }}</p>

        <h3>科目別照合（CSV合計 と DB合計）</h3>
        <p>
            同じ会社・取引日の範囲で、科目ごとにCSVの合計金額とDB上の合計金額を突き合わせています。
            差額がある行は赤字で表示しています。取込むものが無いCSVでも、この照合結果だけを確認する用途で使えます。
        </p>
        <table class="data-table">
            <thead>
                <tr>
                    <th>貸借</th>
                    <th>科目</th>
                    <th class="num">CSV合計</th>
                    <th class="num">DB合計</th>
                    <th class="num">差額</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reconciliation as $row)
                <tr class="{{ $row['has_diff'] ? 'import-review-diff' : '' }}">
                    <td>{{ $row['side'] }}</td>
                    <td>{{ $row['account_title'] }}</td>
                    <td class="num">{{ $row['csv_total'] }}</td>
                    <td class="num">{{ $row['db_total'] }}</td>
                    <td class="num">{{ $row['diff'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if (count($groups) > 0)
        <h3>要確認の仕訳</h3>
        <p>
            以下は、同じ会社・取引日・仕訳Noの仕訳がすでにDBに存在するため自動では取り込んでいません。
            既存の内容とCSVの内容を見比べて、反映してよいものだけチェックを入れて適用してください。
            チェックしなかったものは何も変更されません。
        </p>

        <form method="post" action="{{ route('admin.work.journal_entries.import_apply') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            @foreach ($groups as $group)
            <div class="import-review-group">
                <div class="import-review-group-header">
                    <label>
                        <input type="checkbox" name="group_keys[]" value="{{ $group['group_key'] }}" checked>
                        {{ $group['company_name_short'] }}｜{{ $group['occurred_at'] }}｜仕訳No {{ $group['journal_breakdown'] }}
                        （既存{{ count($group['existing_lines']) }}件 → CSV{{ count($group['new_lines']) }}件）
                    </label>
                </div>
                <div class="import-review-columns">
                    <div>
                        <h4>既存（DB）</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>借方科目</th>
                                    <th class="num">借方金額</th>
                                    <th>貸方科目</th>
                                    <th class="num">貸方金額</th>
                                    <th>摘要</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['existing_lines'] as $line)
                                <tr>
                                    <td>{{ $line['debit_account_title'] }}</td>
                                    <td class="num">{{ $line['debit_amount'] }}</td>
                                    <td>{{ $line['credit_account_title'] }}</td>
                                    <td class="num">{{ $line['credit_amount'] }}</td>
                                    <td>{{ $line['summary_text'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div>
                        <h4>CSV（新）</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>借方科目</th>
                                    <th class="num">借方金額</th>
                                    <th>貸方科目</th>
                                    <th class="num">貸方金額</th>
                                    <th>摘要</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['new_lines'] as $line)
                                <tr>
                                    <td>{{ $line['debit_account_title'] }}</td>
                                    <td class="num">{{ $line['debit_amount'] }}</td>
                                    <td>{{ $line['credit_account_title'] }}</td>
                                    <td class="num">{{ $line['credit_amount'] }}</td>
                                    <td>{{ $line['summary_text'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach

            <div class="import-review-actions">
                <button type="submit" class="btn btn-primary">チェックしたものを適用</button>
                <a class="btn" href="{{ route('admin.work.journal_entries') }}">キャンセル（何も適用しない）</a>
            </div>
        </form>
        @else
        <div class="import-review-actions">
            <a class="btn btn-primary" href="{{ route('admin.work.journal_entries') }}">戻る</a>
        </div>
        @endif
    </section>
</body>

</html>
