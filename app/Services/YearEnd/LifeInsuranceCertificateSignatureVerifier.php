<?php

namespace App\Services\YearEnd;

use phpseclib3\File\X509;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * 電子的控除証明書等（TEG800等）のXMLに埋め込まれているXML-DSig署名（エンベロープ型）を検証する。
 *
 * 検証するのは「保存されているXML本文が、署名された時点から1バイトも変わっていないか」という
 * 改ざん検知だけ（2026-09-13、ユーザー確認：それ以上の高度な改ざんをする人はいない想定でよい）。
 * 署名の検証鍵はXML自身のKeyInfo（X509Certificate）から取り出して使うため、
 * 「その証明書を発行したのが本当に保険会社/信頼できる認証局か」までは保証しない
 * （＝発行者のなりすまし・証明書自体の正当性は範囲外。目視確認は引き続き必要）。
 */
class LifeInsuranceCertificateSignatureVerifier
{
    private const DSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';

    /**
     * @return array{verified: bool, reason: ?string, signer: ?array{subject: string, issuer: string}}
     */
    public function verify(string $xmlContent): array
    {
        $doc = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = @$doc->loadXML($xmlContent, LIBXML_NONET);
        libxml_use_internal_errors($previous);
        if (!$loaded) {
            return ['verified' => false, 'reason' => 'XMLの読み込みに失敗しました。', 'signer' => null];
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('dsig', self::DSIG_NS);
        $certNode = $xpath->query('//dsig:Signature/dsig:KeyInfo/dsig:X509Data/dsig:X509Certificate')->item(0);
        if ($certNode === null) {
            return ['verified' => false, 'reason' => 'このXMLには電子署名が含まれていません。', 'signer' => null];
        }

        $pem = $this->toPem($certNode->textContent);
        $signer = $this->signerInfo($pem);

        try {
            $dsig = new XMLSecurityDSig();
            // TEG800のルート要素は id="..."（小文字）でReferenceのURIから参照されるため、
            // xmlseclibs標準の"Id"（大文字）に加えて"id"も同一視する必要がある。
            $dsig->idKeys = ['id'];

            if ($dsig->locateSignature($doc) === null) {
                return ['verified' => false, 'reason' => '署名(Signature要素)が見つかりません。', 'signer' => $signer];
            }

            $objKey = $dsig->locateKey();
            if ($objKey === null) {
                return ['verified' => false, 'reason' => '署名アルゴリズムを取得できませんでした。', 'signer' => $signer];
            }
            $objKey->loadKey($pem, false, true);

            $dsig->verifyDocument($objKey, $doc);
        } catch (\Throwable $e) {
            return [
                'verified' => false,
                'reason' => '署名の検証に失敗しました（証明書発行後に内容が書き換えられている可能性があります）。',
                'signer' => $signer,
            ];
        }

        return ['verified' => true, 'reason' => null, 'signer' => $signer];
    }

    private function toPem(string $base64Body): string
    {
        $body = preg_replace('/\s+/', '', $base64Body) ?? '';

        return "-----BEGIN CERTIFICATE-----\n" . chunk_split($body, 64, "\n") . "-----END CERTIFICATE-----\n";
    }

    /**
     * @return ?array{subject: string, issuer: string}
     */
    private function signerInfo(string $pem): ?array
    {
        try {
            $x509 = new X509();
            $parsed = $x509->loadX509($pem);
            if ($parsed === false) {
                return null;
            }

            $subject = $x509->getSubjectDN(X509::DN_STRING);
            $issuer = $x509->getIssuerDN(X509::DN_STRING);

            return [
                'subject' => is_string($subject) ? $subject : '不明',
                'issuer' => is_string($issuer) ? $issuer : '不明',
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
