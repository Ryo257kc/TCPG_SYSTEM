@php
$currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();

$navGroups = [
'payroll' => [
'label' => '【勤怠・給与】',
'sections' => [
['heading' => null, 'items' => [
['label' => '勤怠管理', 'route' => 'admin.attendance.index', 'prefixes' => ['admin.attendance.']],
['label' => '給与計算', 'route' => 'admin.payroll.index', 'prefixes' => ['admin.payroll.']],
['label' => '賞与計算', 'route' => 'admin.bonus.index', 'prefixes' => ['admin.bonus.']],
['label' => '有休管理', 'route' => 'admin.paid-leave.index', 'prefixes' => ['admin.paid-leave.']],
['label' => '年末調整管理', 'route' => 'admin.work.year_end_adjustments', 'prefixes' => ['admin.work.year_end_adjustments']],
]],
],
],
'sales' => [
'label' => '【売上・請求】',
'sections' => [
['heading' => '売上', 'items' => [
['label' => '店舗売上', 'route' => 'admin.sales', 'prefixes' => ['admin.sales']],
['label' => '未収入金', 'route' => 'office.sales.uncollected', 'prefixes' => ['office.sales.uncollected']],
['label' => '高額療養費', 'route' => 'office.receipt.high_medical', 'prefixes' => ['office.receipt.high_medical']],
['label' => '往診窓口一覧', 'route' => 'office.receipt.home_visit_counter', 'prefixes' => ['office.receipt.home_visit_counter']],
]],
['heading' => '請求・仕訳', 'items' => [
['label' => '仕訳帳', 'route' => 'admin.work.journal_entries', 'prefixes' => ['admin.work.journal_entries']],
['label' => '現金出納帳', 'route' => 'office.office_menu.cash_book', 'prefixes' => ['office.office_menu.cash_book']],
['label' => '請求一覧', 'route' => 'admin.work.billing_list', 'prefixes' => ['admin.work.billing_list']],
]],
],
],
'notice' => [
'label' => '【通知・申請】',
'sections' => [
['heading' => null, 'items' => [
['label' => 'インフォメーション', 'route' => 'admin.work.information', 'prefixes' => ['admin.work.information']],
['label' => '個人情報変更申請管理', 'route' => 'admin.work.profile_requests', 'prefixes' => ['admin.work.profile_requests']],
['label' => '入社手続き申請管理', 'route' => 'admin.work.onboarding_requests', 'prefixes' => ['admin.work.onboarding_requests']],
]],
],
],
'report' => [
'label' => '【帳票】',
'sections' => [
['heading' => null, 'items' => [
['label' => '帳票一覧', 'route' => 'admin.report.index', 'prefixes' => ['admin.report.index']],
['label' => '算定基礎', 'route' => 'admin.report.santei.index', 'prefixes' => ['admin.report.santei.']],
['label' => '賞与支払届一覧', 'route' => 'admin.report.bonus-payment.index', 'prefixes' => ['admin.report.bonus-payment.']],
['label' => '年度更新', 'route' => 'admin.report.labor-insurance.index', 'prefixes' => ['admin.report.labor-insurance.']],
['label' => '離職票確認', 'route' => 'admin.report.rishoku.index', 'prefixes' => ['admin.report.rishoku.']],
['label' => '入社書類', 'route' => 'office.documents', 'prefixes' => ['office.documents']],
]],
],
],
'master' => [
'label' => '【マスタ】',
'sections' => [
['heading' => null, 'items' => [
['label' => '会社マスタ', 'route' => 'admin.master.company', 'prefixes' => ['admin.master.company']],
['label' => 'スタッフマスタ', 'route' => 'admin.master.staff', 'prefixes' => ['admin.master.staff']],
['label' => '店舗マスタ', 'route' => 'admin.master.store', 'prefixes' => ['admin.master.store']],
['label' => '手当設定', 'route' => 'admin.master.allowance', 'prefixes' => ['admin.master.allowance']],
['label' => 'カレンダー', 'route' => 'admin.master.calendar', 'prefixes' => ['admin.master.calendar']],
]],
],
],
];

