<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiscordNotifier
{
    public static function send(User $recipient, WorkspaceNotification $notification): bool
    {
        $url = config('notifications.discord.webhook_url');

        if (! $url) {
            return false;
        }

        $description = trim(($notification->body ? $notification->body."\n\n" : '').'Untuk: '.$recipient->name);

        $response = Http::timeout(8)->post($url, [
            'username' => config('notifications.discord.username', 'Ruang Kerja'),
            'embeds' => [[
                'title' => Str::limit($notification->title, 250),
                'description' => Str::limit($description, 3500),
                'url' => $notification->link,
                'color' => self::color($notification->type),
                'footer' => [
                    'text' => config('app.name').' · '.$notification->created_at?->translatedFormat('d M Y H:i').' WIB',
                ],
            ]],
        ]);

        $response->throw();

        return true;
    }

    private static function color(string $type): int
    {
        return match ($type) {
            'report.reviewed' => 0x2F9E6B,
            'report.revision' => 0xC24A5E,
            'report.reminder' => 0xD39A2C,
            'report.submitted' => 0x5B6EE1,
            default => 0x6C5CE7,
        };
    }
}
