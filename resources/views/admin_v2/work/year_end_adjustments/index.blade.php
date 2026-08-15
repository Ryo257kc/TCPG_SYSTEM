<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 年末調整管理</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <style>
        .year-end-summary {
            display: grid;
            grid-template-columns: repeat(8, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .year-end-summary-item {
            border: 1px solid #d3dff0;
            border-radius: 8px;
            background: #fff;
            padding: 10px;
            text-align: center;
        }

        .year-end-summary-label {
            color: #667085;
            font-size: 12px;
            font-weight: 700;
        }

        .year-end-summary-value {
            color: #1f4f8f;
            font-size: 22px;
            font-weight: 800;
        }

        .year-end-filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 20px;
            padding: 14px 16px;
            margin-bottom: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .year-end-filter-divider {
            align-self: stretch;
            width: 1px;
            background: #e2e8f0;
        }

        .year-end-toolbar {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            margin: 0;
        }

        .year-end-toolbar label {
            display: grid;
            gap: 4px;
            color: #44546f;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .year-end-filter-bar select,
        .year-end-filter-bar input[type="datetime-local"] {
            min-width: 140px;
            height: 34px;
            padding: 4px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            background: #fff;
            box-sizing: border-box;
        }

        .year-end-filter-bar .btn {
            height: 34px;
            box-sizing: border-box;
        }

        .year-end-table-wrap {
            max-width: 100%;
            overflow: auto;
        }

        .year-end-table {
            min-width: 760px;
        }

        .year-end-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .year-end-status-form {
            display: flex;
            gap: 6px;
            align-items: center;
            margin: 0;
        }

        .year-end-status-form select {
            min-width: 92px;
        }

        .year-end-delete-form {
            margin: 0;
        }

        .year-end-status {
            display: inline-flex;
            min-width: 70px;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 8px;
            background: #eef4ff;
            color: #1f4f8f;
            font-size: 12px;
            font-weight: 800;
        }

        /* ステータスごとに色分け。差戻し(対応必要)を最も目立たせる。
           下書きは「未着手（対象者作成直後で何も保存していない）」と
           「作業中（どこかのセクションを保存済みだが未提出）」を色で区別する。
           似た薄い色が並ぶと判別しづらいとの指摘を受け、彩度をはっきり分けている。 */
        .year-end-status-draft-empty {
            background: #fff;
            border: 1px dashed #d0d5dd;
            color: #98a2b3;
        }

        .year-end-status-draft-started {
            background: #fef6e6;
            color: #b54708;
        }

        .year-end-status-submitted {
            background: #d1e3ff;
            color: #0b3a82;
        }

        .year-end-status-returned {
            background: #fee4e2;
            color: #b42318;
        }

        .year-end-status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .year-end-status-retired {
            background: #e4e4e7;
            color: #52525b;
        }

        .year-end-status-excluded {
            background: #ede9fe;
            color: #5b21b6;
        }

        .year-end-status-legacy-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .year-end-status-legacy-draft {
            background: #fff;
            border: 1px dashed #d0d5dd;
            color: #98a2b3;
        }

        @media (max-width: 1000px) {
            .year-end-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">年末調整管理</h1>
        </div>

        @if (session('status'))
        <div class="status">{{ session('status') }}</div>
        @endif

        @if (!$tableExists)
        <div class="error">mx_nen_tyo テーブルが見つかりません。</div>
        @endif

        <section class="panel">
            <div class="year-end-filter-bar">
                <form method="get" action="{{ route('admin.work.year_end_adjustments') }}" class="year-end-toolbar">
                    <label>
                        <span>対象年</span>
                        <select name="target_year">
                            @foreach ($yearOptions as $year)
                            <option value="{{ $year }}" @selected((int) $targetYear === (int) $year)>{{ $year }}年</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="btn btn-primary">表示</button>
                </form>

                <span class="year-end-filter-divider"></span>

                <form method="post" action="{{ route('admin.work.year_end_adjustments.create_targets') }}" class="year-end-toolbar" onsubmit="return confirm('{{ $targetYear }}年の対象者を作成します。作成済みのスタッフはスキップします。');">
                    @csrf
                    <input type="hidden" name="target_year" value="{{ $targetYear }}">
                    <button type="submit" class="btn btn-primary" @disabled(!$tableExists)>対象者作成</button>
                </form>

                <span class="year-end-filter-divider"></span>

                <form method="post" action="{{ route('admin.work.year_end_adjustments.publish_date') }}" class="year-end-toolbar">
                    @csrf
                    <input type="hidden" name="target_year" value="{{ $targetYear }}">
                    <label>
                        <span>{{ $targetYear }}年 スタッフ公開日時</span>
                        <input type="datetime-local" name="publish_date" value="{{ $publishDate }}">
                    </label>
                    <button type="submit" class="btn btn-primary">保存</button>
                </form>

                <span class="year-end-filter-divider"></span>

                <form method="get" action="{{ route('admin.work.year_end_adjustments.bulk_preview') }}" target="_blank" class="year-end-toolbar">
                    <input type="hidden" name="target_year" value="{{ $targetYear }}">
                    <label>
                        <span>会社</span>
                        <select name="company_id">
                            <option value="">全社</option>
                            @foreach (($companyOptions ?? []) as $company)
                            <option value="{{ $company['company_id'] }}">{{ $company['company_name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>帳票</span>
                        <select name="report">
                            @foreach (($bulkReportOptions ?? []) as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="btn btn-primary">全員分まとめて出力</button>
                </form>
            </div>

            <div class="year-end-summary">
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">全件</div>
                    <div class="year-end-summary-value">{{ count($rows) }}</div>
                </div>
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">未提出</div>
                    <div class="year-end-summary-value">{{ $statusCounts['draft'] }}</div>
                </div>
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">提出済</div>
                    <div class="year-end-summary-value">{{ $statusCounts['submitted'] }}</div>
                </div>
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">差戻し</div>
                    <div class="year-end-summary-value">{{ $statusCounts['returned'] }}</div>
                </div>
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">確認済</div>
                    <div class="year-end-summary-value">{{ $statusCounts['confirmed'] }}</div>
                </div>
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">対象外</div>
                    <div class="year-end-summary-value">{{ $statusCounts['excluded'] }}</div>
                </div>
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">退職済</div>
                    <div class="year-end-summary-value">{{ $statusCounts['retired'] }}</div>
                </div>
            </div>

            <div class="year-end-table-wrap">
                <table class="data-table year-end-table">
                    <thead>
                        <tr>
                            <th>状態</th>
                            <th>スタッフID</th>
                            <th>氏名</th>
                            <th>入社/退社</th>
                            <th>提出日時</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        <tr>
                            <td><span class="year-end-status year-end-status-{{ $row['status_badge_key'] }}">{{ $row['status_label'] }}</span></td>
                            <td>{{ $row['staff_id'] }}</td>
                            <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '---' }}</td>
                            <td>
                                <div @if($row['nyu_date_in_target_year']) style="color:#d32f2f;font-weight:bold;" @endif>入 {{ $row['nyu_date'] !== '' ? $row['nyu_date'] : '---' }}</div>
                                <div @if($row['tai_date_in_target_year']) style="color:#d32f2f;font-weight:bold;" @endif>退 {{ $row['tai_date'] !== '' ? $row['tai_date'] : '---' }}</div>
                            </td>
                            <td>{{ $row['submitted_at'] }}</td>
                            <td>
                                @if ($row['application_id'] !== '')
                                <div class="year-end-actions">
                                    <a href="{{ route('admin.work.year_end_adjustments.show', ['applicationId' => $row['application_id']]) }}" class="btn btn-outline">詳細</a>
                                    <form method="post" action="{{ route('admin.work.year_end_adjustments.update_status') }}" class="year-end-status-form">
                                        @csrf
                                        <input type="hidden" name="application_id" value="{{ $row['application_id'] }}">
                                        <input type="hidden" name="target_year" value="{{ $targetYear }}">
                                        <select name="status">
                                            @foreach ($statusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected($row['status'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-outline">保存</button>
                                    </form>
                                    @if ($row['can_delete'])
                                    <form method="post" action="{{ route('admin.work.year_end_adjustments.delete_target') }}" class="year-end-delete-form" onsubmit="return confirm('{{ $row['staff_name'] !== '' ? $row['staff_name'] : $row['staff_id'] }} を年調対象者から削除します。よろしいですか？');">
                                        @csrf
                                        <input type="hidden" name="application_id" value="{{ $row['application_id'] }}">
                                        <input type="hidden" name="target_year" value="{{ $targetYear }}">
                                        <button type="submit" class="btn btn-outline">削除</button>
                                    </form>
                                    @endif
                                </div>
                                @else
                                <span class="year-end-legacy-note">申請ワークフロー対象外（旧年調）</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="empty">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>

</html>