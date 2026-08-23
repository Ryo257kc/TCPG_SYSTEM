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

        .year-end-xml-status {
            font-size: 14px;
            font-weight: 700;
        }

        .year-end-xml-status.is-error {
            color: #b3261e;
            background: #fdecea;
            border: 1px solid #f2b8b5;
            border-radius: 6px;
            padding: 8px 10px;
        }

        .year-end-xml-status.is-success {
            color: #1b5e20;
            background: #edf7ed;
            border: 1px solid #a5d6a7;
            border-radius: 6px;
            padding: 8px 10px;
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

        .year-end-accordion-section {
            border: 1px solid var(--line);
            border-radius: 10px;
            margin: 16px 0;
            overflow: hidden;
        }

        .year-end-accordion-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            list-style: none;
            background: #fafafa;
        }

        .year-end-accordion-header::-webkit-details-marker {
            display: none;
        }

        .year-end-accordion-section[open]>.year-end-accordion-header {
            border-bottom: 1px solid var(--line);
            background: #fff;
        }

        .year-end-accordion-title {
            flex: 1;
            font-size: 16px;
            font-weight: 700;
        }

        .year-end-accordion-badge {
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            white-space: nowrap;
            background: #ececec;
            color: #666;
        }

        .year-end-accordion-badge.is-saved {
            background: #edf7ed;
            color: #1b5e20;
        }

        .year-end-accordion-badge-danger {
            background: #fdecea;
            color: #b3261e;
        }

        .year-end-accordion-body {
            padding: 16px;
        }

        .year-end-accordion-error {
            background: #fdecea;
            border: 1px solid #f2b8b5;
            color: #b3261e;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .year-end-accordion-saving {
            opacity: 0.6;
            pointer-events: none;
        }

        .year-end-required-choice {
            margin-bottom: 12px;
        }

        .year-end-required-choice-options {
            display: flex;
            gap: 10px;
            margin-top: 6px;
        }

        .year-end-required-choice-option {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            font-weight: 700;
        }

        .year-end-required-choice-option:has(input:checked) {
            border-color: var(--brand, #b5793c);
            background: #fbf3ea;
        }

        .year-end-required-choice-option input {
            width: auto;
        }

        .year-end-guide {
            background: #fbf3ea;
            border: 1px solid var(--brand, #b5793c);
            border-radius: 10px;
            padding: 12px 14px;
            margin: 10px 0 20px;
        }

        .year-end-guide p {
            margin: 0;
        }

        .year-end-guide-progress {
            margin-top: 6px !important;
            font-weight: 700;
        }

        .year-end-row-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .year-end-row-header-title {
            min-width: 0;
        }

        .year-end-row-header-title .year-end-section-title {
            margin: 0;
        }

        .year-end-attachment-warning {
            color: #b3261e;
            font-weight: 700;
            font-size: 13px;
            margin: 4px 0 0;
        }

        .year-end-row-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .year-end-row-action-btn {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            background: #fff;
            color: #263445;
        }

        .year-end-row-action-btn:has(input:checked) {
            border-color: var(--brand, #b5793c);
            background: #fbf3ea;
        }

        .year-end-row-action-btn-danger {
            color: #b3261e;
            border-color: #f2b8b5;
        }

        .year-end-row-action-btn-danger:has(input:checked) {
            background: #fdecea;
            border-color: #b3261e;
        }

        .year-end-row-delete-label.is-marked .year-end-row-delete-label-text::after {
            content: "予定（取り消す）";
        }

        .year-end-section:has(.year-end-row-delete-checkbox:checked) {
            opacity: 0.5;
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

            @php
            // 各セクションが一度でも保存されたか（changed系カラムがNULLでないか）を、
            // アコーディオンの初期開閉・バッジ表示に使う。保存済みかどうかだけを見る
            // （中身が「変更なし」でも、保存ボタンを押していれば完了扱い）。
            $yearEndSectionStates = [
            'personal-info' => ($application['personal_info_changed'] ?? null) !== null,
            'previous-job' => ($application['previous_job_withholding_changed'] ?? null) !== null,
            'spouse' => ($application['spouse_changed'] ?? null) !== null,
            'dependents' => ($application['dependents_changed'] ?? null) !== null,
            'insurance' => ($application['insurance_deduction_changed'] ?? null) !== null,
            'housing-loan' => ($application['housing_loan_changed'] ?? null) !== null,
            ];
            // 保険料控除は証憑を毎年再添付必須の仕様なので、アコーディオンが閉じてても
            // 未添付が分かるよう見出しにも出す（扶養親族は新規追加分のみ必須で、
            // 継続分の古いデータまで警告すると紛らわしいため対象外）。
            $yearEndInsuranceMissingCertificate = collect($hokenRows ?? [])->contains(fn($hoken) => empty($hoken['certificate_original_name'] ?? ''));
            $yearEndFirstIncompleteSection = null;
            foreach ($yearEndSectionStates as $sectionKey => $isSaved) {
            if (!$isSaved) {
            $yearEndFirstIncompleteSection = $sectionKey;
            break;
            }
            }

            // まだ順番が来てない（保存済みでも、今の対象でもない）セクションは、
            // 存在自体を見せない。保存が進むごとに次のセクションが初めて表示される形にする。
            $yearEndVisibleSections = [];
            $yearEndReachedIncomplete = false;
            foreach ($yearEndSectionStates as $sectionKey => $isSaved) {
            if ($isSaved) {
            $yearEndVisibleSections[$sectionKey] = true;
            } elseif (!$yearEndReachedIncomplete) {
            $yearEndVisibleSections[$sectionKey] = true;
            $yearEndReachedIncomplete = true;
            } else {
            $yearEndVisibleSections[$sectionKey] = false;
            }
            }
            $yearEndAllSaved = !in_array(false, $yearEndSectionStates, true);
            @endphp

            @if ($editable)
            <div class="year-end-guide">
                <p>1つずつ質問が表示されます。「はい／いいえ」を選んで「保存して次へ」を押すと、次の質問が表示されます。全部答えると、一番下に「提出する」ボタンが出ます。</p>
                <p class="year-end-guide-progress">進み具合：{{ count(array_filter($yearEndSectionStates)) }} / {{ count($yearEndSectionStates) }}</p>
            </div>
            @endif

            @if (($application['application_status'] ?? '') === 'returned' && !empty($application['return_note']))
            <div class="year-end-return-note">
                <strong>差戻し理由</strong>
                <p>{{ $application['return_note'] }}</p>
            </div>
            @endif

            <details class="year-end-accordion-section" data-section="personal-info" data-changed-field="personal_info_changed" {{ $yearEndFirstIncompleteSection === 'personal-info' ? 'open' : '' }} {{ $yearEndVisibleSections['personal-info'] ? '' : 'hidden' }}>
                <summary class="year-end-accordion-header">
                    <span class="year-end-step-number">1</span>
                    <span class="year-end-accordion-title">本人情報</span>
                    <span class="year-end-accordion-badge {{ $yearEndSectionStates['personal-info'] ? 'is-saved' : '' }}" data-badge>{{ $yearEndSectionStates['personal-info'] ? '保存済み' : '未回答' }}</span>
                </summary>
                <div class="year-end-accordion-body">
            <form method="post" action="{{ route('year_end_adjustment.personal_info.update') }}" class="year-end-ajax-form">
                @csrf
                <div class="year-end-accordion-error" data-error hidden></div>
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
                'toggleLabel' => '本人情報（住所・氏名・世帯主・障害者控除等）に変更や訂正はありますか？',
                'changedFieldName' => 'personal_info_changed',
                'changedChecked' => isset($application['personal_info_changed']) ? (bool) $application['personal_info_changed'] : null,
                'requireAnswer' => true,
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
                    <button type="submit" class="btn year-end-btn-save">保存して次へ</button>
                </div>
                @endif
            </form>
                </div>
            </details>

            <details class="year-end-accordion-section" data-section="previous-job" data-changed-field="previous_job_withholding_changed" {{ $yearEndFirstIncompleteSection === 'previous-job' ? 'open' : '' }} {{ $yearEndVisibleSections['previous-job'] ? '' : 'hidden' }}>
                <summary class="year-end-accordion-header">
                    <span class="year-end-step-number">2</span>
                    <span class="year-end-accordion-title">前職（今年、他社から転職してきた場合）</span>
                    <span class="year-end-accordion-badge {{ $yearEndSectionStates['previous-job'] ? 'is-saved' : '' }}" data-badge>{{ $yearEndSectionStates['previous-job'] ? '保存済み' : '未回答' }}</span>
                </summary>
                <div class="year-end-accordion-body">
                <form method="post" action="{{ route('year_end_adjustment.previous_job.update') }}" enctype="multipart/form-data" class="year-end-ajax-form">
                    @csrf
                    <div class="year-end-accordion-error" data-error hidden></div>
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
                            @php
                            $previousJobChanged = isset($application['previous_job_withholding_changed']) ? (bool) $application['previous_job_withholding_changed'] : null;
                            @endphp
                            <div class="year-end-required-choice">
                                <label class="year-end-field-label">今年、当社以外に収入がありましたか？（アルバイトなども含む）</label>
                                <div class="year-end-required-choice-options">
                                    <label class="year-end-required-choice-option">
                                        <input type="radio" class="year-end-toggle-input" name="previous_job_withholding_changed" value="1" {{ $previousJobChanged === true ? 'checked' : '' }} {{ $editable ? 'required' : 'disabled' }}>
                                        はい
                                    </label>
                                    <label class="year-end-required-choice-option">
                                        <input type="radio" class="year-end-toggle-input-no" name="previous_job_withholding_changed" value="0" {{ $previousJobChanged === false ? 'checked' : '' }} {{ $editable ? 'required' : 'disabled' }}>
                                        いいえ
                                    </label>
                                </div>
                            </div>

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
                        <button type="submit" class="btn year-end-btn-save">保存して次へ</button>
                    </div>
                    @endif
                </form>
                </div>
            </details>

            <details class="year-end-accordion-section" data-section="spouse" data-changed-field="spouse_changed" {{ $yearEndFirstIncompleteSection === 'spouse' ? 'open' : '' }} {{ $yearEndVisibleSections['spouse'] ? '' : 'hidden' }}>
                <summary class="year-end-accordion-header">
                    <span class="year-end-step-number">3</span>
                    <span class="year-end-accordion-title">配偶者</span>
                    <span class="year-end-accordion-badge {{ $yearEndSectionStates['spouse'] ? 'is-saved' : '' }}" data-badge>{{ $yearEndSectionStates['spouse'] ? '保存済み' : '未回答' }}</span>
                </summary>
                <div class="year-end-accordion-body">
                <form method="post" action="{{ route('year_end_adjustment.spouse.update') }}" class="year-end-ajax-form">
                    @csrf
                    <div class="year-end-accordion-error" data-error hidden></div>
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
                    'toggleLabel' => $spouseHasCurrent ? '配偶者の情報に変更はありますか？' : '配偶者はいますか？',
                    'changedFieldName' => 'spouse_engaged',
                    'changedChecked' => isset($application['spouse_changed']) ? (bool) $application['spouse_changed'] : null,
                    'requireAnswer' => true,
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
                        <button type="submit" class="btn year-end-btn-save">保存して次へ</button>
                    </div>
                    @endif
                </form>
                </div>
            </details>

            <details class="year-end-accordion-section" data-section="dependents" data-changed-field="dependents_changed" {{ $yearEndFirstIncompleteSection === 'dependents' ? 'open' : '' }} {{ $yearEndVisibleSections['dependents'] ? '' : 'hidden' }}>
                <summary class="year-end-accordion-header">
                    <span class="year-end-step-number">4</span>
                    <span class="year-end-accordion-title">扶養親族</span>
                    <span class="year-end-accordion-badge {{ $yearEndSectionStates['dependents'] ? 'is-saved' : '' }}" data-badge>{{ $yearEndSectionStates['dependents'] ? '保存済み' : '未回答' }}</span>
                </summary>
                <div class="year-end-accordion-body">
            <form method="post" action="{{ route('year_end_adjustment.dependents.update') }}" class="year-end-ajax-form">
                @csrf
                <div class="year-end-accordion-error" data-error hidden></div>

                @php
                $dependentsEngaged = isset($application['dependents_changed']) ? (bool) $application['dependents_changed'] : null;
                @endphp
                <div class="year-end-required-choice">
                    <label class="year-end-field-label">扶養親族について、今年何か変更（増えた・対象から外れた・住所や収入が変わった等）はありますか？</label>
                    <div class="year-end-required-choice-options">
                        <label class="year-end-required-choice-option">
                            <input type="radio" name="dependents_engaged" value="1" {{ $dependentsEngaged === true ? 'checked' : '' }} {{ $editable ? 'required' : 'disabled' }}>
                            はい
                        </label>
                        <label class="year-end-required-choice-option">
                            <input type="radio" name="dependents_engaged" value="0" {{ $dependentsEngaged === false ? 'checked' : '' }} {{ $editable ? 'required' : 'disabled' }}>
                            いいえ
                        </label>
                    </div>
                </div>

                @forelse ($dependentRows as $dep)
                @php
                $fuyoNo = (int) ($dep['fuyo_no'] ?? 0);
                @endphp
                @include('staff_portal.year_end.sections._confirm_or_change', [
                'sectionKey' => 'fuyo-' . $fuyoNo,
                'title' => ($dep['fuyo_name'] ?? '（氏名未登録）') . '（' . ($dep['fuyo_relationship'] ?? '続柄未登録') . '）',
                'rowMode' => true,
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
                    <button type="submit" class="btn year-end-btn-save">保存して次へ</button>
                </div>
                @endif
            </form>
                </div>
            </details>

            <details class="year-end-accordion-section" data-section="insurance" data-changed-field="insurance_deduction_changed" {{ $yearEndFirstIncompleteSection === 'insurance' ? 'open' : '' }} {{ $yearEndVisibleSections['insurance'] ? '' : 'hidden' }}>
                <summary class="year-end-accordion-header">
                    <span class="year-end-step-number">5</span>
                    <span class="year-end-accordion-title">保険料控除（生命保険・地震保険等）</span>
                    @if ($yearEndInsuranceMissingCertificate)
                    <span class="year-end-accordion-badge year-end-accordion-badge-danger">証憑未添付あり</span>
                    @endif
                    <span class="year-end-accordion-badge {{ $yearEndSectionStates['insurance'] ? 'is-saved' : '' }}" data-badge>{{ $yearEndSectionStates['insurance'] ? '保存済み' : '未回答' }}</span>
                </summary>
                <div class="year-end-accordion-body">
            <form method="post" action="{{ route('year_end_adjustment.insurance.update') }}" enctype="multipart/form-data" class="year-end-ajax-form">
                @csrf
                <div class="year-end-accordion-error" data-error hidden></div>

                @php
                // insurance_deduction_changedが未確定でも、既に行がある（前年からのコピー等）なら
                // 「はい」を初期表示にする。ただし答えたことにはならず、証憑を確認して
                // 「保存して次へ」を押すまでは完了扱いにならない。
                $insuranceEngaged = isset($application['insurance_deduction_changed']) ? (bool) $application['insurance_deduction_changed'] : (!empty($hokenRows) ? true : null);
                @endphp
                <div class="year-end-required-choice">
                    <label class="year-end-field-label">保険料控除（生命保険・地震保険・社会保険・小規模企業共済等）について、今年何か変更はありますか？</label>
                    <div class="year-end-required-choice-options">
                        <label class="year-end-required-choice-option">
                            <input type="radio" name="insurance_engaged" value="1" {{ $insuranceEngaged === true ? 'checked' : '' }} {{ $editable ? 'required' : 'disabled' }}>
                            はい
                        </label>
                        <label class="year-end-required-choice-option">
                            <input type="radio" name="insurance_engaged" value="0" {{ $insuranceEngaged === false ? 'checked' : '' }} {{ $editable ? 'required' : 'disabled' }}>
                            いいえ
                        </label>
                    </div>
                </div>

                @forelse ($hokenRows as $hoken)
                @php
                $hokenNo = (int) ($hoken['hoken_no'] ?? 0);
                $hokenConfirmed = (int) ($hoken['checked_flag'] ?? 0) === 1;
                $hokenMissingCertificate = empty($hoken['certificate_original_name'] ?? '');
                @endphp
                @include('staff_portal.year_end.sections._confirm_or_change', [
                'sectionKey' => 'hoken-' . $hokenNo,
                'title' => ($hoken['insurance_company'] ?? '（保険会社未登録）') . '（' . ($hoken['category'] ?? '種別未登録') . '）',
                'rowMode' => true,
                'missingAttachmentWarning' => $hokenMissingCertificate ? '証憑ファイルが未添付です' : null,
                'deleteFieldName' => "hoken[{$hokenNo}][remove]",
                'deleteDisabled' => $hokenConfirmed,
                'deleteDisabledReason' => $hokenConfirmed ? '事務所確認済みのため削除できません。事務所へご連絡ください。' : null,
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
                ['name' => "hoken[{$hokenNo}][category]", 'label' => '区分', 'type' => 'select', 'options' => ['' => '選択', '一般保険' => '一般保険', '介護保険' => '介護保険', '社会保険' => '社会保険', '地震保険' => '地震保険', '年金保険' => '年金保険', '小規模企業共済（機構）' => '小規模企業共済（機構）', '企業型年金（DC）' => '企業型年金（DC）', '個人型年金（iDeCo）' => '個人型年金（iDeCo）'], 'value' => $hoken['category'] ?? ''],
                ['name' => "hoken[{$hokenNo}][applied_system]", 'label' => '適用制度', 'type' => 'select', 'options' => ['' => '選択', '新制度' => '新制度', '旧制度' => '旧制度'], 'value' => $hoken['applied_system'] ?? ''],
                ['name' => "hoken[{$hokenNo}][declared_amount]", 'label' => '申告額（円、証憑記載額）', 'type' => 'number', 'value' => (int) ($hoken['declared_amount'] ?? 0)],
                ['name' => "hoken[{$hokenNo}][insurance_type]", 'label' => '保険種類', 'value' => $hoken['insurance_type'] ?? '', 'maxlength' => 50],
                ['name' => "hoken[{$hokenNo}][insurance_period]", 'label' => '保険期間', 'value' => $hoken['insurance_period'] ?? '', 'maxlength' => 10],
                ['name' => "hoken[{$hokenNo}][policy_holder_name]", 'label' => '契約者氏名', 'value' => $hoken['policy_holder_name'] ?? '', 'maxlength' => 20],
                ['name' => "hoken[{$hokenNo}][beneficiary_name]", 'label' => '受取人氏名', 'value' => $hoken['beneficiary_name'] ?? '', 'maxlength' => 20],
                ['name' => "hoken[{$hokenNo}][beneficiary_relationship]", 'label' => '受取人続柄', 'value' => $hoken['beneficiary_relationship'] ?? '', 'maxlength' => 10],
                ['name' => "hoken[{$hokenNo}][pension_payment_start_date]", 'label' => '年金支払開始日（年金保険の場合）', 'type' => 'date', 'value' => substr((string) ($hoken['pension_payment_start_date'] ?? ''), 0, 10)],
                ['name' => "hoken[{$hokenNo}][year_end_insurance_note]", 'label' => '備考', 'value' => $hoken['year_end_insurance_note'] ?? ''],
                ['name' => "hoken[{$hokenNo}][certificate_file]", 'label' => '証憑ファイル（差し替える場合のみ選択）', 'type' => 'file', 'accept' => 'image/*,.pdf,.xml', 'value' => $hoken['certificate_original_name'] ?? ''],
                ],
                'editable' => $editable,
                ])
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

                        {{--
                            スタッフ側のXML自動入力は一旦非公開（2026-08-22）。
                            署名検証（DOMNode::C14N()+openssl_verifyでの改ざん検知）を実装するまでの間、
                            手で書き換えたXMLでもそのまま通ってしまう状態のため、本人が自己申告する
                            スタッフ側では出さない。管理側（事務所が証明書を直接受け取った場合の入力用）
                            は引き続き有効。コードは残したままなので、署名検証を実装したら
                            @if ($editable) を戻すだけで復活できる。
                        --}}
                        @if (false)
                        <div class="year-end-field">
                            <label class="year-end-field-label">電子的控除証明書（XML）から自動入力</label>
                            <input type="file" id="hoken-xml-input" accept=".xml,text/xml,application/xml">
                            <p class="year-end-note">保険会社から電子データ（XMLファイル）で証明書を受け取った場合、ここに選択すると内容を読み取って下に自動で行を追加します。ファイル自体も証憑として保存されます。1つのXMLに複数の契約が入っている場合は、契約ごとに行を分けて追加します。</p>
                            <p class="year-end-note year-end-xml-status" id="hoken-xml-status"></p>
                        </div>
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
                            ['name' => 'hoken_add[__SLOT__][category]', 'label' => '区分', 'type' => 'select', 'options' => ['' => '選択', '一般保険' => '一般保険', '介護保険' => '介護保険', '社会保険' => '社会保険', '地震保険' => '地震保険', '年金保険' => '年金保険', '小規模企業共済（機構）' => '小規模企業共済（機構）', '企業型年金（DC）' => '企業型年金（DC）', '個人型年金（iDeCo）' => '個人型年金（iDeCo）'], 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][applied_system]', 'label' => '適用制度', 'type' => 'select', 'options' => ['' => '選択', '新制度' => '新制度', '旧制度' => '旧制度'], 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][declared_amount]', 'label' => '申告額（円、証憑記載額）', 'type' => 'number', 'value' => 0],
                            ['name' => 'hoken_add[__SLOT__][insurance_type]', 'label' => '保険種類', 'value' => '', 'maxlength' => 50],
                            ['name' => 'hoken_add[__SLOT__][insurance_period]', 'label' => '保険期間', 'value' => '', 'maxlength' => 10],
                            ['name' => 'hoken_add[__SLOT__][policy_holder_name]', 'label' => '契約者氏名', 'value' => '', 'maxlength' => 20],
                            ['name' => 'hoken_add[__SLOT__][beneficiary_name]', 'label' => '受取人氏名', 'value' => '', 'maxlength' => 20],
                            ['name' => 'hoken_add[__SLOT__][beneficiary_relationship]', 'label' => '受取人続柄', 'value' => '', 'maxlength' => 10],
                            ['name' => 'hoken_add[__SLOT__][pension_payment_start_date]', 'label' => '年金支払開始日（年金保険の場合）', 'type' => 'date', 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][year_end_insurance_note]', 'label' => '備考', 'value' => ''],
                            ['name' => 'hoken_add[__SLOT__][certificate_file]', 'label' => '証憑ファイル', 'type' => 'file', 'accept' => 'image/*,.pdf,.xml', 'value' => ''],
                            ],
                            'editable' => $editable,
                            ])
                        </template>
                    </div>
                </div>

                @if ($editable)
                <div class="year-end-section-actions">
                    <button type="submit" class="btn year-end-btn-save">保存して次へ</button>
                </div>
                @endif
            </form>
                </div>
            </details>

            <details class="year-end-accordion-section" data-section="housing-loan" data-changed-field="housing_loan_changed" {{ $yearEndFirstIncompleteSection === 'housing-loan' ? 'open' : '' }} {{ $yearEndVisibleSections['housing-loan'] ? '' : 'hidden' }}>
                <summary class="year-end-accordion-header">
                    <span class="year-end-step-number">6</span>
                    <span class="year-end-accordion-title">住宅ローン控除</span>
                    <span class="year-end-accordion-badge {{ $yearEndSectionStates['housing-loan'] ? 'is-saved' : '' }}" data-badge>{{ $yearEndSectionStates['housing-loan'] ? '保存済み' : '未回答' }}</span>
                </summary>
                <div class="year-end-accordion-body">
            <form method="post" action="{{ route('year_end_adjustment.housing_loan.update') }}" enctype="multipart/form-data" class="year-end-ajax-form">
                @csrf
                <div class="year-end-accordion-error" data-error hidden></div>

                @include('staff_portal.year_end.sections._confirm_or_change', [
                'sectionKey' => 'housing-loan',
                'title' => '住宅借入金等特別控除',
                'currentItems' => [
                ['label' => '登録済みの控除額', 'value' => ($currentNenTyo['jyu_kari_kou'] ?? null) !== null ? number_format((float) $currentNenTyo['jyu_kari_kou']) . '円' : ''],
                ['label' => '住宅控除区分', 'value' => (string) ($currentNenTyo['jyu_kojyo_kubun'] ?? '')],
                ['label' => '特定取得区分', 'value' => (string) ($currentNenTyo['toku_kubun'] ?? '')],
                ['label' => '控除区分番号', 'value' => (string) ($currentNenTyo['koujyo_kubun_no'] ?? '')],
                ],
                'toggleLabel' => '今年、住宅ローン控除を受けますか？',
                'changedFieldName' => 'housing_loan_changed',
                'changedChecked' => isset($application['housing_loan_changed']) ? (bool) $application['housing_loan_changed'] : null,
                'requireAnswer' => true,
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
                    <button type="submit" class="btn year-end-btn-save">保存して次へ</button>
                </div>
                @endif
            </form>
                </div>
            </details>

            @if ($editable)
            <div class="year-end-submit-area" {{ $yearEndAllSaved ? '' : 'hidden' }}>
                <form method="post" action="{{ route('year_end_adjustment.submit') }}" onsubmit="return confirm('この内容で提出します。よろしいですか？\n\nこの「提出する」を押すまで事務所には送られません。');">
                    @csrf
                    <button type="submit" class="btn btn_primary year-end-btn-submit">この内容で提出する</button>
                </form>
                <p class="year-end-submit-note">全部の項目に答えて保存すると、ここに提出ボタンが出ます。</p>
            </div>
            @endif

            <div class="back-row">
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
    <script>
        (function() {
            // 保険料の行削除の直後は保存→再読み込みになるが、まだ作業中のはずなので、
            // サーバー側の完了状態に関わらず保険料控除セクションを強制的に開いたままにする。
            try {
                if (sessionStorage.getItem('year_end_force_open_insurance')) {
                    sessionStorage.removeItem('year_end_force_open_insurance');
                    var target = document.querySelector('.year-end-accordion-section[data-section="insurance"]');
                    if (target) {
                        target.hidden = false;
                        target.open = true;
                    }
                }
            } catch (e) {}

            // container/templateから1行分の要素を追加し、その行のルート要素（配列）を返す。
            // ボタンクリックからも、XML自動入力からも同じロジックで行を増やす。
            function makeRowAdder(containerId, templateId, startIndex) {
                var container = document.getElementById(containerId);
                var template = document.getElementById(templateId);
                if (!container || !template) return null;

                var nextIndex = startIndex;
                return function addRow() {
                    var html = template.innerHTML.split('__SLOT__').join(String(nextIndex));
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    var addedElements = Array.prototype.slice.call(wrapper.children);
                    while (wrapper.firstChild) {
                        container.appendChild(wrapper.firstChild);
                    }
                    nextIndex++;
                    return addedElements;
                };
            }

            function bindButton(buttonId, addRow) {
                var button = document.getElementById(buttonId);
                if (!button || !addRow) return;
                button.addEventListener('click', function() {
                    addRow();
                });
            }

            var addFuyoRow = makeRowAdder('fuyo-add-rows', 'fuyo-add-template', 1);
            bindButton('fuyo-add-more-btn', addFuyoRow);

            var addHokenRow = makeRowAdder('hoken-add-rows', 'hoken-add-template', 1);
            bindButton('hoken-add-more-btn', addHokenRow);

            setupInsuranceXmlAutoFill(addHokenRow);
            setupAccordionAjaxForms();
            setupRowDeleteConfirm();

            // 削除ボタン（見た目は普通のボタンだが実体は隠しチェックボックス）を押すと
            // 確認ダイアログを出し、OKなら即そのセクションを保存＝即削除する
            // （「保存して次へ」を別途押さないと消えないのは分かりにくい、という指摘への対応）。
            function setupRowDeleteConfirm() {
                document.querySelectorAll('.year-end-row-delete-checkbox').forEach(function(checkbox) {
                    var label = checkbox.closest('.year-end-row-delete-label');
                    checkbox.addEventListener('change', function() {
                        if (checkbox.checked) {
                            if (!confirm('この保険情報を削除します。よろしいですか？')) {
                                checkbox.checked = false;
                                return;
                            }
                        }
                        if (label) {
                            label.classList.toggle('is-marked', checkbox.checked);
                        }
                        if (checkbox.checked) {
                            var form = checkbox.closest('form.year-end-ajax-form');
                            if (form && typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else if (form) {
                                form.submit();
                            }
                        }
                    });
                });
            }

            // アコーディオン化：セクションの保存ボタン＝そのセクションの保存そのものにする。
            // 「離れた場所の保存ボタンを押し忘れる」問題への対策として、次のセクションへ進む
            // 手段を「保存する」以外に用意しない（ページ遷移ではなくAjaxで保存し、保存できたら
            // 自動でそのセクションを閉じ、次の未保存セクションを開く）。
            function setupAccordionAjaxForms() {
                var sections = Array.prototype.slice.call(document.querySelectorAll('.year-end-accordion-section'));
                if (sections.length === 0) return;

                sections.forEach(function(section) {
                    var form = section.querySelector('form.year-end-ajax-form');
                    if (!form) return;

                    form.addEventListener('submit', function(event) {
                        // formaction違いのボタン（保険料控除の「前年のデータをコピーする」）は
                        // Ajax化せず、今まで通りページ遷移させる。
                        var submitter = event.submitter;
                        if (submitter && submitter.hasAttribute('formaction')) {
                            return;
                        }

                        event.preventDefault();
                        submitSectionForm(section, form);
                    });
                });

                function submitSectionForm(section, form) {
                    var errorBox = form.querySelector('[data-error]');
                    errorBox.hidden = true;
                    errorBox.innerHTML = '';
                    section.classList.add('year-end-accordion-saving');

                    var formData = new FormData(form);

                    fetch(form.getAttribute('action'), {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    }).then(function(res) {
                        return res.json().then(function(data) {
                            return {
                                ok: res.ok,
                                status: res.status,
                                data: data
                            };
                        });
                    }).then(function(result) {
                        section.classList.remove('year-end-accordion-saving');

                        if (!result.ok) {
                            showSectionError(errorBox, result.data);
                            return;
                        }

                        // 行の削除を伴う保存は、その場でDOMから消す処理をしていないので
                        // （見た目上は消えたはずの行が残ったまま表示されてしまう）、
                        // ページを読み込み直して確実に最新の状態を表示する。削除は「このセクションの
                        // 作業が終わった」合図ではない（他の行をまだ編集中のはず）ので、再読み込み後も
                        // このセクションだけは強制的に開いたままにする。
                        var hasRemoval = form.querySelector('input[name$="[remove]"]:checked') !== null;
                        if (hasRemoval) {
                            // 削除ボタンは保険料控除セクションにしか無いので、開いたままにする対象は固定でいい。
                            try {
                                sessionStorage.setItem('year_end_force_open_insurance', '1');
                            } catch (e) {}
                            location.reload();
                            return;
                        }

                        markSectionSaved(section);
                        section.open = false;
                        openNextIncompleteSection(section);
                    }).catch(function() {
                        section.classList.remove('year-end-accordion-saving');
                        errorBox.hidden = false;
                        errorBox.textContent = '通信に失敗しました。時間をおいて再度お試しください。';
                    });
                }

                function showSectionError(errorBox, data) {
                    var messages = [];
                    if (data && data.errors) {
                        Object.keys(data.errors).forEach(function(key) {
                            (data.errors[key] || []).forEach(function(msg) {
                                messages.push(msg);
                            });
                        });
                    }
                    if (messages.length === 0 && data && data.message) {
                        messages.push(data.message);
                    }
                    if (messages.length === 0) {
                        messages.push('入力内容を確認してください。');
                    }

                    errorBox.hidden = false;
                    errorBox.innerHTML = '';
                    var list = document.createElement('ul');
                    messages.forEach(function(msg) {
                        var li = document.createElement('li');
                        li.textContent = msg;
                        list.appendChild(li);
                    });
                    errorBox.appendChild(list);
                    errorBox.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                function markSectionSaved(section) {
                    var badge = section.querySelector('[data-badge]');
                    if (badge) {
                        badge.textContent = '保存済み';
                        badge.classList.add('is-saved');
                    }
                }

                function openNextIncompleteSection(currentSection) {
                    var index = sections.indexOf(currentSection);
                    for (var i = index + 1; i < sections.length; i++) {
                        var badge = sections[i].querySelector('[data-badge]');
                        if (!badge || !badge.classList.contains('is-saved')) {
                            sections[i].hidden = false;
                            sections[i].open = true;
                            sections[i].scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                            return;
                        }
                    }
                    // 残り全部保存済みなら、提出ボタンを初めて表示してスクロール
                    var submitArea = document.querySelector('.year-end-submit-area');
                    if (submitArea) {
                        submitArea.hidden = false;
                        submitArea.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }
            }

            function setupInsuranceXmlAutoFill(addHokenRow) {
                var xmlInput = document.getElementById('hoken-xml-input');
                var xmlStatus = document.getElementById('hoken-xml-status');
                if (!xmlInput || !addHokenRow) return;

                xmlInput.addEventListener('change', function() {
                    var file = xmlInput.files && xmlInput.files[0];
                    if (!file) return;

                    xmlStatus.classList.remove('is-error', 'is-success');
                    xmlStatus.textContent = '読み込み中…';

                    var formData = new FormData();
                    formData.append('certificate_file', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route('year_end_adjustment.insurance.parse_xml') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    }).then(function(res) {
                        return res.json().then(function(data) {
                            return {
                                ok: res.ok,
                                data: data
                            };
                        });
                    }).then(function(result) {
                        if (!result.ok) {
                            xmlStatus.classList.add('is-error');
                            xmlStatus.textContent = (result.data && result.data.error) || '読み込みに失敗しました。';
                            return;
                        }

                        var gate = document.getElementById('hoken-add-gate');
                        if (gate && !gate.checked) {
                            gate.checked = true;
                        }

                        var engagedYes = document.querySelector('input[name="insurance_engaged"][value="1"]');
                        if (engagedYes) {
                            engagedYes.checked = true;
                        }

                        var contracts = result.data.contracts || [];
                        contracts.forEach(function(contract) {
                            var rowEls = addHokenRow();
                            if (rowEls && rowEls[0]) {
                                fillHokenRow(rowEls[0], contract, file);
                            }
                        });

                        xmlStatus.classList.add('is-success');
                        xmlStatus.textContent = contracts.length + '件の契約を読み取り、下に自動入力しました。内容を確認し、この保険料控除セクションの「変更はありますか」に「はい」を選んだうえで「保存して次へ」ボタンを押してください（このボタンを押すまで保存されません）。';
                        xmlInput.value = '';
                    }).catch(function() {
                        xmlStatus.classList.add('is-error');
                        xmlStatus.textContent = '通信に失敗しました。時間をおいて再度お試しください。';
                    });
                });
            }

            function fillHokenRow(rowRoot, contract, file) {
                setRowFieldValue(rowRoot, 'insurance_company', contract.insurance_company);
                setRowFieldValue(rowRoot, 'category', contract.category);
                setRowFieldValue(rowRoot, 'applied_system', contract.applied_system);
                setRowFieldValue(rowRoot, 'declared_amount', contract.declared_amount);
                setRowFieldValue(rowRoot, 'insurance_type', contract.insurance_type);
                setRowFieldValue(rowRoot, 'insurance_period', contract.insurance_period);
                setRowFieldValue(rowRoot, 'policy_holder_name', contract.policy_holder_name);
                setRowFieldValue(rowRoot, 'beneficiary_name', contract.beneficiary_name);
                setRowFieldValue(rowRoot, 'beneficiary_relationship', contract.beneficiary_relationship);
                setRowFieldValue(rowRoot, 'pension_payment_start_date', contract.pension_payment_start_date);
                setRowFieldValue(rowRoot, 'year_end_insurance_note', contract.year_end_insurance_note);

                var fileInput = rowRoot.querySelector('[name$="[certificate_file]"]');
                if (fileInput && typeof DataTransfer !== 'undefined') {
                    var dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;
                }

                var enableToggle = rowRoot.querySelector('.year-end-toggle-input');
                if (enableToggle) {
                    enableToggle.checked = true;
                }
            }

            function setRowFieldValue(rowRoot, fieldSuffix, value) {
                if (value === null || value === undefined) return;
                var el = rowRoot.querySelector('[name$="[' + fieldSuffix + ']"]');
                if (el) {
                    el.value = value;
                }
            }
        })();
    </script>
</body>

</html>
