<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $attributes = []): User
    {
        $role = Role::firstOrCreate(['slug' => 'siswa-pkl'], ['name' => 'Siswa PKL', 'permissions' => []]);
        $level = Level::firstOrCreate(['slug' => 'students'], ['name' => 'Students']);

        return User::factory()->create($attributes + [
            'role_id' => $role->id,
            'level_id' => $level->id,
            'is_active' => true,
        ]);
    }

    public function test_it_reminds_students_without_a_summary_only_once(): void
    {
        $student = $this->student();

        $this->artisan('agenda:send-reminders', ['--date' => today()->toDateString()])->assertSuccessful();
        $this->artisan('agenda:send-reminders', ['--date' => today()->toDateString()])->assertSuccessful();

        $this->assertSame(1, $student->workspaceNotifications()->where('type', 'report.reminder')->count());
    }

    public function test_it_skips_students_who_already_submitted(): void
    {
        $student = $this->student();

        DailyReport::create([
            'user_id' => $student->id,
            'report_date' => today()->toDateString(),
            'status' => 'summary',
            'morning_submitted_at' => now(),
            'summary_submitted_at' => now(),
        ]);

        $this->artisan('agenda:send-reminders', ['--date' => today()->toDateString()])->assertSuccessful();

        $this->assertSame(0, $student->workspaceNotifications()->where('type', 'report.reminder')->count());
    }
}
