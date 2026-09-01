<?php

namespace App\Traits;

use App\Models\Image;
use App\Services\AgentImageUploadService;
use Illuminate\Http\Request;

trait HandlesAgentImageUploads
{
    protected function imageUploadService(): AgentImageUploadService
    {
        return app(AgentImageUploadService::class);
    }

    protected function rejectIfPayloadTooLarge(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $message = $this->imageUploadService()->requestPayloadTooLarge($request);
        if ($message) {
            return $this->returnError(413, $message);
        }

        return null;
    }

    /**
     * @return array{ok: bool, path: ?string, filename: ?string, error: ?string, skipped: bool}|\Illuminate\Http\JsonResponse
     */
    protected function resolveAgentImageUpload(
        Request $request,
        string $field,
        string $folder,
        string $destination = AgentImageUploadService::STORAGE_DISK,
        bool $required = false
    ) {
        $result = $this->imageUploadService()->resolveRequestImage(
            $request,
            $field,
            $folder,
            $destination,
            $required
        );

        if (! $result['ok']) {
            return $this->returnError(422, $result['error']);
        }

        return $result;
    }

    protected function attachStoredImage(string $path, int $imageableId, string $imageableType): Image
    {
        return Image::create([
            'image' => $path,
            'imageable_id' => $imageableId,
            'imageable_type' => $imageableType,
        ]);
    }

    /**
     * @return Image|\Illuminate\Http\JsonResponse|null null when field was not sent
     */
    protected function storeMorphImageFromRequest(
        Request $request,
        string $field,
        string $folder,
        int $imageableId,
        string $imageableType,
        bool $required = false
    ) {
        $result = $this->resolveAgentImageUpload($request, $field, $folder, AgentImageUploadService::STORAGE_DISK, $required);
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        if ($result['skipped'] || empty($result['path'])) {
            return null;
        }

        return $this->attachStoredImage($result['path'], $imageableId, $imageableType);
    }

    protected function storeAdminExpenseImage(Request $request, string $field = 'image', bool $required = false)
    {
        $result = $this->resolveAgentImageUpload(
            $request,
            $field,
            'expenses',
            AgentImageUploadService::ADMIN_PUBLIC,
            $required
        );

        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        if ($result['skipped']) {
            return null;
        }

        return $result['filename'];
    }
}
