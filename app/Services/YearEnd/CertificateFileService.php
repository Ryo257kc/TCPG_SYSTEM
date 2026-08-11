<?php

namespace App\Services\YearEnd;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * 年末調整の証憑ファイル（保険料控除・住宅ローン控除・前職源泉徴収票など）の保存先。
 * localディスク（storage/app/private、Webから直接アクセス不可）に保存し、
 * 認証済みルート経由（Storage::disk('local')->response()）でのみ配信する。
 * 画像はJPEGに圧縮・リサイズして保存する（PDFはそのまま保存）。
 */
class CertificateFileService
{
    private const DISK = 'local';

    private const MAX_IMAGE_SIDE = 1600;

    /** @return array{path: string, original_name: string, uploaded_at: \Illuminate\Support\Carbon} */
    public function store(UploadedFile $file, string $directory, string $baseName): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());
        $directory = trim($directory, '/');

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            $fileName = $baseName . '.pdf';
            $storedPath = $file->storeAs($directory, $fileName, self::DISK);
        } else {
            $compressed = $this->compressImage($file->getRealPath());
            if ($compressed !== null) {
                $fileName = $baseName . '.jpg';
                $storedPath = $directory . '/' . $fileName;
                Storage::disk(self::DISK)->put($storedPath, $compressed);
            } else {
                $fallbackExtension = in_array($extension, ['jpg', 'jpeg', 'png'], true) ? $extension : 'jpg';
                $fileName = $baseName . '.' . $fallbackExtension;
                $storedPath = $file->storeAs($directory, $fileName, self::DISK);
            }
        }

        $originalName = (string) $file->getClientOriginalName();

        return [
            'path' => $storedPath,
            'original_name' => function_exists('mb_substr') ? mb_substr($originalName, 0, 255) : substr($originalName, 0, 255),
            'uploaded_at' => now(),
        ];
    }

    public function delete(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '' || preg_match('/^https?:\/\//', $path) === 1) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path);
    }

    public function extension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /** @return \Symfony\Component\HttpFoundation\StreamedResponse */
    public function response(string $path, string $downloadName)
    {
        return Storage::disk(self::DISK)->response($path, $downloadName);
    }

    private function compressImage(string $sourcePath): ?string
    {
        if (!function_exists('imagejpeg') || !function_exists('imagecreatetruecolor')) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return null;
        }

        [$width, $height, $type] = $info;
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $source = null;
        if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
            $source = @imagecreatefromjpeg($sourcePath);
        } elseif ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $source = @imagecreatefrompng($sourcePath);
        }

        if (!$source) {
            return null;
        }

        $scale = min(1, self::MAX_IMAGE_SIDE / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        if (!$canvas) {
            imagedestroy($source);
            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        $saved = imagejpeg($canvas, null, 78);
        $binary = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return $saved ? $binary : null;
    }
}
