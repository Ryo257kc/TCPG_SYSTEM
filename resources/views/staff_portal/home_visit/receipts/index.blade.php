<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診入金管理</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/home-visit-receipts.css') }}">
    <style>
        /* .receipts-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .receipts-toolbar label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .receipts-toolbar input,
        .receipts-toolbar select {
            min-height: 30px;
        }
*/
        .w-keyword {
            width: 130px;
            /* flex: 1 1 120px; */
        }

        /* .w-filter {
            flex: 1 1 120px;
        } */

        /*
        .receipts-toolbar>label,
        .receipts-toolbar>div,
        .receipts-toolbar>button:not(.receipts-toolbar-visible) {
            display: none;
        }

        .receipts-title-grid {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .receipts-title-grid h2 {
            margin: 0;
            text-align: center;
        }

        .receipts-print-select {
            min-height: 30px;
            min-width: 150px;
        }

        .receipts-mismatch {
            background: #ffb3b3;
        }

        .receipts-transfer {
            background: #ffff66;
        }

        .receipts-selected-row {
            background: #fff8c5;
        }

        .receipts-section-title {
            margin: 18px 0 8px;
            font-size: 18px;
        }

        .receipts-home-visit-row {
            background: #fff8c5;
        }

        .receipts-scroll-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding-right: 4px;
        } */
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">往診入金管理</h2>
            </div>
            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif
            <div class="receipts-scroll-body">
                <form method="get" action="{{ route('home_visit.receipts') }}" class="receipts-toolbar">
                    <input type="month" name="month" value="{{ $month }}">
                    <input class="w-keyword" type="text" name="patient_name" value="{{ $patient_name }}" placeholder="患者名で検索">
                    <select class="w-filter" name="payment_visit_area">
                        <option value="">-入金店舗-</option>
                        @foreach($payment_store_name_options as $option)
                        <option value="{{ $option }}" {{ $payment_store_name === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <select class="w-filter" name="facility_name">
                        <option value="">-施設名-</option>
                        @foreach($facility_name_options as $option)
                        <option value="{{ $option }}" {{ $facility_name === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    @if($canFilterPaymentStaff)
                    <select class="w-filter" name="payment_staff">
                        <option value="">-入金担当-</option>
                        @foreach($payment_staff_options as $option)
                        <option value="{{ $option['staff_id'] }}" {{ (string) $payment_staff === (string) $option['staff_id'] ? 'selected' : '' }}>
                            {{ $option['staff_name'] !== '' ? $option['staff_name'] : $option['staff_name'] }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <button type="submit" class="btn receipts-toolbar-visible">表示</button>
                    <a href="{{ route('home_visit.receipts') }}" class="btn">クリア</a>

                    <select class="receipts-print-select" name="print_type">
                        <option value="">-印刷選択-</option>
                        <option value="payment_management">入金管理表</option>
                        <option value="insurance_receipt">領収書（保険）</option>
                        <option value="private_receipt">領収書（自費）</option>
                    </select>
                </form>

            </div>
            <div class="receipts-scroll-body receipts-list-body">
                @if($items->count() === 0)
                <div class="empty">「{{ $month }}」のデータがありません。</div>
                @else
                <div>
                    <div class="receipts-summary-head">
                        <div class="receipts-summary-meta">
                            @if($payment_store_name !== '')
                            <div>店舗：{{ $payment_store_name }}</div>
                            @endif

                            @php
                            $paymentStaffName = collect($allStaffOptions)
                            ->firstWhere('staff_id', (string) $payment_staff);
                            @endphp

                            @if($payment_staff !== '' && $paymentStaffName)
                            <div>
                                担当：{{ $paymentStaffName['staff_name'] !== '' ? $paymentStaffName['staff_name'] : $paymentStaffName['staff_name'] }}
                            </div>
                            @endif

                            @if($payment_staff !== '' && $is_payment_confirmed === 1 && $payment_confirmed_at)
                            <div class="receipts-confirmed">
                                {{ \Carbon\Carbon::parse($payment_confirmed_at)->format('Y-m-d H:i:s') }} 確定済
                            </div>
                            @endif
                        </div>
                        <h2 class="receipts-summary-title">入金管理表</h2>
                        <div class="receipts-summary-spacer"></div>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table f_size13">
                            <colgroup>
                                <col style="width: 90px;">
                                <col style="width: 30px;">
                                <col style="width: 40px;">
                                <col style="width: 20px;">
                                <col style="width: 30px;">
                                <col style="width: 30px;">
                                <col style="width: 30px;">
                                <col style="width: 30px;">
                                <col style="width: 20px;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>患者名</th>
                                    <th>入金店舗</th>
                                    <th>日報施設</th>
                                    <th>負担割合</th>
                                    <th>日報集計</th>
                                    <th>領収金額</th>
                                    <th>集金額</th>
                                    <th>入金担当</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $visibleItems = $selected_patient_id !== ''
                                ? $items->filter(fn ($row) => (string) $row->patient_id === (string) $selected_patient_id)
                                : $items;
                                @endphp
                                @foreach($visibleItems as $item)
                                @php
                                $unit_price_billing_count_total = (float) ($item->unit_price_billing_count_total ?? 0);
                                $collected_amount_total = (float) ($item->collected_amount_total ?? 0);
                                $copayment_amount_private_fee_total = (float) ($item->copayment_amount_private_fee_total ?? 0);
                                $is_collection_match = $unit_price_billing_count_total === $collected_amount_total;
                                $is_daily_report_match = $unit_price_billing_count_total === $copayment_amount_private_fee_total;
                                $collection_class = $is_collection_match ? '' : ((int) ($item->is_bank_transfer ?? 0) === 1 ? 'receipts-transfer' : 'receipts-mismatch');
                                $collection_display = (int) ($item->is_bank_transfer ?? 0) === 1 && !$is_collection_match
                                ? '振込'
                                : number_format($collected_amount_total);
                                @endphp
                                <tr class="{{ (string) $selected_patient_id === (string) $item->patient_id ? 'receipts-selected-row' : '' }}">
                                    <td>{{ $item->patient_name }}</td>
                                    <td>{{ $item->payment_store_name }}</td>
                                    <td>{{ $item->daily_report_facility_name }}</td>
                                    <td>{{ $item->burden_ratio }}</td>
                                    <td class="{{ $is_daily_report_match ? '' : 'receipts-mismatch' }}">{{ number_format($copayment_amount_private_fee_total) }}</td>
                                    <td>{{ number_format($unit_price_billing_count_total) }}</td>
                                    <td class="{{ $collection_class }}">{{ $collection_display }}</td>
                                    <td>{{ $item->payment_staff_display_name_ja ?: $item->payment_staff_staff_name }}</td>
                                    <td>
                                        <a class="btn btn_small" href="{{ route('home_visit.receipts', [
                                    'month' => $month,
                                    'patient_name' => $patient_name,
                                    'payment_store_name' => $payment_store_name,
                                    'facility_name' => $facility_name,
                                    'payment_staff' => $item->payment_staff,
                                    'patient_id' => $item->patient_id,
                                ]) }}">選択</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($selected_patient_id !== '')
                    <div class="margin_t20">
                        <a class="btn" href="{{ route('home_visit.receipts', [
                    'month' => $month,
                    'patient_name' => $patient_name,
                    'payment_store_name' => $payment_store_name,
                    'facility_name' => $facility_name,
                    'payment_staff' => $payment_staff,
                ]) }}">一覧に戻す</a>
                    </div>
                    @endif
                    @endif

                    @if($selected_patient_id !== '')
                    <!-- <h2 class="receipts-section-title">入金管理表 詳細</h2> -->
                    <br>
                    <br>
                    @if(!$selectedPatient)
                    <div class="empty">選択された患者データがありません。</div>
                    @elseif($receiptDetailRows->count() === 0)
                    <div class="empty">選択された患者の入金明細がありません。</div>
                    @else
                    <div class="receipts-title-grid">
                        <div>
                            <div>患者名：{{ $selectedPatient->patient_name }}</div>
                            <div>負担割合：{{ $selectedPatient->burden_ratio }}割</div>
                        </div>
                        <div class="toolbar-group">
                            <h2>詳細</h2>


                            @if($selected_patient_id !== '')
                            <form method="post" action="{{ route('home_visit.receipts.store') }}">
                                @csrf
                                <input type="hidden" name="patient_id" value="{{ $selected_patient_id }}">
                                <input type="hidden" name="target_month" value="{{ $month }}-01">
                                <input type="hidden" name="payment_staff" value="{{ $payment_staff }}">
                                <input type="hidden" name="payment_visit_area" value="{{ $payment_store_name }}">

                                <button type="submit" class="btn">新規追加</button>
                            </form>
                            @endif
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table f_size13">
                            <colgroup>
                                <col style="width: 100px;">
                                <col style="width: 40px;">
                                <col style="width: 50px;">
                                <col style="width: 50px;">
                                <col style="width: 50px;">
                                <col style="width: 50px;">
                                <col style="width: 50px;">
                                <col style="width: 60px;">
                                <col style="width: 70px;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>内容</th>
                                    <th>自費</th>
                                    <th>単価</th>
                                    <th>回数</th>
                                    <th>領収金額</th>
                                    <th>集金額</th>
                                    <th>過不足金額</th>
                                    <th>入金店舗</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="receipt-table-body">
                                @foreach($receiptDetailRows as $detail)
                                @php
                                $unit_price = (float) ($detail->unit_price ?? 0);
                                $billing_count = (float) ($detail->billing_count ?? 0);
                                $unit_price_billing_count = $unit_price * $billing_count;
                                $collected_amount = (float) ($detail->collected_amount ?? 0);
                                $collected_amount_display = (int) ($detail->is_bank_transfer ?? 0) === 1
                                ? '振込'
                                : number_format($collected_amount);
                                @endphp


                                <tr>
                                    <td>
                                        <span class="display-value">{{ $detail->description }}</span>
                                        <input form="form-{{ $detail->charge_id }}" class="inline-input" type="text" name="description" value="{{ $detail->description }}">
                                    </td>

                                    <td>
                                        <span class="display-value">{{ (int) ($detail->is_private_charge ?? 0) === 1 ? '○' : '' }}</span>
                                        <input form="form-{{ $detail->charge_id }}" class="inline-input" type="checkbox" name="is_private_charge" value="1"
                                            @checked((int) ($detail->is_private_charge ?? 0) === 1)>
                                    </td>

                                    <td>
                                        <span class="display-value">{{ number_format((float)$detail->unit_price) }}</span>
                                        <input form="form-{{ $detail->charge_id }}" class="inline-input" type="text" name="unit_price" value="{{ formatNumber($detail->unit_price) }}">
                                    </td>

                                    <td>
                                        <span class="display-value">{{ number_format((float)$detail->billing_count) }}</span>
                                        <input form="form-{{ $detail->charge_id }}" class="inline-input" type="text" name="billing_count" value="{{ $detail->billing_count }}">
                                    </td>

                                    <td></td>

                                    <td>
                                        <span class="display-value">{{ number_format((float)$detail->collected_amount) }}</span>
                                        <input form="form-{{ $detail->charge_id }}" class="inline-input" type="text" name="collected_amount" value="{{ formatNumber($detail->collected_amount) }}">
                                    </td>

                                    <td>
                                        <span class="display-value">{{ number_format((float)$detail->adjustment_amount) }}</span>
                                        <input form="form-{{ $detail->charge_id }}" class="inline-input" type="text" name="adjustment_amount" value="{{ formatNumber($detail->adjustment_amount) }}">
                                        <!-- <input form=" form-{{ $detail->charge_id }}" class="inline-input" type="text" name="adjustment_amount" value="{{ $detail->adjustment_amount }}"> -->
                                    </td>

                                    <td>
                                        <span class="display-value">{{ $detail->payment_visit_area }}</span>

                                        <select form="form-{{ $detail->charge_id }}" class="inline-input" name="payment_visit_area">
                                            @foreach($payment_store_name_options as $option)
                                            <option value="{{ $option }}"
                                                @selected($detail->payment_visit_area === $option)>
                                                {{ $option }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <button type="button" class="btn_small edit-trigger">編集</button>

                                        <div class="edit-only">
                                            <button form="update-form-{{ $detail->charge_id }}" type="submit" class="btn_small">保存</button>

                                            <button type="button" class="btn_small cancel-edit">取消</button>

                                            <button form="delete-form-{{ $detail->charge_id }}"
                                                type="submit"
                                                onclick="return confirm('削除しますか？')"
                                                class="btn_small">
                                                削除
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <form id="update-form-{{ $detail->charge_id }}"
                                    method="post"
                                    action="{{ route('home_visit.receipts.update', ['id' => $detail->charge_id]) }}">
                                    @csrf
                                </form>

                                <form id="delete-form-{{ $detail->charge_id }}"
                                    method="post"
                                    action="{{ route('home_visit.receipts.destroy', ['id' => $detail->charge_id]) }}">
                                    @csrf
                                    @method('DELETE')
                                </form>

                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p>領収合計：{{ number_format($unit_price_billing_count_detail_total) }}円</p>
                    @endif


                    <div class="receipts-title-grid">
                        <h2>施術履歴</h2>
                    </div>

                    @if($treatmentHistoryRows->count() === 0)
                    <div class="empty">施術履歴がありません。</div>
                    @else
                    <div>（※黄色=往診）</div>
                    <div class="table-wrap">
                        <table class="data-table f_size13">
                            <thead>
                                <tr>
                                    <th>日付</th>
                                    <th>マッサージ</th>
                                    <th>施術</th>
                                    <th>距離</th>
                                    <th>開始</th>
                                    <th>終了</th>
                                    <th>日報店舗</th>
                                    <th>標準<br>距離</th>
                                    <th>負担金</th>
                                    <th>自費</th>
                                    <th>備考</th>
                                    <th>施術担当</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($treatmentHistoryRows as $history)
                                @php
                                $start_time = $history->start_time ? \Carbon\Carbon::parse($history->start_time)->format('G:i') : '';
                                $end_time = $history->end_time ? \Carbon\Carbon::parse($history->end_time)->format('G:i') : '';
                                $start_time = $start_time === '0:00' ? '' : $start_time;
                                $end_time = $end_time === '0:00' ? '' : $end_time;
                                $distance = (float) ($history->distance ?? 0);
                                $standard_distance = (float) ($history->standard_distance ?? 0);
                                $copayment_amount = (float) ($history->copayment_amount ?? 0);
                                $private_fee = (float) ($history->private_fee ?? 0);
                                @endphp
                                <tr class="{{ (int) ($history->is_home_visit ?? 0) === 1 ? 'receipts-home-visit-row' : '' }}">
                                    <td>{{ $history->treatment_date ? \Carbon\Carbon::parse($history->treatment_date)->format('n/j') : '' }}</td>
                                    <td>{{ (int) ($history->is_ma_treatment ?? 0) === 1 ? '○' : '' }}</td>
                                    <td>{{ (int) ($history->is_no_treatment ?? 0) === 1 ? '×' : '' }}</td>
                                    <td>{{ $distance == 0.0 ? '' : number_format($distance, 1) }}</td>
                                    <td>{{ $start_time }}</td>
                                    <td>{{ $end_time }}</td>
                                    <td>{{ $history->daily_report_store_name }}</td>
                                    <td>{{ $standard_distance == 0.0 ? '' : number_format($standard_distance, 1) }}</td>
                                    <td>{{ $copayment_amount == 0.0 ? '' : number_format($copayment_amount) }}</td>
                                    <td>{{ $private_fee == 0.0 ? '' : number_format($private_fee) }}</td>
                                    <td>{{ $history->remarks }}</td>
                                    <td>{{ $history->staff_display_name_ja ?: $history->staff_name }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            <div class="receipts-back-row">
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
                <!-- <a href="{{ route('home_visit.receipts') }}" class="btn_back">戻る</a> -->
                <!-- <button type="button" class="btn_back" onclick="history.back()">戻る</button> -->
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('.edit-trigger').forEach(btn => {
            btn.addEventListener('click', () => {
                const tr = btn.closest('tr');
                tr.classList.add('is-editing');
            });
        });

        document.querySelectorAll('.cancel-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const tr = btn.closest('tr');
                tr.classList.remove('is-editing');
            });
        });

        //    1行追加
        // document.getElementById('add-row').addEventListener('click', () => {
        //     const tbody = document.getElementById('receipt-table-body');
        //     const template = document.getElementById('new-row-template');

        //     const clone = template.content.cloneNode(true);
        //     const row = clone.querySelector('tr');

        //     clone.querySelector('.cancel-add').addEventListener('click', () => {
        //         row.remove();
        //     });

        //     tbody.prepend(clone);
        // });
    </script>
</body>

</html>
