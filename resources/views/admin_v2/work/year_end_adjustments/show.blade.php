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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 10px;
        }

        .year-end-detail-card {
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 10px;
        }

        .year-end-summary-block {
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
        }        @media (max-width: 1000px) {
            .year-end-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">年末調整詳細</h1>
            <a href="{{ route('admin.work.year_end_adjustments', ['target_year' => $targetYear]) }}" class="btn btn-outline">一覧へ戻る</a>
            <form method="post" action="{{ route('admin.work.year_end_adjustments.calculate', ['applicationId' => $applicationId]) }}" onsubmit="return confirm('この対象者を再計算します。処理済の場合は更新されません。よろしいですか？');">
                @csrf
                <button type="submit" class="btn btn-primary">この1件を再計算</button>
            </form>
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
                <div class="year-end-detail-card">
                    <h2>申請状況</h2>
                    <dl class="year-end-fields">
                        <dt>状態</dt>
                        <dd><span class="year-end-status">{{ $application['status_label'] }}</span></dd>
                        <dt>計算処理</dt>
                        <dd>{{ isset($nenTyo['edit_lock']) && in_array((string) $nenTyo['edit_lock'], ['1', 'true', 'True'], true) ? '処理済' : '未処理' }}</dd>
                        <dt>対象年</dt>
                        <dd>{{ $application['target_year'] }}</dd>
                        <dt>スタッフID</dt>
                        <dd>{{ $application['staff_id'] }}</dd>
                        <dt>氏名</dt>
                        <dd>{{ $staff['staff_name'] ?? '---' }}</dd>
                        <dt>年調No</dt>
                        <dd>{{ $application['nen_tyo_no'] !== '' ? $application['nen_tyo_no'] : '---' }}</dd>
                        <dt>年末調整</dt>
                        <dd>{{ $application['year_end_adjustment'] !== '' ? $application['year_end_adjustment'] : '未回答' }}</dd>
                        <dt>特徴希望</dt>
                        <dd>{{ $application['special_collection_requested'] !== '' ? $application['special_collection_requested'] : '未回答' }}</dd>
                    </dl>
                </div>

                <div class="year-end-detail-card">
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

                <div class="year-end-detail-card">
                    <h2>スタッフ情報</h2>
                    <dl class="year-end-fields">
                        <dt>氏名カナ</dt>
                        <dd>{{ $staff['staff_name_furi'] ?? '---' }}</dd>
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

                <div class="year-end-detail-card">
                    <h2>年調計算テーブル</h2>
                    <dl class="year-end-fields">
                        <dt>年調No</dt>
                        <dd>{{ $nenTyo['nen_tyo_no'] ?? '---' }}</dd>
                        <dt>対象年</dt>
                        <dd>{{ $nenTyo['year_end'] ?? '---' }}</dd>
                        <dt>普通徴収</dt>
                        <dd>{{ $nenTyo['futu_tyoushu'] ?? '---' }}</dd>
                        <dt>処理済</dt>
                        <dd>{{ isset($nenTyo['edit_lock']) && in_array((string) $nenTyo['edit_lock'], ['1', 'true', 'True'], true) ? '済' : '未' }}</dd>
                    </dl>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2 class="section-title">計算結果サマリー</h2>
            <div class="year-end-summary-blocks">
                @foreach ($nenTyoSummaryGroups as $groupLabel => $items)
                <div class="year-end-summary-block">
                    <h3>{{ $groupLabel }}</h3>
                    <dl class="year-end-fields">
                        @foreach ($items as $key => $label)
                        <dt>{{ $label }}</dt>
                        <dd>{{ ($nenTyo[$key] ?? '') !== '' ? $nenTyo[$key] : '---' }}</dd>
                        @endforeach
                    </dl>
                </div>
                @endforeach
            </div>
        </section>
        <section class="panel">
            <h2 class="section-title">扶養情報</h2>
            <div class="year-end-table-wrap">
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
</body>

</html>