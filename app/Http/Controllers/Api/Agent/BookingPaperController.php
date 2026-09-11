<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\CarPaperRequest;
use App\Http\Requests\Api\Agent\LoadingBookingRequest;
use App\Http\Requests\Api\Agent\SpecificationBookingYardRequest;
use App\Http\Requests\Api\Agent\unloadingBookingSailRequest;
use App\Http\Resources\Api\Agent\BookingContainerResource;
use App\Models\Booking;
use App\Models\BookingContainer;
use App\Models\BookingPaper;
use App\Traits\HandlesAgentImageUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingPaperController extends Controller
{
    use HandlesAgentImageUploads;

    private const IMAGE_FOLDER = 'booking_papers';

    public function save_specification_booking_yard(SpecificationBookingYardRequest $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {
            return DB::transaction(function () use ($request) {
                $booking = Booking::whereId($request->booking_id)->firstOrFail();

                $booking->update([
                    'yard_id' => $request->yard_id,
                ]);

                foreach ($booking->bookingContainers as $container) {
                    $container->update([
                        'yard_id' => $request->yard_id,
                    ]);
                }

                if ($request->hasFile('image') || $request->has('image')) {
                    BookingPaper::where(['booking_id' => $request->booking_id, 'type' => 0])->delete();

                    $paper = BookingPaper::create([
                        'booking_id' => $booking->id,
                        'type' => 0,
                        'booking_container_id' => $request->booking_container_id,
                    ]);

                    $stored = $this->storeMorphImageFromRequest(
                        $request,
                        'image',
                        self::IMAGE_FOLDER,
                        $paper->id,
                        BookingPaper::class,
                        true
                    );

                    if ($stored instanceof \Illuminate\Http\JsonResponse) {
                        throw new \RuntimeException(json_decode($stored->getContent(), true)['message'] ?? 'فشل رفع الصورة');
                    }
                }

                return $this->returnSuccessMessage(__('alerts.success'));
            });
        } catch (\RuntimeException $e) {
            return $this->returnError(422, $e->getMessage());
        } catch (\Exception $e) {
            return $this->returnError(422, 'فشل حفظ بيانات التخصيص: ' . $e->getMessage());
        }
    }

    public function save_loading_booking_container(LoadingBookingRequest $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {
            return DB::transaction(function () use ($request) {
                $booking_container = BookingContainer::whereId($request->booking_container_id)->firstOrFail();

                $booking_container->update([
                    'container_no' => $request->container_number,
                ]);

                $this->savePaperImage($request, 'image', 1, $booking_container);
                $this->savePaperImage($request, 'specification_latter', 0, $booking_container);
                $this->savePaperImage($request, 'loading_answer', 6, $booking_container);

                return $this->returnAllData(
                    new BookingContainerResource($booking_container->fresh(['bookingPapers.image'])),
                    __('alerts.success')
                );
            });
        } catch (\RuntimeException $e) {
            return $this->returnError(422, $e->getMessage());
        } catch (\Exception $e) {
            return $this->returnError(422, 'فشل حفظ بيانات التحميل: ' . $e->getMessage());
        }
    }

    public function save_unloading_booking_sail(unloadingBookingSailRequest $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {
            return DB::transaction(function () use ($request) {
                $booking_container = BookingContainer::whereId($request->booking_container_id)->firstOrFail();

                $booking_container->update([
                    'sail_of_number' => $request->sail_of_number,
                ]);

                $this->savePaperImage($request, 'image', 2, $booking_container);
                $this->savePaperImage($request, 'unloading_image', 4, $booking_container);
                $this->savePaperImage($request, 'unloading_image_sail', 5, $booking_container);

                return $this->returnAllData(
                    new BookingContainerResource($booking_container->fresh(['bookingPapers.image'])),
                    __('alerts.success')
                );
            });
        } catch (\RuntimeException $e) {
            return $this->returnError(422, $e->getMessage());
        } catch (\Exception $e) {
            return $this->returnError(422, 'فشل حفظ بيانات التعتيق: ' . $e->getMessage());
        }
    }

    public function send_car_papers(CarPaperRequest $request)
    {
        if ($response = $this->rejectIfPayloadTooLarge($request)) {
            return $response;
        }

        try {
            return DB::transaction(function () use ($request) {
                $booking_container = BookingContainer::whereId($request->booking_container_id)->firstOrFail();

                if ($request->hasFile('images') || $request->has('images')) {
                    if (! $request->hasFile('images')) {
                        throw new \RuntimeException('فشل رفع صور السيارة: الحجم كبير جداً أو انقطع الاتصال أثناء الرفع.');
                    }

                    $files = $request->file('images');
                    if (! is_array($files) || count($files) === 0) {
                        throw new \RuntimeException('لم يتم إرسال صور السيارة بشكل صحيح.');
                    }

                    $paper = BookingPaper::create([
                        'booking_container_id' => $booking_container->id,
                        'booking_id' => $booking_container->booking_id,
                        'type' => 3,
                    ]);

                    foreach ($files as $index => $file) {
                        $upload = $this->imageUploadService()->storeUploadedFile($file, self::IMAGE_FOLDER);
                        if (! $upload['ok']) {
                            throw new \RuntimeException($upload['error'] ?? "فشل رفع صورة السيارة رقم " . ($index + 1));
                        }

                        $this->attachStoredImage($upload['path'], $paper->id, BookingPaper::class);
                    }
                }

                return $this->returnAllData(
                    new BookingContainerResource($booking_container->fresh(['bookingPapers.image'])),
                    __('alerts.success')
                );
            });
        } catch (\RuntimeException $e) {
            return $this->returnError(422, $e->getMessage());
        } catch (\Exception $e) {
            return $this->returnError(422, 'فشل حفظ أوراق السيارة: ' . $e->getMessage());
        }
    }

    private function savePaperImage(Request $request, string $field, int $type, BookingContainer $booking_container): void
    {
        if (! $request->hasFile($field) && ! $request->has($field)) {
            return;
        }

        BookingPaper::where([
            'booking_id' => $booking_container->booking_id,
            'type' => $type,
        ])->delete();

        $paper = BookingPaper::create([
            'booking_container_id' => $booking_container->id,
            'booking_id' => $booking_container->booking_id,
            'type' => $type,
        ]);

        $stored = $this->storeMorphImageFromRequest(
            $request,
            $field,
            self::IMAGE_FOLDER,
            $paper->id,
            BookingPaper::class,
            true
        );

        if ($stored instanceof \Illuminate\Http\JsonResponse) {
            $payload = json_decode($stored->getContent(), true);
            throw new \RuntimeException($payload['message'] ?? 'فشل رفع الصورة');
        }

        if ($stored === null) {
            throw new \RuntimeException($this->imageUploadService()->resolveRequestImage($request, $field, self::IMAGE_FOLDER)['error']
                ?? "فشل رفع الصورة ({$field})");
        }
    }
}
