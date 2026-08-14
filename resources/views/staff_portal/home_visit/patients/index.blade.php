<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 患者一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <style>
            .w-keyword {
                flex: 1 1 180px;
            }

            .w-filter {
                flex: 1 1 90px;
            }
        </style>

        <section
            class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">患者一覧</h2>
            </div>

            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif


            @if($errors->has('patients'))
            <div class="alert">{{ $errors->first('patients') }}</div>
            @endif
            <div class="toolbar">
                <form method="get" action="{{ route('home_visit.patients.index') }}">
                    <input class="w-keyword" type="text" name="q" value="{{ request('q') }}" placeholder="患者名で検索">
                    <select class="w-filter" name="shop">
                        <option value="">-店舗-</option>
                        @foreach($storeOptions as $option)
                        <option value="{{ $option }}" {{ $shop === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <select class="w-filter" name="facility">
                        <option value="">-施設名-</option>
                        @foreach($facilityOptions as $option)
                        <option value="{{ $option }}" {{ $facility === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <select class="w-filter" name="staff">
                        <option value="">-集金担当-</option>
                        @foreach($staffOptions as $option)
                        <option value="{{ $option['staff_id'] }}"
                            {{ $staff === $option['staff_id'] ? 'selected' : '' }}>
                            {{ $option['display_name_ja'] !== '' ? $option['display_name_ja'] : $option['staff_name'] }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit">検索</button>
                    {{-- クリア --}}
                    <a href="{{ route('home_visit.patients.index') }}" class="btn">クリア</a>
                    <a href="{{ route('patients.create') }}" class="btn btn-primary">新規追加</a>
                </form>
            </div>
            @if(count($items) === 0)
            <div class="empty">該当するデータがありません。</div>
            @else
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 20px;">
                        <col style="width: 100px;">
                        <col style="width: 70px;">
                        <col style="width: 50px;">
                        <col style="width: 50px;">
                        <col style="width: 50px;">
                        <col style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>患者名</th>
                            <th>町名</th>
                            <th>施設名</th>
                            <th>店舗</th>
                            <th>集金担当</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->patient_name }}</td>
                            <td>{{ $item->visit_town_name }}</td>
                            <td>{{ $item->facility_name }}</td>
                            <td>{{ $item->visit_store_name }}</td>
                            <td>{{ $item->collection_staff_name }}</td>
                            <td><a class="btn btn_small" href="{{ route('patients.edit', ['patient_id' => $item->patient_id]) }}">詳細</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>

    </main>
</body>

</html>
