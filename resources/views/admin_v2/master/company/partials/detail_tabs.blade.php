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
        @include('admin_v2.master.company.partials.tabs.company_info')
      </div>
      <div class="company-tab-panel {{ $selectedTab === 'syaho' ? 'is-active' : '' }}" data-company-tab-panel="syaho">
        @include('admin_v2.master.company.partials.tabs.social_insurance')
      </div>
      <div class="company-tab-panel {{ $selectedTab === 'rouho' ? 'is-active' : '' }}" data-company-tab-panel="rouho">
        @include('admin_v2.master.company.partials.tabs.labor_insurance')
      </div>
      <div class="company-tab-panel {{ $selectedTab === 'mayor' ? 'is-active' : '' }}" data-company-tab-panel="mayor">
        @include('admin_v2.master.company.partials.tabs.mayor_tax')
      </div>
    </div>
  @else
    <div class="company-empty">表示対象の会社がありません</div>
  @endif
</div>
