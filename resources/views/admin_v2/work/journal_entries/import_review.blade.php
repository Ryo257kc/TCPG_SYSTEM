<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 仕訳帳CSV取込確認</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/import_review.css') }}">
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">TCPG SYSTEM 仕訳帳CSV取込の確認</h1>
        </div>

        {{-- 要確認：admin-viewport-panelはビューポート高さ固定＋内部スクロール前提のクラスで、
        この画面のように縦に長く伸びる内容（要確認の仕訳が多いと特に）には合わず、枠の高さを
        超えて中身がはみ出して見えていた。ページ全体で普通にスクロールする単純なpanelに
        変更（2026-08-17）。 --}}
        <section class="panel">
        <p>{{ $summaryMessage }}</p>

        @if ($pendingNewCount > 0)
        <h3>新規追加</h3>
        <p>
            DBにまだ無い新規の仕訳が {{ $pendingNewCount }} 件あります。まだ何も書き込んでいません。
            内容に問題なければ下のボタンで取り込んでください。
        </p>
        <form method="post" action="{{ route('admin.work.journal_entries.import_apply_new') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-primary">この新規分（{{ $pendingNewCount }}件）を取り込む</button>
        </form>
        @else
        <h3>科目別照合（CSV合計 と DB合計）</h3>
        <p>
            同じ会社・取引日の範囲で、科目ごとにCSVの合計金額とDB上の合計金額を突き合わせています。
            差額がある行は赤字で表示しています。
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
        @endif

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

            @php
            $reviewFieldGroups = [
                ['field' => 'debit_account_title', 'label' => '借方科目', 'num' => false],
                ['field' => 'debit_amount', 'label' => '借方金額', 'num' => true],
                ['field' => 'credit_account_title', 'label' => '貸方科目', 'num' => false],
                ['field' => 'credit_amount', 'label' => '貸方金額', 'num' => true],
                ['field' => 'summary_text', 'label' => '摘要', 'num' => false],
            ];
            @endphp
            @foreach ($groups as $group)
            <div class="import-review-group">
                <div class="import-review-group-header">
                    <label>
                        <input type="checkbox" name="group_keys[]" value="{{ $group['group_key'] }}" checked>
                        {{ $group['company_name_short'] }}｜{{ $group['occurred_at'] }}｜仕訳No {{ $group['journal_breakdown'] }}
                        （{{ count($group['line_pairs']) }}行、色が付いてる項目が既存とCSVで違います）
                    </label>
                </div>
                <div class="table-wrap import-review-table-wrap">
                    <table class="data-table import-review-pair-table">
                        <thead>
                            <tr>
                                @foreach ($reviewFieldGroups as $fieldGroup)
                                <th colspan="2">{{ $fieldGroup['label'] }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($reviewFieldGroups as $fieldGroup)
                                <th class="{{ $fieldGroup['num'] ? 'num' : '' }} import-review-sub-existing">既存</th>
                                <th class="{{ $fieldGroup['num'] ? 'num' : '' }} import-review-sub-new">CSV</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['line_pairs'] as $pair)
                            <tr>
                                @foreach ($reviewFieldGroups as $fieldGroup)
                                @php $field = $fieldGroup['field']; $isDiff = $pair['diff'][$field] ?? false; @endphp
                                <td class="import-review-sub-existing {{ $fieldGroup['num'] ? 'num' : '' }} {{ $isDiff ? 'import-review-diff-cell' : '' }}">{{ $pair['existing'][$field] ?? '（対応なし）' }}</td>
                                <td class="import-review-sub-new {{ $fieldGroup['num'] ? 'num' : '' }} {{ $isDiff ? 'import-review-diff-cell' : '' }}">{{ $pair['new'][$field] ?? '（対応なし）' }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
    </div>
</body>

</html>
