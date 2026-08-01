<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 請求一覧</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <style>
        .billing-layout {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            gap: 14px;
            align-items: start;
            max-width: 100%;
        }

        .billing-layout .panel {
            min-width: 0;
        }

        .billing-list-panel {
            position: sticky;
            top: 12px;
        }

        .billing-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .billing-section-head h2 {
            margin: 0;
            color: #1f4f8f;
            font-size: 18px;
        }

        .billing-filter {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }

        .billing-filter label,
        .billing-form label {
            display: grid;
            gap: 4px;
            color: #44546f;
            font-size: 12px;
            font-weight: 700;
        }

        .billing-filter input,
        .billing-filter select,
        .billing-form input,
        .billing-form select,
        .billing-form textarea {
            width: 100%;
            box-sizing: border-box;
        }

        .billing-list {
            display: grid;
            gap: 8px;
            max-height: calc(100vh - 290px);
            overflow: auto;
            padding-right: 2px;
        }

        .billing-list-item {
            display: grid;
            gap: 4px;
            padding: 10px;
            border: 1px solid #d3dff0;
            border-radius: 8px;
            color: #253043;
            text-decoration: none;
            background: #fff;
        }

        .billing-list-item.is-active {
            border-color: #1f4f8f;
            background: #f0f6ff;
        }

        .billing-list-main,
        .billing-list-sub,
        .billing-list-amount,
        .billing-totals,
        .billing-bank-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .billing-list-main {
            justify-content: space-between;
            font-weight: 800;
        }

        .billing-list-sub,
        .billing-list-amount,
        .billing-bank-preview {
            color: #667085;
            font-size: 12px;
        }

        .billing-main {
            display: grid;
            gap: 14px;
            min-width: 0;
            max-width: 100%;
        }

        .billing-form {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .billing-form-wide {
            grid-column: span 3;
        }

        .billing-bank-preview {
            grid-column: span 3;
            padding: 8px 10px;
            border: 1px solid #d3dff0;
            border-radius: 8px;
            background: #f8fbff;
        }

        .billing-actions {
            grid-column: span 3;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .billing-totals {
            justify-content: flex-end;
            color: #1f2f46;
            font-weight: 800;
        }

        .billing-detail-table-wrap {
            max-width: 100%;
            overflow-x: auto;
        }

        .billing-detail-table {
            min-width: 860px;
            table-layout: fixed;
        }

        .billing-detail-table input,
        .billing-detail-table select {
            width: 100%;
            box-sizing: border-box;
            min-width: 0;
            font-size: 12px;
        }

        .billing-detail-add-row td {
            background: #f8fbff;
        }

        .billing-detail-actions {
            display: flex;
            gap: 4px;
            justify-content: center;
        }

        @media (max-width: 1100px) {
            .billing-layout {
                grid-template-columns: 1fr;
            }

            .billing-list-panel {
                position: static;
            }

            .billing-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .billing-form-wide,
            .billing-bank-preview,
            .billing-actions {
                grid-column: span 2;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">請求一覧</h1>
        </div>

        @if (session('status'))
        <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
        @endif

        <section class="billing-layout">
            <aside class="panel billing-list-panel">
                <div class="billing-section-head">
                    <h2>一覧</h2>
                    <span class="meta-count">{{ count($invoices) }}件</span>
                </div>

                <form method="get" action="{{ route('admin.work.billing_list') }}" class="billing-filter">
                    <label>
                        <span>表示</span>
                        <select name="status">
                            <option value="all" @selected(($filters['status'] ?? 'all' )==='all' )>全て</option>
                            <option value="paid" @selected(($filters['status'] ?? 'all' )==='paid' )>入金済</option>
                            <option value="unpaid" @selected(($filters['status'] ?? 'all' )==='unpaid' )>未入金</option>
                        </select>
                    </label>
                    <label>
                        <span>請求元</span>
                        <select name="billing_source">
                            <option value=""></option>
                            @foreach ($companyOptions as $company)
                            <option value="{{ $company['company_id'] }}" @selected(($filters['billing_source'] ?? '' )===$company['company_id'])>{{ $company['company_name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>検索</span>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}">
                    </label>
                    <button type="submit" class="btn btn-primary">表示</button>
                </form>

                <div class="billing-list">
                    @forelse ($invoices as $invoice)
                    <a class="billing-list-item {{ (int) ($filters['invoice_id'] ?? 0) === (int) $invoice['invoice_id'] ? 'is-active' : '' }}" href="{{ route('admin.work.billing_list', ['invoice_id' => $invoice['invoice_id'], 'status' => $filters['status'] ?? 'all', 'billing_source' => $filters['billing_source'] ?? '', 'q' => $filters['q'] ?? '']) }}">
                        <span class="billing-list-main">
                            <span>{{ $invoice['recipient_name'] ?: '宛先なし' }}</span>
                        </span>
                        <span class="billing-list-sub">
                            <span>{{ $invoice['invoice_date_label'] }}</span>
                            <span>{{ $invoice['subject'] }}</span>
                        </span>
                        <span class="billing-list-amount">
                            <span>請求 {{ $invoice['total_amount'] ?: '0' }}</span>
                            <span>未入金 {{ $invoice['remaining_amount'] ?: '0' }}</span>
                        </span>
                    </a>
                    @empty
                    <div class="empty">データがありません。</div>
                    @endforelse
                </div>
            </aside>

            <main class="billing-main">
                <section class="panel">
                    <div class="billing-section-head">
                        <h2>新規登録</h2>
                    </div>

                    <form method="post" action="{{ route('admin.work.billing_list.store') }}" class="billing-form">
                        @csrf
                        <label>
                            <span>請求日</span>
                            <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}">
                        </label>
                        <label>
                            <span>宛先</span>
                            <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" maxlength="50">
                        </label>
                        <label>
                            <span>件名</span>
                            <input type="text" name="subject" value="{{ old('subject') }}" maxlength="30">
                        </label>
                        <label>
                            <span>請求元</span>
                            <select name="billing_source">
                                <option value=""></option>
                                @foreach ($companyOptions as $company)
                                <option value="{{ $company['company_id'] }}" @selected(old('billing_source')===$company['company_id'])>{{ $company['company_name'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>入金額</span>
                            <input type="number" name="paid_amount" value="{{ old('paid_amount', 0) }}" step="1">
                        </label>
                        <label class="billing-form-wide">
                            <span>備考</span>
                            <textarea name="remarks" rows="2">{{ old('remarks') }}</textarea>
                        </label>
                        <label class="billing-form-wide">
                            <span>経理備考</span>
                            <textarea name="accounting_remarks" rows="2">{{ old('accounting_remarks') }}</textarea>
                        </label>
                        <div class="billing-actions">
                            <button type="submit" class="btn btn-primary">登録</button>
                        </div>
                    </form>
                </section>

                @if ($selectedInvoice)
                <section class="panel">
                    <div class="billing-section-head">
                        <h2>請求書 No.{{ $selectedInvoice['invoice_id'] }}</h2>
                        <div class="billing-totals">
                            <span>総請求額 {{ $selectedInvoice['total_amount'] ?: '0' }}　　　</span>
                            <span>入金額 {{ $selectedInvoice['paid_amount'] ?: '0' }}　　　</span>
                            <span>未入金 {{ $selectedInvoice['remaining_amount'] ?: '0' }}</span>
                        </div>
                    </div>

                    <form method="post" action="{{ route('admin.work.billing_list.update') }}" class="billing-form">
                        @csrf
                        <input type="hidden" name="invoice_id" value="{{ $selectedInvoice['invoice_id'] }}">
                        <label>
                            <span>請求日</span>
                            <input type="date" name="invoice_date" value="{{ $selectedInvoice['invoice_date'] }}">
                        </label>
                        <label>
                            <span>宛先</span>
                            <input type="text" name="recipient_name" value="{{ $selectedInvoice['recipient_name'] }}" maxlength="50">
                        </label>
                        <label>
                            <span>件名</span>
                            <input type="text" name="subject" value="{{ $selectedInvoice['subject'] }}" maxlength="30">
                        </label>
                        <label>
                            <span>請求元</span>
                                <select name="billing_source">
                                    <option value=""></option>
                                    @foreach ($companyOptions as $company)
                                    <option value="{{ $company['company_id'] }}" @selected($selectedInvoice['billing_source']===$company['company_id'])>{{ $company['company_name'] }}</option>
                                    @endforeach
                                </select>
                        </label>
                        <label>
                            <span>入金額</span>
                            <input type="number" name="paid_amount" value="{{ str_replace(',', '', $selectedInvoice['paid_amount']) }}" step="1">
                        </label>
                        <label class="billing-form-wide">
                            <span>備考</span>
                            <textarea name="remarks" rows="2">{{ $selectedInvoice['remarks'] }}</textarea>
                        </label>
                        <label class="billing-form-wide">
                            <span>経理備考</span>
                            <textarea name="accounting_remarks" rows="2">{{ $selectedInvoice['accounting_remarks'] }}</textarea>
                        </label>
                        <div class="billing-bank-preview">
                            <span>振込先</span>
                            <span>{{ $selectedInvoice['billing_bank_name'] }}</span>
                            <span>{{ $selectedInvoice['billing_bank_account'] }}</span>
                        </div>
                        <div class="billing-actions">
                            <button type="submit" class="btn btn-primary">保存</button>
                            <a class="btn" href="{{ route('admin.work.billing_list.print', ['invoiceId' => $selectedInvoice['invoice_id']]) }}" target="_blank" rel="noopener">印刷</a>
                            <button type="submit" class="btn" formaction="{{ route('admin.work.billing_list.delete') }}" onclick="return confirm('請求書を削除しますか？');">削除</button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <div class="billing-section-head">
                        <h2>明細</h2>
                    </div>

                    <div class="billing-detail-table-wrap">
                        <table class="data-table billing-detail-table f_size13">
                            <colgroup>
                                <col style="width: 18%;">
                                <col style="width: 12%;">
                                <col style="width: 12%;">
                                <col style="width: 5%;">
                                <col style="width: 5%;">
                                <col style="width: 9%;">
                                <col style="width: 9%;">
                                <col style="width: 7%;">
                                <col style="width: 18%;">
                                <col style="width: 78px;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>項目</th>
                                    <th>区分</th>
                                    <th>店舗</th>
                                    <th>数量</th>
                                    <th>単位</th>
                                    <th>単価</th>
                                    <th>合計</th>
                                    <th>税区分</th>
                                    <th>備考</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($details as $detail)
                                <tr>
                                    <td>
                                        <form id="billing-detail-update-{{ $detail['invoice_detail_id'] }}" method="post" action="{{ route('admin.work.billing_list.detail.update') }}">
                                            @csrf
                                            <input type="hidden" name="invoice_id" value="{{ $selectedInvoice['invoice_id'] }}">
                                            <input type="hidden" name="invoice_detail_id" value="{{ $detail['invoice_detail_id'] }}">
                                        </form>
                                        <input form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="text" name="item_name" value="{{ $detail['item_name'] }}" maxlength="50">
                                    </td>
                                    <td><input form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="text" name="category" value="{{ $detail['category'] }}" maxlength="30"></td>
                                            <td>
                                                <select form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" name="store_name">
                                                    <option value=""></option>
                                                    @if (($detail['store_name'] ?? '') !== '' && !collect($storeOptions)->contains('value', $detail['store_name']))
                                                        <option value="{{ $detail['store_name'] }}" selected>{{ $detail['store_name'] }}</option>
                                                    @endif
                                                    @foreach ($storeOptions as $store)
                                                        <option value="{{ $store['value'] }}" @selected($detail['store_name'] === $store['value'])>{{ $store['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                    <td><input form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="number" name="quantity" value="{{ str_replace(',', '', $detail['quantity']) }}" step="1"></td>
                                    <td><input form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="text" name="unit" value="{{ $detail['unit'] }}" maxlength="10"></td>
                                    <td><input form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="text" name="unit_price" value="{{ $detail['unit_price'] }}" inputmode="numeric"></td>
                                    <td><input form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="text" name="total_amount" value="{{ $detail['total_amount'] }}" inputmode="numeric"></td>
                                    <td>
                                        <select form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" name="tax_category">
                                            <option value=""></option>
                                            @foreach ($taxCategories as $taxCategory)
                                            <option value="{{ $taxCategory }}" @selected($detail['tax_category']===$taxCategory)>{{ $taxCategory }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="text" name="remarks" value="{{ $detail['remarks'] }}" maxlength="100"></td>
                                    <td>
                                        <span class="billing-detail-actions">
                                            <button form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="submit" class="btn btn-small btn-primary">保存</button>
                                            <button form="billing-detail-update-{{ $detail['invoice_detail_id'] }}" type="submit" class="btn btn-small" formaction="{{ route('admin.work.billing_list.detail.delete') }}" onclick="return confirm('明細を削除しますか？');">削除</button>
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="empty">明細がありません。</td>
                                </tr>
                                @endforelse
                                <tr class="billing-detail-add-row">
                                    <td>
                                        <form id="billing-detail-store" method="post" action="{{ route('admin.work.billing_list.detail.store') }}">
                                            @csrf
                                            <input type="hidden" name="invoice_id" value="{{ $selectedInvoice['invoice_id'] }}">
                                        </form>
                                        <input form="billing-detail-store" type="text" name="item_name" placeholder="項目" maxlength="50">
                                    </td>
                                    <td><input form="billing-detail-store" type="text" name="category" placeholder="区分" maxlength="30"></td>
                                        <td>
                                            <select form="billing-detail-store" name="store_name">
                                                <option value=""></option>
                                                @foreach ($storeOptions as $store)
                                                    <option value="{{ $store['value'] }}">{{ $store['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    <td><input form="billing-detail-store" type="number" name="quantity" placeholder="数量" step="1"></td>
                                    <td><input form="billing-detail-store" type="text" name="unit" placeholder="単位" maxlength="10"></td>
                                    <td><input form="billing-detail-store" type="text" name="unit_price" placeholder="単価" inputmode="numeric"></td>
                                    <td><input form="billing-detail-store" type="text" name="total_amount" placeholder="合計" inputmode="numeric"></td>
                                    <td>
                                        <select form="billing-detail-store" name="tax_category">
                                            <option value=""></option>
                                            @foreach ($taxCategories as $taxCategory)
                                            <option value="{{ $taxCategory }}">{{ $taxCategory }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input form="billing-detail-store" type="text" name="remarks" placeholder="備考" maxlength="100"></td>
                                    <td><button form="billing-detail-store" type="submit" class="btn btn-small btn-primary">追加</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                @endif
            </main>
        </section>
    </div>
</body>

</html>
