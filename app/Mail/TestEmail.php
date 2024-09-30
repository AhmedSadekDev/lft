<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // \Illuminate\Support\Facades\Mail::to(['some@body.com'])->send(new \App\Mail\TestEmail);
        // \App\Models\User::find(user_id)->notify(new \App\Mail\TestEmail);
        return $this
            ->markdown('email.test_email')
            ->subject('Testing Application Email')
            ->from(
                config('mail.from')['address'],
                config('mail.from')['name'],
            );
    }
}
