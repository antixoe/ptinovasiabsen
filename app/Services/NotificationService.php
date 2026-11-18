<?php

namespace App\Services;

use App\Mail\WorkspaceNotificationMail;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    public static function send(?User $recipient, string $type, string $title, ?string $body = null, ?string $link = null): ?WorkspaceNotification
    {
        if (! $recipient instanceof User) {
            return null;
        }

        $notification = $recipient->workspaceNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);

        if (in_array($type, config('notifications.important_types', []), true)) {
            self::deliverExternally($recipient, $notification);
        }

        return $notification;
    }

    /**
     * @param  iterable<User>  $recipients
     */
    public static function sendMany(iterable $recipients, string $type, string $title, ?string $body = null, ?string $link = null): int
    {
        $count = 0;

        foreach ($recipients as $recipient) {
            self::send($recipient, $type, $title, $body, $link);
            $count++;
        }

        return $count;
    }

    /**
     * Mirror an important notification to email and Discord when configured.
     * Delivery problems must never break the user action that triggered them.
     */
    private static function deliverExternally(User $recipient, WorkspaceNotification $notification): void
    {
        $email = config('notifications.email.to') ?: $recipient->email;

        if (config('notifications.email.enabled') && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($email)->send(new WorkspaceNotificationMail($recipient, $notification));
            } catch (Throwable $exception) {
                Log::warning('Email notification delivery failed.', [
                    'user_id' => $recipient->id,
                    'notification_id' => $notification->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (config('notifications.discord.webhook_url')) {
            try {
                DiscordNotifier::send($recipient, $notification);
            } catch (Throwable $exception) {
                Log::warning('Discord notification delivery failed.', [
                    'user_id' => $recipient->id,
                    'notification_id' => $notification->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
