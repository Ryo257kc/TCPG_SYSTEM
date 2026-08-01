<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 宛名ラベル</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">

</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">宛名ラベル</h2>
            </div>

            <form method="get" action="{{ route('office.office_menu.address') }}" class="filter-row">
                <input type="text" name="q" value="{{ $keyword ?? '' }}" placeholder="氏名・会社名・住所で検索">

                <select name="category">
                    <option value="">分類</option>
                    @foreach (($categoryOptions ?? []) as $category)
                    <option value="{{ $category }}" @selected(($selectedCategory ?? '' )===$category)>{{ $category }}</option>
                    @endforeach
                </select>
                <!--
                <select name="contact_person">
                    <option value="">担当者</option>
                    @foreach (($contactPersonOptions ?? []) as $contactPerson)
                    <option value="{{ $contactPerson }}" @selected(($selectedContactPerson ?? '' )===$contactPerson)>{{ $contactPerson }}</option>
                    @endforeach
                </select> -->

                <label>
                    <input type="checkbox" name="selected_only" value="1" @checked(!empty($selectedOnly))>
                    個別選択のみ
                </label>

                <button type="submit" class="btn">表示</button>
                <!-- <a href="{{ route('office.office_menu.address') }}" class="btn">クリア</a> -->
                <button type="" class="btn">印刷／準備中</button>
            </form>

            <div class="table-wrap">
                <table class="data-table f_size12">
                    <colgroup>
                        <col style="width: 100px;">
                        <col style="width: 50px;">
                        <col style="width: 30px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 50px;">
                        <col style="width: 90px;">
                        <col style="width: 90px;">
                        <col style="width: 30px;">
                        <col style="width: 30px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>会社名</th>
                            <th>氏名</th>
                            <th>敬称</th>
                            <th>郵便番号</th>
                            <th>都道府県</th>
                            <th>市区郡</th>
                            <th>住所</th>
                            <th>建物名</th>
                            <th>分類</th>
                            <th>個別選択</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($addressRows ?? []) as $row)
                        <tr>
                            <td>{{ $row['company_name'] }}</td>
                            <td>{{ $row['full_name'] }}</td>
                            <td>{{ $row['honorific'] }}</td>
                            <td>{{ $row['postal_code'] }}</td>
                            <td>{{ $row['prefecture'] }}</td>
                            <td>{{ $row['city'] }}</td>
                            <td>{{ $row['address1'] }}</td>
                            <td>{{ $row['building_name'] }}</td>
                            <td>{{ $row['category'] }}</td>
                            <td><input type="checkbox" @checked($row['is_selected'] !=='' ) disabled></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="14">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
            <div>
                <a href="{{ route('office.office_menu') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>

</body>

</html>
