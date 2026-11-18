<?php

namespace Tests\Feature;

use App\Mail\WorkspaceNotificationMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExternalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_important_notifications_are_sent_to_email_and_discord(): void
    {
        config([
            'notifications.email.enabled' => true,
            'notifications.discord.webhook_url' => 'https://discord.test/webhook',
        ]);

        Mail::fake();
        Http::fake();

        $user = User::factory()->create(['email' => 'mentor@example.test']);

        $notification = NotificationService::send(
            $user,
            'report.submitted',
            'Ringkasan kerja baru',
            'Salsabila mengirim ringkasan untuk hari ini.',
            'http://localhost/review/1'
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'report.submitted',
        ]);

        Mail::assertSent(WorkspaceNotificationMail::class, function (WorkspaceNotificationMail $mail) use ($user, $notification) {
            return $mail->hasTo($user->email)
                && $mail->notification->is($notification)
                && $mail->recipient->is($user);
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://discord.test/webhook'
                && $request['embeds'][0]['title'] === 'Ringkasan kerja baru'
                && $request['embeds'][0]['url'] === 'http://localhost/review/1';
        });
    }

    public function test_email_can_be_routed_to_one_monitored_inbox(): void
    {
        config([
            'notifications.email.enabled' => true,
            'notifications.email.to' => 'inbox@example.test',
            'notifications.discord.webhook_url' => null,
        ]);

        Mail::fake();

        NotificationService::send(
            User::factory()->create(['email' => 'fake@ptinovasiabsen.test']),
            'report.reminder',
            'Pengingat'
        );

        Mail::assertSent(WorkspaceNotificationMail::class, fn (WorkspaceNotificationMail $mail) => $mail->hasTo('inbox@example.test'));
    }

    public function test_non_important_notifications_stay_in_app_only(): void
    {
        config([
            'notifications.email.enabled' => true,
            'notifications.discord.webhook_url' => 'https://discord.test/webhook',
        ]);

        Mail::fake();
        Http::fake();

        NotificationService::send(User::factory()->create(), 'other.event', 'Catatan kecil');

        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_external_channel_failures_do_not_block_in_app_notifications(): void
    {
        config([
            'notifications.email.enabled' => false,
            'notifications.discord.webhook_url' => 'https://discord.test/webhook',
        ]);

        Http::fake(['*' => Http::response('server error', 500)]);

        $user = User::factory()->create();

        NotificationService::send($user, 'report.revision', 'Perlu revisi');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Perlu revisi',
        ]);
    }
}
