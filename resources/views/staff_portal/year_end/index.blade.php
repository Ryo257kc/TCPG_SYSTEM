<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 年末調整</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">

    <style>
        .year-end-status {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .year-end-return-note {
            background: rgba(255, 220, 150, 0.3);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        .year-end-section {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 16px;
        }

        .year-end-section-title {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .year-end-current-label {
            color: var(--sub);
            font-size: 12px;
            margin: 0 0 4px;
        }

        .year-end-current-value {
            margin: 0 0 2px;
        }

        .year-end-toggle {
            margin-top: 10px;
        }

        .year-end-toggle-fields {
            display: none;
            margin-top: 10px;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .year-end-toggle-input:checked~.year-end-toggle-fields {
            display: grid;
        }

        .year-end-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .year-end-field-label {
            font-size: 12px;
            color: var(--sub);
        }

        .year-end-field input,
        .year-end-field select {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px 10px;
        }

        .year-end-field input[type="checkbox"] {
            width: auto;
            align-self: flex-start;
        }

        .year-end-subsection-title {
            font-size: 16px;
            margin: 20px 0 8px;
        }

        .year-end-nested-toggle {
            margin-top: 10px;
            padding-left: 14px;
            border-left: 2px solid var(--line);
        }

        .year-end-prevjob-detail {
            display: grid;
            margin-top: 10px;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        #prevjob-submitted:checked~.year-end-prevjob-detail {
            display: none;
        }

        .year-end-note {
            font-size: 12px;
            color: var(--sub);
            margin: 4px 0;
        }

        .year-end-gate {
            border: 1px dashed var(--line);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 14px;
        }

        .year-end-section-actions {
            margin-top: 10px;
            text-align: right;
        }

        .year-end-step {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 32px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--brand, #b5793c);
        }

        .year-end-step:first-of-type {
            margin-top: 20px;
        }

        .year-end-step-number {
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

        .year-end-step-title {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
        }

        .year-end-btn-save {
            display: block;
            margin-left: auto;
            padding: 6px 14px;
            font-size: 13px;
        }

        .year-end-submit-area {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 2px solid var(--line);
            text-align: center;
        }

        .year-end-btn-submit {
            display: inline-block;
            padding: 14px 48px;
            font-size: 16px;
            font-weight: 700;
        }

        .year-end-submit-note {
            margin-top: 8px;
            font-size: 12px;
            color: var(--sub);
        }

        .year-end-add-more-btn {
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
                <h2 class="content-title">{{ $targetYear }}年分 年末調整</h2>
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

            <p class="year-end-status">申請状況：{{ $statusLabel }}</p>

            @if (($application['application_status'] ?? '') === 'returned' && !empty($application['return_note']))
            <div class="year-end-return-note">
                <strong>差戻し理由</strong>
                <p>{{ $application['return_note'] }}</p>
            </div>
            @endif

            <div class="year-end-step">
                <span class="year-end-step-number">1</span>
                <h3 class="year-end-step-title">本人情報</h3>
            </div>
            <form method="post" action="{{ route('year_end_adjustment.personal_info.update') }}">
                @csrf
                @include('staff_portal.year_end.sections._confirm_or_change', [
                'sectionKey' => 'personal-info',
                'title' => '',
                'currentItems' => [
                ['label' => '氏名', 'value' => $currentStaffName],
                ['label' => '氏名フリガナ', 'value' => $currentStaffNameFuri],
                ['label' => '生年月日', 'value' => $currentBirthday],
                ['label' => '住所', 'value' => $currentAddress],
                ['label' => '住所フリガナ', 'value' => $currentAddressFuri],
                ['label' => '世帯主の氏名', 'value' => $currentHeadHouse],
                ['label' => '世帯主との続柄', 'value' => $currentRelationship],
                ],
                'toggleLabel' => '内容を確認・修正する（登録内容に間違いがある場合や、世帯主情報を入力する場合も含む）',
                'changedFieldName' => 'personal_info_changed',
                'changedChecked' => (bool) ($application['personal_info_changed'] ?? false),
                'fields' => [
                ['name' => 'new_staff_name', 'label' => '氏名', 'value' => old('new_staff_name', $application['new_staff_name'] ?? $currentStaffName), 'maxlength' => 50],
                ['name' => 'new_staff_name_furi', 'label' => '氏名フリガナ', 'value' => old('new_staff_name_furi', $application['new_staff_name_furi'] ?? $currentStaffNameFuri), 'maxlength' => 50],
                ['name' => 'name_change_certificate_file', 'label' => '氏名変更の証憑（戸籍謄本・婚姻届受理証明書等。氏名を変更する場合のみ必須）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $application['name_change_certificate_original_name'] ?? ''],
                ['name' => 'new_birthday', 'label' => '生年月日', 'type' => 'date', 'value' => old('new_birthday', substr((string) ($application['new_birthday'] ?? $currentBirthday), 0, 10))],
                ['name' => 'new_address', 'label' => '住所', 'value' => old('new_address', $application['new_address'] ?? $currentAddress), 'maxlength' => 255],
                ['name' => 'new_address_furi', 'label' => '住所フリガナ', 'value' => old('new_address_furi', $application['new_address_furi'] ?? $currentAddressFuri), 'maxlength' => 255],
                ['name' => 'address_change_certificate_file', 'label' => '住所変更の証憑（住民票等。住所を変更する場合のみ必須）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $application['address_change_certificate_original_name'] ?? ''],
                ['name' => 'setai_nushi', 'label' => '世帯主の氏名（本人が世帯主の場合は本人の氏名）', 'value' => old('setai_nushi', $application['setai_nushi'] ?? $currentHeadHouse), 'maxlength' => 50],
                ['name' => 'setai_zoku_gara', 'label' => '世帯主との続柄（本人が世帯主の場合は「本人」）', 'value' => old('setai_zoku_gara', $application['setai_zoku_gara'] ?? $currentRelationship), 'maxlength' => 20],
                ['name' => 'hon_shougai_toku', 'label' => '本人が特別障害者に該当する', 'type' => 'checkbox', 'checked' => (bool) (int) ($currentNenTyo['hon_shougai_toku'] ?? 0)],
                ['name' => 'hon_shougai_ta', 'label' => '本人が一般障害者に該当する', 'type' => 'checkbox', 'checked' => (bool) (int) ($currentNenTyo['hon_shougai_ta'] ?? 0)],
                ['name' => 'disability_certificate_file', 'label' => '障害者手帳の写し（特別障害者・一般障害者のいずれかに該当する場合は必須）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $currentNenTyo['disability_certificate_original_name'] ?? ''],
                ['name' => 'hitori_oya', 'label' => '本人がひとり親に該当する', 'type' => 'checkbox', 'checked' => (bool) (int) ($currentNenTyo['hitori_oya'] ?? 0)],
                ['name' => 'kafu', 'label' => '本人が寡婦に該当する（ひとり親に該当しない場合のみ）', 'type' => 'checkbox', 'checked' => (bool) (int) ($currentNenTyo['kafu'] ?? 0)],
                ['name' => 'student', 'label' => '本人が勤労学生に該当する', 'type' => 'checkbox', 'checked' => (bool) (int) ($currentNenTyo['student'] ?? 0)],
                ],
                'editable' => $editable,
                ])

                @if ($editable)
                <div class="year-end-section-actions">
                    <button type="submit" class="btn year-end-btn-save">本人情報の申告を保存</button>
                </div>
                @endif


                <div class="year-end-step">
                    <span class="year-end-step-number">2</span>
                    <h3 class="year-end-step-title">前職（今年、他社から転職してきた場合）</h3>
                </div>
                <form method="post" action="{{ route('year_end_adjustment.previous_job.update') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="year-end-section">

                        @if (($currentNenTyo['zen_syamei'] ?? '') !== '' || ($currentNenTyo['zen_add'] ?? '') !== '' || ($currentNenTyo['zen_tai_date'] ?? '') !== '')
                        <div class="year-end-current">
                            <p class="year-end-current-label">現在の登録内容</p>
                            @if (($currentNenTyo['zen_syamei'] ?? '') !== '')
                            <p class="year-end-current-value">前職の会社名：{{ $currentNenTyo['zen_syamei'] }}</p>
                            @endif
                            @if (($currentNenTyo['zen_add'] ?? '') !== '')
                            <p class="year-end-current-value">前職の所在地：{{ $currentNenTyo['zen_add'] }}</p>
                            @endif
                            @if (($currentNenTyo['zen_tai_date'] ?? '') !== '')
                            <p class="year-end-current-value">退職日：{{ substr((string) $currentNenTyo['zen_tai_date'], 0, 10) }}</p>
                            @endif
                        </div>
                        @endif

                        <div class="year-end-toggle">
                            <input type="checkbox" class="year-end-toggle-input" id="prevjob-has" name="previous_job_withholding_changed" value="1" {{ (bool) ($application['previous_job_withholding_changed'] ?? false) ? 'checked' : '' }} {{ $editable ? '' : 'disabled' }}>
                            <label for="prevjob-has" class="year-end-toggle-label">今年、当社以外に収入があった場合はチェック（アルバイトなども含む）</label>

                            <div class="year-end-toggle-fields">
                                <div class="year-end-toggle year-end-nested-toggle">
                                    <input type="checkbox" class="year-end-toggle-input" id="prevjob-submitted" name="previous_job_already_submitted" value="1" {{ (bool) ($application['previous_job_already_submitted'] ?? false) ? 'checked' : '' }} {{ $editable ? '' : 'disabled' }}>
                                    <label for="prevjob-submitted" class="year-end-toggle-label">前職の源泉徴収票は、入社時に事務所へ提出済みですか？</label>

                                    <div class="year-end-prevjob-detail">
                                        <p class="year-end-note">「提出済み」にチェックした場合、以下の入力は不要です。</p>
                                        @foreach ([
                                        ['name' => 'zen_syamei', 'label' => '前職の会社名', 'value' => $currentNenTyo['zen_syamei'] ?? '', 'maxlength' => 60],
                                        ['name' => 'zen_add', 'label' => '前職の所在地', 'value' => $currentNenTyo['zen_add'] ?? '', 'maxlength' => 120],
                                        ['name' => 'zen_tai_date', 'label' => '退職日', 'type' => 'date', 'value' => substr((string) ($currentNenTyo['zen_tai_date'] ?? ''), 0, 10)],
                                        ['name' => 'zen_shotoku', 'label' => '前職の給与収入額（源泉徴収票の支払金額）', 'type' => 'number', 'value' => (int) ($currentNenTyo['zen_shotoku'] ?? 0)],
                                        ['name' => 'zen_syaho_kou', 'label' => '前職の社会保険料等の金額', 'type' => 'number', 'value' => (int) ($currentNenTyo['zen_syaho_kou'] ?? 0)],
                                        ['name' => 'zen_kyuyo_tax', 'label' => '前職の源泉徴収税額', 'type' => 'number', 'value' => (int) ($currentNenTyo['zen_kyuyo_tax'] ?? 0)],
                                        ['name' => 'zen_bonus_tax', 'label' => '前職の賞与分源泉徴収税額', 'type' => 'number', 'value' => (int) ($currentNenTyo['zen_bonus_tax'] ?? 0)],
                                        ] as $field)
                                        <div class="year-end-field">
                                            <label class="year-end-field-label">{{ $field['label'] }}</label>
                                            <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] }}" value="{{ $field['value'] }}" @if(!empty($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif {{ $editable ? '' : 'disabled' }}>
                                        </div>
                                        @endforeach
                                        <div class="year-end-field">
                                            <label class="year-end-field-label">前職の源泉徴収票（画像またはPDF）</label>
                                            <input type="file" name="previous_job_certificate_file" accept="image/*,.pdf" {{ $editable ? '' : 'disabled' }}>
                                            @if (!empty($currentNenTyo['previous_job_certificate_original_name']))
                                            <p class="year-end-field-current-file">現在のファイル：{{ $currentNenTyo['previous_job_certificate_original_name'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($editable)
                    <div class="year-end-section-actions">
                        <button type="submit" class="btn year-end-btn-save">前職の申告を保存</button>
                    </div>
                    @endif
                </form>

                <div class="year-end-step">
                    <span class="year-end-step-number">3</span>
                    <h3 class="year-end-step-title">配偶者</h3>
                </div>
                <form method="post" action="{{ route('year_end_adjustment.spouse.update') }}">
                    @csrf
                    @php
                    $spouseHasCurrent = $spouseFuyoRow !== null;
                    @endphp
                    @include('staff_portal.year_end.sections._confirm_or_change', [
                    'sectionKey' => 'spouse',
                    'title' => '',
                    'currentItems' => $spouseHasCurrent ? [
                    ['label' => '氏名', 'value' => (string) ($spouseFuyoRow['fuyo_name'] ?? '')],
                    ['label' => 'フリガナ', 'value' => (string) ($spouseFuyoRow['fuyo_name_furi'] ?? '')],
                    ['label' => '生年月日', 'value' => substr((string) ($spouseFuyoRow['fuyo_birthday'] ?? ''), 0, 10)],
                    ['label' => '住所', 'value' => (string) ($spouseFuyoRow['fuyo_address'] ?? '')],
                    ['label' => '年間収入見込み', 'value' => number_format((float) ($spouseFuyoRow['fuyo_shunyu'] ?? 0))],
                    ['label' => '控除対象', 'value' => ((string) ($spouseFuyoRow['deduction_target'] ?? '')) === '1' ? '対象' : '対象外'],
                    ] : [],
                    'toggleLabel' => $spouseHasCurrent ? '変わった' : '配偶者はいますか？（いる場合はチェックして入力）',
                    'changedFieldName' => 'spouse_engaged',
                    'changedChecked' => false,
                    'fields' => [
                    ['name' => 'fuyo_name', 'label' => '氏名', 'value' => $spouseFuyoRow['fuyo_name'] ?? '', 'maxlength' => 50],
                    ['name' => 'fuyo_name_furi', 'label' => 'フリガナ', 'value' => $spouseFuyoRow['fuyo_name_furi'] ?? '', 'maxlength' => 50],
                    ['name' => 'fuyo_birthday', 'label' => '生年月日', 'type' => 'date', 'value' => substr((string) ($spouseFuyoRow['fuyo_birthday'] ?? ''), 0, 10)],
                    ['name' => 'fuyo_address', 'label' => '住所', 'value' => $spouseFuyoRow['fuyo_address'] ?? '', 'maxlength' => 255],
                    ['name' => 'fuyo_shunyu', 'label' => '配偶者の年間収入見込み（給与収入額、源泉徴収票の支払金額。所得ではありません）', 'type' => 'number', 'value' => (int) ($spouseFuyoRow['fuyo_shunyu'] ?? 0)],
                    ['name' => 'next_year_fuyo_shunyu', 'label' => '来年の配偶者の年間収入見込み（変わる予定がある場合のみ入力。空欄なら今年と同じ額で来年分を登録します）', 'type' => 'number', 'value' => ''],
                    ['name' => 'deduction_target', 'label' => '配偶者控除の対象にする（外すと対象外になります）', 'type' => 'checkbox', 'checked' => (bool) (int) ($spouseFuyoRow['deduction_target'] ?? 1)],
                    ],
                    'editable' => $editable,
                    ])

                    @if ($editable)
                    <div class="year-end-section-actions">
                        <button type="submit" class="btn year-end-btn-save">配偶者の申告を保存</button>
                    </div>
                    @endif
                </form>
            </form>

            <div class="year-end-step">
                <span class="year-end-step-number">4</span>
                <h3 class="year-end-step-title">扶養親族</h3>
            </div>
            <form method="post" action="{{ route('year_end_adjustment.dependents.update') }}">
                @csrf

                @forelse ($dependentRows as $dep)
                @php
                $fuyoNo = (int) ($dep['fuyo_no'] ?? 0);
                @endphp
                @include('staff_portal.year_end.sections._confirm_or_change', [
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
                <p class="year-end-current-value">登録されている扶養親族はありません。</p>
                @endforelse

                <div class="year-end-toggle year-end-gate">
                    <input type="checkbox" class="year-end-toggle-input" id="fuyo-add-gate" value="1" {{ $editable ? '' : 'disabled' }}>
                    <label for="fuyo-add-gate" class="year-end-toggle-label">{{ empty($dependentRows) ? '扶養親族はいますか？（いる場合はチェックして登録）' : '扶養親族が増えましたか？（増えた場合はチェックして登録）' }}</label>
                    <div class="year-end-toggle-fields">
                        <div id="fuyo-add-rows">
                        </div>

                        @if ($editable)
                        <button type="button" class="btn year-end-add-more-btn" id="fuyo-add-more-btn">＋ 扶養親族を追加</button>
                        @endif

                        <template id="fuyo-add-template">
                            @include('staff_portal.year_end.sections._confirm_or_change', [
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
                <div class="year-end-section-actions">
                    <button type="submit" class="btn year-end-btn-save">扶養の申告を保存</button>
                </div>
                @endif
            </form>




            <div class="year-end-step">
                <span class="year-end-step-number">5</span>
                <h3 class="year-end-step-title">保険料控除（生命保険・地震保険等）</h3>
            </div>
            <form method="post" action="{{ route('year_end_adjustment.insurance.update') }}" enctype="multipart/form-data">
                @csrf

                @forelse ($hokenRows as $hoken)
                @php
                $hokenNo = (int) ($hoken['hoken_no'] ?? 0);
                $hokenConfirmed = (int) ($hoken['checked_flag'] ?? 0) === 1;
                @endphp
                @include('staff_portal.year_end.sections._confirm_or_change', [
                'sectionKey' => 'hoken-' . $hokenNo,
                'title' => ($hoken['insurance_company'] ?? '（保険会社未登録）') . '（' . ($hoken['category'] ?? '種別未登録') . '）',
                'currentItems' => [
                ['label' => '保険会社', 'value' => (string) ($hoken['insurance_company'] ?? '')],
                ['label' => '区分', 'value' => (string) ($hoken['category'] ?? '')],
                ['label' => '適用制度', 'value' => (string) ($hoken['applied_system'] ?? '')],
                ['label' => '申告額', 'value' => $hoken['declared_amount'] !== null ? number_format((float) $hoken['declared_amount']) : ''],
                ['label' => '保険種類', 'value' => (string) ($hoken['insurance_type'] ?? '')],
                ['label' => '保険期間', 'value' => (string) ($hoken['insurance_period'] ?? '')],
                ],
                'changedFieldName' => "hoken[{$hokenNo}][changed]",
                'changedChecked' => false,
                'fields' => [
                ['name' => "hoken[{$hokenNo}][insurance_company]", 'label' => '保険会社', 'value' => $hoken['insurance_company'] ?? '', 'maxlength' => 50],
                ['name' => "hoken[{$hokenNo}][category]", 'label' => '区分', 'type' => 'select', 'options' => ['' => '選択', '一般保険' => '一般保険', '介護保険' => '介護保険', '社会保険' => '社会保険', '地震保険' => '地震保険', '年金保険' => '年金保険'], 'value' => $hoken['category'] ?? ''],
                ['name' => "hoken[{$hokenNo}][applied_system]", 'label' => '適用制度', 'type' => 'select', 'options' => ['' => '選択', '新制度' => '新制度', '旧制度' => '旧制度'], 'value' => $hoken['applied_system'] ?? ''],
                ['name' => "hoken[{$hokenNo}][declared_amount]", 'label' => '申告額（円、証憑記載額）', 'type' => 'number', 'value' => (int) ($hoken['declared_amount'] ?? 0)],
                ['name' => "hoken[{$hokenNo}][insurance_type]", 'label' => '保険種類', 'value' => $hoken['insurance_type'] ?? '', 'maxlength' => 50],
                ['name' => "hoken[{$hokenNo}][insurance_period]", 'label' => '保険期間', 'value' => $hoken['insurance_period'] ?? '', 'maxlength' => 10],
                ['name' => "hoken[{$hokenNo}][policy_holder_name]", 'label' => '契約者氏名', 'value' => $hoken['policy_holder_name'] ?? '', 'maxlength' => 20],
                ['name' => "hoken[{$hokenNo}][beneficiary_name]", 'label' => '受取人氏名', 'value' => $hoken['beneficiary_name'] ?? '', 'maxlength' => 20],
                ['name' => "hoken[{$hokenNo}][beneficiary_relationship]", 'label' => '受取人続柄', 'value' => $hoken['beneficiary_relationship'] ?? '', 'maxlength' => 10],
                ['name' => "hoken[{$hokenNo}][pension_payment_start_date]", 'label' => '年金支払開始日（年金保険の場合）', 'type' => 'date', 'value' => substr((string) ($hoken['pension_payment_start_date'] ?? ''), 0, 10)],
                ['name' => "hoken[{$hokenNo}][year_end_insurance_note]", 'label' => '備考', 'value' => $hoken['year_end_insurance_note'] ?? ''],
                ['name' => "hoken[{$hokenNo}][certificate_file]", 'label' => '証憑ファイル（差し替える場合のみ選択）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $hoken['certificate_original_name'] ?? ''],
                ],
                'editable' => $editable,
                ])
                @if ($editable)
                <label class="year-end-field" style="flex-direction:row;align-items:center;gap:6px;">
                    <input type="checkbox" name="hoken[{{ $hokenNo }}][remove]" value="1" {{ $hokenConfirmed ? 'disabled' : '' }}>
                    この保険を削除する{{ $hokenConfirmed ? '（事務所確認済みのため削除できません。事務所へご連絡ください）' : '' }}
                </label>
                @endif
                @empty
                <p class="year-end-current-value">登録されている保険料控除情報はありません。</p>
                @endforelse

                <div class="year-end-toggle year-end-gate">
                    <input type="checkbox" class="year-end-toggle-input" id="hoken-add-gate" value="1" {{ $editable ? '' : 'disabled' }}>
                    <label for="hoken-add-gate" class="year-end-toggle-label">{{ empty($hokenRows) ? '生命保険・地震保険等の保険料控除はありますか？（基礎控除のみの方はチェック不要です）' : '保険料控除が増えましたか？（増えた場合はチェックして登録）' }}</label>
                    <div class="year-end-toggle-fields">
                        @if ($hasPreviousYearInsurance && $editable)
                        <p class="year-end-note">前年の保険料控除データがあります。内容をコピーしてから編集することもできます（証憑は毎年再添付が必要です）。</p>
                        <button type="submit" formaction="{{ route('year_end_adjustment.insurance.copy_previous_year') }}" formnovalidate class="btn">前年のデータをコピーする</button>
                        @endif

                        <div id="hoken-add-rows">
                        </div>

                        @if ($editable)
                        <button type="button" class="btn year-end-add-more-btn" id="hoken-add-more-btn">＋ 保険をもう一件追加</button>
                        @endif

                        <template id="hoken-add-template">
                            @include('staff_portal.year_end.sections._confirm_or_change', [
                            'sectionKey' => 'hoken-add-__SLOT__',
                            'title' => '保険を追加',
                            'currentItems' => [],
                            'toggleLabel' => 'この欄に保険を追加する',
                            'changedFieldName' => 'hoken_add[__SLOT__][enabled]',
                            'changedChecked' => true,
                            'fields' => [
                            ['name' => 'hoken_add[__SLOT__][insurance_company]', 'label' => '保険会社', 'value' => '', 'maxlength' => 50],
                            ['name' => 'hoken_add[__SLOT__][category]', 'label' => '区分', 'type' => 'select', 'options' => ['' => '選択', '一般保険' => '一般保険', '介護保険' => '介護保険', '社会保険' => '社会保険', '地震保険' => '地震保険', '年金保険' => '年金保険'], 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][applied_system]', 'label' => '適用制度', 'type' => 'select', 'options' => ['' => '選択', '新制度' => '新制度', '旧制度' => '旧制度'], 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][declared_amount]', 'label' => '申告額（円、証憑記載額）', 'type' => 'number', 'value' => 0],
                            ['name' => 'hoken_add[__SLOT__][insurance_type]', 'label' => '保険種類', 'value' => '', 'maxlength' => 50],
                            ['name' => 'hoken_add[__SLOT__][insurance_period]', 'label' => '保険期間', 'value' => '', 'maxlength' => 10],
                            ['name' => 'hoken_add[__SLOT__][policy_holder_name]', 'label' => '契約者氏名', 'value' => '', 'maxlength' => 20],
                            ['name' => 'hoken_add[__SLOT__][beneficiary_name]', 'label' => '受取人氏名', 'value' => '', 'maxlength' => 20],
                            ['name' => 'hoken_add[__SLOT__][beneficiary_relationship]', 'label' => '受取人続柄', 'value' => '', 'maxlength' => 10],
                            ['name' => 'hoken_add[__SLOT__][pension_payment_start_date]', 'label' => '年金支払開始日（年金保険の場合）', 'type' => 'date', 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][year_end_insurance_note]', 'label' => '備考', 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][certificate_file]', 'label' => '証憑ファイル', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => ''],
                            ],
                            'editable' => $editable,
                            ])
                        </template>
                    </div>
                </div>

                @if ($editable)
                <div class="year-end-section-actions">
                    <button type="submit" class="btn year-end-btn-save">保険料控除の申告を保存</button>
                </div>
                @endif
            </form>


            <form method="post" action="{{ route('year_end_adjustment.housing_loan.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="year-end-step">
                    <span class="year-end-step-number">6</span>
                    <h3 class="year-end-subsection-title">住宅ローン控除</h3>
                </div>

                @include('staff_portal.year_end.sections._confirm_or_change', [
                'sectionKey' => 'housing-loan',
                'title' => '住宅借入金等特別控除',
                'currentItems' => [
                ['label' => '登録済みの控除額', 'value' => ($currentNenTyo['jyu_kari_kou'] ?? null) !== null ? number_format((float) $currentNenTyo['jyu_kari_kou']) . '円' : ''],
                ['label' => '住宅控除区分', 'value' => (string) ($currentNenTyo['jyu_kojyo_kubun'] ?? '')],
                ['label' => '特定取得区分', 'value' => (string) ($currentNenTyo['toku_kubun'] ?? '')],
                ['label' => '控除区分番号', 'value' => (string) ($currentNenTyo['koujyo_kubun_no'] ?? '')],
                ],
                'toggleLabel' => '今年、住宅ローン控除を受ける',
                'changedFieldName' => 'housing_loan_changed',
                'changedChecked' => (bool) ($application['housing_loan_changed'] ?? false),
                'fields' => [
                ['name' => 'housing_loan_declared_amount', 'label' => '控除額（円）。証憑（住宅借入金等特別控除申告書・残高証明書）に記載の金額をそのまま入力してください。計算は不要です。', 'type' => 'number', 'value' => (int) ($currentNenTyo['jyu_kari_kou'] ?? 0)],
                ['name' => 'jyu_kojyo_kubun', 'label' => '住宅控除区分（証憑に記載の区分をそのまま入力）', 'value' => $currentNenTyo['jyu_kojyo_kubun'] ?? '', 'maxlength' => 50],
                ['name' => 'toku_kubun', 'label' => '特定取得区分（証憑に記載の区分をそのまま入力）', 'value' => $currentNenTyo['toku_kubun'] ?? '', 'maxlength' => 50],
                ['name' => 'koujyo_kubun_no', 'label' => '控除区分番号（証憑に記載の番号をそのまま入力）', 'value' => $currentNenTyo['koujyo_kubun_no'] ?? '', 'maxlength' => 50],
                ['name' => 'housing_loan_certificate_file', 'label' => '証憑ファイル（必須。画像またはPDF）', 'type' => 'file', 'accept' => 'image/*,.pdf', 'value' => $currentNenTyo['housing_loan_certificate_original_name'] ?? ''],
                ],
                'editable' => $editable,
                ])

                @if ($editable)
                <div class="year-end-section-actions">
                    <button type="submit" class="btn year-end-btn-save">住宅ローン控除の申告を保存</button>
                </div>
                @endif
            </form>

            @if ($editable)
            <div class="year-end-submit-area">
                <form method="post" action="{{ route('year_end_adjustment.submit') }}" onsubmit="return confirm('この内容で提出します。よろしいですか？\n\n各セクションの「保存」は一時保存です。この「提出する」を押すまで事務所には送られません。');">
                    @csrf
                    <button type="submit" class="btn btn_primary year-end-btn-submit">この内容で提出する</button>
                </form>
                <p class="year-end-submit-note">各セクションの「保存」ボタンは一時保存です。すべての入力が終わったら、最後にこの「提出する」ボタンを押してください。</p>
            </div>
            @endif

            <div class="back-row">
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
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
                    while (wrapper.firstChild) {
                        container.appendChild(wrapper.firstChild);
                    }
                    nextIndex++;
                });
            }

            setupAddMore('fuyo-add-rows', 'fuyo-add-more-btn', 'fuyo-add-template', 1);
            setupAddMore('hoken-add-rows', 'hoken-add-more-btn', 'hoken-add-template', 1);
        })();
    </script>
</body>

</html>
