<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;

    /**
     * Create a new message instance.
     */
    public function __construct($otpCode)
    {
        $this->otpCode = $otpCode;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رمز إعادة تعيين كلمة المرور - Gym App',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password-otp', // مسار ملف الـ Blade الخاص بتصميم رسالة الإيميل
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachment(): array
    {
       // إرفاق الشعار مع تسمية برمجية لاستدعائه في الـ Blade
        return [
            Attachment::fromPath(public_path('images/logo.png'))
                ->as('logo.png')
                ->withMime('image/png'),
        ];
    }
}