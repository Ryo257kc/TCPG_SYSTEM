<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 個人情報変更申請</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">

    <style>
        .pr-status {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .pr-return-note {
            background: rgba(255, 220, 150, 0.3);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        .pr-section {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }

        .pr-section-title {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .pr-current-label {
            color: var(--sub);
            font-size: 12px;
            margin: 0 0 4px;
        }

        .pr-current-value {
            margin: 0 0 2px;
        }

        .pr-toggle {
            margin-top: 10px;
        }

        .pr-toggle-fields {
            display: none;
            margin-top: 10px;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .pr-toggle-input:checked~.pr-toggle-fields {
            display: grid;
        }

        .pr-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .pr-field-label {
            font-size: 12px;
            color: var(--sub);
        }

        .pr-field input,
        .pr-field select {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 10px;
        }

        .pr-field input[type="checkbox"] {
            width: auto;
            align-self: flex-start;
        }

        .pr-note {
            font-size: 12px;
            color: var(--sub);
            margin: 4px 0;
        }

        .pr-gate {
            border: 1px dashed var(--line);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 14px;
        }

        .pr-section-actions {
            margin-top: 10px;
            text-align: right;
        }

        .pr-step {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 32px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--brand, #b5793c);
        }

        .pr-step:first-of-type {
            margin-top: 20px;
        }

        .pr-step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--brand, #b5793c);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .pr-step-title {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
        }

        .pr-btn-save {
            display: block;
            margin-left: auto;
            padding: 6px 14px;
            font-size: 13px;
        }

        .pr-submit-area {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 2px solid var(--line);
            text-align: center;
        }

        .pr-btn-submit {
            display: inline-block;
            padding: 14px 48px;
            font-size: 16px;
            font-weight: 700;
        }

        .pr-submit-note {
            margin-top: 8px;
            font-size: 12px;
            color: var(--sub);
        }

        .pr-add-more-btn {
            margin-top: 8px;
        }

        template {
            display: none;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel">
            <div class="content-head">
                <h2 class="content-title">個人情報変更申請</h2>
            </div>

            @if (session('statusMessage'))
            <div class="status">{{ session('statusMessage') }}</div>
            @endif
            @if (session('errorMessage'))
            <div class="error">{{ session('errorMessage') }}</div>
            @endif
            @if ($errors->any())
            <div class="error">
                <p>入力内容を確認してください。</p>
                <ul>
                    @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <p class="pr-status">申請状況：{{ $statusLabel }}</p>

            @if ($requestRow['status'] === 'returned' && !empty($requestRow['return_note']))
            <div class="pr-return-note">
                <strong>差戻し理由</strong>
                <p>{{ $requestRow['return_note'] }}</p>
            </div>
            @endif

            <div class="pr-step">
                <span class="pr-step-number">1</span>
                <h3 class="pr-step-title">本人情報（氏名・住所・世帯主）</h3>
            </div>
            <form method="post" action="{{ route('profile_request.personal_info.update') }}" enctype="multipart/form-data">
                @csrf
                @include('staff_portal.profile_request.sections._confirm_or_change', [
                'sectionKey' => 'personal-info',
                'title' => '',
                'currentItems' => [
                ['label' => '氏名', 'value' => $currentStaffName],
                ['label' => '氏名フリガナ', 'value' => $currentStaffNameFuri],
                ['label' => '住所', 'value' => $currentAddress],
                ['label' => '住所フリガナ', 'value' => $currentAddressFuri],
                ['label' => '世帯主の氏名', 'value' => $currentHeadHouse],
                ['label' => '世帯主との続柄', 'value' => $currentRelationship],
                ],
                'toggleLabel' => '内容を確認・修正する',
                'changedFieldName' => 'personal_info_dummy',
                'changedChecked' => true,
                'hideToggle' => true,
                'fields' => [
                ['name' => 'new_staff_name', 'label' => '氏名', 'value' => old('new_staff_name', $requestRow['new_staff_name'] ?? $currentStaffName), 'maxlength' => 50],
                ['name' => 'new_staff_name_furi', 'label' => '氏名フリガナ', 'value' => old('new_staff_name_furi', $requestRow['new_staff_name_furi'] ?? $currentStaffNameFuri), 'maxlength' => 50],
                ['name' => 'name_change_certificate_file', 'label' => '氏名変更の証憑（戸籍謄本・婚姻届受理証明書等。氏名を変更する場合のみ必須）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $requestRow['name_change_certificate_original_name'] ?? ''],
                ['name' => 'new_address', 'label' => '住所', 'value' => old('new_address', $requestRow['new_address'] ?? $currentAddress), 'maxlength' => 255],
                ['name' => 'new_address_furi', 'label' => '住所フリガナ', 'value' => old('new_address_furi', $requestRow['new_address_furi'] ?? $currentAddressFuri), 'maxlength' => 255],
                ['name' => 'address_change_certificate_file', 'label' => '住所変更の証憑（住民票等。住所を変更する場合のみ必須）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $requestRow['address_change_certificate_original_name'] ?? ''],
                ['name' => 'setai_nushi', 'label' => '世帯主の氏名（本人が世帯主の場合は本人の氏名）', 'value' => old('setai_nushi', $requestRow['setai_nushi'] ?? ''), 'maxlength' => 50],
                ['name' => 'setai_zoku_gara', 'label' => '世帯主との続柄（本人が世帯主の場合は「本人」）', 'value' => old('setai_zoku_gara', $requestRow['setai_zoku_gara'] ?? ''), 'maxlength' => 20],
                ],
                'editable' => $editable,
                ])

                <div class="pr-step">
                    <span class="pr-step-number">2</span>
                    <h3 class="pr-step-title">通勤経路</h3>
                </div>
                @include('staff_portal.profile_request.sections._confirm_or_change', [
                'sectionKey' => 'commute',
                'title' => '',
                'currentItems' => [
                ['label' => '車通勤km', 'value' => $currentCarKm],
                ['label' => '日額交通費', 'value' => $currentTrafficDay],
                ['label' => '追加交通費日額', 'value' => $currentTrafficDayTuika],
                ],
                'toggleLabel' => '内容を確認・修正する',
                'changedFieldName' => 'commute_dummy',
                'changedChecked' => true,
                'hideToggle' => true,
                'fields' => [
                ['name' => 'new_car_km', 'label' => '車通勤km（証憑に記載の値をそのまま入力）', 'value' => old('new_car_km', $requestRow['new_car_km'] ?? $currentCarKm), 'maxlength' => 100],
                ['name' => 'new_traffic_day', 'label' => '日額交通費（証憑に記載の値をそのまま入力）', 'value' => old('new_traffic_day', $requestRow['new_traffic_day'] ?? $currentTrafficDay), 'maxlength' => 100],
                ['name' => 'new_traffic_day_tuika', 'label' => '追加交通費日額（証憑に記載の値をそのまま入力）', 'value' => old('new_traffic_day_tuika', $requestRow['new_traffic_day_tuika'] ?? $currentTrafficDayTuika), 'maxlength' => 100],
                ['name' => 'commute_change_certificate_file', 'label' => '通勤経路変更の証憑（定期券の写し等。変更する場合のみ必須）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $requestRow['commute_change_certificate_original_name'] ?? ''],
                ],
                'editable' => $editable,
                ])

                @if ($editable)
                <div class="pr-section-actions">
                    <button type="submit" class="btn pr-btn-save">本人情報・通勤経路を保存</button>
                </div>
                @endif
            </form>

            <div class="pr-step">
                <span class="pr-step-number">3</span>
                <h3 class="pr-step-title">扶養親族</h3>
            </div>
            <form method="post" action="{{ route('profile_request.dependents.update') }}" enctype="multipart/form-data">
                @csrf

                @forelse ($dependentRows as $dep)
                @php
                $fuyoNo = (int) ($dep['fuyo_no'] ?? 0);
                @endphp
                @include('staff_portal.profile_request.sections._confirm_or_change', [
                'sectionKey' => 'fuyo-' . $fuyoNo,
                'title' => ($dep['fuyo_name'] ?? '（氏名未登録）') . '（' . ($dep['fuyo_relationship'] ?? '続柄未登録') . '）',
                'currentItems' => [
                ['label' => '氏名', 'value' => (string) ($dep['fuyo_name'] ?? '')],
                ['label' => 'フリガナ', 'value' => (string) ($dep['fuyo_name_furi'] ?? '')],
                ['label' => '続柄', 'value' => (string) ($dep['fuyo_relationship'] ?? '')],
                ['label' => '生年月日', 'value' => substr((string) ($dep['fuyo_birthday'] ?? ''), 0, 10)],
                ['label' => '住所', 'value' => (string) ($dep['fuyo_address'] ?? '')],
                ['label' => '同居／別居', 'value' => (string) ($dep['kyojyu'] ?? '')],
                ['label' => '年間収入見込み', 'value' => number_format((float) ($dep['fuyo_shunyu'] ?? 0))],
                ['label' => '控除対象', 'value' => ((string) ($dep['deduction_target'] ?? '')) === '1' ? '対象' : '対象外'],
                ],
                'changedFieldName' => "fuyo[{$fuyoNo}][changed]",
                'changedChecked' => false,
                'fields' => [
                ['name' => "fuyo[{$fuyoNo}][fuyo_name]", 'label' => '氏名', 'value' => $dep['fuyo_name'] ?? '', 'maxlength' => 50],
                ['name' => "fuyo[{$fuyoNo}][fuyo_name_furi]", 'label' => 'フリガナ', 'value' => $dep['fuyo_name_furi'] ?? '', 'maxlength' => 50],
                ['name' => "fuyo[{$fuyoNo}][fuyo_relationship]", 'label' => '続柄', 'value' => $dep['fuyo_relationship'] ?? '', 'maxlength' => 50],
                ['name' => "fuyo[{$fuyoNo}][fuyo_birthday]", 'label' => '生年月日', 'type' => 'date', 'value' => substr((string) ($dep['fuyo_birthday'] ?? ''), 0, 10)],
                ['name' => "fuyo[{$fuyoNo}][fuyo_address]", 'label' => '住所', 'value' => $dep['fuyo_address'] ?? '', 'maxlength' => 255],
                ['name' => "fuyo[{$fuyoNo}][fuyo_sex]", 'label' => '性別', 'type' => 'select', 'options' => ['' => '選択', '男' => '男', '女' => '女'], 'value' => $dep['fuyo_sex'] ?? ''],
                ['name' => "fuyo[{$fuyoNo}][kyojyu]", 'label' => '同居／別居', 'type' => 'select', 'options' => ['' => '選択', '同居' => '同居', '別居' => '別居'], 'value' => $dep['kyojyu'] ?? ''],
                ['name' => "fuyo[{$fuyoNo}][fuyo_shunyu]", 'label' => '年間収入見込み（円）', 'type' => 'number', 'value' => (int) ($dep['fuyo_shunyu'] ?? 0)],
                ['name' => "fuyo[{$fuyoNo}][has_disability]", 'label' => '障害者手帳をお持ちですか', 'type' => 'checkbox', 'checked' => ($dep['failure_notebook'] ?? '') !== ''],
                ['name' => "fuyo[{$fuyoNo}][failure_notebook]", 'label' => '障害者手帳の種類', 'value' => $dep['failure_notebook'] ?? '', 'maxlength' => 50],
                ['name' => "fuyo[{$fuyoNo}][failure_judgment]", 'label' => '障害の程度', 'value' => $dep['failure_judgment'] ?? '', 'maxlength' => 50],
                ['name' => "fuyo[{$fuyoNo}][failure_certificate_file]", 'label' => '障害者手帳の写し（お持ちの場合のみ添付）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $dep['failure_certificate_original_name'] ?? ''],
                ['name' => "fuyo[{$fuyoNo}][deduction_target]", 'label' => '控除の対象にする（もう扶養していない場合は外してください）', 'type' => 'checkbox', 'checked' => (bool) (int) ($dep['deduction_target'] ?? 1)],
                ['name' => "fuyo[{$fuyoNo}][widow]", 'label' => '寡婦・ひとり親に該当', 'type' => 'checkbox', 'checked' => (bool) (int) ($dep['widow'] ?? 0)],
                ],
                'editable' => $editable,
                ])
                @empty
                <p class="pr-current-value">登録されている扶養親族はありません。</p>
                @endforelse

                <div class="pr-toggle pr-gate">
                    <input type="checkbox" class="pr-toggle-input" id="fuyo-add-gate" value="1" {{ $editable ? '' : 'disabled' }}>
                    <label for="fuyo-add-gate" class="pr-toggle-label">{{ empty($dependentRows) ? '扶養親族はいますか？（いる場合はチェックして登録）' : '扶養親族が増えましたか？（増えた場合はチェックして登録）' }}</label>
                    <div class="pr-toggle-fields">
                        <div id="fuyo-add-rows">
                        </div>

                        @if ($editable)
                        <button type="button" class="btn pr-add-more-btn" id="fuyo-add-more-btn">＋ 扶養親族を追加</button>
                        @endif

                        <template id="fuyo-add-template">
                            @include('staff_portal.profile_request.sections._confirm_or_change', [
                            'sectionKey' => 'fuyo-add-__SLOT__',
                            'title' => '扶養を追加',
                            'currentItems' => [],
                            'toggleLabel' => 'この欄に扶養を追加する',
                            'changedFieldName' => 'add[__SLOT__][enabled]',
                            'changedChecked' => true,
                            'fields' => [
                            ['name' => 'add[__SLOT__][fuyo_name]', 'label' => '氏名', 'value' => '', 'maxlength' => 50],
                            ['name' => 'add[__SLOT__][fuyo_name_furi]', 'label' => 'フリガナ', 'value' => '', 'maxlength' => 50],
                            ['name' => 'add[__SLOT__][fuyo_relationship]', 'label' => '続柄', 'value' => '', 'maxlength' => 50],
                            ['name' => 'add[__SLOT__][fuyo_birthday]', 'label' => '生年月日', 'type' => 'date', 'value' => ''],
                            ['name' => 'add[__SLOT__][fuyo_address]', 'label' => '住所', 'value' => '', 'maxlength' => 255],
                            ['name' => 'add[__SLOT__][fuyo_sex]', 'label' => '性別', 'type' => 'select', 'options' => ['' => '選択', '男' => '男', '女' => '女'], 'value' => ''],
                            ['name' => 'add[__SLOT__][kyojyu]', 'label' => '同居／別居', 'type' => 'select', 'options' => ['' => '選択', '同居' => '同居', '別居' => '別居'], 'value' => ''],
                            ['name' => 'add[__SLOT__][fuyo_shunyu]', 'label' => '年間収入見込み（円）', 'type' => 'number', 'value' => 0],
                            ['name' => 'add[__SLOT__][has_disability]', 'label' => '障害者手帳をお持ちですか', 'type' => 'checkbox', 'checked' => false],
                            ['name' => 'add[__SLOT__][failure_notebook]', 'label' => '障害者手帳の種類', 'value' => '', 'maxlength' => 50],
                            ['name' => 'add[__SLOT__][failure_judgment]', 'label' => '障害の程度', 'value' => '', 'maxlength' => 50],
                            ['name' => 'add[__SLOT__][failure_certificate_file]', 'label' => '障害者手帳の写し（お持ちの場合のみ添付）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => ''],
                            ['name' => 'add[__SLOT__][deduction_target]', 'label' => '控除の対象にする', 'type' => 'checkbox', 'checked' => true],
                            ['name' => 'add[__SLOT__][widow]', 'label' => '寡婦・ひとり親に該当', 'type' => 'checkbox', 'checked' => false],
                            ],
                            'editable' => $editable,
                            ])
                        </template>
                    </div>
                </div>

                @if ($editable)
                <div class="pr-section-actions">
                    <button type="submit" class="btn pr-btn-save">扶養の申告を保存</button>
                </div>
                @endif
            </form>

            @if ($editable)
            <div class="pr-submit-area">
                <form method="post" action="{{ route('profile_request.submit') }}" onsubmit="return confirm('保存した内容で提出します。提出後は事務所の確認まで編集できません。よろしいですか？');">
                    @csrf
                    <button type="submit" class="btn pr-btn-submit">この内容で提出する</button>
                </form>
                <p class="pr-submit-note">本人情報・通勤経路・扶養、それぞれ保存してから提出してください。</p>
            </div>
            @endif
        </section>
    </main>

    <script>
        (function() {
            function setupAddMore(containerId, buttonId, templateId, startIndex) {
                var container = document.getElementById(containerId);
                var button = document.getElementById(buttonId);
                var template = document.getElementById(templateId);
                if (!container || !button || !template) return;

                var nextIndex = startIndex;

                button.addEventListener('click', function() {
                    var html = template.innerHTML.split('__SLOT__').join(String(nextIndex));
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    container.appendChild(wrapper);
                    nextIndex++;
                });
            }

            setupAddMore('fuyo-add-rows', 'fuyo-add-more-btn', 'fuyo-add-template', 1);
        })();
    </script>
</body>

</html>
