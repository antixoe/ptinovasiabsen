<?php

namespace App\Mail;

use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkspaceNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public WorkspaceNotification $notification,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->title.' · '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.workspace-notification',
            with: [
                'recipientName' => $this->recipient->name,
                'title' => $this->notification->title,
                'body' => $this->notification->body,
                'link' => $this->notification->link,
                'sentAt' => $this->notification->created_at,
            ],
        );
    }
}
