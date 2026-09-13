<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 証明書プレビュー</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <style>
        body {
            margin: 0;
            background: #f3f6fa;
            color: #1f2937;
            font-family: "Yu Gothic", "Meiryo", sans-serif;
        }

        .proof-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid #d7dee8;
            background: rgba(255, 255, 255, 0.96);
        }

        .proof-title {
            min-width: 0;
            font-size: 14px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .proof-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .proof-button {
            min-width: 82px;
            border: 1px solid #bcc8d8;
            border-radius: 8px;
            padding: 8px 12px;
            background: #fff;
            color: #263445;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .proof-stage {
            min-height: calc(100vh - 58px);
            display: grid;
            place-items: center;
            padding: 18px;
            overflow: auto;
        }

        .proof-image-wrap {
            display: grid;
            place-items: center;
            min-width: 100%;
            min-height: calc(100vh - 100px);
        }

        .proof-image {
            max-width: 92vw;
            max-height: calc(100vh - 110px);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.18);
            background: #fff;
            transform-origin: center center;
        }

        .proof-pdf {
            width: 100%;
            height: calc(100vh - 86px);
            border: 1px solid #d7dee8;
            background: #fff;
        }

        .proof-message {
            max-width: 620px;
            border: 1px solid #d7dee8;
            border-radius: 8px;
            padding: 18px;
            background: #fff;
            line-height: 1.7;
        }

        .proof-xml-official-wrap {
            width: min(800px, 98vw);
            margin-bottom: 16px;
        }

        .proof-xml-official-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .proof-xml-official {
            /* LifeInsuranceCertificateXsltRenderer側で表の幅を750px（印刷用と同じ幅）に
               狭めているため、こちらの下限もそれに合わせる（2026-09-13、900px幅だと
               横にだだっ広く見にくいとユーザーから指摘）。はみ出す分は
               親(.proof-xml-official-scroll)の横スクロールに任せる。iframe自体は
               まず親の幅に合わせ(100%)、それが750px+スクロールバー分より狭い時だけ
               min-widthで下限を確保する（固定幅にすると常に親よりはみ出して
               意味もなく横スクロールが出続けてしまうため）。
               box-sizing: border-boxも指定し、borderの分だけ幅が親より
               はみ出して不要な横スクロールが出るのを防ぐ。 */
            box-sizing: border-box;
            width: 100%;
            min-width: 770px;
            max-width: none;
            height: 1450px;
            border: 1px solid #d7dee8;
            background: #fff;
        }

        .proof-xml {
            width: min(760px, 92vw);
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            background: #fff;
            border: 1px solid #d7dee8;
            border-radius: 8px;
            padding: 20px 24px;
        }

        .proof-xml-header {
            margin: 0 0 12px;
            padding-bottom: 10px;
            border-bottom: 2px solid #263445;
            font-size: 15px;
            font-weight: 700;
        }

        .proof-xml-contract {
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }

        .proof-xml-contract-title {
            margin: 0;
            padding: 8px 12px;
            background: #eef2f8;
            font-size: 13px;
            font-weight: 700;
        }

        .proof-xml-signature {
            margin: 0;
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            font-weight: 700;
        }

        .proof-xml-signature.is-ok {
            background: #eefcf3;
            color: #1a7f4b;
        }

        .proof-xml-signature.is-ng {
            background: #fdeeee;
            color: #b3261e;
        }

        .proof-xml-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .proof-xml-table th,
        .proof-xml-table td {
            padding: 6px 12px;
            border-top: 1px solid #eef1f5;
            text-align: left;
        }

        .proof-xml-table th {
            width: 34%;
            color: #4b5768;
            font-weight: 600;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <header class="proof-toolbar">
        <div class="proof-title">証明書プレビュー{{ $fileName !== '' ? '：'.$fileName : '' }}</div>
        <div class="proof-actions">
            @if ($isImage)
            <button type="button" class="proof-button" data-rotate="-90">左回転</button>
            <button type="button" class="proof-button" data-rotate="90">右回転</button>
            <button type="button" class="proof-button" data-reset>戻す</button>
            @endif
            <a class="proof-button" href="{{ $fileUrl }}" target="_blank" rel="noopener">元を開く</a>
            <button type="button" class="proof-button" onclick="window.close()">閉じる</button>
        </div>
    </header>

    <main class="proof-stage">
        @if ($isImage)
        <div class="proof-image-wrap">
            <img id="proof-image" class="proof-image" src="{{ $fileUrl }}" alt="証明書">
        </div>
        @elseif ($isPdf)
        <iframe class="proof-pdf" src="{{ $fileUrl }}" title="証明書PDF"></iframe>
        @elseif ($isXml)
        <div class="proof-xml-official-wrap">
            <p class="proof-xml-contract-title">国税庁公式レイアウトでの表示（証憑としての見た目確認用）</p>
            <div class="proof-xml-official-scroll">
                <iframe class="proof-xml-official"
                    src="{{ route('admin.work.year_end_adjustments.hoken.certificate_xslt_preview', ['applicationId' => $applicationId, 'hokenNo' => $hokenNo]) }}"
                    title="生命保険料控除証明書（国税庁公式レイアウト）"></iframe>
            </div>
            <p class="year-end-note f_size11">国税庁がe-Taxで公開しているスタイルシートでそのまま表示したもの（元のレイアウトが横900px固定のため、はみ出す場合は枠内を横スクロールしてください）。下の「自動入力用に読み取った内容」と見比べて確認できます。</p>
        </div>
        <div class="proof-xml">
            <p class="proof-xml-header">
                電子的控除証明書（XML）の読み取り内容
                {{ $xmlParsed['insurance_company'] !== '' ? '：'.$xmlParsed['insurance_company'] : '' }}
                {{ $xmlParsed['certificate_date'] ? '（証明日：'.$xmlParsed['certificate_date'].'）' : '' }}
            </p>
            <div class="proof-xml-contract">
                <p class="proof-xml-contract-title">文書情報（改ざん・使い回しの確認用）</p>
                @if ($signatureVerification !== null)
                <p class="proof-xml-signature {{ $signatureVerification['verified'] ? 'is-ok' : 'is-ng' }}">
                    @if ($signatureVerification['verified'])
                    ✓ 電子署名の検証OK（保存されている内容は署名時点から変更されていません）
                    @else
                    ⚠ 電子署名の検証NG（{{ $signatureVerification['reason'] ?? '内容が署名時点から変更されている可能性があります' }}）
                    @endif
                    @if ($signatureVerification['signer'])
                    ／署名者：{{ $signatureVerification['signer']['subject'] }}
                    @endif
                </p>
                @endif
                <table class="proof-xml-table">
                    <tbody>
                        <tr>
                            <th>作成日</th>
                            <td>{{ $xmlParsed['created_at'] ?? '不明' }}</td>
                        </tr>
                        <tr>
                            <th>作成者</th>
                            <td>{{ $xmlParsed['created_by'] !== '' ? $xmlParsed['created_by'] : '不明' }}</td>
                        </tr>
                        <tr>
                            <th>文書ID</th>
                            <td>{{ $xmlParsed['document_id'] !== '' ? $xmlParsed['document_id'] : '不明' }}</td>
                        </tr>
                        <tr>
                            <th>様式バージョン</th>
                            <td>{{ $xmlParsed['document_version'] !== '' ? $xmlParsed['document_version'] : '不明' }}</td>
                        </tr>
                    </tbody>
                </table>
                <p class="year-end-note">電子署名の検証は「保存内容が署名時点から変わっていないか」の改ざん検知のみです。署名した証明書自体の発行元が正しいか（認証局の検証）までは行っていないため、会社名・金額等が正規のものかは引き続き目視で確認してください。</p>
            </div>
            @foreach ($xmlParsed['contracts'] as $index => $contract)
            <div class="proof-xml-contract">
                <p class="proof-xml-contract-title">契約 {{ $index + 1 }}／{{ count($xmlParsed['contracts']) }}：{{ $contract['category'] }}（{{ $contract['applied_system'] }}）</p>
                <table class="proof-xml-table">
                    <tbody>
                        <tr>
                            <th>証明年</th>
                            <td>{{ $contract['certificate_year'] ?? '不明' }}年分</td>
                        </tr>
                        <tr>
                            <th>保険会社</th>
                            <td>{{ $contract['insurance_company'] }}</td>
                        </tr>
                        <tr>
                            <th>区分</th>
                            <td>{{ $contract['category'] }}</td>
                        </tr>
                        <tr>
                            <th>適用制度</th>
                            <td>{{ $contract['applied_system'] }}</td>
                        </tr>
                        <tr>
                            <th>申告額（参考）</th>
                            <td>{{ number_format((float) $contract['declared_amount']) }}円</td>
                        </tr>
                        <tr>
                            <th>保険種類</th>
                            <td>{{ $contract['insurance_type'] }}</td>
                        </tr>
                        <tr>
                            <th>契約者氏名</th>
                            <td>{{ $contract['policy_holder_name'] }}</td>
                        </tr>
                        <tr>
                            <th>受取人氏名</th>
                            <td>{{ $contract['beneficiary_name'] }}</td>
                        </tr>
                        @if ($contract['pension_payment_start_date'])
                        <tr>
                            <th>年金支払開始日</th>
                            <td>{{ $contract['pension_payment_start_date'] }}</td>
                        </tr>
                        @endif
                        @if ($contract['year_end_insurance_note'] !== '')
                        <tr>
                            <th>備考（証券番号・被保険者等）</th>
                            <td>{{ $contract['year_end_insurance_note'] }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @endforeach
            <p class="year-end-note f_size13"><b>この内容はXMLから自動で読み取ったものです。原本のXMLファイルは「元を開く」からダウンロードして確認できます。</b></p>
        </div>
        @elseif ($extension === 'xml')
        <div class="proof-message f_size13"><b>
                このXMLファイルの内容を読み取れませんでした（対応していない様式の可能性があります）。上の「元を開く」から元ファイルを確認してください。</b>
        </div>
        @else
        <div class="proof-message f_size13"><b>
                この形式は画面内プレビューに対応していません。上の「元を開く」から確認してください。</b>
        </div>
        @endif
    </main>

    @if ($isImage)
    <script>
        (() => {
            const image = document.getElementById('proof-image');
            let angle = 0;

            const applyRotation = () => {
                const normalized = Math.abs(angle % 180);
                image.style.transform = `rotate(${angle}deg)`;
                image.style.maxWidth = normalized === 90 ? 'calc(100vh - 110px)' : '92vw';
                image.style.maxHeight = normalized === 90 ? '92vw' : 'calc(100vh - 110px)';
            };

            document.querySelectorAll('[data-rotate]').forEach((button) => {
                button.addEventListener('click', () => {
                    angle += Number(button.dataset.rotate || 0);
                    applyRotation();
                });
            });

            document.querySelector('[data-reset]')?.addEventListener('click', () => {
                angle = 0;
                applyRotation();
            });
        })();
    </script>
    @endif
</body>

</html>
