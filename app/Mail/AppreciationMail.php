<?php

namespace App\Mail;

use App\Models\Appreciation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppreciationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Appreciation $appreciation) {}

    public function envelope(): Envelope
    {
        $senderName = $this->appreciation->sender?->full_name ?? 'A Colleague';
        return new Envelope(
            subject: "You received an appreciation from {$senderName}!",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appreciation',
            with: [
                'appreciation' => $this->appreciation,
                'sender'       => $this->appreciation->sender,
                'receiver'     => $this->appreciation->receiver,
                'platformName' => \App\Models\Setting::getValue('platform_name_en', 'Appreciation Platform'),
            ],
        );
    }
}
