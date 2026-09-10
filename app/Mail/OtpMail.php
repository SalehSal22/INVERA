<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public int $ttlMinutes;
    public string $appName;

    public function __construct(string $otp, int $ttlMinutes, ?string $appName = null)
    {
        $this->otp = $otp;
        $this->ttlMinutes = $ttlMinutes;
        $this->appName = $appName ?: (string) config('app.name', 'Thawani');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->appName . ' verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otp' => $this->otp,
                'ttlMinutes' => $this->ttlMinutes,
                'appName' => $this->appName,
            ],
        );
    }
}