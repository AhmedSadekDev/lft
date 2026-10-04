<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentPhoto;
use App\Models\Booking;
use App\Models\BookingPaper;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentPhotoController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'agent_id' => 'nullable|integer|exists:agents,id',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'status' => 'nullable|in:all,unassigned,assigned',
        ]);

        $status = $data['status'] ?? 'all';

        $relations = ['agent'];
        if (\Illuminate\Support\Facades\Schema::hasTable('bookings')) {
            $relations[] = 'booking.factory';
            $relations[] = 'booking.company';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('booking_containers')) {
            $relations[] = 'bookingContainer';
        }

        $photos = AgentPhoto::with($relations)
            ->when($status === 'unassigned', fn ($q) => $q->unassigned())
            ->when($status === 'assigned', fn ($q) => $q->assigned())
            ->when($data['agent_id'] ?? null, fn ($q, $id) => $q->where('agent_id', $id))
            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        $agents = Agent::orderBy('name')->get(['id', 'name']);

        $unassignedCount = AgentPhoto::unassigned()->count();
        $assignedCount = AgentPhoto::assigned()->count();
        $totalCount = AgentPhoto::count();

        $bookings = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('bookings')) {
            $bookingRelations = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('booking_containers')) {
                $bookingRelations[] = 'bookingContainers:id,booking_id,container_no,sail_of_number';
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('factories')) {
                $bookingRelations[] = 'factory:id,name';
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
                $bookingRelations[] = 'company:id,name';
            }

            $bookings = Booking::with($bookingRelations)
                ->latest('id')
                ->take(150)
                ->get();
        }

        return view('admin.agent-photos.index', compact(
            'photos',
            'agents',
            'status',
            'unassignedCount',
            'assignedCount',
            'totalCount',
            'bookings'
        ));
    }

    public function image(AgentPhoto $photo)
    {
        abort_unless(Storage::disk('agent_photos')->exists($photo->path), 404);

        return Storage::disk('agent_photos')->response($photo->path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(AgentPhoto $photo)
    {
        if (Storage::disk('agent_photos')->exists($photo->path)) {
            if (! Storage::disk('agent_photos')->delete($photo->path)) {
                throw new \RuntimeException('Unable to delete agent photo file.');
            }
        }

        $photo->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['status' => true, 'message' => 'تم حذف الصورة بنجاح.']);
        }

        return back()->with('success', 'تم حذف الصورة بنجاح.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'photo_ids' => 'required|array|min:1',
            'photo_ids.*' => 'required|integer|exists:agent_photos,id',
        ]);

        $photos = AgentPhoto::whereIn('id', $data['photo_ids'])->get();
        $deletedCount = 0;

        foreach ($photos as $photo) {
            if (Storage::disk('agent_photos')->exists($photo->path)) {
                Storage::disk('agent_photos')->delete($photo->path);
            }
            $photo->delete();
            $deletedCount++;
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => "تم حذف {$deletedCount} صورة بنجاح.",
                'deleted_count' => $deletedCount,
            ]);
        }

        return back()->with('success', "تم حذف {$deletedCount} صورة بنجاح.");
    }

    public function searchBookings(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        if (mb_strlen($query) < 1) {
            return response()->json([]);
        }

        $with = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('factories')) {
            $with[] = 'factory:id,name';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
            $with[] = 'company:id,name';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('booking_containers')) {
            $with[] = 'bookingContainers:id,booking_id,container_no,sail_of_number';
        }

        $bookings = Booking::query()
            ->with($with)
            ->where(function ($q) use ($query) {
                $q->where('id', $query)
                    ->orWhere('booking_number', 'like', "%{$query}%");

                if (\Illuminate\Support\Facades\Schema::hasTable('booking_containers')) {
                    $q->orWhereHas('bookingContainers', function ($cq) use ($query) {
                        $cq->where('container_no', 'like', "%{$query}%")
                            ->orWhere('sail_of_number', 'like', "%{$query}%");
                    });
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
                    $q->orWhereHas('company', function ($cq) use ($query) {
                        $cq->where('name', 'like', "%{$query}%");
                    });
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('factories')) {
                    $q->orWhereHas('factory', function ($fq) use ($query) {
                        $fq->where('name', 'like', "%{$query}%");
                    });
                }
            })
            ->latest('id')
            ->take(15)
            ->get();

        $results = $bookings->map(function ($booking) use ($query) {
            return [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number ?: ('#' . $booking->id),
                'client_name' => $booking->factory?->name ?: ($booking->company?->name ?: 'غير محدد'),
                'containers' => $booking->bookingContainers->map(fn ($c) => [
                    'id' => $c->id,
                    'container_no' => $c->container_no ?: ('حاوية #' . $c->id),
                    'sail_of_number' => $c->sail_of_number,
                    'is_match' => ! empty($query) && (
                        str_contains(strtolower((string) $c->container_no), strtolower($query)) ||
                        str_contains(strtolower((string) $c->sail_of_number), strtolower($query))
                    ),
                ])->values(),
            ];
        });

        return response()->json($results);
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'photo_ids' => 'required|array|min:1',
            'photo_ids.*' => 'required|integer|exists:agent_photos,id',
            'booking_id' => 'required|integer|exists:bookings,id',
            'booking_container_id' => 'nullable|integer|exists:booking_containers,id',
            'type' => 'required|integer|in:0,1,2,3,4,5,6,8,9',
        ]);

        $booking = Booking::findOrFail($data['booking_id']);
        $containerId = ! empty($data['booking_container_id']) ? (int) $data['booking_container_id'] : null;
        $type = (int) $data['type'];

        $photos = AgentPhoto::whereIn('id', $data['photo_ids'])->get();
        $assignedCount = 0;

        DB::transaction(function () use ($photos, $booking, $containerId, $type, &$assignedCount) {
            foreach ($photos as $photo) {
                if (! Storage::disk('agent_photos')->exists($photo->path)) {
                    continue;
                }

                $extension = pathinfo($photo->path, PATHINFO_EXTENSION) ?: 'jpg';
                $fileName = Str::random(40) . '.' . $extension;
                $relativePath = 'booking_papers/' . $fileName;

                $fileContents = Storage::disk('agent_photos')->get($photo->path);
                Storage::disk('public')->put($relativePath, $fileContents);

                $paper = BookingPaper::create([
                    'booking_id' => $booking->id,
                    'booking_container_id' => $containerId,
                    'agent_id' => $photo->agent_id,
                    'type' => $type,
                ]);

                Image::create([
                    'image' => $relativePath,
                    'imageable_id' => $paper->id,
                    'imageable_type' => BookingPaper::class,
                ]);

                $photo->update([
                    'booking_id' => $booking->id,
                    'booking_container_id' => $containerId,
                    'booking_paper_id' => $paper->id,
                    'assigned_at' => now(),
                ]);

                $assignedCount++;
            }
        });

        $bookingLabel = $booking->booking_number ?: ('#' . $booking->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => "تم تسكين {$assignedCount} صورة بنجاح في الحجز {$bookingLabel}.",
                'assigned_count' => $assignedCount,
            ]);
        }

        return back()->with('success', "تم تسكين {$assignedCount} صورة بنجاح في الحجز {$bookingLabel}.");
    }
}

