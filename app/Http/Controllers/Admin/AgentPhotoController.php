<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentPhotoController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'agent_id' => 'nullable|integer|exists:agents,id',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
        ]);
        $photos = AgentPhoto::with('agent')
            ->when($data['agent_id'] ?? null, fn ($q, $id) => $q->where('agent_id', $id))
            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest('id')->paginate(24)->withQueryString();
        $agents = Agent::orderBy('name')->get(['id', 'name']);

        return view('admin.agent-photos.index', compact('photos', 'agents'));
    }

    public function image(AgentPhoto $photo)
    {
        abort_unless(Storage::disk('agent_photos')->exists($photo->path), 404);

        return Storage::disk('agent_photos')->response($photo->path, null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