foreach ($navGroups as $groupKey => $group) {
$selectedUrl = '';
$resolvedSections = [];
foreach ($group['sections'] as $section) {
$resolvedItems = [];
foreach ($section['items'] as $item) {
$url = route($item['route']);
foreach ($item['prefixes'] as $prefix) {
if ($currentRoute === $prefix || str_starts_with($currentRoute ?? '', $prefix)) {
$selectedUrl = $url;
}
}
$resolvedItems[] = ['label' => $item['label'], 'url' => $url];
}
$resolvedSections[] = ['heading' => $section['heading'], 'items' => $resolvedItems];
}
$navGroups[$groupKey]['selectedUrl'] = $selectedUrl;
$navGroups[$groupKey]['resolvedSections'] = $resolvedSections;
}
@endphp
<div class="global-nav">
    <style>
        .global-nav {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 0 0 6px;
            padding: 6px 10px;
            border-bottom: 1px solid #dbe4ee;
            background: #f8fbff;
        }

        .global-nav-desktop {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .global-nav-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .global-nav-select {
            min-width: 160px;
            height: 34px;
            padding: 0 10px;
            border: 1px solid #c7d2df;
            border-radius: 10px;
            background: #fff;
            color: #1f2d3d;
            font-size: 14px;
        }

        .global-nav-select.is-active {
            background: #dcecff;
            border-color: #9fc2ef;
            color: #183b67;
            font-weight: 700;
        }

        .global-nav-spacer {
            flex: 1 1 auto;
        }

        .global-nav-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0 10px;
            border: 1px solid #cfdae7;
            border-radius: 999px;
            background: #ffffff;
            color: #4f647b;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .global-nav-home:hover {
            border-color: #a9c0dd;
            background: #f3f8ff;
            color: #264a75;
        }

        .global-nav-burger {
            display: none;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: 1px solid #c7d2df;
            border-radius: 8px;
            background: #fff;
            font-size: 18px;
            line-height: 1;
            color: #41556b;
            cursor: pointer;
        }

        .global-nav-mobile-panel {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 40;
            background: #fff;
            border: 1px solid #dbe4ee;
            border-top: none;
            box-shadow: 0 8px 16px rgba(31, 45, 61, 0.12);
            max-height: 75vh;
            overflow-y: auto;
        }

        .global-nav-mobile-panel.is-open {
            display: block;
        }

        .global-nav-mobile-group+.global-nav-mobile-group {
            border-top: 1px solid #eef2f7;
        }

        .global-nav-mobile-heading {
            padding: 10px 14px 4px;
            font-size: 12px;
            font-weight: 800;
            color: #6d7f93;
            letter-spacing: .02em;
        }

        .global-nav-mobile-subheading {
            padding: 6px 20px 2px;
            font-size: 11px;
            font-weight: 700;
            color: #8b9bab;
        }

        .global-nav-mobile-link {
            display: block;
            padding: 10px 20px;
            font-size: 14px;
            color: #1f2d3d;
            text-decoration: none;
        }

        .global-nav-mobile-link:active,
        .global-nav-mobile-link.is-current {
            background: #eaf3ff;
            color: #183b67;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .global-nav-desktop {
                display: none;
            }

            .global-nav-burger {
                display: inline-flex;
            }
        }
    </style>

    <button type="button" class="global-nav-burger" data-global-nav-burger aria-label="menu">&#9776;</button>

    <div class="global-nav-desktop">
        @foreach ($navGroups as $group)
        <div class="global-nav-group">
            <select class="global-nav-select {{ $group['selectedUrl'] !== '' ? 'is-active' : '' }}" data-global-nav-select>
                <option value="">{{ $group['label'] }}</option>
                @foreach ($group['resolvedSections'] as $section)
                @if ($section['heading'])
                <optgroup label="{{ $section['heading'] }}">
                    @foreach ($section['items'] as $item)
                    <option value="{{ $item['url'] }}" @selected($group['selectedUrl']===$item['url'])>{{ $item['label'] }}</option>
                    @endforeach
                </optgroup>
                @else
                @foreach ($section['items'] as $item)
                <option value="{{ $item['url'] }}" @selected($group['selectedUrl']===$item['url'])>{{ $item['label'] }}</option>
                @endforeach
                @endif
                @endforeach
            </select>
        </div>
        @endforeach
    </div>

    <div class="global-nav-spacer"></div>
    <a class="global-nav-home" href="{{ route('admin.dashboard') }}">ダッシュボード</a>

    <div class="global-nav-mobile-panel" data-global-nav-mobile-panel>
        @foreach ($navGroups as $group)
        <div class="global-nav-mobile-group">
            <div class="global-nav-mobile-heading">{{ $group['label'] }}</div>
            @foreach ($group['resolvedSections'] as $section)
            @if ($section['heading'])
            <div class="global-nav-mobile-subheading">{{ $section['heading'] }}</div>
            @endif
            @foreach ($section['items'] as $item)
            <a class="global-nav-mobile-link {{ $group['selectedUrl']===$item['url'] ? 'is-current' : '' }}" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @endforeach
            @endforeach
        </div>
        @endforeach
    </div>

    <script>
        (function() {
            const root = document.currentScript.closest('.global-nav');
            if (!root || root.dataset.bound === '1') return;
            root.dataset.bound = '1';

            root.querySelectorAll('[data-global-nav-select]').forEach((select) => {
                select.addEventListener('change', () => {
                    if (select.value) {
                        window.location.href = select.value;
                    }
                });
            });

            const burger = root.querySelector('[data-global-nav-burger]');
            const panel = root.querySelector('[data-global-nav-mobile-panel]');
            if (burger && panel) {
                burger.addEventListener('click', () => {
                    panel.classList.toggle('is-open');
                });
                document.addEventListener('click', (event) => {
                    if (!root.contains(event.target)) {
                        panel.classList.remove('is-open');
                    }
                });
            }
        })();
    </script>
</div>
