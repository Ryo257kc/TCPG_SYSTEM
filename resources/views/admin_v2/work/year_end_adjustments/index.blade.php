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

        .year-end-toolbar {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .year-end-toolbar label {
            display: grid;
            gap: 4px;
            color: #44546f;
            font-size: 12px;
            font-weight: 700;
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
        <div class="error">staff_year_end_applications テーブルが見つかりません。</div>
        @endif

        <section class="panel">
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

            <form method="post" action="{{ route('admin.work.year_end_adjustments.create_targets') }}" class="year-end-toolbar" onsubmit="return confirm('{{ $targetYear }}年の対象者を作成します。作成済みのスタッフはスキップします。');">
                @csrf
                <input type="hidden" name="target_year" value="{{ $targetYear }}">
                <button type="submit" class="btn btn-primary" @disabled(!$tableExists)>対象者作成</button>
            </form>

            <div class="year-end-summary">
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">全件</div>
                    <div class="year-end-summary-value">{{ count($rows) }}</div>
                </div>
                <div class="year-end-summary-item">
                    <div class="year-end-summary-label">下書き</div>
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
                    <div class="year-end-summary-label">反映済</div>
                    <div class="year-end-summary-value">{{ $statusCounts['reflected'] }}</div>
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
                            <th>年調</th>
                            <th>提出日時</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        <tr>
                            <td><span class="year-end-status">{{ $row['status_label'] }}</span></td>
                            <td>{{ $row['staff_id'] }}</td>
                            <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '---' }}</td>
                            <td>
                                <div>入 {{ $row['nyu_date'] !== '' ? $row['nyu_date'] : '---' }}</div>
                                <div>退 {{ $row['tai_date'] !== '' ? $row['tai_date'] : '---' }}</div>
                            </td>
                            <td>{{ $row['year_end_adjustment'] }}</td>
                            <td>{{ $row['submitted_at'] }}</td>
                            <td>
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