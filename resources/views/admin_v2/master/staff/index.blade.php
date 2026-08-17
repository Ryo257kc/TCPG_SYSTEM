<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - スタッフマスタ</title>
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

        .meta {
            margin: 10px 0 0;
        }

        .meta-count {
            margin: 6px 0 0;
            color: #5b708f;
            line-height: 1.2;
        }

        .staff-layout {
            display: flex;
            gap: 16px;
            margin-top: 14px;
            align-items: flex-start;
        }

        .staff-list-panel {
            flex: 0 0 220px;
            max-width: 220px;
            border: 1px solid #d3dff0;
            border-radius: 14px;
            background: #fdfefe;
            overflow: hidden;
            min-width: 0;
        }

        .staff-detail-panel {
            flex: 1 1 auto;
            border: 1px solid #d3dff0;
            border-radius: 14px;
            background: #fdfefe;
            overflow: hidden;
            min-width: 0;
        }

        .panel-title {
            padding: 12px 14px;
            background: #f5f8fd;
            border-bottom: 1px solid #d3dff0;
            color: #123c73;
            font-weight: 700;
        }

        .staff-list {
            max-height: 70vh;
            overflow: auto;
        }

        .staff-list-item {
            display: block;
            padding: 10px 12px;
            border-bottom: 1px solid #e5edf8;
            text-decoration: none;
            color: #1f2937;
            background: #fff;
        }

        .staff-list-item:hover {
            background: #f8fbff;
        }

        .staff-list-item-active {
            background: #e8f1ff;
        }

        .staff-list-main {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .staff-list-id {
            font-weight: 700;
            color: #123c73;
            min-width: 40px;
            font-size: 12px;
        }

        .staff-list-name {
            font-weight: 600;
            font-size: 14px;
        }

        .staff-list-sub {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
            font-size: 12px;
            color: #5b708f;
        }

        .staff-tab-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 12px 14px;
            border-bottom: 1px solid #d3dff0;
            background: #fbfdff;
        }

        .staff-tab {
            display: inline-flex;
            padding: 7px 12px;
            border: 1px solid #c9d7eb;
            border-radius: 999px;
            background: #fff;
            color: #46658b;
            font-weight: 700;
            text-decoration: none;
        }

        .staff-tab.is-active {
            background: #1f4f8f;
            border-color: #1f4f8f;
            color: #fff;
        }

        .staff-tab-actions {
            margin-left: auto;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .staff-tab-panels {
            padding: 14px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .detail-field {
            display: grid;
            grid-template-columns: 80px minmax(0, 1fr);
            gap: 3px;
            align-items: center;
        }

        .detail-field-compact {
            gap: 2px;
        }

        .detail-field-tight {
            grid-template-columns: 56px minmax(0, 1fr);
            gap: 2px;
        }

        .detail-field-work {
            grid-template-columns: 118px minmax(0, 72px);
        }

        .detail-field span {
            font-size: 12px;
            color: #46658b;
            font-weight: 700;
            line-height: 1.2;
        }

        .detail-value-sub {
            margin-top: 2px;
            font-size: 12px;
            color: #5b708f;
            line-height: 1.2;
        }

        .detail-field-wide {
            grid-column: 1 / -1;
        }

        .detail-field-textarea {
            grid-template-columns: 1fr;
            gap: 6px;
            align-items: stretch;
        }

        .detail-field-textarea-no-label {
            gap: 0;
        }

        .detail-section {
            padding: 8px 10px;
            border: 1px solid #c8d9f0;
            border-radius: 10px;
            background: #eaf2ff;
            margin-top: 6px;
        }

        .detail-section span {
            display: block;
            font-size: 13px;
            color: #123c73;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .detail-value {
            min-height: 24px;
            padding: 2px 0 3px;
            border: 0;
            border-bottom: 1px solid #d3dff0;
            border-radius: 0;
            background: transparent;
            color: #1f2937;
            box-sizing: border-box;
            line-height: 1.25;
            width: 100%;
            font-size: 14px;
        }

        .detail-value-bool {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 20px;
            max-width: 52px;
            padding: 0 4px;
            font-size: 15px;
            line-height: 1;
            background: transparent;
            border: 0;
            border-bottom: 1px solid #d3dff0;
        }

        .detail-value-bool-tight {
            min-height: 24px;
            justify-content: flex-start;
        }

        .detail-value-bool-rights {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 20px;
            width: 20px;
            padding: 0;
            font-size: 15px;
            line-height: 1;
            border: 0;
            background: transparent;
            border-radius: 0;
        }

        .detail-value-textarea {
            min-height: 128px;
            padding: 4px 6px;
            border: 1px solid #d3dff0;
            border-radius: 4px;
            background: #f7f9fc;
            white-space: pre-wrap;
            align-content: flex-start;
            overflow: auto;
            line-height: 1.1;
        }

        .detail-value-empty {
            color: #8ca0ba;
        }

        .staff-display-value {
            min-height: 24px;
            padding: 2px 0 3px;
            border: 0;
            border-bottom: 1px solid #d3dff0;
            border-radius: 0;
            background: transparent;
            color: #1f2937;
            box-sizing: border-box;
            line-height: 1.25;
            width: 100%;
            font-size: 14px;
        }

        .staff-info-form .staff-info-edit {
            display: none !important;
        }

        .staff-info-form.editing .staff-info-view {
            display: none !important;
        }

        .staff-info-form.editing .staff-info-edit {
            display: block !important;
        }

        .detail-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 14px;
        }

        .staff-edit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .staff-edit-grid .detail-field-wide {
            grid-column: 1 / -1;
        }

        .staff-edit-grid input,
        .staff-edit-grid select,
        .staff-edit-grid textarea {
            width: 100%;
            box-sizing: border-box;
            font: inherit;
        }

        .staff-edit-grid textarea {
            min-height: 96px;
            resize: vertical;
        }

        .detail-field-textarea textarea {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            display: block;
            min-height: 128px;
            resize: vertical;
        }

        .checkbox-line {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
        }

        .staff-info-sections {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .info-block {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 10px;
            border: 1px solid #dbe6f5;
            border-radius: 10px;
            background: #fbfdff;
        }

        .info-block-wide {
            grid-column: 1 / -1;
        }

        .info-block-title {
            font-size: 13px;
            font-weight: 800;
            color: #123c73;
            padding-bottom: 6px;
            border-bottom: 1px solid #dbe6f5;
        }

        .info-block-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px 8px;
        }

        .name-block-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px 8px;
        }

        .name-block-wide {
            grid-column: 1 / -1;
        }

        .detail-pair {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .detail-pair-wide {
            grid-column: 1 / -1;
        }

        .detail-pair-inline {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px 8px;
        }

        .detail-pair-inline-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 4px 8px;
        }

        .detail-divider {
            height: 1px;
            background: #dbe6f5;
            margin: 2px 0 4px;
        }

        .detail-divider-wide {
            grid-column: 1 / -1;
        }

        .payroll-master-block input[type="text"],
        .payroll-master-block input[type="date"],
        .social-insurance-block input[type="text"],
        .social-insurance-block input[type="date"] {
            max-width: 140px;
            min-width: 0;
            box-sizing: border-box;
        }

        .payroll-master-block .detail-field,
        .social-insurance-block .detail-field {
            grid-template-columns: max-content minmax(0, 140px);
        }

        .payroll-master-block .detail-field span,
        .social-insurance-block .detail-field span,
        .resident-tax-block .detail-field span {
            white-space: nowrap;
        }

        .payroll-master-history,
        .social-insurance-history,
        .resident-tax-history {
            background: #f8fafc;
        }

        .payroll-master-title-row,
        .social-insurance-title-row,
        .resident-tax-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dbe6f5;
        }

        .payroll-master-title,
        .social-insurance-title,
        .resident-tax-title {
            font-size: 16px;
            font-weight: 800;
            color: #bd721c;
        }

        .payroll-master-badge,
        .social-insurance-badge,
        .resident-tax-badge {
            display: inline-flex;
            align-items: center;
            min-height: 20px;
            padding: 0 8px;
            border-radius: 999px;
            background: #1f4f8f;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
        }

        .payroll-master-value,
        .social-insurance-value,
        .resident-tax-value {
            min-height: 24px;
            padding: 2px 0 3px;
            border-bottom: 1px solid #d3dff0;
            color: #1f2937;
            font-size: 15px;
            font-weight: 700;
            text-align: right;
        }

        .address-block-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }

        .address-block-wide {
            grid-column: 1 / -1;
        }

        .contract-block-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }

        .contract-block-wide {
            grid-column: 1 / -1;
        }

        .contract-inline-field {
            align-items: start;
        }

        .contract-inline-values {
            display: flex;
            gap: 8px;
            align-items: center;
            min-width: 0;
        }

        .contract-inline-text {
            flex: 1 1 auto;
            min-width: 0;
        }

        .related-card {
            border: 1px solid #d3dff0;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
        }

        .related-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            background: #f8fbff;
            border-bottom: 1px solid #dbe6f5;
            color: #46658b;
        }

        .related-header h3 {
            margin: 0;
            font-size: 14px;
            color: #123c73;
        }

        .staff-content-table-wrap {
            overflow: auto;
        }

        .staff-content-table-wrap table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .staff-content-table-wrap th,
        .staff-content-table-wrap td {
            border: 1px solid #d3dff0;
            padding: 6px;
            white-space: nowrap;
            text-align: center;
        }

        .staff-empty {
            padding: 18px;
            color: #5b708f;
        }

        .staff-date-era-field {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
        }

        .staff-date-era-field input[type="date"] {
            width: 132px;
            min-width: 0;
            max-width: 132px;
        }

        .staff-date-era-hint {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            min-width: 28px;
            color: #1f4f8f;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        @media (max-width: 760px) {
            .staff-layout {
                display: block;
            }

            .staff-list-panel {
                max-width: none;
                width: 100%;
                margin-bottom: 16px;
            }

            .staff-list {
                max-height: none;
            }

            .detail-grid,
            .staff-info-sections,
            .info-block-grid,
            .name-block-grid,
            .address-block-grid,
            .contract-block-grid {
                grid-template-columns: 1fr;
            }

            .contract-inline-values {
                flex-direction: column;
                align-items: stretch;
            }

            .detail-field {
                grid-template-columns: 1fr;
                gap: 3px;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')
    <div class="wrap">
        <div class="top">
            <div class="title">TCPG SYSTEM スタッフマスタ</div>
        </div>
        <section class="panel">
            <form method="get" class="filter-form">
                <input type="text" name="q" value="{{ $keyword }}" placeholder="ID / 氏名 / 店舗コードで検索">
                <select name="employment_filter">
                    <option value="active" {{ ($employmentFilter ?? 'active') === 'active' ? 'selected' : '' }}>在籍</option>
                    <option value="all" {{ ($employmentFilter ?? 'active') === 'all' ? 'selected' : '' }}>すべて</option>
                    <option value="retired" {{ ($employmentFilter ?? 'active') === 'retired' ? 'selected' : '' }}>退職</option>
                </select>
                <select name="company_filter">
                    <option value="">会社すべて</option>
                    @foreach(($companyOptions ?? []) as $company)
                    <option value="{{ $company['company_id'] }}" @selected((string)($companyFilter ?? '' )===(string)$company['company_id'])>{{ $company['company_name'] !== '' ? $company['company_name'] : $company['company_id'] }}</option>
                    @endforeach
                </select>
                <button type="submit">検索</button>

                <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'new' => 1]) }}" class="btn btn-primary">新規登録</a>
                <a href="{{ route('admin.master.staff.permissions') }}" class="btn btn-secondary">権限一覧</a>
            </form>
            <p class="meta-count">件数: {{ number_format($rowCount) }}</p>

            <div class="staff-layout">
                <div class="staff-list-panel">
                    <div class="panel-title">スタッフ一覧</div>
                    <div class="staff-list">
                        @forelse($rows as $row)
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'staff_id' => $row['staff_id'], 'tab' => $selectedTab]) }}" class="staff-list-item {{ $selectedStaffId === $row['staff_id'] ? 'staff-list-item-active' : '' }}">
                            <div class="staff-list-main">
                                <span class="staff-list-id">{{ $row['staff_id'] }}</span>
                                <span class="staff-list-name">{{ $row['staff_name'] }}</span>
                            </div>
                            <div class="staff-list-sub">
                                <span>{{ $row['store_name'] !== '' ? $row['store_name'] : '---' }}</span>
                                <span>{{ $row['employment_status'] !== '' ? $row['employment_status'] : '---' }}</span>
                            </div>
                        </a>
                        @empty
                        <div class="staff-empty">スタッフがいません</div>
                        @endforelse
                    </div>
                </div>

                <div class="staff-detail-panel">
                    <div class="panel-title">スタッフ詳細</div>
                    @if($selectedRow)
                    <div class="staff-tab-bar">
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'staff_id' => $selectedStaffId, 'tab' => 'staff']) }}" class="staff-tab {{ $selectedTab === 'staff' ? 'is-active' : '' }}">スタッフ情報</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'staff_id' => $selectedStaffId, 'tab' => 'shift']) }}" class="staff-tab {{ $selectedTab === 'shift' ? 'is-active' : '' }}">基本シフト</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'staff_id' => $selectedStaffId, 'tab' => 'kihon']) }}" class="staff-tab {{ $selectedTab === 'kihon' ? 'is-active' : '' }}">給与マスタ</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'staff_id' => $selectedStaffId, 'tab' => 'shaho']) }}" class="staff-tab {{ $selectedTab === 'shaho' ? 'is-active' : '' }}">社保・雇保</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'staff_id' => $selectedStaffId, 'tab' => 'resident']) }}" class="staff-tab {{ $selectedTab === 'resident' ? 'is-active' : '' }}">住民税</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'employment_filter' => $employmentFilter, 'company_filter' => $companyFilter, 'staff_id' => $selectedStaffId, 'tab' => 'fuyo']) }}" class="staff-tab {{ $selectedTab === 'fuyo' ? 'is-active' : '' }}">扶養</a>
                        @if($selectedTab === 'staff')
                        <div class="staff-tab-actions">
                            <button type="button" class="btn-secondary staff-tab-edit-button staff-tab-edit-only" onclick="toggleStaffInfoEdit(true)">編集</button>
                            <button type="submit" form="staff-info-form" class="btn-primary staff-tab-edit-button staff-tab-save-only" hidden>保存</button>
                            <button type="button" class="btn-secondary staff-tab-edit-button staff-tab-save-only" hidden onclick="toggleStaffInfoEdit(false)">取消</button>
                        </div>
                        @endif
                    </div>
                    <div class="staff-tab-panels">
                        @if($selectedTab === 'staff')
                        @include('admin_v2.master.staff.tabs.staff_info')
                        @elseif($selectedTab === 'shift')
                        @include('admin_v2.master.staff.tabs.basic_shifts')
                        @elseif($selectedTab === 'kihon')
                        @include('admin_v2.master.staff.tabs.payroll_master')
                        @elseif($selectedTab === 'shaho')
                        @include('admin_v2.master.staff.tabs.social_insurance')
                        @elseif($selectedTab === 'fuyo')
                        @include('admin_v2.master.staff.tabs.dependents')
                        @else
                        @include('admin_v2.master.staff.tabs.resident_tax')
                        @endif
                    </div>
                    @else
                    <div class="staff-empty">表示対象のスタッフがありません</div>
                    @endif
                </div>
            </div>
        </section>
    </div>
    @include('admin_v2.master.staff.page_script')
</body>

</html>
