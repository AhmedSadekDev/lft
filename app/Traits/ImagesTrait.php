<?php
namespace App\Traits;

use App\Services\AgentImageUploadService;

trait ImagesTrait{

    private function uploadImage($file, $fileName, $path, $oldFile = null): string
    {
        $service = app(AgentImageUploadService::class);
        $result = $service->storeUploadedFile($file, $path, AgentImageUploadService::ADMIN_PUBLIC);

        if (! $result['ok']) {
            throw new \RuntimeException($result['error'] ?? 'فشل رفع الصورة');
        }

        if (! is_null($oldFile) && file_exists(public_path($oldFile))) {
            @unlink(public_path($oldFile));
        }

        return (string) $result['filename'];
    }
}
