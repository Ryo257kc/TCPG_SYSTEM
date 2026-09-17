<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 仕訳帳</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/journal_entries.css') }}"> -->
    <style>
        /* .journal-entries-panel {
            display: grid;
            gap: 12px;
        } */

        /* .journal-entries-filter-row {
            display: grid;
            grid-template-columns: minmax(200px, 250px) minmax(160px, 200px) minmax(160px, 200px) minmax(160px, 200px) minmax(160px, 200px)auto;
            gap: 10px;
            align-items: end;
        } */
        .journal-entries-filter-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(140px, 1fr));
            gap: 10px;
            align-items: end;
        }

        .journal-entries-pager {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }

        .journal-entries-pager .btn.is-disabled {
            pointer-events: none;
            opacity: .45;
        }

        .journal-entries-pager-label {
            font-weight: 700;
            color: var(--muted);
        }

        .journal-entries-filter-group-wide {
            grid-column: span 2;
        }

        .journal-entries-date-range {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
        }

        .journal-entries-filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
            white-space: nowrap;
        }


        .journal-entries-filter-group input {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .journal-entries-filter-group {
            display: grid;
            gap: 6px;
            min-width: 0;
        }

        .journal-entries-filter-label {
            font-size: 12px;
            color: #5b6474;
            font-weight: 700;
        }

        /* .journal-entries-filter-group select,
        .journal-entries-filter-group input[type="date"] {
            width: 100%;
            box-sizing: border-box;
        } */

        /* .journal-entries-date-range {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            width: 250px;
        } */

        .journal-entries-date-range span {
            color: #5b6474;
            font-size: 12px;
            font-weight: 700;
        }

        .journal-entries-filter-actions {
            display: flex;
            /* justify-content: flex-end;
            align-items: end; */
        }

        .journal-entries-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .journal-entries-display-toggle {
            display: flex;
            gap: 6px;
        }

        .journal-entries-table th.num,
        .journal-entries-table td.num {
            text-align: right;
        }

        .journal-entries-panel {
            min-height: 0;
        }

        .journal-entries-table-wrap {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding-bottom: 72px;
            scroll-padding-bottom: 72px;
            border: 1px solid #d9e0ea;
            border-radius: 10px;
            background: #fff;
        }

        .journal-entries-table {
            /* min-width: 1540px; */
        }

        .journal-entries-group-row th {
            text-align: center;
            font-size: 11px;
            background: #edf4fd;
            color: #46638a;
            border-bottom: 1px solid #d6e2f2;
        }

        .journal-entries-table th,
        .journal-entries-table td {
            vertical-align: top;
        }

        /* フラット表示の見出しが一緒にスクロールされてた原因はposition:stickyを一度も
           指定していなかっただけ（2026-09-14）。スクロールする親(.journal-entries-table-wrap、
           overflow:auto)を基準に上端で固定する。背景色が無いと下の行が透けて見えるため
           不透明な背景も指定する。 */
        .journal-entries-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #edf4fd;
        }

        .journal-entries-flag-cell {
            /* width: 48px; */
            font-weight: 700;
        }

        .journal-entries-stack-cell,
        .journal-entries-note-cell,
        .journal-entries-summary-cell {
            white-space: normal !important;
            line-height: 1.45;
        }

        /* .journal-entries-stack-cell {
            min-width: 220px;
        } */

        .journal-entries-stack-main {
            font-weight: 700;
            color: #23354d;
        }

        .journal-entries-stack-sub {
            color: #5b6474;
            font-size: 11px;
        }

        /* .journal-entries-note-cell {
            min-width: 150px;
        } */

        .journal-entries-note-sub {
            color: #5b6474;
            font-size: 11px;
        }

        /* .journal-entries-summary-cell {
            min-width: 220px;
        } */

        .journal-entries-edit-input {
            box-sizing: border-box;
            width: 100%;
            min-width: 90px;
            height: 26px;
            font-size: 12px;
        }

        .journal-entries-edit-actions {
            display: flex;
            gap: 6px;
            white-space: nowrap;
        }

        .journal-entries-edit {
            display: none;
        }

        .journal-entries-row.is-editing .journal-entries-view {
            display: none;
        }

        .journal-entries-row.is-editing .journal-entries-edit {
            display: block;
        }

        .journal-entries-table {
            table-layout: fixed;
        }

        .journal-group-list {
            display: grid;
            gap: 8px;
            min-width: 0;
            padding-bottom: 72px;
        }

        .journal-group {
            border: 1px solid #d9e0ea;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }

        .journal-group-summary {
            display: grid;
            grid-template-columns: 54px 110px 120px 72px 120px 120px minmax(220px, 1fr) 140px;
            gap: 10px;
            align-items: center;
            padding: 8px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        .journal-group-summary>div:last-child {
            display: none;
        }

        .journal-group-summary::-webkit-details-marker {
            display: none;
        }

        .journal-group-summary::before {
            content: "開く";
            color: #1f5f99;
            font-weight: 700;
        }

        .journal-group[open] .journal-group-summary::before {
            content: "閉じる";
        }

        .journal-group-main {
            font-weight: 700;
            color: #23354d;
        }

        .journal-group-sub {
            color: #5b6474;
            font-size: 11px;
        }

        .journal-group-details {
            border-top: 1px solid #e7eef8;
            padding: 10px 10px 18px;
            display: grid;
            gap: 10px;
            background: #fbfdff;
            overflow-x: auto;
        }

        .journal-detail-common {
            width: max-content;
            min-width: 0;
            border: 1px solid #dbe5f1;
            border-radius: 6px;
            background: #fff;
            padding: 8px;
            display: grid;
            gap: 6px;
        }

        .journal-detail-common-row {
            display: grid;
            grid-template-columns: 100px 130px 130px 100px 110px 160px;
            gap: 8px;
            align-items: start;
        }

        .journal-detail-common-row-wide {
            grid-template-columns: 1fr;
        }

        .journal-detail-common-footer {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #e4ebf4;
            padding-top: 6px;
        }

        .journal-detail-common-item {
            min-width: 0;
        }

        .journal-detail-common-value {
            margin-top: 3px;
            min-height: 22px;
            font-weight: 700;
            color: #23354d;
            overflow-wrap: anywhere;
        }

        .journal-detail-common-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 12px;
            align-items: center;
        }

        .journal-detail-common-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .journal-detail-common-input {
            margin-top: 3px;
            width: 100%;
            min-height: 30px;
            font-weight: 700;
        }

        .journal-detail-table {
            min-width: 0;
            width: max-content;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #dbe5f1;
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
        }

        .journal-detail-table th,
        .journal-detail-table td {
            min-width: 0;
            padding: 4px;
            border-right: 1px solid #e4ebf4;
            border-bottom: 1px solid #e4ebf4;
            vertical-align: middle;
        }

        .journal-detail-table .journal-entries-edit-input {
            min-width: 0;
        }

        .journal-detail-table th {
            background: #eef4fb;
            color: #46638a;
            font-weight: 700;
            font-size: 11px;
            text-align: left;
        }

        .journal-detail-table th:last-child,
        .journal-detail-table td:last-child {
            border-right: 0;
        }

        .journal-detail-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .journal-detail-table .journal-entries-edit-input {
            min-height: 44px;
            white-space: normal;
        }

        .journal-detail-stack {
            display: grid;
            gap: 3px;
        }

        .journal-detail-stack .journal-entries-edit-input {
            min-height: 24px;
        }

        .journal-detail-row-actions {
            display: flex;
            gap: 4px;
            align-items: center;
            justify-content: center;
        }

        .journal-detail-icon-button {
            width: 24px;
            height: 24px;
            min-width: 24px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            font-weight: 700;
        }

        .journal-import-status {
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px solid #9fc5f8;
            border-radius: 6px;
            background: #eaf3ff;
            color: #1558a8;
            font-weight: 700;
        }

        @media (max-width: 1080px) {
            .journal-entries-filter-row {
                grid-template-columns: 1fr;
            }

            .journal-group-summary,
            .journal-detail-grid {
                grid-template-columns: 1fr;
            }

            .journal-entries-filter-actions {
                justify-content: flex-start;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        @include('shared.status_message')
        <div class="top">
            <h1 class="title">TCPG SYSTEM 仕訳帳</h1>
        </div>

        <section class="panel journal-entries-panel admin-viewport-panel ">

            <form id="journal-entries-import-form" method="post" action="{{ route('admin.work.journal_entries.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="journal-import-date-from" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" id="journal-import-date-to" name="date_to" value="{{ $dateTo }}">
                <input type="hidden" id="journal-import-company-name-short" name="company_name_short" value="{{ $selectedCompanyName }}">
                <input type="file" id="journal-import-csv-file" name="csv_file" accept=".csv,text/csv" hidden>
            </form>
            <div class="journal-import-status" id="journal-import-status" hidden>仕訳データを取込中です。画面を閉じずにお待ちください。</div>

            <form method="get" action="{{ route('admin.work.journal_entries') }}" id="journal-entries-filter-form">
                <div class="journal-entries-filter-row">
                    <div class="journal-entries-filter-group journal-entries-filter-group-wide">
                        <label class="journal-entries-filter-label">期間</label>
                        <div class="journal-entries-date-range">
                            <button type="button" class="btn_small" id="journal-period-prev" title="1ヶ月前へ">◀</button>
                            <input type="date" name="date_from" value="{{ $dateFrom }}">
                            <span>〜</span>
                            <input type="date" name="date_to" value="{{ $dateTo }}">
                            <button type="button" class="btn_small" id="journal-period-next" title="1ヶ月先へ">▶</button>
                        </div>
                    </div>

                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-company-name-short">会社選択</label>
                        <input type="text" id="journal-company-name-short" name="company_name_short" list="journal-company-options" value="{{ $selectedCompanyName }}">
                    </div>

                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-counterparty">取引先検索</label>
                        <input type="text" id="journal-counterparty" name="counterparty" list="journal-counterparty-options" value="{{ $counterparty }}">
                    </div>

                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-amount">金額検索</label>
                        <input type="text" id="journal-amount" name="amount" list="journal-amount-options" value="{{ $amount }}">
                    </div>
                    <!-- </div>

                <div class="journal-entries-filter-row"> -->


                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-account-title">勘定科目</label>
                        <input type="text" id="journal-account-title" name="account_title" list="journal-account-title-options" value="{{ $accountTitle }}">
                    </div>

                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-item-name">品目</label>
                        <input type="text" id="journal-item-name" name="item_name" list="journal-item-name-options" value="{{ $itemName }}">
                    </div>

                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-department-name">部門</label>
                        <input type="text" id="journal-department-name" name="department_name" list="journal-department-options" value="{{ $departmentName }}">
                    </div>

                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-vault-name">金庫</label>
                        <input type="text" id="journal-vault-name" name="vault_name" list="journal-vault-name-options" value="{{ $vaultName }}">
                    </div>
                    <!-- </div>

                <div class="journal-entries-filter-row"> -->
                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-management-number">管理番号</label>
                        <input type="text" id="journal-management-number" name="management_number" list="journal-management-number-options" value="{{ $managementNumber }}">
                    </div>

                    <div class="journal-entries-filter-group">
                        <label class="journal-entries-filter-label" for="journal-breakdown">仕訳内訳</label>
                        <input type="text" id="journal-breakdown" name="journal_breakdown" list="journal-breakdown-options" value="{{ $journalBreakdown }}">
                    </div>
                    <div class="journal-entries-filter-group journal-entries-filter-group-wide">
                        <label class="journal-entries-filter-label" for="journal-summary-text">摘要検索</label>
                        <input type="text" id="journal-summary-text" name="summary_text" list="journal-summary-text-options" value="{{ $summaryText }}">
                    </div>
                    <div class="journal-entries-filter-actions">
                        <label class="journal-entries-exclude-toggle">
                            <input type="checkbox" name="exclude_mode" value="1" @checked($excludeMode)>
                            除外モード
                        </label>
                        <button type="submit" class="btn btn-primary">表示</button>
                        {{-- 要確認：期間・会社選択だけ残す部分クリアだと「期間を今月に戻したい」時に
                        結局手で両方打ち直す必要があり紛らわしいという指摘のため、期間・会社を含む
                        全項目をデフォルト（当月・全社）に戻す全クリアに変更（2026-08-18）。 --}}
                        <a class="btn" href="{{ route('admin.work.journal_entries') }}">全クリア</a>
                        <a class="btn" href="{{ route('admin.work.journal_entries.sakura_reward_print', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" target="_blank" rel="noopener">報酬計算印刷</a>
                        <button type="button" class="btn btn-primary" id="journal-import-select-btn">取込</button>
                    </div>
                </div>

                <datalist id="journal-company-options">
                    @foreach ($companyOptions as $companyOption)
                    <option value="{{ $companyOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-counterparty-options">
                    @foreach ($counterpartyOptions as $counterpartyOption)
                    <option value="{{ $counterpartyOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-amount-options">
                    @foreach ($amountOptions as $amountOption)
                    <option value="{{ $amountOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-summary-text-options">
                    @foreach ($summaryTextOptions as $summaryTextOption)
                    <option value="{{ $summaryTextOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-account-title-options">
                    @foreach ($accountTitleOptions as $accountTitleOption)
                    <option value="{{ $accountTitleOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-item-name-options">
                    @foreach ($itemNameOptions as $itemNameOption)
                    <option value="{{ $itemNameOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-department-options">
                    {{-- 「空白」を選ぶと部門未入力の明細だけを拾う特別扱いになる（2026-09-14、
                    専用チェックボックスの代わりにこの欄の中で完結させる）。 --}}
                    <option value="{{ $blankFilterSentinel }}"></option>
                    @foreach ($departmentOptions as $departmentOption)
                    <option value="{{ $departmentOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-vault-name-options">
                    @foreach ($vaultNameOptions as $vaultNameOption)
                    <option value="{{ $vaultNameOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-management-number-options">
                    @foreach ($managementNumberOptions as $managementNumberOption)
                    <option value="{{ $managementNumberOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="journal-breakdown-options">
                    @foreach ($journalBreakdownOptions as $journalBreakdownOption)
                    <option value="{{ $journalBreakdownOption }}"></option>
                    @endforeach
                </datalist>
            </form>

            <div class="journal-entries-meta">
                <span class="meta-count">{{ count($journalGroups) }}件 / 明細 {{ count($rows) }}件</span>
                {{-- 2026-09-14追加：グループ折りたたみ/フラット(明細1行=1行)の切り替え。
                Accessでは全行フラットに並べて部門欄等を目視確認できていたが、今のグループ
                折りたたみではそれができないという相談から。現在のフィルタ条件は
                そのまま維持する（displayだけ差し替え）。 --}}
                <div class="journal-entries-display-toggle margin_t20 margin_b10">
                    <a class="btn btn-small {{ $displayMode === 'group' ? 'btn-primary' : '' }}" href="{{ request()->fullUrlWithQuery(['display' => 'group']) }}">グループ表示</a>
                    <a class="btn btn-small {{ $displayMode === 'flat' ? 'btn-primary' : '' }}" href="{{ request()->fullUrlWithQuery(['display' => 'flat']) }}">フラット表示</a>
                </div>
            </div>

            @php
            $journalEntryFilterFields = [
            'filter_date_from' => $dateFrom,
            'filter_date_to' => $dateTo,
            'filter_company_name_short' => $selectedCompanyName,
            'filter_counterparty' => $counterparty,
            'filter_amount' => $amount,
            'filter_summary_text' => $summaryText,
            'filter_account_title' => $accountTitle,
            'filter_item_name' => $itemName,
            'filter_department_name' => $departmentName,
            'filter_vault_name' => $vaultName,
            'filter_exclude_mode' => $excludeMode ? '1' : '0',
            'filter_management_number' => $managementNumber,
            'filter_journal_breakdown' => $journalBreakdown,
            ];
            @endphp

            <div class="journal-entries-table-wrap">
                @if ($displayMode === 'flat')
                <table class="data-table journal-entries-table f_size12">
                    <colgroup>
                        <col style="width: 60px;">
                        <col style="width: 90px;">
                        <col style="width: 90px;">
                        <col style="width: 60px;">
                        <col style="width: 50px;">
                        <col style="width: 90px;">
                        <col style="width: 60px;">
                        <col style="width: 50px;">
                        <col style="width: 180px;">
                        <col style="width: 60px;">
                        <col style="width: 30px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>発生日</th>
                            <th>管理番号</th>
                            <th>借方科目</th>
                            <th>借方部門</th>
                            <th class="num">借方金額</th>
                            <th>貸方科目</th>
                            <th>貸方部門</th>
                            <th class="num">貸方金額</th>
                            <th>摘要</th>
                            <th>取引先</th>
                            <th>編集</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        @php
                        // グループ表示側の group_key と同じ計算式（2026-09-14、フラットで見つけた
                        // 仕訳をワンクリックでグループ表示側の該当箇所へ飛べるようにする）。
                        $rowGroupKey = md5(($row['company_name_short'] ?? '') . '|' . ($row['occurred_at'] ?? '') . '|' . ($row['journal_breakdown'] ?? ''));
                        $rowGroupPage = $groupPageByKey[$rowGroupKey] ?? 1;
                        @endphp
                        <tr>
                            <td>{{ $row['occurred_at'] }}</td>
                            <td>{{ $row['management_number'] }}</td>
                            <td>{{ $row['debit_account_title'] }}</td>
                            <td>{{ $departmentLabelMap[$row['debit_department_name']] ?? $row['debit_department_name'] }}</td>
                            <td class="num">{{ $row['debit_amount'] }}</td>
                            <td>{{ $row['credit_account_title'] }}</td>
                            <td>{{ $departmentLabelMap[$row['credit_department_name']] ?? $row['credit_department_name'] }}</td>
                            <td class="num">{{ $row['credit_amount'] }}</td>
                            <td>{{ $row['summary_text'] }}</td>
                            <td>{{ $row['debit_counterparty'] ?: $row['credit_counterparty'] }}</td>
                            <td><a class="btn btn-small" href="{{ request()->fullUrlWithQuery(['display' => 'group', 'page' => $rowGroupPage]) }}#journal-group-{{ $rowGroupKey }}">編集</a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="empty">表示できる仕訳データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @else
                <div class="journal-group-list">
                    @forelse ($journalGroupsPaged as $group)
                    <details class="journal-group" id="journal-group-{{ $group['group_key'] }}">
                        <summary class="journal-group-summary">
                            <div>
                                <div class="journal-group-sub">発生日</div>
                                <div class="journal-group-main">{{ $group['occurred_at'] }}</div>
                            </div>
                            <div>
                                <div class="journal-group-sub">仕訳内訳</div>
                                <div class="journal-group-main">{{ $group['journal_breakdown'] }}</div>
                            </div>
                            <div>
                                <div class="journal-group-sub">明細</div>
                                <div>{{ $group['detail_count'] }}件</div>
                            </div>
                            <div class="num">
                                <div class="journal-group-sub">借方合計</div>
                                <div class="journal-group-main">{{ $group['debit_total'] }}</div>
                            </div>
                            <div class="num">
                                <div class="journal-group-sub">貸方合計</div>
                                <div class="journal-group-main">{{ $group['credit_total'] }}</div>
                            </div>
                            <div>
                                <div class="journal-group-sub">摘要</div>
                                <div>{{ $group['summary_text'] }}</div>
                            </div>
                            <div>
                                <div class="journal-group-sub">社名</div>
                                <div>{{ $group['company_name_short'] }}</div>
                            </div>
                            <div>
                                <div class="journal-group-sub">明細</div>
                                <div>{{ $group['detail_count'] }}件</div>
                            </div>
                        </summary>
                        <div class="journal-group-details">
                            @php
                            $firstDetail = $group['details'][0] ?? null;
                            @endphp
                            @if ($firstDetail)
                            @php
                            $journalGroupFormId = 'journal-entry-group-form-' . $group['group_key'];
                            @endphp
                            <form id="{{ $journalGroupFormId }}" method="post" action="{{ route('admin.work.journal_entries.group_update') }}">
                                @csrf
                                @foreach ($journalEntryFilterFields as $filterName => $filterValue)
                                <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                                @endforeach
                            </form>
                            @php
                            $journalGroupDeleteFormId = 'journal-entry-group-delete-form-' . $group['group_key'];
                            @endphp
                            <form id="{{ $journalGroupDeleteFormId }}" method="post" action="{{ route('admin.work.journal_entries.delete_group') }}">
                                @csrf
                                @foreach ($journalEntryFilterFields as $filterName => $filterValue)
                                <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                                @endforeach
                                @foreach ($group['details'] as $detailRow)
                                <input type="hidden" name="journal_entry_ids[]" value="{{ $detailRow['journal_entry_id'] }}">
                                @endforeach
                            </form>
                            <div class="journal-detail-common">
                                <div class="journal-detail-common-row">
                                    <div class="journal-detail-common-item">
                                        <div class="journal-group-sub">発生日</div>
                                        <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input journal-detail-common-input" type="date" name="common[occurred_at]" value="{{ $firstDetail['occurred_at_raw'] ?? '' }}">
                                    </div>
                                    <div class="journal-detail-common-item">
                                        <div class="journal-group-sub">仕訳内訳</div>
                                        <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input journal-detail-common-input" type="text" name="common[journal_breakdown]" value="{{ $firstDetail['journal_breakdown'] }}">
                                    </div>
                                    <div class="journal-detail-common-item">
                                        <div class="journal-group-sub">管理番号</div>
                                        <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input journal-detail-common-input" type="text" name="common[management_number]" value="{{ $firstDetail['management_number'] }}">
                                    </div>
                                    <div class="journal-detail-common-item">
                                        <div class="journal-group-sub">金庫</div>
                                        <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input journal-detail-common-input" type="text" name="common[vault_name]" value="{{ $firstDetail['vault_name'] }}">
                                    </div>
                                    <div class="journal-detail-common-item">
                                        <div class="journal-group-sub">社名</div>
                                        <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input journal-detail-common-input" type="text" name="common[company_name_short]" value="{{ $firstDetail['company_name_short'] }}">
                                    </div>
                                    <div class="journal-detail-common-item">
                                        <div class="journal-group-sub">取引先</div>
                                        <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input journal-detail-common-input" type="text" name="common[counterparty]" value="{{ $firstDetail['debit_counterparty'] ?: $firstDetail['credit_counterparty'] }}">
                                    </div>
                                </div>
                                <div class="journal-detail-common-row journal-detail-common-row-wide">
                                    <div class="journal-detail-common-item">
                                        <div class="journal-group-sub">摘要</div>
                                        <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input journal-detail-common-input" type="text" name="common[summary_text]" value="{{ $firstDetail['summary_text'] }}">
                                    </div>
                                </div>
                                <div class="journal-detail-common-footer">
                                    <div class="journal-detail-common-checks">
                                        <label><input form="{{ $journalGroupFormId }}" type="checkbox" name="common[is_trial_balance_excluded]" value="1" @checked($firstDetail['is_trial_balance_excluded'])> 試算表除外</label>
                                        <label><input form="{{ $journalGroupFormId }}" type="checkbox" name="common[is_upload_unnecessary]" value="1" @checked($firstDetail['is_upload_unnecessary'])> UP不要</label>
                                        <label><input form="{{ $journalGroupFormId }}" type="checkbox" name="common[is_reward_excluded]" value="1" @checked($firstDetail['is_reward_excluded'])> 報酬計算除外</label>
                                    </div>
                                    @php
                                    $journalGroupDetailCount = $group['detail_count'];
                                    @endphp
                                    <div class="journal-detail-common-actions">
                                        <button form="{{ $journalGroupFormId }}" type="submit" class="btn_small btn-primary">保存</button>
                                        <button form="{{ $journalGroupDeleteFormId }}" type="submit" class="btn_small" onclick="return confirm('この仕訳グループ（{{ $journalGroupDetailCount }}件）をまとめて削除しますか？');">グループ削除</button>
                                    </div>
                                </div>
                            </div> @endif

                            <table class="journal-detail-table">
                                <colgroup>
                                    <col style="width: 30px;">
                                    <col style="width: 100px;">
                                    <col style="width: 80px;">
                                    <col style="width: 190px;">
                                    <col style="width: 100px;">
                                    <col style="width: 100px;">
                                    <col style="width: 80px;">
                                    <col style="width: 190px;">
                                    <col style="width: 100px;">
                                    <col style="width: 80px;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>割合</th>
                                        <th>借方科目</th>
                                        <th>借方金額</th>
                                        <th>借方 品目/メモ</th>
                                        <th>借方部門</th>
                                        <th>貸方科目</th>
                                        <th>貸方金額</th>
                                        <th>貸方 品目/メモ</th>
                                        <th>貸方部門</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['details'] as $row)
                                    @php
                                    $journalEntryDeleteFormId = 'journal-entry-delete-form-' . $row['journal_entry_id'];
                                    @endphp
                                    <tr>
                                        <td>
                                            <form id="{{ $journalEntryDeleteFormId }}" method="post" action="{{ url('/admin/work/journal-entries/delete') }}">
                                                @csrf
                                                <input type="hidden" name="journal_entry_id" value="{{ $row['journal_entry_id'] }}">
                                                @foreach ($journalEntryFilterFields as $filterName => $filterValue)
                                                <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                                                @endforeach
                                                <input type="hidden" name="occurred_at" value="{{ $row['occurred_at_raw'] ?? '' }}">
                                                <input type="hidden" name="journal_breakdown" value="{{ $row['journal_breakdown'] }}">
                                                <input type="hidden" name="management_number" value="{{ $row['management_number'] }}">
                                                <input type="hidden" name="vault_name" value="{{ $row['vault_name'] }}">
                                                <input type="hidden" name="company_name_short" value="{{ $row['company_name_short'] }}">
                                                <input type="hidden" name="summary_text" value="{{ $row['summary_text'] }}">
                                                <input type="hidden" name="debit_counterparty" value="{{ $firstDetail['debit_counterparty'] ?: $firstDetail['credit_counterparty'] }}">
                                                <input type="hidden" name="credit_counterparty" value="{{ $firstDetail['debit_counterparty'] ?: $firstDetail['credit_counterparty'] }}">
                                                <input type="hidden" name="is_trial_balance_excluded" value="{{ $row['is_trial_balance_excluded'] ? 1 : 0 }}">
                                                <input type="hidden" name="is_upload_unnecessary" value="{{ $row['is_upload_unnecessary'] ? 1 : 0 }}">
                                                <input type="hidden" name="is_reward_excluded" value="{{ $row['is_reward_excluded'] ? 1 : 0 }}">
                                            </form>
                                            <input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][allocation_ratio]" value="{{ $row['allocation_ratio'] }}">
                                        </td>
                                        <td><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][debit_account_title]" value="{{ $row['debit_account_title'] }}"></td>
                                        <td><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][debit_amount]" value="{{ $row['debit_amount'] }}"></td>
                                        <td>
                                            <div class="journal-detail-stack"><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][debit_item_name]" value="{{ $row['debit_item_name'] }}"><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][debit_memo_tag]" value="{{ $row['debit_memo_tag'] }}"></div>
                                        </td>
                                        <td>
                                            <select form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" name="rows[{{ $row['journal_entry_id'] }}][debit_department_name]">
                                                <option value=""></option>
                                                @foreach ($departmentSelectOptions as $departmentOption)
                                                <option value="{{ $departmentOption['value'] }}" @selected($row['debit_department_name']===$departmentOption['value'])>{{ $departmentOption['label'] }}</option>
                                                @endforeach
                                                @if ($row['debit_department_name'] !== '' && !collect($departmentSelectOptions)->contains('value', $row['debit_department_name']))
                                                <option value="{{ $row['debit_department_name'] }}" selected>{{ $row['debit_department_name'] }}</option>
                                                @endif
                                            </select>
                                        </td>
                                        <td><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][credit_account_title]" value="{{ $row['credit_account_title'] }}"></td>
                                        <td><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][credit_amount]" value="{{ $row['credit_amount'] }}"></td>
                                        <td>
                                            <div class="journal-detail-stack"><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][credit_item_name]" value="{{ $row['credit_item_name'] }}"><input form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" type="text" name="rows[{{ $row['journal_entry_id'] }}][credit_memo_tag]" value="{{ $row['credit_memo_tag'] }}"></div>
                                        </td>
                                        <td>
                                            <select form="{{ $journalGroupFormId }}" class="journal-entries-edit-input" name="rows[{{ $row['journal_entry_id'] }}][credit_department_name]">
                                                <option value=""></option>
                                                @foreach ($departmentSelectOptions as $departmentOption)
                                                <option value="{{ $departmentOption['value'] }}" @selected($row['credit_department_name']===$departmentOption['value'])>{{ $departmentOption['label'] }}</option>
                                                @endforeach
                                                @if ($row['credit_department_name'] !== '' && !collect($departmentSelectOptions)->contains('value', $row['credit_department_name']))
                                                <option value="{{ $row['credit_department_name'] }}" selected>{{ $row['credit_department_name'] }}</option>
                                                @endif
                                            </select>
                                        </td>
                                        <td>
                                            <div class="journal-detail-row-actions">
                                                <button type="button" class="btn_small journal-detail-icon-button" title="追加（準備中）" disabled>+</button>
                                                <button form="{{ $journalEntryDeleteFormId }}" type="submit" class="btn_small journal-detail-icon-button" onclick="return confirm('削除しますか？');" title="削除">-</button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                    @empty
                    <div class="empty">表示できる仕訳データがありません。</div>
                    @endforelse
                </div>
                @endif
            </div>

            @if ($displayMode === 'group' && $journalGroupsLastPage > 1)
            @php
            $journalListParams = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'company_name_short' => $selectedCompanyName,
            'counterparty' => $counterparty,
            'amount' => $amount,
            'summary_text' => $summaryText,
            'account_title' => $accountTitle,
            'item_name' => $itemName,
            'department_name' => $departmentName,
            'vault_name' => $vaultName,
            'exclude_mode' => $excludeMode ? '1' : '0',
            'management_number' => $managementNumber,
            'journal_breakdown' => $journalBreakdown,
            'display' => $displayMode,
            ];
            @endphp
            <div class="journal-entries-pager">
                <a href="{{ route('admin.work.journal_entries', $journalListParams + ['page' => max(1, $journalGroupsPage - 1)]) }}"
                    class="btn {{ $journalGroupsPage <= 1 ? 'is-disabled' : '' }}">前へ</a>
                <span class="journal-entries-pager-label">{{ $journalGroupsPage }} / {{ $journalGroupsLastPage }}</span>
                <a href="{{ route('admin.work.journal_entries', $journalListParams + ['page' => min($journalGroupsLastPage, $journalGroupsPage + 1)]) }}"
                    class="btn {{ $journalGroupsPage >= $journalGroupsLastPage ? 'is-disabled' : '' }}">次へ</a>
            </div>
            @endif

            <div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn_back">戻る</a>
            </div>

            @if (!empty($journalImportedThrough))
            <p class="f_size12">
                仕訳取込済み：
                @foreach ($journalImportedThrough as $imported)
                {{ $imported['company_name_short'] }} {{ $imported['latest_occurred_at'] }}まで@if (!$loop->last)　/@endif
                @endforeach
            </p>
            @endif
        </section>
    </div>
    <script>
        // フラット表示の「編集」リンクから飛んできた時、対象のグループを開いた状態で
        // スクロール表示する（2026-09-14）。近年のブラウザは<details>内のフラグメントへ
        // 遷移すると自動でopenになるが、確実にするため明示的にも開く。一覧自体が
        // overflow:autoの入れ子スクロールコンテナのため、native anchor scrollが
        // 効かないことがあり、scrollIntoViewも明示的に呼ぶ。
        (function() {
            if (!location.hash || location.hash.indexOf('#journal-group-') !== 0) return;
            var target = document.querySelector(location.hash);
            if (!target) return;
            target.open = true;
            target.scrollIntoView({
                block: 'center'
            });
        })();

        (function() {
            var form = document.getElementById('journal-entries-import-form');
            var fileInput = document.getElementById('journal-import-csv-file');
            var selectButton = document.getElementById('journal-import-select-btn');
            var statusBox = document.getElementById('journal-import-status');

            function syncImportFields() {
                var filterForm = document.getElementById('journal-entries-filter-form');
                var dateFrom = filterForm ? filterForm.querySelector('input[name="date_from"]') : null;
                var dateTo = filterForm ? filterForm.querySelector('input[name="date_to"]') : null;
                var company = document.getElementById('journal-company-name-short');

                document.getElementById('journal-import-date-from').value = dateFrom ? dateFrom.value : '';
                document.getElementById('journal-import-date-to').value = dateTo ? dateTo.value : '';
                document.getElementById('journal-import-company-name-short').value = company ? company.value : '';
            }

            function formatDateLabel(value) {
                if (!value) {
                    return '';
                }

                var parts = value.split('-');
                if (parts.length < 2) {
                    return value;
                }

                return parts[0] + '/' + parts[1] + (parts[2] ? '/' + parts[2] : '');
            }

            function buildTargetLabel() {
                var dateFrom = document.getElementById('journal-import-date-from').value;
                var dateTo = document.getElementById('journal-import-date-to').value;

                if (dateFrom && dateTo && dateFrom !== dateTo) {
                    return formatDateLabel(dateFrom) + '〜' + formatDateLabel(dateTo);
                }

                return formatDateLabel(dateFrom || dateTo);
            }

            function showImporting() {
                if (selectButton) {
                    selectButton.disabled = true;
                    selectButton.textContent = '取込中...';
                }

                if (statusBox) {
                    statusBox.hidden = false;
                }
            }
            if (selectButton && fileInput) {
                selectButton.addEventListener('click', function() {
                    syncImportFields();
                    fileInput.value = '';
                    fileInput.click();
                });
            }

            function shiftMonth(dateStr, delta) {
                var parts = dateStr.split('-');
                if (parts.length !== 3) {
                    return dateStr;
                }

                var year = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10) - 1;
                var day = parseInt(parts[2], 10);

                // 2/28（月末）から前月に戻ると単純なmin()では1/28になってしまう。
                // 月末の日付は移動先の月の月末に合わせることで、月初〜月末の期間指定が
                // 前後の月でもずれないようにする。
                var lastDayOfCurrentMonth = new Date(year, month + 1, 0).getDate();
                var wasLastDayOfMonth = day === lastDayOfCurrentMonth;

                var targetMonthIndex = month + delta;
                var targetYear = year + Math.floor(targetMonthIndex / 12);
                var targetMonth = ((targetMonthIndex % 12) + 12) % 12;
                var lastDayOfTargetMonth = new Date(targetYear, targetMonth + 1, 0).getDate();
                var targetDay = wasLastDayOfMonth ? lastDayOfTargetMonth : Math.min(day, lastDayOfTargetMonth);

                var mm = String(targetMonth + 1).padStart(2, '0');
                var dd = String(targetDay).padStart(2, '0');

                return targetYear + '-' + mm + '-' + dd;
            }

            function shiftPeriod(delta) {
                var filterForm = document.getElementById('journal-entries-filter-form');
                if (!filterForm) {
                    return;
                }

                var dateFromInput = filterForm.querySelector('input[name="date_from"]');
                var dateToInput = filterForm.querySelector('input[name="date_to"]');
                if (!dateFromInput || !dateFromInput.value) {
                    return;
                }

                dateFromInput.value = shiftMonth(dateFromInput.value, delta);
                if (dateToInput && dateToInput.value) {
                    dateToInput.value = shiftMonth(dateToInput.value, delta);
                }
            }

            var periodPrevButton = document.getElementById('journal-period-prev');
            var periodNextButton = document.getElementById('journal-period-next');
            if (periodPrevButton) {
                periodPrevButton.addEventListener('click', function() {
                    shiftPeriod(-1);
                });
            }
            if (periodNextButton) {
                periodNextButton.addEventListener('click', function() {
                    shiftPeriod(1);
                });
            }

            if (fileInput && form) {
                fileInput.addEventListener('change', function() {
                    if (!fileInput.files || fileInput.files.length === 0) {
                        return;
                    }

                    syncImportFields();

                    var targetLabel = buildTargetLabel();
                    var companyName = document.getElementById('journal-import-company-name-short').value;
                    var message = (targetLabel ? targetLabel + '分の' : '選択中の') + '仕訳データを取込します。';

                    if (companyName) {
                        message += '\n会社: ' + companyName;
                    }

                    message += '\nよろしいですか？';

                    if (!window.confirm(message)) {
                        fileInput.value = '';
                        return;
                    }

                    showImporting();
                    form.submit();
                });
            }
        })();
    </script>
</body>

</html>
