@php
    $t = static fn (string $s) => json_decode('"' . $s . '"', true, 512, JSON_THROW_ON_ERROR);
    $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();

    $navGroups = [
        'payroll' => [
            'label' => $t('\\u52e4\\u6020\\u30fb\\u7d66\\u4e0e'),
            'items' => [
                ['label' => $t('\\u52e4\\u6020\\u7ba1\\u7406'), 'route' => 'admin.attendance.index', 'prefixes' => ['admin.attendance.']],
                ['label' => $t('\\u7d66\\u4e0e\\u8a08\\u7b97'), 'route' => 'admin.payroll.index', 'prefixes' => ['admin.payroll.', 'admin.payroll-v2.']],
                ['label' => $t('\\u8cde\\u4e0e\\u8a08\\u7b97'), 'route' => 'admin.bonus.index', 'prefixes' => ['admin.bonus.']],
                ['label' => $t('\\u6709\\u4f11\\u7ba1\\u7406'), 'route' => 'admin.paid-leave.index', 'prefixes' => ['admin.paid-leave.']],
            ],
        ],
        'report' => [
            'label' => $t('\\u5e33\\u7968'),
            'items' => [
                ['label' => $t('\\u5e33\\u7968\\u4e00\\u89a7'), 'route' => 'admin.report.index', 'prefixes' => ['admin.report.index']],
                ['label' => $t('\\u7b97\\u5b9a\\u57fa\\u790e'), 'route' => 'admin.report.santei.index', 'prefixes' => ['admin.report.santei.']],
                ['label' => $t('\\u8cde\\u4e0e\\u652f\\u6255\\u5c4a\\u4e00\\u89a7'), 'route' => 'admin.report.bonus-payment.index', 'prefixes' => ['admin.report.bonus-payment.']],
                ['label' => $t('\\u5e74\\u5ea6\\u66f4\\u65b0'), 'route' => 'admin.report.labor-insurance.index', 'prefixes' => ['admin.report.labor-insurance.']],
                ['label' => $t('\\u96e2\\u8077\\u7968\\u78ba\\u8a8d'), 'route' => 'admin.report.rishoku.index', 'prefixes' => ['admin.report.rishoku.']],
            ],
        ],
        'master' => [
            'label' => $t('\\u30de\\u30b9\\u30bf'),
            'items' => [
                ['label' => $t('\\u4f1a\\u793e\\u30de\\u30b9\\u30bf'), 'route' => 'admin.master.company', 'prefixes' => ['admin.master.company']],
                ['label' => $t('\\u30b9\\u30bf\\u30c3\\u30d5\\u30de\\u30b9\\u30bf'), 'route' => 'admin.master.staff', 'prefixes' => ['admin.master.staff']],
                ['label' => $t('\\u5e97\\u8217\\u30de\\u30b9\\u30bf'), 'route' => 'admin.master.store', 'prefixes' => ['admin.master.store']],
                ['label' => $t('\\u624b\\u5f53\\u8a2d\\u5b9a'), 'route' => 'admin.master.allowance', 'prefixes' => ['admin.master.allowance']],
                ['label' => $t('\\u30ab\\u30ec\\u30f3\\u30c0\\u30fc'), 'route' => 'admin.master.calendar', 'prefixes' => ['admin.master.calendar']],
            ],
        ],
    ];

    $selectedGroup = 'report';
    $selectedItemUrl = route('admin.report.index');

    foreach ($navGroups as $groupKey => $group) {
        foreach ($group['items'] as $item) {
            foreach ($item['prefixes'] as $prefix) {
                if ($currentRoute === $prefix || str_starts_with($currentRoute ?? '', $prefix)) {
                    $selectedGroup = $groupKey;
                    $selectedItemUrl = route($item['route']);
                    break 3;
                }
            }
        }
    }

    $groupItems = [];
    foreach ($navGroups as $groupKey => $group) {
        $groupItems[$groupKey] = array_map(function ($item) {
            return [
                'label' => $item['label'],
                'url' => route($item['route']),
            ];
        }, $group['items']);
    }
@endphp
<div class="global-nav">
    <style>
        .global-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 0 0 6px;
            padding: 6px 10px;
            border-bottom: 1px solid #dbe4ee;
            background: #f8fbff;
        }
        .global-nav-groups {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .global-nav-group-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0 12px;
            border: 1px solid #c7d2df;
            border-radius: 999px;
            background: #fff;
            color: #41556b;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .global-nav-group-btn.is-active {
            background: #dcecff;
            border-color: #9fc2ef;
            color: #183b67;
        }
        .global-nav-select {
            min-width: 220px;
            height: 34px;
            padding: 0 10px;
            border: 1px solid #c7d2df;
            border-radius: 10px;
            background: #fff;
            color: #1f2d3d;
            font-size: 13px;
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
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }
        .global-nav-home:hover {
            border-color: #a9c0dd;
            background: #f3f8ff;
            color: #264a75;
        }
    </style>
    <div class="global-nav-groups">
        @foreach ($navGroups as $groupKey => $group)
            <button
                type="button"
                class="global-nav-group-btn {{ $groupKey === $selectedGroup ? 'is-active' : '' }}"
                data-global-nav-group="{{ $groupKey }}"
            >{{ $group['label'] }}</button>
        @endforeach
    </div>
    <select class="global-nav-select" data-global-nav-item>
        <option value="">{{ $t('\\u9078\\u629e\\u3057\\u3066\\u304f\\u3060\\u3055\\u3044') }}</option>
        @foreach ($navGroups[$selectedGroup]['items'] as $item)
            <option value="{{ route($item['route']) }}" @selected($selectedItemUrl === route($item['route']))>{{ $item['label'] }}</option>
        @endforeach
    </select>
    <div class="global-nav-spacer"></div>
    <a class="global-nav-home" href="{{ route('admin.dashboard') }}">{{ $t('\\u30c0\\u30c3\\u30b7\\u30e5\\u30dc\\u30fc\\u30c9') }}</a>
    <script>
        (function () {
            const root = document.currentScript.closest('.global-nav');
            if (!root || root.dataset.bound === '1') return;
            root.dataset.bound = '1';

            const groups = @json($groupItems, JSON_UNESCAPED_UNICODE);
            const groupButtons = Array.from(root.querySelectorAll('[data-global-nav-group]'));
            const itemSelect = root.querySelector('[data-global-nav-item]');

            const renderItems = (groupKey) => {
                const items = groups[groupKey] || [];
                itemSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = @json($t('\\u9078\\u629e\\u3057\\u3066\\u304f\\u3060\\u3055\\u3044'), JSON_UNESCAPED_UNICODE);
                itemSelect.appendChild(placeholder);
                items.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.url;
                    option.textContent = item.label;
                    itemSelect.appendChild(option);
                });
            };

            groupButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    groupButtons.forEach((btn) => btn.classList.remove('is-active'));
                    button.classList.add('is-active');
                    renderItems(button.dataset.globalNavGroup);
                    itemSelect.value = '';
                });
            });

            itemSelect.addEventListener('change', () => {
                const url = itemSelect.value;
                if (url) {
                    window.location.href = url;
                }
            });
        })();
    </script>
</div>
