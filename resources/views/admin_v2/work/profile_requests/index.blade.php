<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 個人情報変更申請管理</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <style>
        .pr-status-badge {
            display: inline-flex;
            min-width: 70px;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 800;
        }

        .pr-status-draft {
            background: #fff;
            border: 1px dashed #d0d5dd;
            color: #98a2b3;
        }

        .pr-status-submitted {
            background: #d1e3ff;
            color: #0b3a82;
        }

        .pr-status-returned {
            background: #fee4e2;
            color: #b42318;
        }

        .pr-status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .pr-status-reflected {
            background: #bbf7d0;
            color: #14532d;
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">個人情報変更申請管理</h1>
        </div>

        @if (session('status'))
        <div class="status">{{ session('status') }}</div>
        @endif

        <section class="panel">
            <form method="get" action="{{ route('admin.work.profile_requests') }}" class="year-end-toolbar">
                <label>
                    <span>状態</span>
                    <select name="status">
                        <option value="">すべて</option>
                        @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="btn btn-primary">表示</button>
            </form>

            <div class="year-end-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>状態</th>
                            <th>スタッフID</th>
                            <th>氏名</th>
                            <th>提出日時</th>
                            <th>確認日時</th>
                            <th>反映日時</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        <tr>
                            <td><span class="pr-status-badge pr-status-{{ $row['status'] }}">{{ $row['status_label'] }}</span></td>
                            <td>{{ $row['staff_id'] }}</td>
                            <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '---' }}</td>
                            <td>{{ $row['submitted_at'] !== '' ? $row['submitted_at'] : '---' }}</td>
                            <td>{{ $row['confirmed_at'] !== '' ? $row['confirmed_at'] : '---' }}</td>
                            <td>{{ $row['reflected_at'] !== '' ? $row['reflected_at'] : '---' }}</td>
                            <td>
                                <a href="{{ route('admin.work.profile_requests.show', ['requestId' => $row['request_id']]) }}" class="btn btn-outline">詳細</a>
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
