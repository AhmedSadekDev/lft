<?php

namespace App\Observers;

use App\Models\BookingContainer;
use App\Models\BookingContainerAgent;
use App\Models\DailyBookingContainer;
use App\Notifications\BookingContainerStatus;
use App\Notifications\NewBooking;
use Illuminate\Support\Facades\Notification;

class BookingContainerObserver
{
    /**
     * Handle the BookingContainer "created" event.
     *
     * @param  \App\Models\BookingContainer  $bookingContainer
     * @return void
     */
    public function created(BookingContainer $bookingContainer)
    {
        //
    }

    /**
     * Handle the BookingContainer "updated" event.
     *
     * @param  \App\Models\BookingContainer  $bookingContainer
     * @return void
     */
    public function updated(BookingContainer $bookingContainer)
    {
        if ($bookingContainer->isDirty('status')) {
            $newStatus = $bookingContainer->getOriginal('status');
            // Notification::send($bookingContainer->booking?->company, new BookingContainerStatus($bookingContainer));
        }
    }

    /**
     * Handle the BookingContainer "deleted" event.
     *
     * @param  \App\Models\BookingContainer  $bookingContainer
     * @return void
     */
    public function deleted(BookingContainer $bookingContainer)
    {
        BookingContainerAgent::where('booking_container_id', $bookingContainer->id)->delete();
        DailyBookingContainer::where('booking_container_id', $bookingContainer->id)->delete();
    }

    /**
     * Handle the BookingContainer "restored" event.
     *
     * @param  \App\Models\BookingContainer  $bookingContainer
     * @return void
     */
    public function restored(BookingContainer $bookingContainer)
    {
        //
    }

    /**
     * Handle the BookingContainer "force deleted" event.
     *
     * @param  \App\Models\BookingContainer  $bookingContainer
     * @return void
     */
    public function forceDeleted(BookingContainer $bookingContainer)
    {
        //
    }
}
