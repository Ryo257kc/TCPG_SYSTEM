<?php

namespace App\Services\YearEnd;

/**
 * 国税庁の電子的控除証明書等（生命保険料控除証明書、様式コードTEG800）のXMLを読み、
 * mx_hokenの保険料控除フォームへ自動入力するための値を取り出す。
 *
 * スキーマの元は docs/reference/nta_certificate_xsd/kyotsu/TEG800-001.xsd
 * （国税庁 e-Tax「電子的控除証明書等（金融機関等発行書類）に係る仕様書一覧」より取得、2026-08-19）。
 *
 * 要確認：実際の保険会社発行のサンプルXMLでまだ検証していない。本番投入前に必ず実物で確認すること。
 */
class LifeInsuranceCertificateXmlParser
{
    private const NS = 'http://xml.e-tax.nta.go.jp/XSD/kyotsu';

    /**
     * 明細（WCE00000）1件・区分1つにつき1契約として返す。
     * 「証明額（12月期想定）」の「申告額（参考）」を申告額として使う
     * （年末調整の時点でまだ確定していない12月分を見込んだ、国税庁側が申告用に案内する額のため）。
     *
     * @return array{insurance_company:string,certificate_date:?string,document_id:string,document_version:string,created_at:?string,created_by:string,contracts:list<array<string,mixed>>}
     */
    public function parse(string $xmlContent): array
    {
        if (trim($xmlContent) === '') {
            throw new \RuntimeException('XMLファイルが空です。');
        }

        // LIBXML_NOENTは付けない：外部SYSTEMエンティティを本文へ展開してしまうと
        // XXE（ローカルファイル読み出し等）の入口になり得るため、アップロードされた
        // 信頼できないXMLに対しては使わない。LIBXML_NONETはネットワーク経由の外部参照を遮断する。
        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($xmlContent, LIBXML_NONET);
        if (!$loaded) {
            throw new \RuntimeException('XMLの読み込みに失敗しました。ファイルが破損しているか、対応していない形式です。');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('k', self::NS);

        $root = $xpath->query('/k:TEG800')->item(0);
        if ($root === null) {
            throw new \RuntimeException('この様式は生命保険料控除証明書（TEG800）ではありません。');
        }

        $insuranceCompany = $this->text($xpath, $root, 'k:WCA00000');
        $certificateDate = $this->formatDate($this->text($xpath, $root, 'k:WCC00000'));
        $policyHolder = $this->text($xpath, $root, 'k:WCD00000');

        // ルート要素の属性（id/VR/sakuseiDay/sakuseiNM）。改ざん・使い回しの手がかりとして
        // 人間が目視確認できるよう、証明書プレビュー画面に出す用に取り出しておく
        // （2026-08-22追加。署名検証はしていないため、最終的な真正性の担保はこれと
        // 事務所側の目視確認が頼り。この値自体も書き換え可能な点は変わらないので過信しない）。
        $documentId = trim($root->getAttribute('id'));
        $documentVersion = trim($root->getAttribute('VR'));
        // sakuseiDayは既にYYYY-MM-DD形式の属性値（gen:yyyy/mm/dd分割ではない）。
        $createdAtRaw = trim($root->getAttribute('sakuseiDay'));
        $createdAt = preg_match('/^\d{4}-\d{2}-\d{2}$/', $createdAtRaw) === 1 ? $createdAtRaw : null;
        $createdBy = trim($root->getAttribute('sakuseiNM'));

        $contracts = [];
        foreach ($xpath->query('k:WCE00000', $root) as $detail) {
            $certificateYear = $this->text($xpath, $detail, 'k:WCE00010');
            $policyNumber = $this->text($xpath, $detail, 'k:WCE00040');
            $insuranceType = $this->text($xpath, $detail, 'k:WCE00050');
            $insuredPerson = $this->text($xpath, $detail, 'k:WCE00080');
            $beneficiary = $this->text($xpath, $detail, 'k:WCE00110');
            $pensionStartDate = $this->formatDate($this->text($xpath, $detail, 'k:WCE00180'));

            // 「証明額（12月期想定）」(WCE00440) 配下、旧制度/新制度 × 一般/介護医療/年金の
            // 5組み合わせをそれぞれ確認し、「申告額（参考）」が入っている分だけ契約として拾う。
            $categoryPaths = [
                ['一般保険', '旧制度', 'k:WCE00440/k:WCE00460/k:WCE00470/k:WCE00500'],
                ['年金保険', '旧制度', 'k:WCE00440/k:WCE00460/k:WCE00510/k:WCE00540'],
                ['一般保険', '新制度', 'k:WCE00440/k:WCE00550/k:WCE00560/k:WCE00590'],
                ['介護保険', '新制度', 'k:WCE00440/k:WCE00550/k:WCE00600/k:WCE00630'],
                ['年金保険', '新制度', 'k:WCE00440/k:WCE00550/k:WCE00640/k:WCE00670'],
            ];

            foreach ($categoryPaths as [$category, $appliedSystem, $path]) {
                $amountText = $this->text($xpath, $detail, $path);
                $amount = $amountText !== '' ? (float) $amountText : 0.0;
                if ($amount <= 0.0) {
                    continue;
                }

                $noteParts = array_filter([
                    $policyNumber !== '' ? '証券番号:' . $policyNumber : null,
                    $insuredPerson !== '' ? '被保険者:' . $insuredPerson : null,
                ]);

                $contracts[] = [
                    'insurance_company' => $insuranceCompany,
                    'certificate_year' => $certificateYear !== '' ? (int) $certificateYear : null,
                    'category' => $category,
                    'applied_system' => $appliedSystem,
                    'declared_amount' => $amount,
                    'insurance_type' => $insuranceType,
                    'insurance_period' => '',
                    'policy_holder_name' => $policyHolder,
                    'beneficiary_name' => $beneficiary,
                    'beneficiary_relationship' => '',
                    'pension_payment_start_date' => $category === '年金保険' ? $pensionStartDate : '',
                    'year_end_insurance_note' => implode(' / ', $noteParts),
                ];
            }
        }

        return [
            'insurance_company' => $insuranceCompany,
            'certificate_date' => $certificateDate,
            'document_id' => $documentId,
            'document_version' => $documentVersion,
            'created_at' => $createdAt,
            'created_by' => $createdBy,
            'contracts' => $contracts,
        ];
    }

    private function text(\DOMXPath $xpath, \DOMNode $context, string $relativePath): string
    {
        $node = $xpath->query($relativePath, $context)->item(0);
        return $node !== null ? trim((string) $node->textContent) : '';
    }

    /** gen:yyyymmdd（YYYYMMDD）をYYYY-MM-DDへ。空・不正な値はnull。 */
    private function formatDate(string $value): ?string
    {
        if (!preg_match('/^\d{8}$/', $value)) {
            return null;
        }

        return substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2);
    }
}
