<?php

namespace App\Observers;

use App\Models\Booking;
use App\Notifications\NewBooking;
use Illuminate\Support\Facades\Notification;

class BookingObserver
{

    public function created(Booking $booking)
    {
        // Notification::send($booking->company, new NewBooking($booking));

    }

    public function updated(Booking $booking)
    {
        //
    }


    public function deleted(Booking $booking)
    {
        //
    }


    public function restored(Booking $booking)
    {
        //
    }

    public function forceDeleted(Booking $booking)
    {
        //
    }
}
