<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/admin/attendance?month=2026-02', 'GET');
try {
    $response = $kernel->handle($request);
    echo "STATUS=" . $response->getStatusCode() . PHP_EOL;
    $content = $response->getContent();
    echo "LEN=" . strlen((string)$content) . PHP_EOL;
    echo substr((string)$content, 0, 200) . PHP_EOL;
} catch (Throwable $e) {
    echo "EX=" . get_class($e) . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
