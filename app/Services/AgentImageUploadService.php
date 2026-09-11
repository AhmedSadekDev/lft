<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AgentImageUploadService
{
    public const STORAGE_DISK = 'storage';

    public const ADMIN_PUBLIC = 'admin_public';

    /**
     * @return array{ok: bool, path: ?string, filename: ?string, error: ?string}
     */
    public function storeUploadedFile(UploadedFile $file, string $folder, string $destination = self::STORAGE_DISK): array
    {
        if (! $file->isValid()) {
            return [
                'ok' => false,
                'path' => null,
                'filename' => null,
                'error' => $this->uploadErrorMessage($file),
            ];
        }

        $storedName = $this->buildFileName($file);
        if (strtolower((string) $file->getClientOriginalExtension()) === 'pdf') {
            $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'document';
            $storedName = Str::snake($base . '_' . time() . '_' . Str::random(6) . '.pdf');
        }
        $relativePath = trim($folder, '/') . '/' . $storedName;

        try {
            if ($destination === self::ADMIN_PUBLIC) {
                $directory = public_path('Admin/images/' . trim($folder, '/'));
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                $fullPath = $directory . DIRECTORY_SEPARATOR . $storedName;
                $this->writeCompressedImage($file, $fullPath);

                return [
                    'ok' => true,
                    'path' => $relativePath,
                    'filename' => $storedName,
                    'error' => null,
                ];
            }

            $directory = storage_path('app/public/' . trim($folder, '/'));
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            $fullPath = $directory . DIRECTORY_SEPARATOR . $storedName;
            $this->writeCompressedImage($file, $fullPath);

            return [
                'ok' => true,
                'path' => $relativePath,
                'filename' => $storedName,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'path' => null,
                'filename' => null,
                'error' => 'فشل حفظ الصورة على السيرفر: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{ok: bool, path: ?string, filename: ?string, error: ?string, skipped: bool}
     */
    public function resolveRequestImage(
        Request $request,
        string $field,
        string $folder,
        string $destination = self::STORAGE_DISK,
        bool $required = false
    ): array {
        if ($request->hasFile($field)) {
            $result = $this->storeUploadedFile($request->file($field), $folder, $destination);
            $result['skipped'] = false;

            return $result;
        }

        if ($this->clientAttemptedImageUpload($request, $field)) {
            return [
                'ok' => false,
                'path' => null,
                'filename' => null,
                'error' => $this->missingUploadMessage($field),
                'skipped' => false,
            ];
        }

        if ($required) {
            return [
                'ok' => false,
                'path' => null,
                'filename' => null,
                'error' => "الصورة ({$field}) مطلوبة",
                'skipped' => false,
            ];
        }

        return [
            'ok' => true,
            'path' => null,
            'filename' => null,
            'error' => null,
            'skipped' => true,
        ];
    }

    public function requestPayloadTooLarge(Request $request): ?string
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        if ($contentLength <= 0) {
            return null;
        }

        $postMax = $this->iniBytes('post_max_size');
        if ($postMax > 0 && $contentLength > $postMax) {
            return 'حجم الطلب أكبر من الحد المسموح على السيرفر (post_max_size). قلّل حجم الصور أو ارفع صورة واحدة في كل مرة.';
        }

        if ($request->isMethod('POST') && empty($request->all()) && empty($_FILES) && $contentLength > 0) {
            return 'فشل استقبال الطلب: الحجم كبير جداً أو انقطع الاتصال أثناء الرفع.';
        }

        return null;
    }

    private function clientAttemptedImageUpload(Request $request, string $field): bool
    {
        if ($request->hasFile($field)) {
            return false;
        }

        return $request->exists($field) || $request->has($field);
    }

    private function missingUploadMessage(string $field): string
    {
        $labels = [
            'image' => 'الصورة',
            'images' => 'الصور',
            'specification_latter' => 'خطاب التخصيص',
            'loading_answer' => 'إجابة التحميل',
            'unloading_image' => 'صورة التعتيق',
            'unloading_image_sail' => 'صورة الإبحار',
        ];

        $label = $labels[$field] ?? $field;

        return "فشل رفع {$label}: الحجم كبير جداً، الملف غير صالح، أو انقطع الاتصال أثناء الرفع.";
    }

    private function uploadErrorMessage(UploadedFile $file): string
    {
        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم الصورة أكبر من الحد المسموح على السيرفر.',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الصورة فقط بسبب انقطاع الاتصال.',
            UPLOAD_ERR_NO_FILE => 'لم يتم إرسال ملف صورة.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'خطأ في إعدادات السيرفر أثناء رفع الصورة.',
            default => 'فشل رفع الصورة: ' . $file->getErrorMessage(),
        };
    }

    private function buildFileName(UploadedFile $file): string
    {
        $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'image';

        return Str::snake($base . '_' . time() . '_' . Str::random(6) . '.jpg');
    }

    private function writeCompressedImage(UploadedFile $file, string $fullPath, int $maxWidth = 1280, int $quality = 75): void
    {
        if (strtolower((string) $file->getClientOriginalExtension()) === 'pdf') {
            $file->move(dirname($fullPath), basename($fullPath));

            return;
        }

        if (! function_exists('imagecreatefromstring')) {
            $file->move(dirname($fullPath), basename($fullPath));

            return;
        }

        $contents = @file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('تعذر قراءة ملف الصورة.');
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            $file->move(dirname($fullPath), basename($fullPath));

            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newHeight = max(1, (int) round($height * ($maxWidth / $width)));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $maxWidth;
            $height = $newHeight;
        }

        if (! imagejpeg($image, $fullPath, $quality)) {
            imagedestroy($image);
            throw new \RuntimeException('تعذر ضغط وحفظ الصورة.');
        }

        imagedestroy($image);
    }

    private function iniBytes(string $key): int
    {
        $value = ini_get($key);
        if ($value === false || $value === '') {
            return 0;
        }

        $value = trim((string) $value);
        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
