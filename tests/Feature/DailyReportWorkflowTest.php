<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\DailyReport;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $level = null, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role), 'permissions' => $role === 'supervisor' ? ['review-reports'] : []]);
        $levelModel = $level ? Level::firstOrCreate(['slug' => $level], ['name' => ucfirst($level)]) : null;

        return User::factory()->create($attributes + [
            'role_id' => $roleModel->id,
            'level_id' => $levelModel?->id,
            'is_active' => true,
        ]);
    }

    public function test_student_morning_summary_and_mentor_review_flow(): void
    {
        $mentor = $this->makeUser('supervisor', 'pembimbing');
        $student = $this->makeUser('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);

        $this->actingAs($student)
            ->post(route('reports.morning'), [
                'items' => [[
                    'title' => 'Riset alur review',
                    'description' => 'Memetakan status laporan',
                    'priority' => 'high',
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                ]],
            ])
            ->assertRedirect(route('reports.today'));

        $report = DailyReport::where('user_id', $student->id)->firstOrFail();
        $this->assertSame('morning', $report->status);
        $this->assertNotNull($report->morning_submitted_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $mentor->id,
            'type' => 'report.submitted',
        ]);

        $agenda = Agenda::where('daily_report_id', $report->id)->firstOrFail();

        $this->actingAs($student)
            ->post(route('reports.summary'), [
                'items' => [$agenda->id => ['done' => 1, 'summary' => 'Selesai dan dicek']],
                'summary_note' => 'Semua target tercapai.',
            ])
            ->assertRedirect(route('reports.today'));

        $report->refresh();
        $this->assertSame('summary', $report->status);
        $this->assertSame('completed', $agenda->fresh()->status);

        $this->actingAs($mentor)
            ->patch(route('reviews.update', $report), [
                'action' => 'approve',
                'review_note' => 'Hasil sudah sesuai.',
            ])
            ->assertRedirect(route('reviews.show', $report));

        $this->assertSame('reviewed', $report->fresh()->status);
        $this->assertSame('reviewed', $agenda->fresh()->status);
        $this->assertNotNull($report->fresh()->reviewed_at);
    }

    public function test_review_requires_a_submitted_summary(): void
    {
        $mentor = $this->makeUser('supervisor', 'pembimbing');
        $student = $this->makeUser('siswa-pkl', 'students', ['mentor_id' => $mentor->id]);

        $report = DailyReport::create([
            'user_id' => $student->id,
            'report_date' => today()->toDateString(),
            'status' => 'morning',
            'morning_submitted_at' => now(),
        ]);

        $this->actingAs($mentor)
            ->patch(route('reviews.update', $report), ['action' => 'approve'])
            ->assertSessionHasErrors('review_note');

        $this->assertSame('morning', $report->fresh()->status);
    }

    public function test_student_cannot_review_own_report(): void
    {
        $student = $this->makeUser('siswa-pkl', 'students');

        $report = DailyReport::create([
            'user_id' => $student->id,
            'report_date' => today()->toDateString(),
            'status' => 'summary',
            'morning_submitted_at' => now(),
            'summary_submitted_at' => now(),
        ]);

        $this->actingAs($student)->get(route('reviews.show', $report))->assertForbidden();
    }
}
