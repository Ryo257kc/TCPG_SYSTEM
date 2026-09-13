<?php

namespace App\Services\YearEnd;

/**
 * 国税庁が電子的控除証明書等（生命保険料控除証明書、TEG800）向けに公開しているXSLTスタイルシート
 * （`docs/reference/nta_certificate_xsd/stylesheet/CMTEG800-001.xsl`、e-Tax「電子的控除証明書等
 * （金融機関等発行書類）に係る仕様書一覧」より取得、2026-09-13）を使って、アップロードされた
 * 生のXMLを国税庁の想定する見た目（罫線付きの証明書データシート）のHTMLへ変換する。
 *
 * `LifeInsuranceCertificateXmlParser`（mx_hokenへの自動入力用に必要な値だけを抜き出す）とは
 * 役割が別。こちらは「証憑として正しい内容か人が確認する」ための、公式レイアウトの再現が目的。
 */
class LifeInsuranceCertificateXsltRenderer
{
    private const STYLESHEET_PATH = 'nta_certificate_xsd/stylesheet/CMTEG800-001.xsl';

    /**
     * @throws \RuntimeException XMLの読み込み・変換に失敗した場合
     */
    public function render(string $xmlContent): string
    {
        if (trim($xmlContent) === '') {
            throw new \RuntimeException('XMLファイルが空です。');
        }

        $xml = new \DOMDocument();
        $loaded = @$xml->loadXML($xmlContent, LIBXML_NONET);
        if (!$loaded) {
            throw new \RuntimeException('XMLの読み込みに失敗しました。');
        }

        $stylesheetPath = base_path('docs/reference/' . self::STYLESHEET_PATH);
        if (!is_file($stylesheetPath)) {
            throw new \RuntimeException('スタイルシートが見つかりません。');
        }

        $xsl = new \DOMDocument();
        $xsl->load($stylesheetPath);

        $processor = new \XSLTProcessor();
        $processor->importStylesheet($xsl);

        $html = $processor->transformToXML($xml);
        if ($html === false) {
            throw new \RuntimeException('XSLT変換に失敗しました。');
        }

        return $this->enlargeAmounts($this->widenRowSpacing($html));
    }

    /**
     * 金額セル（末尾が「円」で、数字とカンマだけの内容のセル）だけを大きく・太字にする
     * （2026-09-13、ユーザー要望：見た目上どの数字が重要かひと目でわかるようにしたい）。
     * 国税庁側は金額専用のCSSクラスを用意していない（罫線用のline0〜line12クラスのみ）ため、
     * セルの文字内容を見て判定する。項目名・レイアウトは変えず、対象セルへ
     * インラインstyleを足すだけ。
     */
    private function enlargeAmounts(string $html): string
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            // 加工に失敗しても元のHTMLをそのまま返す（証憑の確認自体は継続できるようにする）。
            return $html;
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//td') as $cell) {
            $text = trim((string) $cell->textContent);
            if ($text === '' || !preg_match('/^[\d,]+円$/u', $text)) {
                continue;
            }

            $existingStyle = trim($cell->getAttribute('style'), "; \t\n\r\0\x0B");
            $newStyle = 'font-size: 14pt !important; font-weight: bold !important;';
            $cell->setAttribute('style', $existingStyle === '' ? $newStyle : $existingStyle . '; ' . $newStyle);
        }

        return $dom->saveHTML() ?: $html;
    }

    /**
     * 国税庁のスタイルシートは各行に`<tr height="19px">`のような固定の低い高さがHTML属性で
     * 直接指定されており、画面で見ると窮屈に詰まって見える（2026-09-13、ユーザーから指摘）。
     * 国税庁側のファイル（CMTEG800-001.xsl）自体は書き換えず、変換後のHTMLへ追加の
     * `<style>`を1つ差し込むだけで行の高さ・セルの余白を広げる。項目名・値・レイアウトの
     * 骨格（何がどこに書かれるか）は元のまま、見た目の詰まり具合だけを変える。
     */
    private function widenRowSpacing(string $html): string
    {
        // 画面用は表が900px幅（`@media screen { table {width:900px} }`）で横にだだっ広く
        // 見える。同じスタイルシートの印刷用設定（`@media print`側）は650〜750px幅にして
        // いるため、それに寄せて750pxへ狭める（2026-09-13、ユーザー確認）。
        // 要注意：<tr>にheight/min-heightを指定してもブラウザが無視する（行の高さは
        // 中身のセルから決まる標準的な挙動のため）。td/th側のmin-heightへ変えても
        // 効果が無かった（2026-09-13、ユーザーの手元で確認）ため、min-heightは諦めて
        // 確実に効くpaddingだけで行の高さを底上げする。
        // 「その他特記事項」等の自由記述欄は元のスタイルシート側で.newLine1クラスに
        // height:107px（複数行入る前提の固定サイズ）が指定済みのため、ここへは
        // paddingを足さない（二重に広がらないようにする）。
        // 金額の表（ラベル行＋金額行の2行1組が何組も並ぶ）が間延びして見える原因は
        // paddingの量そのものではなく、td/thへ一律にpadding-top/bottomを足すと
        // 「ラベル行の下端」と「金額行の上端」の両方に効いてしまい、本来1組として
        // 詰まっているべきラベル・金額間の間隔まで2倍に広がっていたこと
        // （2026-09-13、ユーザーが具体例を示して指摘：一般生命保険料(Ｅ)のラベル行と
        // その下の金額行の間が広すぎる）。ラベル行はclass="line2/7/9/11"、対応する
        // 金額行はclass="line3/5/8/10/12"で固定的に出力されるため、組の内側
        // （ラベル行の下端・金額行の上端）だけpaddingを0に戻し、組と組の間
        // （ラベル行の上端・金額行の下端）は広げたまま残す。
        $override = <<<'CSS'
<style>
table { width: 750px !important; }
table tr { height: auto !important; }
table td, table th { padding-top: 8px !important; padding-bottom: 8px !important; }
table td.newLine1, table th.newLine1 { padding-top: 0 !important; padding-bottom: 0 !important; }
table td.line2, table td.line7, table td.line9, table td.line11 { padding-bottom: 0 !important; }
table td.line3, table td.line5, table td.line8, table td.line10, table td.line12 { padding-top: 0 !important; }
</style>
</head>
CSS;

        $replaced = preg_replace('/<\/head>/i', $override, $html, 1, $count);

        return $count > 0 && $replaced !== null ? $replaced : $html;
    }
}
