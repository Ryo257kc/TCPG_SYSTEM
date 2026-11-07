<?php

namespace App\Services\YearEnd;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    public function validateUpload(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            $message = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'ファイルサイズが上限を超えています。画像を小さくするかPDFで添付してください。',
                UPLOAD_ERR_PARTIAL => 'ファイルの送信が途中で止まりました。通信状態を確認して選択し直してください。',
                default => 'ファイルを受け取れませんでした。選択し直して再度保存してください。',
            };
            throw ValidationException::withMessages(['certificate_file' => $message]);
        }
        $mime = strtolower((string) $file->getMimeType());
        if (in_array($mime, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true) ||
            (in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif'], true) && !in_array($mime, ['image/jpeg', 'image/png'], true))) {
            throw ValidationException::withMessages(['certificate_file' => 'HEIC／HEIF形式には対応していません。JPEG・PNG・PDFに変換して添付してください。']);
        }
        if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf', 'application/xml', 'text/xml'], true)) {
            throw ValidationException::withMessages(['certificate_file' => '対応していないファイル形式です。JPEG・PNG・PDF（保険証明書はXMLも可）を添付してください。']);
        }
    }

    /** @return array{path: string, original_name: string, uploaded_at: \Illuminate\Support\Carbon} */
    public function store(UploadedFile $file, string $directory, string $baseName): array
    {
        $this->validateUpload($file);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());
        $directory = trim($directory, '/');

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            $fileName = $baseName . '.pdf';
            $storedPath = $file->storeAs($directory, $fileName, self::DISK);
        } elseif ($extension === 'xml' || in_array($mime, ['text/xml', 'application/xml'], true)) {
            $fileName = $baseName . '.xml';
            $storedPath = $file->storeAs($directory, $fileName, self::DISK);
        } else {
            $compressed = $this->compressImage($file->getRealPath());
            if ($compressed !== null) {
                $fileName = $baseName . '.jpg';
                $storedPath = $directory . '/' . $fileName;
                if (!Storage::disk(self::DISK)->put($storedPath, $compressed)) {
                    throw ValidationException::withMessages(['certificate_file' => '添付ファイルを保存できませんでした。時間をおいて再度お試しください。']);
                }
            } else {
                $fallbackExtension = $mime === 'image/png' ? 'png' : 'jpg';
                $fileName = $baseName . '.' . $fallbackExtension;
                $storedPath = $file->storeAs($directory, $fileName, self::DISK);
            }
        }

        if (!$storedPath) {
            throw ValidationException::withMessages(['certificate_file' => '添付ファイルを保存できませんでした。時間をおいて再度お試しください。']);
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

    public function getContents(string $path): string
    {
        return (string) Storage::disk(self::DISK)->get($path);
    }

    public function mimeType(string $path): string
    {
        return (string) Storage::disk(self::DISK)->mimeType($path);
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
