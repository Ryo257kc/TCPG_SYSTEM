<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 会社マスタ</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/company.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 会社マスタ</div>
    </div>
    <section class="panel">
        @if(session('status'))
            <p class="status">{{ session('status') }}</p>
        @endif

        <div class="company-layout">
            <div class="company-list-panel">
                <div class="panel-title">会社一覧</div>
                <div class="company-list">
                    @forelse($rows as $row)
                        <a class="company-list-item {{ $selectedCompanyId === $row['company_id'] ? 'company-list-item-active' : '' }}" href="{{ route('admin.master.company', ['q' => $keyword, 'company_id' => $row['company_id'], 'tab' => $selectedTab]) }}">
                            <div class="company-list-main">
                                <span class="company-list-code">{{ $row['company_id'] }}</span>
                                <span class="company-list-name">{{ $row['company_name'] !== '' ? $row['company_name'] : '会社名未設定' }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="company-empty">データがありません</div>
                    @endforelse
                </div>
            </div>

            <div class="company-detail-panel" id="company-detail-panel">
                <div class="panel-title">会社詳細</div>
                @if($selectedRow)
                    <div class="company-tab-bar">
                        <button type="button" class="company-tab {{ $selectedTab === 'company' ? 'is-active' : '' }}" data-company-tab="company" onclick="setCompanyTab('company')">会社情報</button>
                        <button type="button" class="company-tab {{ $selectedTab === 'syaho' ? 'is-active' : '' }}" data-company-tab="syaho" onclick="setCompanyTab('syaho')">社会保険</button>
                        <button type="button" class="company-tab {{ $selectedTab === 'rouho' ? 'is-active' : '' }}" data-company-tab="rouho" onclick="setCompanyTab('rouho')">労働保険</button>
                        <button type="button" class="company-tab {{ $selectedTab === 'mayor' ? 'is-active' : '' }}" data-company-tab="mayor" onclick="setCompanyTab('mayor')">住民税</button>
                    </div>
                    <div class="company-tab-panels">
                        <div class="company-tab-panel {{ $selectedTab === 'company' ? 'is-active' : '' }}" data-company-tab-panel="company">
                            @include('admin_v2.master.company.tabs.company_info')
                        </div>
                        <div class="company-tab-panel {{ $selectedTab === 'syaho' ? 'is-active' : '' }}" data-company-tab-panel="syaho">
                            @include('admin_v2.master.company.tabs.social_insurance')
                        </div>
                        <div class="company-tab-panel {{ $selectedTab === 'rouho' ? 'is-active' : '' }}" data-company-tab-panel="rouho">
                            @include('admin_v2.master.company.tabs.labor_insurance')
                        </div>
                        <div class="company-tab-panel {{ $selectedTab === 'mayor' ? 'is-active' : '' }}" data-company-tab-panel="mayor">
                            @include('admin_v2.master.company.tabs.mayor_tax')
                        </div>
                    </div>
                @else
                    <div class="company-empty">表示対象の会社がありません</div>
                @endif
            </div>
        </div>
    </section>
</div>
@include('admin_v2.master.company.page_script')
</body>
</html>
