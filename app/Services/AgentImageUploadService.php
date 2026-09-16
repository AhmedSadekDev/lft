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
            $directory = $destination === self::ADMIN_PUBLIC
                ? public_path('Admin/images/' . trim($folder, '/'))
                : storage_path('app/public/' . trim($folder, '/'));

            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new \RuntimeException('تعذر إنشاء مجلد حفظ الصور على السيرفر.');
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
        $file = $this->extractUploadedFile($request, $field);
        if ($file instanceof UploadedFile) {
            $result = $this->storeUploadedFile($file, $folder, $destination);
            $result['skipped'] = false;

            return $result;
        }

        $base64 = $this->extractBase64Image($request->input($field));
        if ($base64 !== null) {
            $result = $this->storeBinaryImage($base64, $folder, $destination);
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
            return 'فشل استقبال الطلب: الحجم كبير جداً أو انقطع الاتصال أثناء الرفع. جرّب صورة أصغر أو ارفع صورة واحدة فقط.';
        }

        return null;
    }

    private function extractUploadedFile(Request $request, string $field): ?UploadedFile
    {
        $file = $request->file($field);

        if ($file instanceof UploadedFile) {
            return $file;
        }

        if (is_array($file)) {
            foreach ($file as $item) {
                if ($item instanceof UploadedFile) {
                    return $item;
                }
            }
        }

        return null;
    }

    private function extractBase64Image(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $value) === 1) {
            $encoded = substr($value, strpos($value, ',') + 1);
            $binary = base64_decode($encoded, true);

            return $binary === false || $binary === '' ? null : $binary;
        }

        // Raw base64 payload (Flutter sometimes sends this without a data-URI prefix).
        if (strlen($value) < 256 || preg_match('/\s/', $value) || ! preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
            return null;
        }

        $binary = base64_decode($value, true);
        if ($binary === false || strlen($binary) < 24) {
            return null;
        }

        if (@getimagesizefromstring($binary) === false) {
            return null;
        }

        return $binary;
    }

    /**
     * @return array{ok: bool, path: ?string, filename: ?string, error: ?string}
     */
    private function storeBinaryImage(string $binary, string $folder, string $destination): array
    {
        $storedName = 'image_' . time() . '_' . Str::random(6) . '.jpg';
        $relativePath = trim($folder, '/') . '/' . $storedName;

        try {
            $directory = $destination === self::ADMIN_PUBLIC
                ? public_path('Admin/images/' . trim($folder, '/'))
                : storage_path('app/public/' . trim($folder, '/'));

            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new \RuntimeException('تعذر إنشاء مجلد حفظ الصور على السيرفر.');
            }

            $fullPath = $directory . DIRECTORY_SEPARATOR . $storedName;
            $tmp = tempnam(sys_get_temp_dir(), 'agent_img_');
            if ($tmp === false || file_put_contents($tmp, $binary) === false) {
                throw new \RuntimeException('تعذر تجهيز الصورة للرفع.');
            }

            try {
                $uploaded = new UploadedFile($tmp, $storedName, 'image/jpeg', null, true);
                $this->writeCompressedImage($uploaded, $fullPath);
            } finally {
                @unlink($tmp);
            }

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

    private function clientAttemptedImageUpload(Request $request, string $field): bool
    {
        if ($this->extractUploadedFile($request, $field) instanceof UploadedFile) {
            return true;
        }

        if (! $request->exists($field) && ! $request->has($field)) {
            return false;
        }

        $value = $request->input($field);

        if ($value === null || $value === '' || $value === []) {
            return false;
        }

        return true;
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

        return "فشل رفع {$label}: أرسل الملف كـ multipart/form-data أو base64 لصورة صالحة، وتأكد أن الحجم ضمن الحد المسموح.";
    }

    private function uploadErrorMessage(UploadedFile $file): string
    {
        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم الصورة أكبر من الحد المسموح على السيرفر.',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الصورة فقط بسبب انقطاع الاتصال. أعد المحاولة بصورة أصغر.',
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
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === 'pdf') {
            $file->move(dirname($fullPath), basename($fullPath));

            return;
        }

        if (! function_exists('imagecreatefromstring')) {
            $file->move(dirname($fullPath), basename($fullPath));

            return;
        }

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw new \RuntimeException('تعذر قراءة ملف الصورة.');
        }

        $contents = @file_get_contents($realPath);
        if ($contents === false) {
            throw new \RuntimeException('تعذر قراءة ملف الصورة.');
        }

        $image = @imagecreatefromstring($contents);
        unset($contents);

        if ($image === false) {
            // Keep original bytes for formats GD cannot decode (e.g. HEIC).
            if (! @copy($realPath, $fullPath) && ! $file->move(dirname($fullPath), basename($fullPath))) {
                throw new \RuntimeException('تعذر حفظ ملف الصورة.');
            }

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
