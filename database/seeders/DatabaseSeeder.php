<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\AuditLog;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            ['name' => 'Superadmin', 'slug' => 'superadmin'],
            ['name' => 'Direktur', 'slug' => 'direktur'],
            ['name' => 'Manager', 'slug' => 'manager'],
            ['name' => 'Supervisor', 'slug' => 'supervisor'],
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Karyawan', 'slug' => 'karyawan'],
            ['name' => 'Siswa PKL', 'slug' => 'siswa-pkl'],
        ])->mapWithKeys(fn (array $role) => [$role['slug'] => Role::updateOrCreate(['slug' => $role['slug']], $role)]);

        $levels = collect([
            ['name' => 'Students', 'slug' => 'students'],
            ['name' => 'Pembimbing', 'slug' => 'pembimbing'],
        ])->mapWithKeys(fn (array $level) => [$level['slug'] => Level::updateOrCreate(['slug' => $level['slug']], $level)]);

        $people = [
            ['name' => 'Andi Ramadhan', 'email' => 'andi@ptinovasiabsen.test', 'role' => 'superadmin', 'level' => null, 'department' => 'Platform'],
            ['name' => 'Fikri Ananta', 'email' => 'fikri@ptinovasiabsen.test', 'role' => 'supervisor', 'level' => 'pembimbing', 'department' => 'Design'],
            ['name' => 'Nadia Rahma', 'email' => 'nadia@ptinovasiabsen.test', 'role' => 'supervisor', 'level' => 'pembimbing', 'department' => 'Product'],
            ['name' => 'Bima Saputra', 'email' => 'bima@ptinovasiabsen.test', 'role' => 'manager', 'level' => 'pembimbing', 'department' => 'People Ops'],
            ['name' => 'Salsabila Putri', 'email' => 'salsabila@ptinovasiabsen.test', 'role' => 'siswa-pkl', 'level' => 'students', 'department' => 'Engineering'],
            ['name' => 'Dimas Ilham', 'email' => 'dimas@ptinovasiabsen.test', 'role' => 'siswa-pkl', 'level' => 'students', 'department' => 'Engineering'],
            ['name' => 'Alya Taufik', 'email' => 'alya@ptinovasiabsen.test', 'role' => 'siswa-pkl', 'level' => 'students', 'department' => 'Design'],
        ];

        $users = collect($people)->mapWithKeys(function (array $person) use ($roles, $levels) {
            $user = User::updateOrCreate(['email' => $person['email']], [
                'name' => $person['name'],
                'password' => 'password',
                'role_id' => $roles[$person['role']]->id,
                'level_id' => $person['level'] ? $levels[$person['level']]->id : null,
                'department' => $person['department'],
            ]);
            return [$person['name'] => $user];
        });

        $today = now()->toDateString();
        $agendaData = [
            ['user' => 'Nadia Rahma', 'title' => 'Daily stand-up & pembagian task', 'description' => 'Sinkronisasi singkat bersama tim produk dan engineering.', 'start_time' => '08:15', 'status' => 'reviewed', 'summary' => 'Stand-up selesai dan task sprint sudah dibagikan.'],
            ['user' => 'Andi Ramadhan', 'title' => 'Perbaikan modul laporan kehadiran', 'description' => 'Melanjutkan refactor query dan menambahkan export CSV.', 'start_time' => '09:00', 'status' => 'in_progress'],
            ['user' => 'Fikri Ananta', 'title' => 'Review desain halaman dashboard', 'description' => 'Feedback untuk eksplorasi layout v2 dan komponen calendar.', 'start_time' => '10:30', 'status' => 'planned'],
            ['user' => 'Bima Saputra', 'title' => 'Sinkronisasi progres PKL', 'description' => 'Checkpoint mingguan dengan seluruh siswa PKL.', 'start_time' => '13:30', 'status' => 'planned'],
        ];

        foreach ($agendaData as $item) {
            $agenda = Agenda::updateOrCreate(['title' => $item['title'], 'agenda_date' => $today], [
                'user_id' => $users[$item['user']]->id,
                'reviewer_id' => $users['Fikri Ananta']->id,
                'description' => $item['description'],
                'start_time' => $item['start_time'],
                'status' => $item['status'],
                'morning_submitted_at' => now()->setTime(8, 0),
                'summary' => $item['summary'] ?? null,
                'summary_submitted_at' => isset($item['summary']) ? now()->setTime(17, 42) : null,
                'reviewed_at' => $item['status'] === 'reviewed' ? now()->setTime(9, 0) : null,
                'reviewed_by' => $item['status'] === 'reviewed' ? $users['Fikri Ananta']->id : null,
            ]);

            AuditLog::updateOrCreate(['action' => 'agenda.seeded', 'entity_id' => $agenda->id], [
                'user_id' => $users[$item['user']]->id,
                'entity_type' => Agenda::class,
                'metadata' => ['title' => $agenda->title],
            ]);
        }
    }
}
