<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Services\StudentCompliance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $level, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], [
            'name' => ucfirst($role),
            'permissions' => $role === 'supervisor' ? ['review-reports'] : ['view-all-reports', 'review-reports'],
        ]);
        $levelModel = $level ? Level::firstOrCreate(['slug' => $level], ['name' => ucfirst($level)]) : null;

        return User::factory()->create($attributes + [
            'role_id' => $roleModel->id,
            'level_id' => $levelModel?->id,
            'is_active' => true,
        ]);
    }

    private function report(User $student, array $attributes): DailyReport
    {
        return DailyReport::create($attributes + [
            'user_id' => $student->id,
            'report_date' => today()->toDateString(),
        ]);
    }

    public function test_snapshot_flags_missing_agenda_and_missing_summary(): void
    {
        $mentor = $this->user('supervisor', 'pembimbing');

        $missingAgenda = $this->user('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);
        $missingSummary = $this->user('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);
        $reviewed = $this->user('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);

        $this->report($missingSummary, [
            'status' => 'morning',
            'morning_submitted_at' => now(),
        ]);

        $this->report($reviewed, [
            'status' => 'reviewed',
            'morning_submitted_at' => now(),
            'summary_submitted_at' => now(),
            'reviewed_at' => now(),
        ]);

        $rows = StudentCompliance::snapshot($mentor);

        $this->assertSame(StudentCompliance::MISSING_MORNING, $rows->firstWhere('student.id', $missingAgenda->id)['state']);
        $this->assertSame(StudentCompliance::MISSING_SUMMARY, $rows->firstWhere('student.id', $missingSummary->id)['state']);
        $this->assertSame(StudentCompliance::REVIEWED, $rows->firstWhere('student.id', $reviewed->id)['state']);
        $this->assertSame(2, StudentCompliance::attentionCount($mentor));
        $this->assertSame($missingAgenda->id, $rows->first()['student']->id);
    }

    public function test_manager_sees_all_students_while_supervisor_sees_only_mentees(): void
    {
        $manager = $this->user('manager', null);
        $supervisor = $this->user('supervisor', 'pembimbing');

        $this->user('siswa-pkl', 'students', ['mentor_id' => $supervisor->id]);
        $this->user('siswa-pkl', 'students');

        $this->assertCount(2, StudentCompliance::snapshot($manager));
        $this->assertCount(1, StudentCompliance::snapshot($supervisor));
    }

    public function test_mentor_is_reminded_about_students_without_any_report(): void
    {
        $mentor = $this->user('supervisor', 'pembimbing');
        $student = $this->user('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);

        $this->artisan('agenda:send-reminders', ['--date' => today()->toDateString()])->assertSuccessful();
        $this->artisan('agenda:send-reminders', ['--date' => today()->toDateString()])->assertSuccessful();

        $this->assertSame(1, $mentor->workspaceNotifications()->where('type', 'report.reminder')->count());
        $this->assertStringContainsString('belum mengisi agenda pagi', $mentor->workspaceNotifications()->first()->body);
    }

    public function test_student_list_marks_students_who_have_not_submitted(): void
    {
        $mentor = $this->user('supervisor', 'pembimbing');
        $student = $this->user('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);

        $this->actingAs($mentor)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Belum isi agenda')
            ->assertSee($student->name);
    }

    public function test_mentor_dashboard_shows_attention_panel(): void
    {
        $mentor = $this->user('supervisor', 'pembimbing');
        $student = $this->user('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);

        $this->actingAs($mentor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Perlu perhatian')
            ->assertSee($student->name)
            ->assertSee('Belum isi agenda');
    }
}
