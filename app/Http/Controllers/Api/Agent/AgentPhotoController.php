<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentPhoto;
use App\Traits\HandlesAgentImageUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AgentPhotoController extends Controller
{
    use HandlesAgentImageUploads;

    private function photoData(AgentPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'image' => route('api.agent.photos.image', $photo),
            'original_name' => $photo->original_name,
            'created_at' => $photo->created_at->toIso8601String(),
        ];
    }

    public function index(Request $request)
    {
        $data = $request->validate(['per_page' => 'sometimes|integer|min:1|max:100']);
        $photos = AgentPhoto::where('agent_id', auth('agent')->id())
            ->latest('id')->paginate($data['per_page'] ?? 24);
        $photos->getCollection()->transform(fn ($photo) => $this->photoData($photo));

        return $this->returnAllData($photos, __('alerts.success'));
    }

    public function store(Request $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }
        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'booking_id' => 'prohibited',
            'booking_container_id' => 'prohibited',
            'type_id' => 'prohibited',
        ]);
        $paths = [];
        try {
            $photos = DB::transaction(function () use ($request, &$paths) {
                $photos = [];
                foreach ($request->file('images') as $file) {
                    $path = $file->store((string) auth('agent')->id(), 'agent_photos');
                    $paths[] = $path;
                    $photo = AgentPhoto::create([
                        'agent_id' => auth('agent')->id(),
                        'path' => $path,
                        'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    ]);
                    $photos[] = $this->photoData($photo);
                }

                return $photos;
            });
        } catch (\Throwable $e) {
            Storage::disk('agent_photos')->delete($paths);
            report($e);

            return $this->returnError(500, 'تعذر حفظ الصور، يرجى المحاولة مرة أخرى.');
        }

        return $this->returnAllData($photos, __('alerts.success'));
    }

    public function image(int $photo)
    {
        $photo = AgentPhoto::where('agent_id', auth('agent')->id())->find($photo);
        if (! $photo || ! Storage::disk('agent_photos')->exists($photo->path)) {
            return $this->returnError(404, 'الصورة غير موجودة.');
        }

        return Storage::disk('agent_photos')->response($photo->path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
