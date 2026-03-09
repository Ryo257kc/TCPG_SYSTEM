<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$controller = $app->make(App\Http\Controllers\Admin\AttendanceController::class);
$request = Illuminate\Http\Request::create('/admin/attendance', 'GET', ['month' => '2026-02']);
try {
    $resp = $controller->index($request);
    echo "RESP=" . get_class($resp) . PHP_EOL;
    if ($resp instanceof Illuminate\View\View) {
        $html = $resp->render();
        echo "HTML_LEN=" . strlen($html) . PHP_EOL;
        echo substr($html, 0, 200) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "EX=" . get_class($e) . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
