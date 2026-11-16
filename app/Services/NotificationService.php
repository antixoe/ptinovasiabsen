<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkspaceNotification;

class NotificationService
{
    public static function send(?User $recipient, string $type, string $title, ?string $body = null, ?string $link = null): ?WorkspaceNotification
    {
        if (! $recipient instanceof User) {
            return null;
        }

        return $recipient->workspaceNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);
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
}
