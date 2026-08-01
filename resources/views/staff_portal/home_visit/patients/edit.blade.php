<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 患者詳細</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">

    <style>
        .view-value {
            display: inline;
        }

        .edit-input {
            display: none;
            width: 100%;
            text-align: center;
        }

        .edit-check {
            display: none;
            width: 18px;
            height: 18px;
        }

        .edit-with-unit {
            display: none;
            align-items: center;
            gap: 8px;
        }

        .edit-consent {
            display: none;
            align-items: center;
            gap: 8px;
        }

        .edit-consent .edit-input {
            flex: 1 1 auto;
        }

        .edit-unit {
            color: #3f4b63;
            font-size: 13px;
            white-space: nowrap;
        }

        .editing .view-value {
            display: none;
        }

        .editing .edit-input {
            display: block;
        }

        .editing .edit-check {
            display: inline-block;
        }

        .editing .edit-with-unit {
            display: flex;
        }

        .editing .edit-with-unit .edit-input {
            display: block;
        }

        .editing .edit-consent {
            display: flex;
        }

        .patient-detail-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 4px;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])



        <section class="panel content-panel staff-viewport-panel">

            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <h2 class="content-title">患者詳細</h2>

            <div class="patient-detail-actions width_edit">
                <div class="links">
                    @if(($mode ?? 'edit') === 'edit')
                    <button type="button" id="toggle-edit" class="link-btn link-btn-primary">
                        編集する
                    </button>
                    @else
                    <button type="submit" form="inline-edit-form" class="link-btn link-btn-primary">
                        登録する
                    </button>
                    @endif
                </div>
            </div>


            @php
            $isEdit = isset($item) && $item !== null;
            @endphp

            <form id="inline-edit-form"
                class="{{ $errors->any() || !$isEdit ? 'editing' : '' }}"
                method="post"
                action="{{ $isEdit
          ? route('patients.update', ['patient_id' => $item->patient_id])
          : route('patients.store') }}">
                @csrf
                <div class="table-wrap width_edit">
                    <table class="data-table">
                        <tbody>
                            <tr>
                                <th>共通ID</th>
                                <td>
                                    <span class="view-value">{{ $item->common_id ?? '' }}</span>
                                    <input class="edit-input" type="text" name="common_id" value="{{ old('common_id', $item->common_id ?? '') }}">
                                </td>
                            </tr>
                            <tr>
                                <th>マッサージID</th>
                                <td>
                                    <span class="view-value">{{ $item->massage_id ?? '' }}</span>
                                    <input class="edit-input" type="text" name="massage_id" value="{{ old('massage_id', $item->massage_id ?? '') }}">
                                </td>
                            </tr>
                            <tr>
                                <th>患者名</th>
                                <td>
                                    <span class="view-value">{{ $item->patient_name ?? '' }}</span>

                                    <input
                                        class="edit-input"
                                        type="text"
                                        name="patient_name"
                                        value="{{ old('patient_name', $item->patient_name ?? '') }}"
                                        required>

                                    <div id="patient_suggestions" class="autocomplete-list"></div>
                                </td>
                            </tr>
                            <tr>
                                <th>集金担当</th>
                                <td>
                                    <span class="view-value">{{ $item->collection_staff_name ?? '' }}</span>

                                    @php
                                    $selectedCollect = old('collection_staff', $item->collection_staff ?? '');
                                    @endphp

                                    <select class="edit-input" name="collection_staff">
                                        <option value="">選択してください</option>
                                        @foreach($staffOptions as $option)
                                        <option value="{{ $option['staff_id'] }}"
                                            {{ $selectedCollect == $option['staff_id'] ? 'selected' : '' }}>
                                            {{ $option['staff_name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>請求担当</th>
                                <td>
                                    <span class="view-value">{{ $item->billing_staff_name ?? '' }}</span>
                                    @php
                                    $selectedCollect = old('billing_staff', $item->billing_staff ?? '');
                                    @endphp
                                    <select class="edit-input" name="billing_staff">
                                        <option value="">選択してください</option>
                                        @foreach($staffOptions as $option)
                                        <option value="{{ $option['staff_id'] }}"
                                            {{ $selectedCollect == $option['staff_id'] ? 'selected' : '' }}>
                                            {{ $option['staff_name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>町名</th>
                                <td>
                                    <span class="view-value">{{ $item->visit_town_name ?? '' }}</span>
                                    <input class="edit-input" type="text" name="visit_town_name" value="{{ old('visit_town_name', $item->visit_town_name ?? '') }}">
                                </td>
                            </tr>
                            <tr>
                                <th>正式住所</th>
                                <td>
                                    <span class="view-value">{{ $item->full_address ?? '' }}</span>
                                    <input class="edit-input" type="text" name="full_address" value="{{ old('full_address', $item->full_address ?? '') }}">
                                </td>
                            </tr>
                            <tr>
                                <th>施設名</th>
                                <td>
                                    <span class="view-value">{{ $item->facility_name ?? '' }}</span>

                                    @php
                                    $selectedFacility = old('facility_name', $item->facility_name ?? '');
                                    @endphp

                                    <select class="edit-input" name="facility_name">
                                        <option value="">選択してください</option>
                                        @if($selectedFacility !== '' && !in_array($selectedFacility, $facilityOptions, true))
                                        <option value="{{ $selectedFacility }}" selected>{{ $selectedFacility }}</option>
                                        @endif
                                        @foreach($facilityOptions as $option)
                                        <option value="{{ $option }}" {{ $selectedFacility === $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>店舗</th>
                                <td>
                                    <span class="view-value">
                                        {{ $item->visit_store_name ?? '' }}
                                    </span>

                                    <select class="edit-input" name="visit_store_name">
                                        <option value="">-店舗選択-</option>
                                        @foreach($storeOptions as $option)
                                        <option value="{{ $option }}"
                                            @selected(old('visit_store_name', $item->visit_store_name ?? '') === $option)>
                                            {{ $option }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>マッサージ</th>
                                <td>
                                    <span class="view-value">
                                        {{ old('is_massage_target', $item->is_massage_target ?? 0) ? '☑' : '☐' }}
                                    </span>

                                    <input type="hidden" name="is_massage_target" value="0">

                                    <input class="edit-check" type="checkbox"
                                        name="is_massage_target"
                                        value="1"
                                        {{ old('is_massage_target', $item->is_massage_target ?? 0) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <th>標準距離</th>
                                <td>
                                    <span class="view-value">{{ formatNumber($item->standard_distance ?? '') }} km</span>
                                    <div class="edit-with-unit">
                                        <input class="edit-input" type="text" name="standard_distance"
                                            value="{{ old('standard_distance', $item->standard_distance ?? '') }}">
                                        <span class="edit-unit">km</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>負担割合</th>
                                <td>
                                    <span class="view-value">{{ $item->burden_ratio ?? '' }} 割</span>
                                    <div class="edit-with-unit">
                                        <input class="edit-input" type="text" name="burden_ratio"
                                            value="{{ old('burden_ratio', $item->burden_ratio ?? '') }}">
                                        <span class="edit-unit">割</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>標準負担金</th>
                                <td>
                                    <span class="view-value"> {{ formatNumber($item->standard_burden_amount ?? '') }} 円</span>
                                    <div class="edit-with-unit">
                                        <input class="edit-input" type="text" name="standard_burden_amount"
                                            value="{{ old('standard_burden_amount', isset($item->standard_burden_amount) ? (int)$item->standard_burden_amount : '') }}">
                                        <span class="edit-unit">円</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>カウント除外</th>
                                <td>
                                    <span class="view-value">{{ old('is_excluded_from_count', $item->is_excluded_from_count ?? 0) ? '☑' : '☐' }}</span>
                                    <input type="hidden" name="is_excluded_from_count" value="0">
                                    <input class="edit-check" type="checkbox" name="is_excluded_from_count" value="1"
                                        {{ old('is_excluded_from_count', $item->is_excluded_from_count ?? 0) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th>施術料金</th>
                                <td>
                                    <span class="view-value">{{ formatNumber($item->treatment_fee ?? '') }} 円</span>
                                    <div class="edit-with-unit">
                                        <input class="edit-input" type="text" name="treatment_fee"
                                            value="{{ old('treatment_fee', isset($item->treatment_fee) ? (int)$item->treatment_fee : '') }}">
                                        <span class="edit-unit">円</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>窓口距離</th>
                                <td>
                                    <span class="view-value">{{ $item->window_distance ?? '' }} km</span>
                                    <div class="edit-with-unit">
                                        <input class="edit-input" type="text" name="window_distance"
                                            value="{{ old('window_distance', $item->window_distance ?? '') }}">
                                        <span class="edit-unit">km</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>医療助成上限回数</th>
                                <td>
                                    <span class="view-value">{{ $item->subsidy_limit_count ?? '' }} 回</span>
                                    <div class="edit-with-unit">
                                        <input class="edit-input" type="text" name="subsidy_limit_count"
                                            value="{{ old('subsidy_limit_count', $item->subsidy_limit_count ?? '') }}">
                                        <span class="edit-unit">回</span>
                                    </div>
                                </td>
                            </tr>


                            @php
                            $consentType = old('consent_category', $item->consent_category ?? '');
                            $rawConsentDate = old('consent_date', $item->consent_date ?? '');
                            $formattedConsentDate = $rawConsentDate ? date('Y-m-d', strtotime($rawConsentDate)) : '';
                            @endphp

                            <tr>
                                <th>同意区分 / 同意日</th>
                                <td>
                                    <span class="view-value">
                                        {{ trim($consentType . ' ／ ' . $formattedConsentDate) }}
                                    </span>

                                    <div class="edit-consent">
                                        <select id="consent_type" class="edit-input" name="consent_category">
                                            <option value="">同意区分を選択</option>
                                            @foreach($consentTypeOptions as $option)
                                            <option value="{{ $option }}" {{ $consentType == $option ? 'selected' : '' }}>
                                                {{ $option }}
                                            </option>
                                            @endforeach
                                        </select>

                                        <input id="consent_date" class="edit-input" type="date" name="consent_date"
                                            value="{{ $formattedConsentDate }}">
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="actions {{ $errors->any() ? '' : 'hidden' }}" id="edit-actions">
                    <button class="btn-apply margin_t20 btn-primary" type="submit">更新する</button>

                </div>
            </form>

            <div class="back-row">
                <a href="{{ route('home_visit.patients.index') }}" class="btn btn_back">戻る</a>
            </div>
        </section>

    </main>

    <script>
        function hidePatientSuggestions() {
            box.innerHTML = '';
            box.style.display = 'none';
        }
        // 編集ボタン
        (() => {
            const toggle = document.getElementById('toggle-edit');
            const form = document.getElementById('inline-edit-form');
            const actions = document.getElementById('edit-actions');
            const consentType = document.getElementById('consent_type');
            const consentDate = document.getElementById('consent_date');

            if (!toggle || !form || !actions) return;

            const syncButton = () => {
                toggle.textContent = form.classList.contains('editing') ? '閉じる' : '編集する';
            };

            const syncConsentDate = () => {
                if (!consentType || !consentDate) return;

                const enabled = consentType.value.trim() !== '';
                consentDate.disabled = !enabled;
                consentDate.required = enabled;
            };

            syncButton();
            syncConsentDate();

            toggle.addEventListener('click', () => {
                form.classList.toggle('editing');
                actions.classList.toggle('hidden', !form.classList.contains('editing'));

                // 追加：編集を閉じたら候補を消す
                if (!form.classList.contains('editing') && typeof hidePatientSuggestions === 'function') {
                    hidePatientSuggestions();
                }

                syncButton();
                syncConsentDate();
            });

            if (consentType) {
                consentType.addEventListener('change', syncConsentDate);
            }
        })();


        // // 編集・表示切替ボタン
        // document.addEventListener('DOMContentLoaded', function() {
        //     const toggleBtn = document.getElementById('toggle-edit');
        //     const saveBtn = document.getElementById('save-btn');

        //     const viewBlocks = document.querySelectorAll('.view-mode');
        //     const editBlocks = document.querySelectorAll('.edit-mode');

        //     let isEdit = false;

        //     function switchMode(editMode) {
        //         isEdit = editMode;

        //         viewBlocks.forEach(el => {
        //             el.style.display = isEdit ? 'none' : '';
        //         });

        //         editBlocks.forEach(el => {
        //             el.style.display = isEdit ? '' : 'none';
        //         });

        //         if (saveBtn) {
        //             saveBtn.style.display = isEdit ? 'inline-block' : 'none';
        //         }

        //         if (toggleBtn) {
        //             toggleBtn.textContent = isEdit ? '閉じる' : '編集する';
        //         }
        //     }

        //     if (toggleBtn) {
        //         toggleBtn.addEventListener('click', function() {
        //             switchMode(!isEdit);
        //         });
        //     }

        //     switchMode(false);
        // });
    </script>
</body>

</html>
