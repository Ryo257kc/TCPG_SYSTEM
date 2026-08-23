<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * レシートスキャンツール（外部で個人開発している静的なhtml/css/jsツールをそのまま配信するだけの窓口）。
 * ツール本体（resources/tools/receipt_scan/）は編集しない。更新はこのフォルダへの上書きだけで反映される。
 * スタッフポータルのログイン認証（staff.authミドルウェア）配下でのみ配信し、公開の public/ には置かない。
 */
class ReceiptScanController extends Controller
{
    private const BASE_PATH = 'tools/receipt_scan';

    /**
     * index.htmlはCSS/JS/画像を"static/..."という相対パスで参照している。相対パスはURLの
     * 末尾スラッシュの有無で解決先が変わるが、Laravelのroute()ヘルパーはルート定義に付けた
     * 末尾スラッシュを生成URLから落としてしまうため、メニューカードのリンクは末尾スラッシュ
     * 無しの/staff/tools/receipt-scanになる。その状態だと相対パスが/staff/tools/static/...と
     * 誤って解決されCSS/JSが読み込めなくなる（2026-08-20、CSSが効かない不具合の実際の原因）。
     * ツール本体（index.html）は編集しない方針のため、配信時に<base>タグを注入して
     * 相対パスの基準を固定する。
     */
    public function index(): Response
    {
        $path = resource_path(self::BASE_PATH . '/index.html');
        $html = file_get_contents($path);
        $baseHref = e(route('tools.receipt_scan')) . '/';
        $html = preg_replace('/<head>/', '<head><base href="' . $baseHref . '">', $html, 1);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Windows環境ではfileinfoによるMIME自動判定がtext/plain等になりCSS/JSがブラウザに
     * 適用されないことがあるため、拡張子で明示的に指定する（2026-08-20、CSSが効かない不具合で発覚）。
     */
    private const MIME_TYPES = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    public function asset(string $path): BinaryFileResponse
    {
        $basePath = realpath(resource_path(self::BASE_PATH . '/static'));
        $fullPath = realpath(resource_path(self::BASE_PATH . '/static/' . $path));

        abort_if($basePath === false || $fullPath === false || !str_starts_with($fullPath, $basePath), 404);

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = self::MIME_TYPES[$extension] ?? null;

        return response()->file($fullPath, $mimeType !== null ? ['Content-Type' => $mimeType] : []);
    }
}
