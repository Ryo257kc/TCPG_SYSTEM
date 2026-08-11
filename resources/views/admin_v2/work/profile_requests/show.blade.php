<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 個人情報変更申請詳細</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <style>
        .pr-fields {
            display: grid;
            grid-template-columns: minmax(60px, max-content) minmax(0, 1fr);
            gap: 6px 10px;
            font-size: 13px;
        }

        .pr-fields dt {
            color: #667085;
            font-weight: 700;
        }

        .pr-fields dd {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .pr-table-wrap {
            max-width: 100%;
            overflow: auto;
        }

        .pr-table {
            min-width: 700px;
        }

        .pr-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 12px;
        }

        .pr-item {
            min-width: 0;
            border: 1px solid #d3dff0;
            border-radius: 8px;
            background: #fff;
            padding: 10px;
        }

        .pr-item-head {
            font-size: 14px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .pr-item-form {
            display: grid;
            gap: 8px;
            min-width: 0;
        }

        .pr-edit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 10px;
            min-width: 0;
        }

        .pr-edit-field {
            display: grid;
            grid-template-columns: 92px minmax(0, 1fr);
            align-items: center;
            gap: 6px;
            min-width: 0;
            font-size: 12px;
            font-weight: 700;
            color: #667085;
        }

        .pr-edit-field input {
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

        .pr-edit-field-wide {
            grid-column: 1 / -1;
        }

        .pr-item-proof {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #e4e7ec;
            font-size: 13px;
        }

        .pr-proof-missing {
            color: #b42318;
            font-weight: 800;
        }

        .pr-item-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 2px;
        }

        @media (max-width: 560px) {
            .pr-list {
                grid-template-columns: 1fr;
            }

            .pr-edit-grid {
                grid-template-columns: 1fr;
            }

            .pr-edit-field {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <h1 class="title">個人情報変更申請詳細</h1>
            <a href="{{ route('admin.work.profile_requests') }}" class="btn btn-outline">一覧へ戻る</a>
        </div>

        @if (session('status'))
        <div class="status">{{ session('status') }}</div>
        @endif

        <section class="panel">
            <dl class="pr-fields">
                <dt>状態</dt>
                <dd>{{ $request['status_label'] }}</dd>
                <dt>スタッフID</dt>
                <dd>{{ $request['staff_id'] }}</dd>
                <dt>提出日時</dt>
                <dd>{{ $request['submitted_at'] !== '' ? $request['submitted_at'] : '---' }}</dd>
                <dt>確認日時</dt>
                <dd>{{ $request['confirmed_at'] !== '' ? $request['confirmed_at'] : '---' }}</dd>
                <dt>反映日時</dt>
                <dd>{{ $request['reflected_at'] !== '' ? $request['reflected_at'] : '---' }}</dd>
            </dl>

            @if ($request['status'] === 'returned' && $request['return_note'] !== '')
            <p><strong>差戻し理由：</strong>{{ $request['return_note'] }}</p>
            @endif

            <h3 style="margin-top:16px;">本人情報（マスタへの反映が必要）</h3>
            <form method="post" action="{{ route('admin.work.profile_requests.personal_info.update', ['requestId' => $requestId]) }}" enctype="multipart/form-data">
                @csrf
                <div class="pr-table-wrap">
                    <table class="data-table pr-table">
                        <thead>
                            <tr>
                                <th>項目</th>
                                <th>現在の登録（マスタ）</th>
                                <th>申告内容（訂正可）</th>
                                <th>証憑</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>氏名</td>
                                <td>{{ $staff['staff_name'] ?? '---' }}</td>
                                <td><input type="text" name="new_staff_name" value="{{ $request['new_staff_name'] }}" maxlength="50"></td>
                                <td rowspan="2">
                                    @if ($request['name_change_certificate_original_name'] !== '')
                                    <a href="{{ route('admin.work.profile_requests.name.certificate_file', ['requestId' => $requestId]) }}" target="_blank" rel="noopener">{{ $request['name_change_certificate_original_name'] }}</a><br>
                                    @endif
                                    <input type="file" name="name_change_certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                                </td>
                            </tr>
                            <tr>
                                <td>フリガナ（氏名）</td>
                                <td>{{ $staff['staff_name_furi'] ?? '---' }}</td>
                                <td><input type="text" name="new_staff_name_furi" value="{{ $request['new_staff_name_furi'] }}" maxlength="50"></td>
                            </tr>
                            <tr>
                                <td>住所</td>
                                <td>{{ $staff['address'] ?? '---' }}</td>
                                <td><input type="text" name="new_address" value="{{ $request['new_address'] }}" maxlength="255"></td>
                                <td rowspan="2">
                                    @if ($request['address_change_certificate_original_name'] !== '')
                                    <a href="{{ route('admin.work.profile_requests.address.certificate_file', ['requestId' => $requestId]) }}" target="_blank" rel="noopener">{{ $request['address_change_certificate_original_name'] }}</a><br>
                                    @endif
                                    <input type="file" name="address_change_certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                                </td>
                            </tr>
                            <tr>
                                <td>フリガナ（住所）</td>
                                <td>{{ $staff['address_furi'] ?? '---' }}</td>
                                <td><input type="text" name="new_address_furi" value="{{ $request['new_address_furi'] }}" maxlength="255"></td>
                            </tr>
                            <tr>
                                <td>世帯主の氏名</td>
                                <td>{{ ($staff['head_house'] ?? '') !== '' ? $staff['head_house'] : '---' }}</td>
                                <td><input type="text" name="setai_nushi" value="{{ $request['setai_nushi'] }}" maxlength="50"></td>
                                <td>---</td>
                            </tr>
                            <tr>
                                <td>世帯主との続柄</td>
                                <td>{{ ($staff['relationship'] ?? '') !== '' ? $staff['relationship'] : '---' }}</td>
                                <td><input type="text" name="setai_zoku_gara" value="{{ $request['setai_zoku_gara'] }}" maxlength="20"></td>
                                <td>---</td>
                            </tr>
                            <tr>
                                <td>車通勤km</td>
                                <td>{{ ($staff['car_km'] ?? '') !== '' ? $staff['car_km'] : '---' }}</td>
                                <td><input type="text" name="new_car_km" value="{{ $request['new_car_km'] }}" maxlength="100"></td>
                                <td rowspan="3">
                                    @if ($request['commute_change_certificate_original_name'] !== '')
                                    <a href="{{ route('admin.work.profile_requests.commute.certificate_file', ['requestId' => $requestId]) }}" target="_blank" rel="noopener">{{ $request['commute_change_certificate_original_name'] }}</a><br>
                                    @endif
                                    <input type="file" name="commute_change_certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                                </td>
                            </tr>
                            <tr>
                                <td>日額交通費</td>
                                <td>{{ ($staff['traffic_day'] ?? '') !== '' ? $staff['traffic_day'] : '---' }}</td>
                                <td><input type="text" name="new_traffic_day" value="{{ $request['new_traffic_day'] }}" maxlength="100"></td>
                            </tr>
                            <tr>
                                <td>追加交通費日額</td>
                                <td>{{ ($staff['traffic_day_tuika'] ?? '') !== '' ? $staff['traffic_day_tuika'] : '---' }}</td>
                                <td><input type="text" name="new_traffic_day_tuika" value="{{ $request['new_traffic_day_tuika'] }}" maxlength="100"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pr-item-actions">
                    <button type="submit" class="btn btn-primary">申告内容を訂正して保存</button>
                </div>
            </form>

            <h3 style="margin-top:16px;">扶養情報（実データ反映済み）</h3>
            <div class="pr-list">
                @forelse ($dependentRows as $row)
                @php
                $fuyoNo = (int) ($row['fuyo_no'] ?? 0);
                $fuyoBirthdayInput = substr((string) ($row['fuyo_birthday'] ?? ''), 0, 10);
                $fuyoIncomeInput = str_replace(',', '', (string) ($row['fuyo_shunyu'] ?? ''));
                @endphp
                <article class="pr-item">
                    <form method="post" action="{{ route('admin.work.profile_requests.fuyo.update', ['requestId' => $requestId, 'fuyoNo' => $fuyoNo]) }}" enctype="multipart/form-data" class="pr-item-form">
                        @csrf
                        <div class="pr-item-head">{{ $row['fuyo_name'] ?? '氏名未登録' }}（{{ $row['fuyo_relationship'] ?? '続柄未登録' }}）</div>

                        <div class="pr-edit-grid">
                            <label class="pr-edit-field">
                                氏名
                                <input type="text" name="fuyo_name" value="{{ $row['fuyo_name'] ?? '' }}" maxlength="50">
                            </label>
                            <label class="pr-edit-field">
                                フリガナ
                                <input type="text" name="fuyo_name_furi" value="{{ $row['fuyo_name_furi'] ?? '' }}" maxlength="50">
                            </label>
                            <label class="pr-edit-field">
                                続柄
                                <input type="text" name="fuyo_relationship" value="{{ $row['fuyo_relationship'] ?? '' }}" maxlength="50">
                            </label>
                            <label class="pr-edit-field">
                                生年月日
                                <input type="date" name="fuyo_birthday" value="{{ $fuyoBirthdayInput }}">
                            </label>
                            <label class="pr-edit-field">
                                同居／別居
                                <select name="kyojyu">
                                    <option value="" @selected(($row['kyojyu'] ?? '') === '')>選択</option>
                                    <option value="同居" @selected(($row['kyojyu'] ?? '') === '同居')>同居</option>
                                    <option value="別居" @selected(($row['kyojyu'] ?? '') === '別居')>別居</option>
                                </select>
                            </label>
                            <label class="pr-edit-field">
                                年間収入見込み
                                <input type="number" name="fuyo_shunyu" value="{{ $fuyoIncomeInput }}" step="1">
                            </label>
                            <label class="pr-edit-field pr-edit-field-wide">
                                住所
                                <input type="text" name="fuyo_address" value="{{ $row['fuyo_address'] ?? '' }}" maxlength="255">
                            </label>
                            <label class="pr-edit-field" style="grid-template-columns:auto auto;">
                                <input type="checkbox" name="deduction_target" value="1" @checked(((int) ($row['deduction_target'] ?? 0)) === 1)>
                                控除の対象にする
                            </label>
                        </div>

                        <div class="pr-item-proof">
                            @if (!empty($row['failure_certificate_original_name']))
                            <a href="{{ route('admin.work.profile_requests.fuyo.certificate_file', ['requestId' => $requestId, 'fuyoNo' => $fuyoNo]) }}" target="_blank" rel="noopener" class="btn btn-outline">障害者手帳を開く（{{ $row['failure_certificate_original_name'] }}）</a>
                            @else
                            <span class="pr-proof-missing">障害者手帳の写し未添付</span>
                            @endif
                        </div>

                        <label class="pr-edit-field pr-edit-field-wide">
                            証憑添付（差し替える場合のみ選択）
                            <input type="file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                        </label>

                        <div class="pr-item-actions">
                            <button type="submit" class="btn btn-primary">保存</button>
                        </div>
                    </form>
                </article>
                @empty
                <div class="empty">扶養情報がありません。</div>
                @endforelse
            </div>

            @if ($request['status'] === 'submitted')
            <div class="pr-item-actions" style="margin-top:16px;">
                <form method="post" action="{{ route('admin.work.profile_requests.confirm', ['requestId' => $requestId]) }}" onsubmit="return confirm('確認済みにします。よろしいですか？');">
                    @csrf
                    <button class="btn btn-primary" type="submit">確認済みにする</button>
                </form>
            </div>
            <form method="post" action="{{ route('admin.work.profile_requests.return', ['requestId' => $requestId]) }}" onsubmit="return confirm('差し戻します。よろしいですか？');" style="margin-top:8px;">
                @csrf
                <label>差戻し理由</label>
                <textarea name="return_note" maxlength="2000" rows="2" style="width:100%;" required></textarea>
                <button class="btn" type="submit">差し戻す</button>
            </form>
            @endif

            @if ($request['status'] === 'confirmed')
            <div class="pr-item-actions" style="margin-top:16px;">
                <form method="post" action="{{ route('admin.work.profile_requests.reflect', ['requestId' => $requestId]) }}" onsubmit="return confirm('スタッフマスタ（氏名・住所・世帯主・通勤経路）へ反映します。よろしいですか？');">
                    @csrf
                    <button class="btn btn-primary" type="submit">実データへ反映</button>
                </form>
            </div>
            @endif
        </section>
    </div>
</body>

</html>
