<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - レセ請求入力</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/receipt-entry.css') }}">
</head>
<body>
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section
        class="panel content-panel receipt-entry-panel"
        data-receipt-save-url="{{ route('office.receipt.entry.save') }}"
        data-receipt-delete-url="{{ route('office.receipt.entry.delete') }}"
        data-receipt-csrf="{{ csrf_token() }}"
    >
        <div class="content-head">
            <h1 class="content-title">レセ請求入力</h1>
        </div>

        <form method="get" action="{{ route('office.receipt.entry') }}" class="office-toolbar">
            <div class="office-toolbar-group">
                <label for="receipt-target-month">施術月</label>
                <input id="receipt-target-month" name="target_month" type="month" value="{{ $targetMonth }}">

                <label for="receipt-store">店舗選択</label>
                <select id="receipt-store" name="store_name">
                    <option value="">--</option>
                    @foreach (($storeOptions ?? []) as $storeName)
                        <option value="{{ $storeName }}" @selected(($selectedStore ?? '') === $storeName)>{{ $storeName }}</option>
                    @endforeach
                </select>

                <a href="{{ route('office.receipt') }}" class="btn margin20">戻る</a>
                <button type="submit" class="btn btn-primary">表示</button>
                <button type="button" class="btn">柔整取込</button>
            </div>
            <button type="button" class="btn btn-primary receipt-entry-toolbar-add-btn" data-receipt-add>新規追加</button>
        </form>

        <div class="table-wrap receipt-entry-list-wrap">
            <div class="receipt-entry-alert receipt-entry-alert-error" data-receipt-inline-error hidden></div>
            <table class="data-table">
                    <colgroup>
                        <col style="width: 60px;">
                        <col style="width: 100px;">
                        <col style="width: 50px;">
                        <col style="width: 35px;">
                        <col style="width: 45px;">
                        <col style="width: 65px;">
                        <col style="width: 65px;">
                        <col style="width: 65px;">
                        <col style="width: 65px;">
                        <col style="width: 65px;">
                        <col style="width: 90px;">
                        <col style="width: 100px;">
                        <col style="width: 70px;">
                        <col style="width: 40px;">
                    </colgroup>
                <thead>
                    <tr>
                        <th>保険者番号</th>
                        <th>保険者名称</th>
                        <th>集計<br>区分</th>
                        <th>枚数</th>
                        <th>部位数</th>
                        <th>合計金額</th>
                        <th>一部負担金</th>
                        <th>請求金額</th>
                        <th>返戻額</th>
                        <th>修正額</th>
                        <th>患者名</th>
                        <th>備考</th>
                        <th>入金額</th>
                        <th>編集</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($receiptRows ?? []) as $row)
                        <tr class="receipt-entry-row" data-receipt-row data-receipt-id="{{ $row['insurance_claim_detail_id'] }}">
                            <td>
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['insurer_number'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control" data-insurer-number-input list="receipt-insurer-number-options" maxlength="8" value="{{ $row['insurer_number_raw'] }}">
                            </td>
                            <td class="receipt-entry-wrap">
                                <span class="receipt-entry-readonly">{{ $row['insurer_name'] }}</span>
                            </td>
                            <td>
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['summary_category'] }}</span>
                                <select class="form-input receipt-entry-edit-control">
                                    <option value="">--</option>
                                    <option value="本人" @selected($row['summary_category_raw'] === '本人')>本人</option>
                                    <option value="家族" @selected($row['summary_category_raw'] === '家族')>家族</option>
                                </select>
                            </td>
                            <td>
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['total_sheets'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control" value="{{ $row['total_sheets_raw'] }}">
                            </td>
                            <td>
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['total_parts_count'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control" value="{{ $row['total_parts_count_raw'] }}">
                            </td>
                            <td class="receipt-entry-money-cell">
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['total_amount'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="{{ $row['total_amount_raw'] }}">
                            </td>
                            <td class="receipt-entry-money-cell">
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['copayment_amount'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="{{ $row['copayment_amount_raw'] }}">
                            </td>
                            <td class="receipt-entry-money-cell">
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['claim_amount'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="{{ $row['claim_amount_raw'] }}">
                            </td>
                            <td class="receipt-entry-money-cell">
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['returned_amount'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="{{ $row['returned_amount_raw'] }}">
                            </td>
                            <td class="receipt-entry-money-cell">
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['adjustment_amount'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="{{ $row['adjustment_amount_raw'] }}">
                            </td>
                            <td>
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['patient_name'] }}</span>
                                <input type="text" class="form-input receipt-entry-edit-control" value="{{ $row['patient_name_raw'] }}">
                            </td>
                            <td class="receipt-entry-wrap">
                                <span class="receipt-entry-readonly" data-receipt-editable-display>{{ $row['remarks'] }}</span>
                                <textarea class="form-input receipt-entry-edit-control" rows="2">{{ $row['remarks_raw'] }}</textarea>
                            </td>
                            <td class="receipt-entry-money-cell">
                                <span class="receipt-entry-readonly">{{ $row['payment_amount'] }}</span>
                            </td>
                            <td class="receipt-entry-row-actions">
                                <button type="button" class="receipt-entry-toggle" data-receipt-toggle aria-expanded="false" aria-label="詳細を開く">▾</button>
                            </td>
                        </tr>
                        <tr class="receipt-entry-detail" data-receipt-detail hidden>
                            <td colspan="14" class="receipt-entry-detail-cell">
                                <div class="receipt-entry-detail-layout">
                                    <div class="receipt-entry-detail-grid">
                                        <div class="receipt-entry-detail-item">
                                            <span class="receipt-entry-detail-label">施術月</span>
                                            <span class="receipt-entry-detail-value">{{ $row['treatment_month'] }}</span>
                                        </div>
                                        <div class="receipt-entry-detail-item">
                                            <span class="receipt-entry-detail-label">保険区分</span>
                                            <span class="receipt-entry-detail-value">{{ $row['insurance_category'] }}</span>
                                        </div>
                                        <div class="receipt-entry-detail-item">
                                            <span class="receipt-entry-detail-label">入金名称</span>
                                            <span class="receipt-entry-detail-value">{{ $row['deposit_name'] }}</span>
                                        </div>
                                        <div class="receipt-entry-detail-item">
                                            <span class="receipt-entry-detail-label">店舗名</span>
                                            <span class="receipt-entry-detail-value">{{ $row['store_name'] }}</span>
                                        </div>
                                        <div class="receipt-entry-detail-item">
                                            <span class="receipt-entry-detail-label">入金日</span>
                                            <span class="receipt-entry-detail-value">{{ $row['payment_date_display'] }}</span>
                                        </div>
                                    </div>
                                    <div class="receipt-entry-detail-actions">
                                        <button type="button" class="btn" data-receipt-duplicate>複製</button>
                                        <button type="button" class="btn btn-primary">保存</button>
                                        <button type="button" class="btn">取消</button>
                                        <button type="button" class="btn">削除</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-receipt-empty>
                            <td colspan="14">対象データがありません。</td>
                        </tr>
                    @endforelse
                    <tr class="receipt-entry-row" data-receipt-row data-receipt-id="" data-receipt-template hidden>
                        <td>
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control" data-insurer-number-input list="receipt-insurer-number-options" maxlength="8" value="">
                        </td>
                        <td class="receipt-entry-wrap">
                            <span class="receipt-entry-readonly"></span>
                        </td>
                        <td>
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <select class="form-input receipt-entry-edit-control">
                                <option value="">--</option>
                                <option value="本人">本人</option>
                                <option value="家族">家族</option>
                            </select>
                        </td>
                        <td>
                            <span class="receipt-entry-readonly"></span>
                        </td>
                        <td>
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control" value="">
                        </td>
                        <td class="receipt-entry-money-cell">
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="">
                        </td>
                        <td class="receipt-entry-money-cell">
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="">
                        </td>
                        <td class="receipt-entry-money-cell">
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="">
                        </td>
                        <td class="receipt-entry-money-cell">
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="">
                        </td>
                        <td class="receipt-entry-money-cell">
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control receipt-entry-money-input" value="">
                        </td>
                        <td>
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <input type="text" class="form-input receipt-entry-edit-control" value="">
                        </td>
                        <td class="receipt-entry-wrap">
                            <span class="receipt-entry-readonly" data-receipt-editable-display></span>
                            <textarea class="form-input receipt-entry-edit-control" rows="2"></textarea>
                        </td>
                        <td class="receipt-entry-money-cell">
                            <span class="receipt-entry-readonly"></span>
                        </td>
                        <td class="receipt-entry-row-actions">
                            <button type="button" class="receipt-entry-toggle" data-receipt-toggle aria-expanded="false" aria-label="詳細を開く">▾</button>
                        </td>
                    </tr>
                    <tr class="receipt-entry-detail" data-receipt-detail data-receipt-template hidden>
                        <td colspan="14" class="receipt-entry-detail-cell">
                            <div class="receipt-entry-detail-layout">
                                <div class="receipt-entry-detail-grid">
                                    <div class="receipt-entry-detail-item">
                                        <span class="receipt-entry-detail-label">施術月</span>
                                        <span class="receipt-entry-detail-value"></span>
                                    </div>
                                    <div class="receipt-entry-detail-item">
                                        <span class="receipt-entry-detail-label">保険区分</span>
                                        <span class="receipt-entry-detail-value"></span>
                                    </div>
                                    <div class="receipt-entry-detail-item">
                                        <span class="receipt-entry-detail-label">入金名称</span>
                                        <span class="receipt-entry-detail-value"></span>
                                    </div>
                                    <div class="receipt-entry-detail-item">
                                        <span class="receipt-entry-detail-label">店舗名</span>
                                        <span class="receipt-entry-detail-value"></span>
                                    </div>
                                    <div class="receipt-entry-detail-item">
                                        <span class="receipt-entry-detail-label">入金日</span>
                                        <span class="receipt-entry-detail-value"></span>
                                    </div>
                                </div>
                                <div class="receipt-entry-detail-actions">
                                    <button type="button" class="btn" data-receipt-duplicate>複製</button>
                                    <button type="button" class="btn btn-primary">保存</button>
                                    <button type="button" class="btn">取消</button>
                                    <button type="button" class="btn">削除</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-wrap receipt-entry-summary-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>件数</th>
                        <th>種別</th>
                        <th>枚数</th>
                        <th>部位数</th>
                        <th>合計金額</th>
                        <th>一部負担金</th>
                        <th>請求金額</th>
                        <th>修正額</th>
                        <th>返戻額</th>
                        <th>総合計</th>
                        <th>入金額</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($receiptSummaryRows ?? []) as $summaryRow)
                        <tr>
                            <td>{{ $summaryRow['row_count'] }}</td>
                            <td>{{ $summaryRow['label'] }}</td>
                            <td>{{ $summaryRow['total_sheets'] }}</td>
                            <td>{{ $summaryRow['total_parts_count'] }}</td>
                            <td>{{ $summaryRow['total_amount'] }}</td>
                            <td>{{ $summaryRow['copayment_amount'] }}</td>
                            <td>{{ $summaryRow['claim_amount'] }}</td>
                            <td>{{ $summaryRow['adjustment_amount'] }}</td>
                            <td>{{ $summaryRow['returned_amount'] }}</td>
                            <td>{{ $summaryRow['grand_total'] }}</td>
                            <td>{{ $summaryRow['payment_amount'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>
<datalist id="receipt-insurer-number-options"></datalist>
@include('staff_portal.office.receipt_entry.page_script')

</body>
</html>
