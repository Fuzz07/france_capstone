<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryRepliedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $inquiry;

    public function __construct(Inquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reply to your inquiry: " . $this->inquiry->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiry_replied',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
