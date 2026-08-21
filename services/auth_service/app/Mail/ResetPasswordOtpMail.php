<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    
    //Create a new message instance.
    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    // Get the message envelope.
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Reset OTP Code',
        );
    }
    // Get the message content definition.
    public function content(): Content
    {
        return new Content(
            view: 'emails.reset_otp', 
            with: [
                'otpCode' => $this->otp,
            ],
        );
    }
}
