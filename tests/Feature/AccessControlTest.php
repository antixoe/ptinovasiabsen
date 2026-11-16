<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $level = null): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], [
            'name' => ucfirst($role),
            'permissions' => match ($role) {
                'supervisor' => ['review-reports'],
                'admin' => ['manage-users', 'manage-roles', 'view-audit', 'view-all-reports', 'view-all-agendas'],
                default => [],
            },
        ]);
        $levelModel = $level ? Level::firstOrCreate(['slug' => $level], ['name' => ucfirst($level)]) : null;

        return User::factory()->create([
            'role_id' => $roleModel->id,
            'level_id' => $levelModel?->id,
            'is_active' => true,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $user = $this->makeUser('karyawan');
        $user->update(['is_active' => false]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_student_is_blocked_from_admin_and_review_pages(): void
    {
        $student = $this->makeUser('siswa-pkl', 'students');

        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($student)->get(route('reviews.index'))->assertForbidden();
        $this->actingAs($student)->get(route('dashboard'))->assertOk();
        $this->actingAs($student)->get(route('reports.today'))->assertOk();
    }

    public function test_mentor_can_review_queues_but_not_manage_members(): void
    {
        $mentor = $this->makeUser('supervisor', 'pembimbing');

        $this->actingAs($mentor)->get(route('reviews.index'))->assertOk();
        $this->actingAs($mentor)->get(route('students.index'))->assertOk();
        $this->actingAs($mentor)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_manage_members_but_cannot_assign_superadmin(): void
    {
        $admin = $this->makeUser('admin');
        $superadminRole = Role::create(['name' => 'Superadmin', 'slug' => 'superadmin', 'permissions' => []]);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Calon Superadmin',
                'email' => 'calon@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $superadminRole->id,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('role_id');
    }
}
