<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 年末調整詳細</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <style>
        .year-end-detail-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 10px;
        }

        .year-end-detail-card {
            box-sizing: border-box;
            min-width: 0;
            border: 1px solid #d3dff0;
            border-radius: 8px;
            background: #fff;
            padding: 10px 12px;
        }

        .year-end-detail-card h2 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .year-end-fields {
            display: grid;
            grid-template-columns: minmax(60px, max-content) minmax(0, 1fr);
            gap: 6px 10px;
            font-size: 13px;
        }

        .year-end-fields dt {
            color: #667085;
            font-weight: 700;
        }

        .year-end-fields dd {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .year-end-table-wrap {
            max-width: 100%;
            overflow: auto;
        }

        .year-end-table {
            min-width: 900px;
        }

        .year-end-status {
            display: inline-flex;
            min-width: 70px;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 8px;
            background: #eef4ff;
            color: #1f4f8f;
            font-size: 12px;
            font-weight: 800;
        }

        .year-end-summary-blocks {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 10px;
        }

        .year-end-summary-block {
            box-sizing: border-box;
            min-width: 0;
            border: 1px solid #d3dff0;
            border-radius: 8px;
            background: #fff;
            padding: 10px 12px;
        }

        .year-end-summary-block h3 {
            margin: 0 0 10px;
            font-size: 14px;
        }

        .year-end-insurance-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 12px;
        }

        .year-end-insurance-item {
            min-width: 0;
            border: 1px solid #d3dff0;
            border-radius: 8px;
            background: #fff;
            padding: 10px;
        }

        .year-end-insurance-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
        }

        .year-end-insurance-title {
            min-width: 0;
            overflow-wrap: anywhere;
            font-size: 14px;
            font-weight: 800;
            color: #1f2937;
        }

        .year-end-insurance-meta {
            margin-top: 3px;
            color: #667085;
            font-size: 12px;
        }

        .year-end-insurance-amount {
            min-width: 80px;
            text-align: right;
            color: #0f766e;
            font-size: 16px;
            font-weight: 800;
            white-space: nowrap;
        }

        .year-end-insurance-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 12px;
            font-size: 13px;
        }

        .year-end-insurance-field dt {
            color: #667085;
            font-size: 12px;
            font-weight: 700;
        }

        .year-end-insurance-field dd {
            margin: 2px 0 0;
            overflow-wrap: anywhere;
        }

        .year-end-insurance-proof {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #e4e7ec;
            font-size: 13px;
        }

        .year-end-proof-missing {
            color: #b42318;
            font-weight: 800;
        }

        .year-end-hoken-form {
            display: grid;
            gap: 8px;
            min-width: 0;
        }

        .year-end-hoken-edit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 10px;
            min-width: 0;
        }

        .year-end-hoken-field {
            display: grid;
            grid-template-columns: 92px minmax(0, 1fr);
            align-items: center;
            gap: 6px;
            min-width: 0;
            font-size: 12px;
            font-weight: 700;
            color: #667085;
        }

        .year-end-hoken-field input,
        .year-end-hoken-field textarea {
            width: 100%;
            min-width: 0;
            border: 1px solid #cfd8e3;
            border-radius: 4px;
            background: #fff;
            padding: 4px 6px;
            color: #111827;
            font-size: 13px;
            font-weight: 400;
            box-sizing: border-box;
        }

        .year-end-hoken-field-wide {
            grid-column: 1 / -1;
        }

        .year-end-hoken-field-narrow input {
            max-width: 140px;
        }

        .year-end-hoken-field textarea {
            min-height: 42px;
            resize: vertical;
        }

        .year-end-hoken-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 2px;
        }

        .year-end-hoken-delete-form {
            margin: 0;
        }

        .year-end-hoken-delete-btn {
            color: #b42318;
            border-color: #f3c6c1;
        }

        .year-end-hoken-delete-btn:hover {
            background: #fef3f2;
            border-color: #b42318;
            color: #b42318;
        }

        .year-end-hoken-add {
            margin-top: 14px;
            border: 1px dashed #a7b7cc;
            border-radius: 8px;
            background: #f8fafc;
            padding: 10px;
        }

        @media (max-width: 560px) {
            .year-end-insurance-list {
                grid-template-columns: 1fr;
            }

            .year-end-hoken-edit-grid {
                grid-template-columns: 1fr;
            }

            .year-end-hoken-field {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1000px) {
            .year-end-detail-grid {
                grid-template-columns: 1fr;
            }
        }

        /* 計算結果サマリー：給与画面と同じ「編集ボタン→保存」の見せ方 */
        #nen-tyo-summary .actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        #nen-tyo-summary .actions .section-title {
            margin: 0;
        }

        #nen-tyo-summary .actions form {
            margin: 0;
        }

        #nen-tyo-summary .actions .btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .year-end-summary-edit-input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #cfd8e3;
            border-radius: 4px;
            background: #fff;
            padding: 2px 4px;
            font-size: 13px;
        }

        .edit-only {
            display: none;
        }

        .is-editing .edit-only {
            display: inline-block;
        }

        .is-editing .view-only {
            display: none;
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">{{ $targetYear }}年　年末調整詳細</h1>
            <a href="{{ route('admin.work.year_end_adjustments', ['target_year' => $targetYear]) }}" class="btn btn-outline">一覧へ戻る</a>
            <a href="{{ route('admin.work.year_end_adjustments.hoken.preview', ['applicationId' => $applicationId]) }}" target="_blank" rel="noopener" class="btn btn-outline">保険料控除申告書</a>
            <a href="{{ route('admin.work.year_end_adjustments.kiso.preview', ['applicationId' => $applicationId]) }}" target="_blank" rel="noopener" class="btn btn-outline">基礎控除申告書</a>
            <a href="{{ route('admin.work.year_end_adjustments.fuyo.preview', ['applicationId' => $applicationId]) }}" target="_blank" rel="noopener" class="btn btn-outline">扶養控除申告書</a>
            <a href="{{ route('admin.work.year_end_adjustments.gensen_bo.preview', ['applicationId' => $applicationId]) }}" target="_blank" rel="noopener" class="btn btn-outline">源泉徴収簿</a>
            <a href="{{ route('admin.work.year_end_adjustments.gensen_hyou.preview', ['applicationId' => $applicationId]) }}" target="_blank" rel="noopener" class="btn btn-outline">源泉徴収票</a>
        </div>

        @if (session('status'))
        <div class="panel">{{ session('status') }}</div>
        @endif

        <section class="panel">
            <div class="year-end-detail-grid">

                <div class="year-end-detail-card" style="grid-column: span 5;">
                    <h2>スタッフ情報</h2>
                    <dl class="year-end-fields">
                        <dt>スタッフID</dt>
                        <dd>{{ $application['staff_id'] }}</dd>
                        <dt>氏名カナ</dt>
                        <dd>{{ $staff['staff_name_furi'] ?? '---' }}</dd>
                        <dt>氏名</dt>
                        <dd class="f_size18"><b>{{ $staff['staff_name'] ?? '---' }}</b></dd>
                        <dt>生年月日</dt>
                        <dd>{{ $staff['birthday'] ?? '---' }}</dd>
                        <dt>性別</dt>
                        <dd>{{ $staff['sex'] ?? '---' }}</dd>
                        <dt>住所</dt>
                        <dd>{{ $staff['address'] ?? '---' }}</dd>
                        <dt>住所フリガナ</dt>
                        <dd>{{ $staff['address_furi'] ?? '---' }}</dd>
                        <dt>雇用区分</dt>
                        <dd>{{ $staff['employment'] ?? '---' }}</dd>
                        <dt>税区分</dt>
                        <dd>{{ $staff['tax_amount'] ?? '---' }}</dd>
                    </dl>
                </div>

                <div class="year-end-detail-card" style="grid-column: span 3;">
                    <h2>申請状況</h2>
                    <dl class="year-end-fields">
                        <dt>状態</dt>
                        <dd><span class="year-end-status">{{ $application['status_label'] }}</span></dd>
                        <dt>計算処理</dt>
                        <dd>{{ isset($nenTyo['edit_lock']) && in_array((string) $nenTyo['edit_lock'], ['1', 'true', 'True'], true) ? '処理済' : '未処理' }}</dd>
                        <dt>年末調整</dt>
                        <dd>{{ $application['year_end_adjustment'] !== '' ? $application['year_end_adjustment'] : '未回答' }}</dd>
                        <dt>特徴希望</dt>
                        <dd>{{ $application['special_collection_requested'] !== '' ? $application['special_collection_requested'] : '未回答' }}</dd>
                        <dt>普通徴収</dt>
                        <dd>{{ $nenTyo['futu_tyoushu'] ?? '---' }}</dd>
                        <dt>処理済</dt>
                        <dd>{{ isset($nenTyo['edit_lock']) && in_array((string) $nenTyo['edit_lock'], ['1', 'true', 'True'], true) ? '済' : '未' }}</dd>
                    </dl>
                </div>

                <div class="year-end-detail-card" style="grid-column: span 4;">
                    <h2>変更申告</h2>
                    <dl class="year-end-fields">
                        <dt>個人情報</dt>
                        <dd>{{ $application['personal_info_changed'] !== '' ? $application['personal_info_changed'] : '未回答' }}</dd>
                        <dt>扶養</dt>
                        <dd>{{ $application['dependents_changed'] !== '' ? $application['dependents_changed'] : '未回答' }}</dd>
                        <dt>保険料控除</dt>
                        <dd>{{ $application['insurance_deduction_changed'] !== '' ? $application['insurance_deduction_changed'] : '未回答' }}</dd>
                        <dt>住宅ローン</dt>
                        <dd>{{ $application['housing_loan_changed'] !== '' ? $application['housing_loan_changed'] : '未回答' }}</dd>
                        <dt>前職源泉</dt>
                        <dd>{{ $application['previous_job_withholding_changed'] !== '' ? $application['previous_job_withholding_changed'] : '未回答' }}</dd>
                        <dt>提出日時</dt>
                        <dd>{{ $application['submitted_at'] !== '' ? $application['submitted_at'] : '---' }}</dd>
                        <dt>確認日時</dt>
                        <dd>{{ $application['confirmed_at'] !== '' ? $application['confirmed_at'] : '---' }}</dd>
                    </dl>
                </div>

            </div>
        </section>

        @php
        $isNenTyoLocked = isset($nenTyo['edit_lock']) && in_array((string) $nenTyo['edit_lock'], ['1', 'true', 'True'], true);
        $hasNenTyoNo = ($nenTyo['nen_tyo_no'] ?? '') !== '';
        @endphp
        <section class="panel" id="nen-tyo-summary">
            <div class="actions">
                <h2 class="section-title">計算結果サマリー</h2>
                <span class="year-end-status" id="nen-tyo-lock-status">{{ $isNenTyoLocked ? '処理済' : '未処理' }}</span>
                <button class="btn" type="button" id="nen-tyo-edit-toggle-btn" @disabled($isNenTyoLocked || !$hasNenTyoNo)>編集</button>
                <button class="btn edit-only" type="button" id="nen-tyo-edit-cancel-btn">取消</button>
                <button class="btn btn-primary" type="button" id="nen-tyo-lock-toggle-btn" data-locked="{{ $isNenTyoLocked ? '1' : '0' }}" @disabled(!$hasNenTyoNo)>{{ $isNenTyoLocked ? '未確定に戻す' : '確定' }}</button>
                <form method="post" action="{{ route('admin.work.year_end_adjustments.calculate', ['applicationId' => $applicationId]) }}" onsubmit="return confirm('この対象者を再計算します。よろしいですか？');">
                    @csrf
                    <button type="submit" class="btn" @disabled($isNenTyoLocked)>再計算</button>
                </form>
            </div>
            @php
            // グループごとの幅（12分割グリッドの何マス分か）。項目数や値の長さに応じてここで
            // 個別に調整する（画面幅が変わっても行の列位置が揃うよう、px直接指定ではなく
            // grid-column: span Nにしている）。
            $summaryBlockSpans = [
            '本人関係' => 3,
            '給与・税額' => 4,
            '社保・控除' => 5,
            '配偶者関係' => 3,
            '扶養関係' => 3,
            '前職情報' => 3,
            '住宅・定額減税' => 3,
            ];
            @endphp
            <div class="year-end-summary-blocks">
                @foreach ($nenTyoSummaryGroups as $groupLabel => $items)
                <div class="year-end-summary-block" style="grid-column: span {{ $summaryBlockSpans[$groupLabel] ?? 3 }};">
                    <h3>{{ $groupLabel }}</h3>
                    <dl class="year-end-fields">
                        @foreach ($items as $key => $label)
                        <dt>{{ $label }}</dt>
                        @if (in_array($key, ['kiso_bunrui', 'haigu_bunrui'], true))
                        @php
                        $bunruiValue = (string) ($nenTyo[$key] ?? '');
                        $symbolKey = $key === 'haigu_bunrui' ? 'haiguBunruiSymbol' : 'kisoBunruiSymbol';
                        // 所得の細かい表（kisoBracketOptions）をそのまま選択肢にする。値は旧Access側と
                        // 同じ細かい所得区分のラベル（例:「655万円超900万円以下」）そのものを保存する。
                        // 選択肢の見た目には記号（A/B/C・①②③）・金額も一緒に出す。
                        $bunruiOptionsForSelect = [];
                        $bunruiMatchedExisting = false;
                        foreach (($kisoBracketOptions ?? []) as $bracket) {
                        $isSelected = !$bunruiMatchedExisting && $bunruiValue !== '' && $bunruiValue === $bracket['label'];
                        if ($isSelected) { $bunruiMatchedExisting = true; }
                        $bunruiOptionsForSelect[] = [
                        'value' => $bracket['label'],
                        'text' => $bracket['label'] . '　' . $bracket[$symbolKey] . '　' . number_format($bracket['amount']) . '円',
                        'selected' => $isSelected,
                        ];
                        }
                        // 保存値が上の選択肢のどれにも一致しない場合（未計算・旧データ等）に
                        // 選択肢を開いた瞬間に空欄扱いで保存されてしまわないよう、現在値をそのまま
                        // 選択肢の先頭に足して選択させておく。
                        if ($bunruiValue !== '' && !$bunruiMatchedExisting) {
                        array_unshift($bunruiOptionsForSelect, ['value' => $bunruiValue, 'text' => $bunruiValue . '（保存されている値）', 'selected' => true]);
                        }
                        array_unshift($bunruiOptionsForSelect, ['value' => '', 'text' => '（空欄）', 'selected' => $bunruiValue === '']);
                        @endphp
                        <dd>
                            <span class="view-only">{{ $bunruiValue !== '' ? $bunruiValue : '---' }}</span>
                            <select class="year-end-summary-edit-input edit-only" data-key="{{ $key }}">
                                @foreach ($bunruiOptionsForSelect as $opt)
                                <option value="{{ $opt['value'] }}" @selected($opt['selected'])>{{ $opt['text'] }}</option>
                                @endforeach
                            </select>
                        </dd>
                        @else
                        <dd>
                            <span class="view-only">{{ ($nenTyo[$key] ?? '') !== '' ? $nenTyo[$key] : '---' }}</span>
                            <input class="year-end-summary-edit-input edit-only" type="text" value="{{ $nenTyo[$key] ?? '' }}" data-key="{{ $key }}">
                        </dd>
                        @endif
                        @endforeach
                    </dl>
                </div>
                @endforeach
            </div>
        </section>
        <section class="panel">
            <h2 class="section-title">扶養情報</h2>
            <div class="year-end-table-wrap f_size12">
                <table class="data-table year-end-table">
                    <thead>
                        <tr>
                            <th>登録日</th>
                            <th>氏名</th>
                            <th>フリガナ</th>
                            <th>続柄</th>
                            <th>生年月日</th>
                            <th>性別</th>
                            <th>収入</th>
                            <th>控除対象</th>
                            <th>住所</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fuyoRows as $row)
                        <tr>
                            <td>{{ $row['registration_date'] ?? '' }}</td>
                            <td>{{ $row['fuyo_name'] ?? '' }}</td>
                            <td>{{ $row['fuyo_name_furi'] ?? '' }}</td>
                            <td>{{ $row['fuyo_relationship'] ?? '' }}</td>
                            <td>{{ $row['fuyo_birthday'] ?? '' }}</td>
                            <td>{{ $row['fuyo_sex'] ?? '' }}</td>
                            <td>{{ $row['fuyo_shunyu'] ?? '' }}</td>
                            <td>{{ $row['deduction_target'] ?? '' }}</td>
                            <td>{{ $row['fuyo_address'] ?? '' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="empty">扶養情報がありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2 class="section-title">保険料控除情報</h2>
            <div class="year-end-insurance-list">
                @forelse ($hokenRows as $row)
                @php
                $company = $row['insurance_company'] ?? '';
                $type = $row['insurance_type'] ?? '';
                $category = $row['category'] ?? '';
                $amount = $row['declared_amount'] ?? '';
                $period = $row['insurance_period'] ?? '';
                $contractor = $row['policy_holder_name'] ?? '';
                $recipient = $row['beneficiary_name'] ?? '';
                $recipientRelation = $row['beneficiary_relationship'] ?? '';
                $system = $row['applied_system'] ?? '';
                $pensionStart = $row['pension_payment_start_date'] ?? '';
                $pensionInput = $pensionStart !== '' ? str_replace('/', '-', substr($pensionStart, 0, 10)) : '';
                $note = $row['year_end_insurance_note'] ?? '';
                $proofPath = $row['certificate_file_path'] ?? '';
                $proofName = $row['certificate_original_name'] ?? '';
                $proofUrl = '';
                if ($proofPath !== '') {
                $proofUrl = preg_match('/^https?:\/\//', $proofPath) === 1 ? $proofPath : asset(ltrim($proofPath, '/'));
                }
                @endphp
                <article class="year-end-insurance-item">
                    <form method="post" action="{{ route('admin.work.year_end_adjustments.hoken.update', ['applicationId' => $applicationId, 'hokenNo' => $row['hoken_no'] ?? 0]) }}" enctype="multipart/form-data" class="year-end-hoken-form">
                        @csrf
                        <div class="year-end-insurance-head">
                            <div>
                                <div class="year-end-insurance-title">{{ $company !== '' ? $company : '保険会社未入力' }}</div>
                                <div class="year-end-insurance-meta">
                                    No.{{ $row['hoken_no'] ?? '---' }} / {{ $row['insurance_year'] ?? $targetYear }} / {{ $type !== '' ? $type : '種類未入力' }}
                                </div>
                            </div>
                            <div class="year-end-insurance-amount">{{ $amount !== '' ? $amount : '---' }}</div>
                        </div>

                        <div class="year-end-hoken-edit-grid">
                            <label class="year-end-hoken-field">
                                保険会社
                                <input type="text" name="insurance_company" value="{{ $company }}" maxlength="50">
                            </label>
                            <label class="year-end-hoken-field">
                                保険種類
                                <input type="text" name="insurance_type" value="{{ $type }}" maxlength="50">
                            </label>
                            <label class="year-end-hoken-field">
                                区分
                                <input type="text" name="category" value="{{ $category }}" maxlength="20">
                            </label>
                            <label class="year-end-hoken-field">
                                適用制度
                                <input type="text" name="applied_system" value="{{ $system }}" maxlength="20">
                            </label>
                            <label class="year-end-hoken-field year-end-hoken-field-narrow">
                                申告額
                                <input type="number" name="declared_amount" value="{{ str_replace(',', '', $amount) }}" step="1">
                            </label>
                            <label class="year-end-hoken-field">
                                保険期間
                                <input type="text" name="insurance_period" value="{{ $period }}" maxlength="10">
                            </label>
                            <label class="year-end-hoken-field">
                                契約者
                                <input type="text" name="policy_holder_name" value="{{ $contractor }}" maxlength="20">
                            </label>
                            <label class="year-end-hoken-field">
                                受取人
                                <input type="text" name="beneficiary_name" value="{{ $recipient }}" maxlength="20">
                            </label>
                            <label class="year-end-hoken-field">
                                受取人続柄
                                <input type="text" name="beneficiary_relationship" value="{{ $recipientRelation }}" maxlength="10">
                            </label>
                            <label class="year-end-hoken-field">
                                年金支払開始日
                                <input type="date" name="pension_payment_start_date" value="{{ $pensionInput }}">
                            </label>
                            <label class="year-end-hoken-field year-end-hoken-field-wide">
                                備考
                                <textarea name="year_end_insurance_note" rows="2">{{ $note }}</textarea>
                            </label>
                        </div>

                        <div class="year-end-insurance-proof">
                            @if ($proofUrl !== '')
                            <a href="{{ route('admin.work.year_end_adjustments.hoken.certificate_preview', ['applicationId' => $applicationId, 'hokenNo' => $row['hoken_no'] ?? 0]) }}" target="_blank" rel="noopener" class="btn btn-outline">証明書を開く{{ $proofName !== '' ? ' ('.$proofName.')' : '' }}</a>
                            @else
                            <span class="year-end-proof-missing">証明書未添付</span>
                            @endif
                        </div>

                        <label class="year-end-hoken-field">
                            証明書添付
                            <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                        </label>

                        <div class="year-end-hoken-actions">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <button type="submit" form="year-end-hoken-delete-{{ $row['hoken_no'] ?? 0 }}" class="btn year-end-hoken-delete-btn">削除</button>
                        </div>
                    </form>
                    <form method="post" id="year-end-hoken-delete-{{ $row['hoken_no'] ?? 0 }}" action="{{ route('admin.work.year_end_adjustments.hoken.delete', ['applicationId' => $applicationId, 'hokenNo' => $row['hoken_no'] ?? 0]) }}" class="year-end-hoken-delete-form" onsubmit="return confirm('この保険情報を削除します。よろしいですか？');">
                        @csrf
                    </form>
                </article>
                @empty
                <div class="empty">保険料控除情報がありません。</div>
                @endforelse
            </div>

            <div class="year-end-hoken-add">
                <h3>保険情報を追加</h3>
                <form method="post" action="{{ route('admin.work.year_end_adjustments.hoken.create', ['applicationId' => $applicationId]) }}" enctype="multipart/form-data" class="year-end-hoken-form">
                    @csrf
                    <div class="year-end-hoken-edit-grid">
                        <label class="year-end-hoken-field">
                            保険会社
                            <input type="text" name="insurance_company" maxlength="50">
                        </label>
                        <label class="year-end-hoken-field">
                            保険種類
                            <input type="text" name="insurance_type" maxlength="50">
                        </label>
                        <label class="year-end-hoken-field">
                            区分
                            <input type="text" name="category" maxlength="20">
                        </label>
                        <label class="year-end-hoken-field">
                            適用制度
                            <input type="text" name="applied_system" maxlength="20">
                        </label>
                        <label class="year-end-hoken-field year-end-hoken-field-narrow">
                            申告額
                            <input type="number" name="declared_amount" step="1">
                        </label>
                        <label class="year-end-hoken-field">
                            保険期間
                            <input type="text" name="insurance_period" maxlength="10">
                        </label>
                        <label class="year-end-hoken-field">
                            契約者
                            <input type="text" name="policy_holder_name" maxlength="20">
                        </label>
                        <label class="year-end-hoken-field">
                            受取人
                            <input type="text" name="beneficiary_name" maxlength="20">
                        </label>
                        <label class="year-end-hoken-field">
                            受取人続柄
                            <input type="text" name="beneficiary_relationship" maxlength="10">
                        </label>
                        <label class="year-end-hoken-field">
                            年金支払開始日
                            <input type="date" name="pension_payment_start_date">
                        </label>
                        <label class="year-end-hoken-field year-end-hoken-field-wide">
                            備考
                            <textarea name="year_end_insurance_note" rows="2"></textarea>
                        </label>
                        <label class="year-end-hoken-field year-end-hoken-field-wide">
                            証明書添付
                            <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                        </label>
                    </div>
                    <div class="year-end-hoken-actions">
                        <button type="submit" class="btn btn-primary">追加</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script>
        (function() {
            var editBtn = document.getElementById('nen-tyo-edit-toggle-btn');
            var cancelBtn = document.getElementById('nen-tyo-edit-cancel-btn');
            var root = document.getElementById('nen-tyo-summary');
            if (!editBtn || !root) return;

            var saveUrl = @json(route('admin.work.year_end_adjustments.nen_tyo.update', ['applicationId' => $applicationId]));
            var csrf = @json(csrf_token());

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    // 入力欄の値を保存せずに表示モードへ戻す。値そのものはページ再読み込みで
                    // 元の保存値に戻すのが一番確実（入力途中の値をDOM上で個別に巻き戻さない）。
                    location.reload();
                });
            }

            editBtn.addEventListener('click', function() {
                if (!root.classList.contains('is-editing')) {
                    root.classList.add('is-editing');
                    editBtn.textContent = '保存';
                    return;
                }

                editBtn.disabled = true;
                var values = {};
                root.querySelectorAll('.year-end-summary-edit-input[data-key]').forEach(function(input) {
                    var key = (input.getAttribute('data-key') || '').trim();
                    if (!key) return;
                    values[key] = input.value;
                });

                fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            values: values
                        })
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(json) {
                        if (!json || json.ok !== true) {
                            throw new Error(json && json.message ? json.message : 'save failed');
                        }
                        root.classList.remove('is-editing');
                        editBtn.textContent = '編集';
                        location.reload();
                    })
                    .catch(function(err) {
                        alert(err && err.message ? err.message : '保存に失敗しました。');
                    })
                    .finally(function() {
                        editBtn.disabled = false;
                    });
            });
        })();

        (function() {
            var lockBtn = document.getElementById('nen-tyo-lock-toggle-btn');
            if (!lockBtn) return;

            var toggleUrl = @json(route('admin.work.year_end_adjustments.nen_tyo.lock_toggle', ['applicationId' => $applicationId]));
            var csrf = @json(csrf_token());

            lockBtn.addEventListener('click', function() {
                var confirmMessage = lockBtn.getAttribute('data-locked') === '1' ?
                    '未確定に戻します。よろしいですか？' :
                    '確定します。確定後はこのレコードを編集できなくなります。よろしいですか？';
                if (!confirm(confirmMessage)) return;

                lockBtn.disabled = true;
                fetch(toggleUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: '{}'
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(json) {
                        if (!json || json.ok !== true) {
                            throw new Error(json && json.message ? json.message : 'failed');
                        }
                        location.reload();
                    })
                    .catch(function(err) {
                        alert(err && err.message ? err.message : '処理に失敗しました。');
                    })
                    .finally(function() {
                        lockBtn.disabled = false;
                    });
            });
        })();
    </script>
</body>

</html>
