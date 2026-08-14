<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 保険者一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">

    <style>
        .insurers-panel {
            overflow: hidden;
        }

        .insurers-table-wrap {
            flex: 1 1 0;
            min-height: 0;
            max-height: calc(100vh - 260px);
            overflow-x: auto;
            overflow-y: auto;
            position: relative;
        }

        .insurers-table-wrap .data-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #fff4e8;
        }

        .insurer-inline-input {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 6px 8px;
            border: 1px solid #cfd7e3;
            border-radius: 6px;
            background: #fff;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.2;
            display: block;
        }

        .office-toolbar .insurer-inline-input {
            width: auto;
            min-width: 160px;
        }

        .office-toolbar .office-toolbar-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .office-toolbar .office-toolbar-group label {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .insurers-tab-row {
            margin: 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .insurers-tab-add-btn {
            margin-left: auto;
        }

        .insurers-table-wrap .insurers-name-cell,
        .insurers-table-wrap .data-table-wrap {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }

        [data-insurer-input] {
            display: none !important;
        }

        [data-insurer-row][data-is-editing="1"] [data-insurer-input] {
            display: block !important;
        }

        [data-insurer-row][data-is-editing="1"] [data-insurer-display] {
            display: none !important;
        }

        .insurer-row-actions {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .insurer-row-actions .btn {
            padding: 3px 6px;
            font-size: 11px;
            line-height: 1.2;
        }
    </style>
</head>

<body>
    <main class="container">@include('staff_portal.shared.app_header', ['displayName'=> $displayName, 'hidePayrollLinks'=> $hidePayrollLinks ?? false]) @php $oldOriginalInsurerNumber =trim((string) old('original_insurer_number', ''));
        $oldInsurerNumber =old('insurer_number');
        $hasOldInsurerInput =$oldInsurerNumber !==null;
        $isNewErrorRow =$hasOldInsurerInput && $oldOriginalInsurerNumber ==='';
        @endphp <section class="panel content-panel sales-panel staff-viewport-panel insurers-panel"
            data-insurer-save-url="{{ route('office.receipt.insurers.save') }}"
            data-insurer-delete-url="{{ route('office.receipt.insurers.delete') }}"
            data-insurer-csrf="{{ csrf_token() }}">
            <div class="content-head">
                <h2 class="content-title">保険者一覧</h2>
            </div>
            <form method="get" action="{{ route('office.receipt.insurers') }}" class="office-toolbar">
                <div class="office-toolbar-group">
                    <input id="insurer-search" name="search" type="text" value="{{ $search ?? '' }}" class="insurer-inline-input" placeholder="保険者検索">
                    <label>入金名称表示</label>
                    <label><input type="radio" name="deposit_name_display" value="all" @checked(($depositNameFilter ?? 'all' )==='all' )>全て</label>
                    <label><input type="radio" name="deposit_name_display" value="with" @checked(($depositNameFilter ?? 'all' )==='with' )>有</label>
                    <label><input type="radio" name="deposit_name_display" value="without" @checked(($depositNameFilter ?? 'all' )==='without' )>無</label>
                    <button type="submit" class="btn btn-primary margin_l20">表示</button>
                </div>
            </form>
            @if (session('errorMessage'))
            <div class="error">{{ session('errorMessage') }}</div>
            @endif @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif @if (session('successMessage'))
            <div class="status">{{ session('successMessage') }}</div>@endif
            <datalist id="insurer-scheduled-payment-name-options">
                @foreach (($scheduledPaymentNameOptions ?? []) as $paymentNameOption)
                <option value="{{ $paymentNameOption }}"></option>
                @endforeach
            </datalist>
            <div class="insurers-tab-row">
                <a href="{{ route('office.receipt.insurers', ['search' => ($search ?? ''), 'deposit_name_display' => ($depositNameFilter ?? 'all'), 'tab' => 'all']) }}" class="btn {{ ($activeTab ?? 'all') === 'all' ? 'btn-primary' : '' }}">全て</a>
                <a href="{{ route('office.receipt.insurers', ['search' => ($search ?? ''), 'deposit_name_display' => ($depositNameFilter ?? 'all'), 'tab' => 'general']) }}" class="btn {{ ($activeTab ?? 'all') === 'general' ? 'btn-primary' : '' }}">一般</a>
                <a href="{{ route('office.receipt.insurers', ['search' => ($search ?? ''), 'deposit_name_display' => ($depositNameFilter ?? 'all'), 'tab' => 'elderly']) }}" class="btn {{ ($activeTab ?? 'all') === 'elderly' ? 'btn-primary' : '' }}">後期高齢</a>
                <a href="{{ route('office.receipt.insurers', ['search' => ($search ?? ''), 'deposit_name_display' => ($depositNameFilter ?? 'all'), 'tab' => 'medical_support']) }}" class="btn {{ ($activeTab ?? 'all') === 'medical_support' ? 'btn-primary' : '' }}">医療助成</a>
                <a href="{{ route('office.receipt.insurers', ['search' => ($search ?? ''), 'deposit_name_display' => ($depositNameFilter ?? 'all'), 'tab' => 'incomplete']) }}" class="btn {{ ($activeTab ?? 'all') === 'incomplete' ? 'btn-primary' : '' }}">未入力項目</a>
                <button type="button" class="btn insurers-tab-add-btn" data-insurer-add>新規追加</button>
            </div>

            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table f_size13">
                    <colgroup>
                        <col style="width: 40px;">
                        <col style="width: 100px;">
                        <col style="width: 60px;">
                        <col style="width: 60px;">
                        <col style="width: 50px;">
                        <col style="width: 40px;">
                        <col style="width: 50px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>保険者番号</th>
                            <th>保険者名称</th>
                            <th>入金名称(柔整)</th>
                            <th>入金名称(鍼灸)</th>
                            <th>保険種別</th>
                            <th>助成種類</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>@if ($isNewErrorRow) <tr data-insurer-row data-is-new="1" data-is-editing="1" data-original-insurer-number="">
                            <td><span data-insurer-display> {
                                    {
                                    old('insurer_number', '')
                                    }
                                    }

                                </span><input type="text" value="{{ old('insurer_number', '') }}" class="insurer-inline-input" data-insurer-input hidden></td>
                            <td class="insurers-name-cell data-table-wrap"><span data-insurer-display> {
                                    {
                                    old('insurer_name', '')
                                    }
                                    }

                                </span><input type="text" value="{{ old('insurer_name', '') }}" class="insurer-inline-input" data-insurer-input hidden></td>
                            <td><span data-insurer-display> {
                                    {
                                    old('scheduled_payment_name', '')
                                    }
                                    }

                                </span><input type="text" value="{{ old('scheduled_payment_name', '') }}" class="insurer-inline-input" data-insurer-input list="insurer-scheduled-payment-name-options" hidden></td>
                            <td><span data-insurer-display> {
                                    {
                                    old('scheduled_payment_name_2', '')
                                    }
                                    }

                                </span><input type="text" value="{{ old('scheduled_payment_name_2', '') }}" class="insurer-inline-input" data-insurer-input list="insurer-scheduled-payment-name-options" hidden></td>
                            <td><span data-insurer-display> {
                                    {
                                    old('insurance_type', '')
                                    }
                                    }

                                </span><select class="insurer-inline-input" data-insurer-input hidden>
                                    <option value=""></option>
                                    <option value="区外国保" @selected(old('insurance_type', '' )==='区外国保' )>区外国保</option>
                                    <option value="一般国保" @selected(old('insurance_type', '' )==='一般国保' )>一般国保</option>
                                    <option value="協会けんぽ" @selected(old('insurance_type', '' )==='協会けんぽ' )>協会けんぽ</option>
                                    <option value="健保組合" @selected(old('insurance_type', '' )==='健保組合' )>健保組合</option>
                                    <option value="共済" @selected(old('insurance_type', '' )==='共済' )>共済</option>
                                    <option value="63退職" @selected(old('insurance_type', '' )==='63退職' )>63退職</option>
                                    <option value="67退職" @selected(old('insurance_type', '' )==='67退職' )>67退職</option>
                                    <option value="後期高齢" @selected(old('insurance_type', '' )==='後期高齢' )>後期高齢</option>
                                    <option value="医療助成" @selected(old('insurance_type', '' )==='医療助成' )>医療助成</option>
                                </select></td>
                            <td><span data-insurer-display> {
                                    {
                                    old('subsidy_type', '')
                                    }
                                    }

                                </span><select class="insurer-inline-input" data-insurer-input hidden>
                                    <option value=""></option>
                                    <option value="障害" @selected(old('subsidy_type', '' )==='障害' )>障害</option>
                                    <option value="福祉" @selected(old('subsidy_type', '' )==='福祉' )>福祉</option>
                                    <option value="母子" @selected(old('subsidy_type', '' )==='母子' )>母子</option>
                                    <option value="子ども" @selected(old('subsidy_type', '' )==='子ども' )>子ども</option>
                                    <option value="乳幼児" @selected(old('subsidy_type', '' )==='乳幼児' )>乳幼児</option>
                                    <option value="水俣" @selected(old('subsidy_type', '' )==='水俣' )>水俣</option>
                                    <option value="原爆" @selected(old('subsidy_type', '' )==='原爆' )>原爆</option>
                                    <option value="一人親" @selected(old('subsidy_type', '' )==='一人親' )>一人親</option>
                                </select></td>
                            <td><button type="button" class="btn">編集</button></td>
                        </tr>
                        @endif @forelse (($insurerRows ?? []) as $row)
                        @php
                        $isEditingRow = $hasOldInsurerInput && $oldOriginalInsurerNumber !== '' && $oldOriginalInsurerNumber === $row->insurer_number;

                        $rowInsurerNumber = $isEditingRow
                        ? trim((string) old('insurer_number', $row->insurer_number))
                        : $row->insurer_number;

                        $rowInsurerName = $isEditingRow
                        ? trim((string) old('insurer_name', $row->insurer_name))
                        : $row->insurer_name;

                        $rowPaymentName = $isEditingRow
                        ? trim((string) old('scheduled_payment_name', $row->scheduled_payment_name))
                        : $row->scheduled_payment_name;

                        $rowPaymentName2 = $isEditingRow
                        ? trim((string) old('scheduled_payment_name_2', $row->scheduled_payment_name_2))
                        : $row->scheduled_payment_name_2;

                        $rowInsuranceType = $isEditingRow
                        ? trim((string) old('insurance_type', $row->insurance_type))
                        : $row->insurance_type;

                        $rowSubsidyType = $isEditingRow
                        ? trim((string) old('subsidy_type', $row->subsidy_type))
                        : $row->subsidy_type;
                        @endphp
                        <tr data-insurer-row data-original-insurer-number="{{ $row->insurer_number }}" @if($isEditingRow) data-is-editing="1" @endif>

                            <td>
                                <span data-insurer-display>{{ $rowInsurerNumber }}</span>
                                <input type="text" value="{{ $rowInsurerNumber }}" class="insurer-inline-input" data-insurer-input hidden>
                            </td>

                            <td class="insurers-name-cell">
                                <span data-insurer-display>{{ $rowInsurerName }}</span>
                                <input type="text" value="{{ $rowInsurerName }}" class="insurer-inline-input" data-insurer-input hidden>
                            </td>

                            <td>
                                <span data-insurer-display>{{ $rowPaymentName }}</span>
                                <input type="text" value="{{ $rowPaymentName }}" class="insurer-inline-input" data-insurer-input list="insurer-scheduled-payment-name-options" hidden>
                            </td>

                            <td>
                                <span data-insurer-display>{{ $rowPaymentName2 }}</span>
                                <input type="text" value="{{ $rowPaymentName2 }}" class="insurer-inline-input" data-insurer-input list="insurer-scheduled-payment-name-options" hidden>
                            </td>

                            <td>
                                <span data-insurer-display>{{ $rowInsuranceType }}</span>
                                <select class="insurer-inline-input" data-insurer-input hidden>
                                    <option value=""></option>
                                    <option value="区外国保" @selected($rowInsuranceType==='区外国保' )>区外国保</option>
                                    <option value="一般国保" @selected($rowInsuranceType==='一般国保' )>一般国保</option>
                                    <option value="協会けんぽ" @selected($rowInsuranceType==='協会けんぽ' )>協会けんぽ</option>
                                    <option value="健保組合" @selected($rowInsuranceType==='健保組合' )>健保組合</option>
                                    <option value="共済" @selected($rowInsuranceType==='共済' )>共済</option>
                                    <option value="63退職" @selected($rowInsuranceType==='63退職' )>63退職</option>
                                    <option value="67退職" @selected($rowInsuranceType==='67退職' )>67退職</option>
                                    <option value="後期高齢" @selected($rowInsuranceType==='後期高齢' )>後期高齢</option>
                                    <option value="医療助成" @selected($rowInsuranceType==='医療助成' )>医療助成</option>
                                </select>
                            </td>

                            <td>
                                <span data-insurer-display>{{ $rowSubsidyType }}</span>
                                <select class="insurer-inline-input" data-insurer-input hidden>
                                    <option value=""></option>
                                    <option value="障害" @selected($rowSubsidyType==='障害' )>障害</option>
                                    <option value="福祉" @selected($rowSubsidyType==='福祉' )>福祉</option>
                                    <option value="母子" @selected($rowSubsidyType==='母子' )>母子</option>
                                    <option value="子ども" @selected($rowSubsidyType==='子ども' )>子ども</option>
                                    <option value="乳幼児" @selected($rowSubsidyType==='乳幼児' )>乳幼児</option>
                                    <option value="水俣" @selected($rowSubsidyType==='水俣' )>水俣</option>
                                    <option value="原爆" @selected($rowSubsidyType==='原爆' )>原爆</option>
                                    <option value="一人親" @selected($rowSubsidyType==='一人親' )>一人親</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn">編集</button></td>
                        </tr>@empty <tr>
                            <td colspan="7">対象データがありません。</td>
                        </tr>@endforelse <tr data-insurer-template data-original-insurer-number="" hidden>
                            <td><span data-insurer-display></span><input type="text" value="" class="insurer-inline-input" data-insurer-input hidden></td>
                            <td class="insurers-name-cell"><span data-insurer-display></span><input type="text" value="" class="insurer-inline-input" data-insurer-input hidden></td>
                            <td><span data-insurer-display></span><input type="text" value="" class="insurer-inline-input" data-insurer-input list="insurer-scheduled-payment-name-options" hidden></td>
                            <td><span data-insurer-display></span><input type="text" value="" class="insurer-inline-input" data-insurer-input list="insurer-scheduled-payment-name-options" hidden></td>
                            <td><span data-insurer-display></span><select class="insurer-inline-input" data-insurer-input hidden>
                                    <option value=""></option>
                                    <option value="区外国保">区外国保</option>
                                    <option value="一般国保">一般国保</option>
                                    <option value="協会けんぽ">協会けんぽ</option>
                                    <option value="健保組合">健保組合</option>
                                    <option value="共済">共済</option>
                                    <option value="63退職">63退職</option>
                                    <option value="67退職">67退職</option>
                                    <option value="後期高齢">後期高齢</option>
                                    <option value="医療助成">医療助成</option>
                                </select></td>
                            <td><span data-insurer-display></span><select class="insurer-inline-input" data-insurer-input hidden>
                                    <option value=""></option>
                                    <option value="障害">障害</option>
                                    <option value="福祉">福祉</option>
                                    <option value="母子">母子</option>
                                    <option value="子ども">子ども</option>
                                    <option value="乳幼児">乳幼児</option>
                                    <option value="水俣">水俣</option>
                                    <option value="原爆">原爆</option>
                                    <option value="一人親">一人親</option>
                                </select></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div><a href="{{ route('office.receipt') }}" class="btn btn_back">戻る</a></div>
        </section>
    </main>@include('staff_portal.office.receipt.insurers.page_script')
</body>

</html>
