<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingOTPMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $bookingReference;
    public $expiresInMinutes;

    // Create a new message instance.
    public function __construct($otpCode, $bookingReference = null, $expiresInMinutes = 10)
    {
        $this->otpCode = $otpCode;
        $this->bookingReference = $bookingReference;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    // Get the message envelope.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking OTP Verification - SJP Booking System',
        );
    }

    // Get the message content definition.
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-otp',
        );
    }

    // Get the attachments for the message.
    public function attachments(): array
    {
        return [];
    }
}