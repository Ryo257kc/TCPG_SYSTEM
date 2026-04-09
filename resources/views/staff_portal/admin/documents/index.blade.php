<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 書類</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/documents-index.css') }}">
</head>
<body class="documents-index-page">
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName ?? '', 'hidePayrollLinks' => $hidePayrollLinks ?? false])

<section class="panel content-panel">
        <div class="content-head">
            <h2 class="content-title">入社準備書類</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th class="num"></th>
                    <th>記載書類</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="num">1</th>
                    <td>
                        <a class="doc-link" href="{{ url('/document/PG-誓約書.pdf') }}" target="_blank">プレッジ誓約書</a><br>
                        <a class="doc-link" href="{{ url('/document/TC-誓約書.pdf') }}" target="_blank">トータルケア誓約書</a>
                    </td>
                </tr>
                <tr>
                    <th class="num">2</th>
                    <td>
                        <a class="doc-link" href="{{ url('/document/PG-身元保証引受書.pdf') }}" target="_blank">プレッジ身元保証引受書</a><br>
                        <a class="doc-link" href="{{ url('/document/TC-身元保証引受書.pdf') }}" target="_blank">トータルケア身元保証引受書</a>
                    </td>
                </tr>
                <tr>
                    <th class="num">3</th>
                    <td><a class="doc-link" href="{{ url('/document/通勤手段経路申請書.pdf') }}" target="_blank">通勤手段経路申請書</a></td>
                </tr>
                <tr>
                    <th class="num">4</th>
                    <td><a class="doc-link" href="{{ url('/document/扶養控除申告書.pdf') }}" target="_blank">扶養控除申告書（弊社で年末調整する場合のみ）</a></td>
                </tr>
                </tbody>
            </table>

            <table>
                <thead>
                <tr>
                    <th class="num"></th>
                    <th>本人準備書類</th>
                </tr>
                </thead>
                <tbody>
                <tr><th class="num">5</th><td>施術者免許（原本）</td></tr>
                <tr><th class="num">6</th><td>運転免許証（通勤や往診で車利用する場合は必須）</td></tr>
                <tr><th class="num">7</th><td>住民票（運転免許証なし or 運転免許証の住所が違う場合）</td></tr>
                <tr><th class="num">8</th><td>マイナンバー（住民票記載でも可）</td></tr>
                <tr><th class="num">9</th><td>振込口座</td></tr>
                </tbody>
            </table>

            <table>
                <thead>
                <tr>
                    <th class="num"></th>
                    <th>前職場より受取書類</th>
                </tr>
                </thead>
                <tbody>
                <tr><th class="num">10</th><td>雇用保険被保険者証</td></tr>
                <tr><th class="num">11</th><td>前職源泉徴収票</td></tr>
                </tbody>
            </table>
        </div>

        <div class="note">
            ※原本と書いてあるもの以外はコピー及び写メ提出でも可。<br>
            ※上記、⑩及び⑪は前職場より受取次第、速やかに提出をお願いします。
        </div>
    </section>

    
</main>
</body>
</html>
