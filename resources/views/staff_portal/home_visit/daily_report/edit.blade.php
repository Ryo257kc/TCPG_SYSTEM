<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診日報 詳細編集</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])


        <style>
            /* .error { margin-bottom: 10px; color: #b00020; font-size: 12px; } */
            .actions {
                margin-top: 20px;
                display: flex;
                gap: 10px;
                justify-content: center;
                width: 100%;
            }

            .row {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 10px;
                flex-wrap: wrap;
            }

            .row label {
                width: 150px;
                text-align: right;
            }

            .row .down {
                width: 100%;
                margin-left: 170px;
            }

            /* .row textarea {
            flex: 1;
        } */
            .field-area {
                flex: 1;
                min-width: 0;
                display: flex;
                gap: 8px;
            }

            .field-area .flex-input {
                flex: 1;
            }

            .field-area .fixed-input {
                width: 120px;
                /* 好きな幅に調整 */
            }

            .field-area .small-input {
                width: 80px;
            }

            .field-area {
                flex: 1;
                min-width: 0;
            }

            .field-area textarea,
            .field-area input,
            .field-area select {
                width: 100% !important;
                box-sizing: border-box;
            }

            .flex-input {
                width: 100% !important;
                box-sizing: border-box;
            }

            .search-select-wrap {
                flex: 1;
                width: 100%;
                min-width: 0;
            }

            .search-select-wrap input {
                width: 100%;
                box-sizing: border-box;
            }

            .autocomplete-list {
                display: none;
                position: absolute;
                background: #fff;
                border: 1px solid #ccc;
                z-index: 1000;
                max-height: 240px;
                overflow-y: auto;
                min-width: 360px;
            }

            .autocomplete-list:empty {
                display: none;
            }

            .autocomplete-item {
                padding: 6px 10px;
                cursor: pointer;
            }

            .autocomplete-item:hover {
                background: #f3f4f6;
            }
        </style>



        <section class="panel content-panel staff-viewport-panel">
            <h1 align="center" class="margin_b20">往診日報 詳細編集</h1>
            <!-- <h2 class="content-title">往診日報 詳細編集</h2> -->

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif
            <div class="table-wrap width_edit">
                <form method="post" action="{{ route('home_visit.daily_report.update', ['nippouNo' => $item->daily_report_id]) }}">
                    @csrf

                    <div class="row">
                        <label for="treatment_date">施術日</label>
                        <input
                            id="treatment_date"
                            type="date"
                            name="treatment_date"
                            value="{{ old('treatment_date', $item->treatment_date ? \Carbon\Carbon::parse($item->treatment_date)->format('Y-m-d') : '') }}"
                            required>
                    </div>

                    <div class="row check">
                        <label for="is_home_visit">往診</label>
                        <input id="is_home_visit" type="checkbox" name="is_home_visit" value="1"
                            {{ old('is_home_visit', $item->is_home_visit) ? 'checked' : '' }}>
                    </div>

                    <div class="row">
                        <label for="patient_search">患者名</label>

                        <div class="field-area">
                            <div class="search-select-wrap" style="position: relative;">
                                <input
                                    type="text"
                                    id="patient_search"
                                    autocomplete="off"
                                    value="{{ old('patient_search', $item->patient_name ?? '') }}"
                                    placeholder="患者名または患者IDで検索">

                                <input
                                    type="hidden"
                                    id="patient_id"
                                    name="patient_id"
                                    value="{{ old('patient_id', $item->patient_id ?? '') }}">

                                <div
                                    id="patient_search_results"
                                    style="
                    display:none;
                    position:absolute;
                    top:100%;
                    left:0;
                    right:0;
                    background:#fff;
                    border:1px solid #ccc;
                    max-height:220px;
                    overflow-y:auto;
                    z-index:1000;
                "></div>
                            </div>
                        </div>
                    </div>

                    @error('patient_id')
                    <div class="error" style="color:red;">{{ $message }}</div>
                    @enderror
                    <!-- </div> -->

                    <div class="row">
                        <label for="daily_report_town_name">日報町名</label>
                        <input id="daily_report_town_name" type="text" name="daily_report_town_name"
                            value="{{ old('daily_report_town_name', $item->daily_report_town_name) }}">
                    </div>

                    <div class="row">
                        <label for="daily_report_facility_name">日報施設名</label>
                        <select id="daily_report_facility_name" name="daily_report_facility_name">
                            <option value=""></option>
                            @foreach($facilities as $facility)
                            <option value="{{ $facility }}"
                                {{ old('daily_report_facility_name', $item->daily_report_facility_name) == $facility ? 'selected' : '' }}>
                                {{ $facility }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <label for="daily_report_store_name">日報店舗</label>
                        <select id="daily_report_store_name" name="daily_report_store_name">
                            <option value=""></option>
                            @foreach($stores as $store)
                            <option value="{{ $store }}"
                                {{ old('daily_report_store_name', $item->daily_report_store_name) == $store ? 'selected' : '' }}>
                                {{ $store }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <label for="distance">標準距離</label>
                        <div id="patient_standard_distance" class="readonly-display"></div>
                    </div>

                    <div class="row">
                        <label for="distance">標準負担金</label>
                        <div id="patient_standard_burden_amount" class="readonly-display">
                            {{ $item->standard_burden_amount ? formatNumber($item->standard_burden_amount) . '円' : '' }}
                        </div>
                    </div>

                    <div class="row">
                        <label for="distance">負担割合</label>
                        <div id="patient_burden_ratio" class="readonly-display">割</div>
                    </div>

                    <!-- <div class="row">
                <label for="distance">助成上限回数</label>
                <div id="patient_subsidy_limit_count" class="readonly-display">回</div>
            </div> -->

                    <!-- <div class="row">
                <label for="distance">助成負担額</label>
                <div id="patient_subsidy_burden_amount" class="readonly-display"></div>
            </div> -->

                    <div class="row">
                        <label for="distance">距離</label>
                        <input
                            id="distance"
                            type="text"
                            name="distance"
                            value="{{ old('distance', $item->distance) }}">
                    </div>

                    <div class="row">
                        <label for="start_time">開始</label>
                        <input
                            id="start_time"
                            type="time"
                            name="start_time"
                            value="{{ old('start_time', $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '') }}">
                    </div>

                    <div class="row">
                        <label for="end_time">終了</label>
                        <input
                            id="end_time"
                            type="time"
                            name="end_time"
                            value="{{ old('end_time', $item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('H:i') : '') }}">
                    </div>

                    <div class="row">
                        <label for="treatment_details">領収項目</label>
                        <input id="treatment_details" type="text" name="treatment_details"
                            value="{{ old('treatment_details', $item->treatment_details) }}">
                        <span class="f_size12 down">※下記「自費」に値する鍼灸治療以外の領収書項目を入力</span>
                    </div>

                    <div class="row">
                        <label for="private_fee">自費</label>
                        <input id="private_fee" type="text" name="private_fee"
                            value="{{ formatNumber($item->private_fee) }}">
                    </div>

                    <div class="row">
                        <label for="uncollected_amount">未収金</label>
                        <input id="uncollected_amount" type="text" name="uncollected_amount"
                            value="{{ formatNumber($item->uncollected_amount) }}">
                    </div>

                    <div class="row">
                        <label>負担金</label>

                        <div class="">

                            @php
                            // 回数（未定義対策）
                            $kaisu = $kaisu ?? 0;

                            // 助成上限回数
                            $limit = $item->subsidy_limit_count ?? 0;

                            // 元の負担金
                            $baseFutan = $item->copayment_amount ?? 0;

                            // 上限判定
                            if ($limit != 0 && $limit <= $kaisu) {
                                $displayFutan=0;
                                $isOver=true;
                                } else {
                                $displayFutan=$baseFutan;
                                $isOver=false;
                                }
                                @endphp

                                {{-- 助成情報 --}}
                                <!-- @if($limit !=0) -->
                                <div class="error">
                                    医療助成上限回数：{{ $limit }}回<br>
                                    当月本日以前施術回数：{{ $kaisu }}回
                                </div>
                                <!-- @endif -->

                                {{-- 上限超え --}}
                                @if($isOver)
                                <div class="error">
                                    上限以上の施術のため、0円
                                </div>
                                @endif

                                {{-- 入力欄 --}}
                                <input
                                    type="text"
                                    name="copayment_amount"
                                    value="{{ $displayFutan ? number_format($displayFutan) : '' }}"
                                    placeholder="標準負担金を入力">

                        </div>
                        <span class="f_size12 down">※全て「鍼灸治療」として集計され、領収書に記載されます。</span>
                    </div>
                    <div class="row">
                        <label for="insurance_amount">施術料金</label>
                        <input id="insurance_amount" type="text" name="insurance_amount"
                            value="{{ formatNumber($item->insurance_amount ? $item->insurance_amount : $item->treatment_fee) }}">
                    </div>

                    <div class="row check">
                        <label for="is_no_treatment">施術なし</label>
                        <input id="is_no_treatment" type="checkbox" name="is_no_treatment" value="1"
                            {{ old('is_no_treatment', $item->is_no_treatment) ? 'checked' : '' }}>
                        <span class="f_size12">※施術をしなかった場合はチェック</span>
                    </div>

                    <div class="row">
                        <label for="added_at">申請日</label>
                        <input id="added_at" type="date" name="added_at"
                            value="{{ old('added_at', $item->added_at ? \Carbon\Carbon::parse($item->added_at)->format('Y-m-d') : '') }}">
                    </div>

                    <div class="row check">
                        <label for="is_same_day_duplicate">同日重複</label>
                        <input id="is_same_day_duplicate" type="checkbox" name="is_same_day_duplicate" value="1"
                            {{ old('is_same_day_duplicate', $item->is_same_day_duplicate) ? 'checked' : '' }}>
                        <span class="f_size12">※同日に同一患者を施術した場合はチェック</span>
                    </div>

                    <div class="row">
                        <label for="remarks">備考</label>
                        <div class="field-area">
                            <textarea id="remarks" name="remarks">{{ old('remarks', $item->remarks) }}</textarea>
                        </div>
                    </div>

                    @if($isVisitManagement)
                    <h2 align="center" class="margin_b20">管理者編集</h2>
                    <div class="row check">
                        <label for="is_ma_treatment">マッサージ施術</label>
                        <input id="is_ma_treatment" type="checkbox" name="is_ma_treatment" value="1"
                            {{ old('is_ma_treatment', $item->is_ma_treatment) ? 'checked' : '' }}>
                        <span class="f_size12">※マッサージ対象者の場合にチェック</span>
                    </div>

                    <div class="row">
                        <label for="receipt_home_visit_distance_ma">マッサージ施術選択</label>
                        <div>
                            <select id="receipt_home_visit_distance_ma" name="receipt_home_visit_distance_ma">
                                <option value=""></option>
                                <option value="〇" {{ old('receipt_home_visit_distance_ma', $item->receipt_home_visit_distance_ma) == '〇' ? 'selected' : '' }}>【〇】通所</option>
                                <option value="◎" {{ old('receipt_home_visit_distance_ma', $item->receipt_home_visit_distance_ma) == '◎' ? 'selected' : '' }}>【◎】通所（往療あり）</option>
                                <option value="△" {{ old('receipt_home_visit_distance_ma', $item->receipt_home_visit_distance_ma) == '△' ? 'selected' : '' }}>【△】通所（往療なし）</option>
                                <option value="①" {{ old('receipt_home_visit_distance_ma', $item->receipt_home_visit_distance_ma) == '①' ? 'selected' : '' }}>【①】1人</option>
                                <option value="②" {{ old('receipt_home_visit_distance_ma', $item->receipt_home_visit_distance_ma) == '②' ? 'selected' : '' }}>【②】2人</option>
                                <option value="③" {{ old('receipt_home_visit_distance_ma', $item->receipt_home_visit_distance_ma) == '③' ? 'selected' : '' }}>【③】3人～9人</option>
                                <option value="④" {{ old('receipt_home_visit_distance_ma', $item->receipt_home_visit_distance_ma) == '④' ? 'selected' : '' }}>【④】10人以上</option>
                            </select>
                        </div>
                    </div>

                    <div class="row check">
                        <label for="is_receipt_home_visit">レセ往療</label>
                        <input id="is_receipt_home_visit" type="checkbox" name="is_receipt_home_visit" value="1"
                            {{ old('is_receipt_home_visit', $item->is_receipt_home_visit) ? 'checked' : '' }}>
                        <span class="f_size12">※レセにて往療が必要な場合にチェック</span>
                    </div>

                    <div class="row">
                        <label for="kiten_patient_search">往療の起点</label>

                        <div class="field-area">
                            <div class="search-select-wrap" style="position: relative;">
                                <input
                                    type="text"
                                    id="kiten_patient_search"
                                    autocomplete="off"
                                    value=""
                                    placeholder="起点患者を検索">

                                <input
                                    type="hidden"
                                    id="kiten_patient_id"
                                    name="kiten_patient_id"
                                    value="{{ old('kiten_patient_id', $item->home_visit_start_point ?? '') }}">

                                <div
                                    id="kiten_patient_search_results"
                                    style="
                    display:none;
                    position:absolute;
                    top:100%;
                    left:0;
                    right:0;
                    background:#fff;
                    border:1px solid #ccc;
                    max-height:220px;
                    overflow-y:auto;
                    z-index:1000;
                "></div>
                            </div>
                        </div>

                        <span class="f_size12 down">
                            起点住所：
                            <span id="kiten_address">
                                @php
                                $kitenAddress = '';
                                if (!empty($item->home_visit_start_point ?? null)) {
                                $matchedPatient = $patients->firstWhere('patient_id', $item->home_visit_start_point);
                                $kitenAddress = $matchedPatient->full_address ?? '';
                                }
                                @endphp
                                {{ $kitenAddress }}
                            </span>
                        </span>
                    </div>


                    <div class="row">
                        <label for="receipt_home_visit_distance">鍼灸施術選択</label>
                        <div>
                            <select id="receipt_home_visit_distance" name="receipt_home_visit_distance">
                                <option value=""></option>
                                <option value="〇" {{ old('receipt_home_visit_distance', $item->receipt_home_visit_distance) == '〇' ? 'selected' : '' }}>【〇】通所</option>
                                <option value="◎" {{ old('receipt_home_visit_distance', $item->receipt_home_visit_distance) == '◎' ? 'selected' : '' }}>【◎】通所（往療あり）</option>
                                <option value="①" {{ old('receipt_home_visit_distance', $item->receipt_home_visit_distance) == '①' ? 'selected' : '' }}>【①】1人</option>
                                <option value="②" {{ old('receipt_home_visit_distance', $item->receipt_home_visit_distance) == '②' ? 'selected' : '' }}>【②】2人</option>
                                <option value="③" {{ old('receipt_home_visit_distance', $item->receipt_home_visit_distance) == '③' ? 'selected' : '' }}>【③】3人～9人</option>
                                <option value="④" {{ old('receipt_home_visit_distance', $item->receipt_home_visit_distance) == '④' ? 'selected' : '' }}>【④】10人以上</option>
                            </select>
                        </div>
                    </div>
                    @endif

                    <div class="actions">
                        <button type="submit" class="btn">保存</button>
                </form>

                <form method="post"
                    action="{{ route('home_visit.daily_report.destroy', ['nippouNo' => $item->daily_report_id]) }}"
                    onsubmit="return confirm('削除しますか？');">
                    @csrf

                    <input type="hidden" name="date"
                        value="{{ request('date') ?? \Carbon\Carbon::parse($item->treatment_date)->format('Y-m-d') }}">

                    <button type="submit" class="btn">削除</button>
                </form>
            </div>
            </div>
            <div class="back-row">
                <button type="button" class="btn btn_back"
                    onclick="location.href='{{ route('home_visit.daily_report', ['date' => request('date') ?? \Carbon\Carbon::parse($item->treatment_date)->format('Y-m-d')]) }}'">
                    戻る
                </button>
            </div>
        </section>

        <script>
            /**
             * =========================================================
             * 【① 患者選択 → 各種情報表示（距離・負担金など）】
             * =========================================================
             *
             * ここで更新する表示先
             * - 標準距離
             * - 負担割合
             * - 標準負担金
             * - 助成情報（上限回数 / 当月本日以前施術回数）
             * - 助成負担額
             */
            document.addEventListener('DOMContentLoaded', function() {

                // ▼ hiddenに入る「患者ID」
                const patientSelect = document.getElementById('patient_id');

                // ▼ 表示する各項目（div）
                const standardDistanceBox = document.getElementById('patient_standard_distance');
                const burdenRatioBox = document.getElementById('patient_burden_ratio');
                const standardBurdenAmountBox = document.getElementById('patient_standard_burden_amount');
                const subsidyInfoBox = document.getElementById('subsidy_info');

                // ▼ 必要な要素が無ければ処理しない
                if (!patientSelect || !standardDistanceBox) {
                    return;
                }

                /**
                 * ▼ 患者IDからサーバーへ問い合わせて情報取得
                 */
                async function loadPatientInfo() {
                    const patientId = patientSelect.value;

                    // ▼ 一旦クリア
                    standardDistanceBox.textContent = '';
                    burdenRatioBox.textContent = '';
                    standardBurdenAmountBox.textContent = '';

                    if (subsidyInfoBox) {
                        subsidyInfoBox.innerHTML = '';
                    }

                    // ▼ 患者未選択ならここで終わり
                    if (!patientId) {
                        return;
                    }

                    try {
                        // ▼ LaravelのrouteでURL生成
                        const baseUrl = "{{ route('home_visit.patient_info', ['patientId' => '__PATIENT_ID__']) }}";
                        const url = baseUrl.replace('__PATIENT_ID__', encodeURIComponent(patientId));

                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();

                        // =====================================================
                        // 標準距離
                        // =====================================================
                        standardDistanceBox.textContent =
                            data.standard_distance !== null &&
                            data.standard_distance !== undefined &&
                            data.standard_distance !== '' ?
                            (Number(data.standard_distance) % 1 === 0 ?
                                Number(data.standard_distance) + 'km' :
                                Number(data.standard_distance).toFixed(1) + 'km') :
                            '';

                        // =====================================================
                        // 負担割合
                        // =====================================================
                        burdenRatioBox.textContent =
                            data.burden_ratio !== null &&
                            data.burden_ratio !== undefined &&
                            data.burden_ratio !== '' ?
                            data.burden_ratio + '割' :
                            '';

                        // =====================================================
                        // 標準負担金
                        // =====================================================
                        standardBurdenAmountBox.textContent =
                            data.standard_burden_amount !== null &&
                            data.standard_burden_amount !== undefined &&
                            data.standard_burden_amount !== '' ?
                            Number(data.standard_burden_amount).toLocaleString() + '円' :
                            '';

                        // =====================================================
                        // 助成情報（表示場所は subsidy_info 1か所だけ）
                        // =====================================================
                        if (subsidyInfoBox) {
                            subsidyInfoBox.innerHTML =
                                '医療助成上限回数：' + (data.subsidy_limit_count ?? 0) + '回<br>' +
                                '当月本日以前施術回数：' + (data.kaisu ?? 0) + '回';
                        }

                        // =====================================================
                        // 助成負担額
                        // =====================================================
                        subsidyBurdenAmountBox.textContent =
                            data.subsidy_burden_amount !== null &&
                            data.subsidy_burden_amount !== undefined &&
                            data.subsidy_burden_amount !== '' ?
                            Number(data.subsidy_burden_amount).toLocaleString() + '円' :
                            '';

                    } catch (error) {
                        console.error(error);
                    }
                }

                // ▼ 患者変更時に再取得
                patientSelect.addEventListener('change', loadPatientInfo);

                // ▼ 他のJSから呼べるようにする
                window.loadPatientInfo = loadPatientInfo;

                // ▼ 初期表示
                loadPatientInfo();
            });


            /**
             * =========================================================
             * 【② 患者検索（メイン患者）】
             * =========================================================
             */

            // ▼ PHPから患者リストをJSへ渡す

            window.patientSearchList = @json($patients);

            document.addEventListener('DOMContentLoaded', function() {
                const patients = window.patientSearchList || [];

                const patientIdInput = document.getElementById('patient_id');
                const patientSearchInput = document.getElementById('patient_search');
                const patientResultsBox = document.getElementById('patient_search_results');

                const kitenIdInput = document.getElementById('kiten_patient_id');
                const kitenSearchInput = document.getElementById('kiten_patient_search');
                const kitenResultsBox = document.getElementById('kiten_patient_search_results');
                const kitenAddressBox = document.getElementById('kiten_address');

                const standardDistanceBox = document.getElementById('patient_standard_distance');
                const burdenRatioBox = document.getElementById('patient_burden_ratio');
                const standardBurdenAmountBox = document.getElementById('patient_standard_burden_amount');
                const subsidyInfoBox = document.getElementById('subsidy_info');
                const subsidyBurdenAmountBox = document.getElementById('patient_subsidy_burden_amount');

                function normalize(value) {
                    return String(value ?? '').trim().toLowerCase();
                }

                function patientLabel(p) {
                    return [
                        p.patient_name ?? '',
                        p.full_address ?? '',
                        p.facility_name ?? ''
                    ].filter(v => String(v).trim() !== '').join(' ｜ ');
                }

                function hideBox(box) {
                    if (!box) return;
                    box.innerHTML = '';
                    box.style.display = 'none';
                }

                function matchedPatients(keyword) {
                    const q = normalize(keyword);
                    if (!q) return [];

                    return patients.filter(function(p) {
                        return normalize(p.patient_name).includes(q) ||
                            normalize(p.patient_id).includes(q) ||
                            normalize(p.full_address).includes(q) ||
                            normalize(p.facility_name).includes(q);
                    }).slice(0, 30);
                }

                async function loadPatientInfo() {
                    if (!patientIdInput) return;

                    const patientId = patientIdInput.value;

                    if (standardDistanceBox) standardDistanceBox.textContent = '';
                    if (burdenRatioBox) burdenRatioBox.textContent = '';
                    if (standardBurdenAmountBox) standardBurdenAmountBox.textContent = '';
                    if (subsidyInfoBox) subsidyInfoBox.innerHTML = '';
                    if (subsidyBurdenAmountBox) subsidyBurdenAmountBox.textContent = '';

                    if (!patientId) return;

                    try {
                        const baseUrl = "{{ route('home_visit.patient_info', ['patientId' => '__PATIENT_ID__']) }}";
                        const url = baseUrl.replace('__PATIENT_ID__', encodeURIComponent(patientId));

                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });

                        if (!response.ok) return;

                        const data = await response.json();

                        if (standardDistanceBox) {
                            standardDistanceBox.textContent =
                                data.standard_distance !== null && data.standard_distance !== undefined && data.standard_distance !== '' ?
                                (Number(data.standard_distance) % 1 === 0 ?
                                    Number(data.standard_distance) + 'km' :
                                    Number(data.standard_distance).toFixed(1) + 'km') :
                                '';
                        }

                        if (burdenRatioBox) {
                            burdenRatioBox.textContent =
                                data.burden_ratio !== null && data.burden_ratio !== undefined && data.burden_ratio !== '' ?
                                data.burden_ratio + '割' :
                                '';
                        }

                        if (standardBurdenAmountBox) {
                            standardBurdenAmountBox.textContent =
                                data.standard_burden_amount !== null && data.standard_burden_amount !== undefined && data.standard_burden_amount !== '' ?
                                Number(data.standard_burden_amount).toLocaleString() + '円' :
                                '';
                        }

                        if (subsidyInfoBox) {
                            subsidyInfoBox.innerHTML =
                                '医療助成上限回数：' + (data.subsidy_limit_count ?? 0) + '回<br>' +
                                '当月本日以前施術回数：' + (data.kaisu ?? 0) + '回';
                        }

                        if (subsidyBurdenAmountBox) {
                            subsidyBurdenAmountBox.textContent =
                                data.subsidy_burden_amount !== null && data.subsidy_burden_amount !== undefined && data.subsidy_burden_amount !== '' ?
                                Number(data.subsidy_burden_amount).toLocaleString() + '円' :
                                '';
                        }
                    } catch (error) {
                        console.error(error);
                    }
                }

                function renderPatientResults(items) {
                    if (!patientResultsBox) return;

                    patientResultsBox.innerHTML = items.map(function(p) {
                        return `
 <div class="patient-result-item"
 data-patient-id="${p.patient_id}"
 data-patient-name="${p.patient_name ?? ''}"
 style="padding:8px; cursor:pointer;">
 ${patientLabel(p)}
 </div>
 `;
                    }).join('');

                    patientResultsBox.style.display = items.length ? 'block' : 'none';
                }

                function renderKitenResults(items) {
                    if (!kitenResultsBox) return;

                    kitenResultsBox.innerHTML = items.map(function(p) {
                        return `
 <div class="kiten-result-item"
 data-id="${p.patient_id}"
 data-name="${p.patient_name ?? ''}"
 data-address="${p.full_address ?? ''}"
 style="padding:8px; cursor:pointer;">
 ${patientLabel(p)}
 </div>
 `;
                    }).join('');

                    kitenResultsBox.style.display = items.length ? 'block' : 'none';
                }

                if (patientSearchInput && patientIdInput && patientResultsBox) {
                    if (patientIdInput.value) {
                        const match = patients.find(p => String(p.patient_id) === String(patientIdInput.value));
                        if (match) patientSearchInput.value = match.patient_name ?? '';
                    }

                    patientSearchInput.addEventListener('input', function() {
                        patientIdInput.value = '';
                        renderPatientResults(matchedPatients(patientSearchInput.value));
                    });

                    patientResultsBox.addEventListener('click', function(e) {
                        const item = e.target.closest('.patient-result-item');
                        if (!item) return;

                        patientIdInput.value = item.dataset.patientId;
                        patientSearchInput.value = item.dataset.patientName;
                        hideBox(patientResultsBox);
                        loadPatientInfo();
                    });

                    loadPatientInfo();
                }

                if (kitenSearchInput && kitenIdInput && kitenResultsBox) {
                    if (kitenIdInput.value) {
                        const match = patients.find(p => String(p.patient_id) === String(kitenIdInput.value));
                        if (match) {
                            kitenSearchInput.value = match.patient_name ?? '';
                            if (kitenAddressBox) kitenAddressBox.textContent = match.full_address ?? '';
                        }
                    }

                    kitenSearchInput.addEventListener('input', function() {
                        kitenIdInput.value = '';
                        if (kitenAddressBox) kitenAddressBox.textContent = '';
                        renderKitenResults(matchedPatients(kitenSearchInput.value));
                    });

                    kitenResultsBox.addEventListener('click', function(e) {
                        const item = e.target.closest('.kiten-result-item');
                        if (!item) return;

                        kitenIdInput.value = item.dataset.id;
                        kitenSearchInput.value = item.dataset.name;
                        if (kitenAddressBox) kitenAddressBox.textContent = item.dataset.address ?? '';
                        hideBox(kitenResultsBox);
                    });
                }
            });
        </script>

    </main>
</body>

</html>
