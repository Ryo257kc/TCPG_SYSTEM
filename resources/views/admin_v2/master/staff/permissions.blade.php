<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - スタッフ権限一覧</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">

    <style>
        body {
            font-size: 14px;
        }

        .filter-form {
            margin-top: 0;
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .permissions-table-wrap {
            margin-top: 14px;
            overflow-x: auto;
        }

        .permissions-table {
            border-collapse: collapse;
            width: 100%;
            white-space: nowrap;
        }

        .permissions-table th,
        .permissions-table td {
            border: 1px solid #d3dff0;
            padding: 6px 8px;
            text-align: center;
        }

        .permissions-table thead th {
            background: #f5f8fd;
            color: #123c73;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            height: 90px;
            padding: 6px 4px;
        }

        .permissions-table thead th.col-staff {
            writing-mode: horizontal-tb;
            height: auto;
            text-align: left;
        }

        .permissions-table td.col-staff {
            text-align: left;
        }

        #permissions-body tr:hover {
            background: #f0f6ff;
        }

        #permissions-body tr.row-selected {
            background: #d9ebff;
        }

        .permissions-actions {
            margin-top: 14px;
            position: sticky;
            bottom: 0;
            background: #fff;
            padding: 10px 0;
        }

        .permissions-legend {
            margin-top: 28px;
        }

        .permissions-legend .permissions-table {
            white-space: normal;
        }

        .permissions-legend .permissions-table td.col-staff:first-child {
            width: 140px;
            font-weight: bold;
            color: #123c73;
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')
    <div class="wrap">
        <div class="top">
            <div class="title">TCPG SYSTEM スタッフ権限一覧</div>
        </div>
        <section class="panel">
            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            <form method="get" class="filter-form">
                <input type="text" name="q" value="{{ $keyword }}" placeholder="ID / 氏名 / 店舗名で検索">
                <select name="employment_filter">
                    <option value="active" {{ $employmentFilter === 'active' ? 'selected' : '' }}>在籍</option>
                    <option value="all" {{ $employmentFilter === 'all' ? 'selected' : '' }}>すべて</option>
                    <option value="retired" {{ $employmentFilter === 'retired' ? 'selected' : '' }}>退職</option>
                </select>
                <select name="company_filter">
                    <option value="">会社すべて</option>
                    @foreach($companyOptions as $company)
                    <option value="{{ $company['company_id'] }}" @selected((string) $companyFilter===(string) $company['company_id'])>{{ $company['company_name'] !== '' ? $company['company_name'] : $company['company_id'] }}</option>
                    @endforeach
                </select>
                <button type="submit">検索</button>
                <a href="{{ route('admin.master.staff') }}" class="btn btn-secondary">スタッフマスタへ戻る</a>
            </form>
            <p class="meta-count">件数: {{ number_format(count($rows)) }}</p>

            @php
            $permissionLabels = [
            'is_admin' => '全権管理者',
            'oushin_staff' => '往診スタッフ',
            'is_accounting_user' => '往診売上',
            'is_payment_check_user' => '事務所',
            'is_visit_management_user' => '往診管理',
            'is_view_only_user' => '往診閲覧',
            'is_store_management_user' => '店舗管理',
            'is_daily_report_user' => '店舗日報',
            'front_staff' => '店舗システム',
            ];
            @endphp

            <form method="post" action="{{ route('admin.master.staff.permissions.update') }}">
                @csrf
                <input type="hidden" name="q" value="{{ $keyword }}">
                <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
                <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

                <div class="permissions-table-wrap">
                    <table class="permissions-table">
                        <thead>
                            <tr>
                                <th class="col-staff">スタッフ</th>
                                @foreach($permissionColumns as $column)
                                <th>{{ $permissionLabels[$column] ?? $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="permissions-body">
                            @forelse($rows as $row)
                            <tr>
                                <td class="col-staff">
                                    {{ $row['staff_id'] }} {{ $row['staff_name'] }}
                                    <div style="font-size:11px;color:#5b708f;">{{ $row['store_name'] }}</div>
                                </td>
                                @foreach($permissionColumns as $column)
                                <td>
                                    <input type="hidden" name="permissions[{{ $row['staff_id'] }}][{{ $column }}]" value="0">
                                    <input type="checkbox" name="permissions[{{ $row['staff_id'] }}][{{ $column }}]" value="1" @checked($row[$column])>
                                </td>
                                @endforeach
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ count($permissionColumns) + 1 }}">スタッフがいません</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="permissions-actions">
                    <button type="submit" class="btn-primary">保存</button>
                </div>
            </form>

            <div class="permissions-legend">
                <p class="meta-count">権限の意味</p>
                <table class="permissions-table">
                    <thead>
                        <tr>
                            <th class="col-staff">権限</th>
                            <th class="col-staff">説明</th>
                            <th class="col-staff">ダッシュボードで表示されるメニュー</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="col-staff">全権管理者</td>
                            <td class="col-staff">他の権限をすべて無視して全機能にアクセスできる最上位権限。</td>
                            <td class="col-staff">全メニュー</td>
                        </tr>
                        <tr>
                            <td class="col-staff">往診スタッフ</td>
                            <td class="col-staff">往診担当者であることを示す。ダッシュボードに「往診」メニューが表示され、日報・患者などの担当者選択リストに載る。</td>
                            <td class="col-staff">往診</td>
                        </tr>

                        <tr>
                            <td class="col-staff">往診売上</td>
                            <td class="col-staff">患者情報・入金管理表・領収書の編集権限に加え、売上入力ボタンが表示される権限（往診管理と同等の編集権限＋売上入力）。</td>
                            <td class="col-staff">往診事務</td>
                        </tr>
                        <tr>
                            <td class="col-staff">事務所</td>
                            <td class="col-staff">店舗日報が確定された後でも編集できる権限（通常、確定後は編集不可）。</td>
                            <td class="col-staff">事務所</td>
                        </tr>
                        <tr>
                            <td class="col-staff">往診管理</td>
                            <td class="col-staff">日報・患者・月次訪問・領収書・入金確定など、往診関連のほぼ全ての管理操作ができる権限。他人のデータも確定後含め編集可能。</td>
                            <td class="col-staff">往診管理（+「往診」内の入金確定履歴（管理）カードも表示）</td>
                        </tr>
                        <tr>
                            <td class="col-staff">往診閲覧</td>
                            <td class="col-staff">往診メニューが表示され、往診関連データを全て閲覧できる（編集は患者一覧のみ）権限。</td>
                            <td class="col-staff">往診、往診事務（往診事務内の「往診売上」カードは往診売上権限が無いと表示されない）</td>
                        </tr>
                        <tr>
                            <td class="col-staff">店舗管理</td>
                            <td class="col-staff">シフト・勤怠（他人の打刻修正など）の管理権限。</td>
                            <td class="col-staff">店舗管理</td>
                        </tr>
                        <tr>
                            <td class="col-staff">店舗日報</td>
                            <td class="col-staff">店舗スタッフ向け。このLaravelシステムではなく、Accessで作られた店舗日報システムへのログイン可否・担当者名リストへの表示可否を制御する権限（このシステムのコードでは参照していない）。</td>
                            <td class="col-staff">（このダッシュボードには該当なし）</td>
                        </tr>
                        <tr>
                            <td class="col-staff">店舗システム</td>
                            <td class="col-staff">現在システム内で参照している箇所なし（未使用）。旧システムが往診用と店舗用に分かれていた頃の名残。</td>
                            <td class="col-staff">（なし）</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        document.getElementById('permissions-body').addEventListener('click', function(event) {
            var row = event.target.closest('tr');
            if (row) {
                row.classList.toggle('row-selected');
            }
        });
    </script>
</body>

</html>
