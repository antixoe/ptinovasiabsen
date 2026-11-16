<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkspaceNotification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = $this->seedRoles();
        $levels = $this->seedLevels();
        $users = $this->seedUsers($roles, $levels);

        $this->seedPastReports($users);
        $this->seedToday($users);
        $this->seedNotificationsAndAudit($users);
    }

    private function seedRoles()
    {
        $definitions = [
            ['name' => 'Superadmin', 'slug' => 'superadmin', 'description' => 'Akses penuh ke seluruh workspace dan pengaturan.', 'permissions' => array_keys(Role::PERMISSIONS)],
            ['name' => 'Direktur', 'slug' => 'direktur', 'description' => 'Melihat seluruh agenda, laporan, dan audit log.', 'permissions' => ['view-audit', 'view-all-reports', 'view-all-agendas', 'review-reports']],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Memantau dan mengelola agenda seluruh tim.', 'permissions' => ['view-all-reports', 'view-all-agendas', 'review-reports', 'manage-all-agendas']],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'description' => 'Membimbing dan mereview siswa PKL.', 'permissions' => ['review-reports']],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Mengelola anggota, peran, dan audit workspace.', 'permissions' => ['manage-users', 'manage-roles', 'view-audit', 'view-all-reports', 'view-all-agendas']],
            ['name' => 'Karyawan', 'slug' => 'karyawan', 'description' => 'Mengelola agenda kerja pribadi.', 'permissions' => []],
            ['name' => 'Siswa PKL', 'slug' => 'siswa-pkl', 'description' => 'Mengisi agenda pagi dan ringkasan kerja harian.', 'permissions' => []],
        ];

        return collect($definitions)->mapWithKeys(function (array $definition) {
            $role = Role::updateOrCreate(['slug' => $definition['slug']], $definition + ['is_system' => true]);

            return [$definition['slug'] => $role];
        });
    }

    private function seedLevels()
    {
        return collect([
            ['name' => 'Students', 'slug' => 'students'],
            ['name' => 'Pembimbing', 'slug' => 'pembimbing'],
        ])->mapWithKeys(function (array $level) {
            $model = Level::updateOrCreate(['slug' => $level['slug']], $level);

            return [$level['slug'] => $model];
        });
    }

    private function seedUsers($roles, $levels): array
    {
        $people = [
            ['name' => 'Andi Ramadhan', 'email' => 'andi@ptinovasiabsen.test', 'role' => 'superadmin', 'level' => null, 'mentor' => null, 'department' => 'Platform', 'phone' => '0812 1000 0001', 'avatar_color' => 'navy'],
            ['name' => 'Hendra Wijaya', 'email' => 'hendra@ptinovasiabsen.test', 'role' => 'direktur', 'level' => null, 'mentor' => null, 'department' => 'Direksi', 'phone' => '0812 1000 0002', 'avatar_color' => 'slate'],
            ['name' => 'Rina Kartika', 'email' => 'rina@ptinovasiabsen.test', 'role' => 'admin', 'level' => null, 'mentor' => null, 'department' => 'People Ops', 'phone' => '0812 1000 0003', 'avatar_color' => 'rose'],
            ['name' => 'Bima Saputra', 'email' => 'bima@ptinovasiabsen.test', 'role' => 'manager', 'level' => 'pembimbing', 'mentor' => null, 'department' => 'People Ops', 'phone' => '0812 1000 0004', 'avatar_color' => 'amber'],
            ['name' => 'Fikri Ananta', 'email' => 'fikri@ptinovasiabsen.test', 'role' => 'supervisor', 'level' => 'pembimbing', 'mentor' => null, 'department' => 'Engineering', 'phone' => '0812 1000 0005', 'avatar_color' => 'violet'],
            ['name' => 'Nadia Rahma', 'email' => 'nadia@ptinovasiabsen.test', 'role' => 'supervisor', 'level' => 'pembimbing', 'mentor' => null, 'department' => 'Design', 'phone' => '0812 1000 0006', 'avatar_color' => 'blue'],
            ['name' => 'Salsabila Putri', 'email' => 'salsabila@ptinovasiabsen.test', 'role' => 'siswa-pkl', 'level' => 'students', 'mentor' => 'Fikri Ananta', 'department' => 'Engineering', 'phone' => '0812 1000 0007', 'avatar_color' => 'green'],
            ['name' => 'Dimas Ilham', 'email' => 'dimas@ptinovasiabsen.test', 'role' => 'siswa-pkl', 'level' => 'students', 'mentor' => 'Fikri Ananta', 'department' => 'Engineering', 'phone' => '0812 1000 0008', 'avatar_color' => 'teal'],
            ['name' => 'Alya Taufik', 'email' => 'alya@ptinovasiabsen.test', 'role' => 'siswa-pkl', 'level' => 'students', 'mentor' => 'Nadia Rahma', 'department' => 'Design', 'phone' => '0812 1000 0009', 'avatar_color' => 'pink'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky@ptinovasiabsen.test', 'role' => 'karyawan', 'level' => null, 'mentor' => null, 'department' => 'Finance', 'phone' => '0812 1000 0010', 'avatar_color' => 'orange'],
        ];

        $created = [];

        foreach ($people as $person) {
            $created[$person['name']] = User::updateOrCreate(['email' => $person['email']], [
                'name' => $person['name'],
                'password' => 'password',
                'role_id' => $roles[$person['role']]->id,
                'level_id' => $person['level'] ? $levels[$person['level']]->id : null,
                'department' => $person['department'],
                'phone' => $person['phone'],
                'avatar_color' => $person['avatar_color'],
                'is_active' => true,
            ]);
        }

        foreach ($people as $person) {
            if ($person['mentor']) {
                $created[$person['name']]->update(['mentor_id' => $created[$person['mentor']]->id]);
            }
        }

        return $created;
    }

    private function seedPastReports(array $users): void
    {
        $tasks = [
            ['Review komponen dashboard kehadiran', 'Merapikan spacing, state kosong, dan tampilan data tabel.'],
            ['Rapikan dokumentasi API absensi', 'Menulis ulang contoh request dan response untuk tim frontend.'],
            ['Uji coba form pengajuan izin', 'Menguji validasi, unggah lampiran, dan notifikasi ke atasan.'],
            ['Perbaiki tampilan kalender agenda', 'Menyesuaikan penanda hari ini dan ringkasan agenda per tanggal.'],
            ['Riset alur review pembimbing', 'Memetakan status agenda pagi sampai ringkasan sore.'],
            ['Buat komponen kartu statistik', 'Membuat komponen ringkasan yang bisa dipakai ulang.'],
            ['Sinkronisasi data anggota dengan tim HR', 'Membandingkan data peran dan level anggota aktif.'],
            ['Optimasi query laporan mingguan', 'Menambahkan index dan mengurangi query berulang.'],
            ['Susun panduan penggunaan workspace', 'Menulis langkah pengisian agenda untuk siswa baru.'],
            ['Eksplorasi desain halaman onboarding', 'Membuat dua alternatif layout dan meminta feedback.'],
        ];

        $summaries = [
            'Selesai dikerjakan dan sudah dicek bersama pembimbing.',
            'Sebagian besar selesai, sisa pengecekan dilakukan besok pagi.',
            'Dikerjakan sesuai catatan review sebelumnya.',
            'Selesai lebih cepat dari target, dokumentasi sudah diperbarui.',
            'Menunggu data dari tim lain, progres sementara sudah dicatat.',
        ];

        $students = [
            ['user' => 'Salsabila Putri', 'mentor' => 'Fikri Ananta'],
            ['user' => 'Dimas Ilham', 'mentor' => 'Fikri Ananta'],
            ['user' => 'Alya Taufik', 'mentor' => 'Nadia Rahma'],
        ];

        $weekdays = collect(range(1, 16))
            ->map(fn (int $offset) => today()->subDays($offset))
            ->reject(fn ($date) => $date->isWeekend())
            ->take(8)
            ->values();

        foreach ($weekdays as $index => $date) {
            foreach ($students as $studentIndex => $student) {
                if (($index + $studentIndex) % 7 === 6) {
                    continue;
                }

                $isRevision = ($index + $studentIndex) % 6 === 2;
                $report = DailyReport::updateOrCreate(
                    ['user_id' => $users[$student['user']]->id, 'report_date' => $date->toDateString()],
                    [
                        'status' => $isRevision ? 'revision' : 'reviewed',
                        'morning_submitted_at' => $date->copy()->setTime(8, 5 + $studentIndex),
                        'summary_submitted_at' => $date->copy()->setTime(17, 25 + $studentIndex * 4),
                        'reviewed_at' => $isRevision ? null : $date->copy()->addDay()->setTime(9, 10),
                        'reviewed_by' => $users[$student['mentor']]->id,
                        'review_note' => $isRevision
                            ? 'Tambahkan detail output dan bukti pekerjaan pada agenda yang selesai.'
                            : 'Ringkasan sudah jelas dan progres sesuai target. Lanjutkan minggu depan.',
                    ]
                );

                if ($report->agendas()->exists()) {
                    continue;
                }

                $itemCount = 2 + (($index + $studentIndex) % 2);

                for ($item = 0; $item < $itemCount; $item++) {
                    [$title, $description] = $tasks[($index * 3 + $studentIndex + $item) % count($tasks)];
                    $done = ($index + $studentIndex + $item) % 3 !== 0;

                    $report->agendas()->create([
                        'user_id' => $users[$student['user']]->id,
                        'title' => $title,
                        'description' => $description,
                        'agenda_date' => $report->report_date,
                        'start_time' => sprintf('%02d:00', 8 + $item * 2),
                        'end_time' => sprintf('%02d:45', 9 + $item * 2),
                        'priority' => $item === 0 ? 'high' : 'medium',
                        'status' => $done ? 'completed' : 'planned',
                        'summary' => $done ? $summaries[($index + $item) % count($summaries)] : null,
                        'sort_order' => $item,
                        'reviewer_id' => $users[$student['mentor']]->id,
                        'morning_submitted_at' => $report->morning_submitted_at,
                        'summary_submitted_at' => $report->summary_submitted_at,
                        'completed_at' => $done ? $report->summary_submitted_at : null,
                        'reviewed_at' => $isRevision ? null : $report->reviewed_at,
                        'reviewed_by' => $isRevision ? null : $users[$student['mentor']]->id,
                    ]);
                }
            }
        }
    }

    private function seedToday(array $users): void
    {
        $today = today();

        $salsabila = DailyReport::updateOrCreate(
            ['user_id' => $users['Salsabila Putri']->id, 'report_date' => $today->toDateString()],
            [
                'status' => 'summary',
                'morning_submitted_at' => $today->copy()->setTime(8, 7),
                'summary_submitted_at' => $today->copy()->setTime(17, 12),
            ]
        );

        if (! $salsabila->agendas()->exists()) {
            foreach ([
                ['Review komponen dashboard kehadiran', 'Memeriksa state kosong dan tabel agenda di layar kecil.', '08:00', 'completed', 'Selesai, tinggal menunggu catatan pembimbing.'],
                ['Rapikan dokumentasi API absensi', 'Memperbarui contoh request untuk endpoint laporan.', '10:00', 'completed', 'Dokumentasi versi baru sudah dipublikasikan.'],
                ['Uji coba form pengajuan izin', 'Menguji validasi tanggal dan unggah lampiran.', '13:30', 'in_progress', 'Validasi tanggal selesai, unggah lampiran masih bermasalah.'],
            ] as $order => [$title, $description, $time, $status, $summary]) {
                $salsabila->agendas()->create([
                    'user_id' => $salsabila->user_id,
                    'title' => $title,
                    'description' => $description,
                    'agenda_date' => $today,
                    'start_time' => $time,
                    'priority' => $order === 0 ? 'high' : 'medium',
                    'status' => $status,
                    'summary' => $summary,
                    'sort_order' => $order,
                    'reviewer_id' => $users['Fikri Ananta']->id,
                    'morning_submitted_at' => $salsabila->morning_submitted_at,
                    'summary_submitted_at' => $salsabila->summary_submitted_at,
                    'completed_at' => $status === 'completed' ? $salsabila->summary_submitted_at : null,
                ]);
            }
        }

        $dimas = DailyReport::updateOrCreate(
            ['user_id' => $users['Dimas Ilham']->id, 'report_date' => $today->toDateString()],
            [
                'status' => 'morning',
                'morning_submitted_at' => $today->copy()->setTime(8, 22),
            ]
        );

        if (! $dimas->agendas()->exists()) {
            foreach ([
                ['Buat komponen kartu statistik', 'Membuat komponen ringkasan dengan data nyata dari database.', '09:00', 'in_progress'],
                ['Optimasi query laporan mingguan', 'Menambahkan index untuk filter tanggal dan status.', '13:00', 'planned'],
            ] as $order => [$title, $description, $time, $status]) {
                $dimas->agendas()->create([
                    'user_id' => $dimas->user_id,
                    'title' => $title,
                    'description' => $description,
                    'agenda_date' => $today,
                    'start_time' => $time,
                    'priority' => 'medium',
                    'status' => $status,
                    'sort_order' => $order,
                    'reviewer_id' => $users['Fikri Ananta']->id,
                    'morning_submitted_at' => $dimas->morning_submitted_at,
                ]);
            }
        }

        $employeeAgendas = [
            ['Fikri Ananta', 'Review desain halaman dashboard', 'Menyiapkan catatan untuk sesi review bersama siswa PKL.', '10:30', 'planned'],
            ['Nadia Rahma', 'Sprint planning tim desain', 'Menyusun prioritas komponen untuk sprint berikutnya.', '09:00', 'in_progress'],
            ['Andi Ramadhan', 'Audit akses workspace', 'Memeriksa peran, izin, dan akun tidak aktif.', '13:00', 'planned'],
        ];

        foreach ($employeeAgendas as [$owner, $title, $description, $time, $status]) {
            Agenda::updateOrCreate(
                ['user_id' => $users[$owner]->id, 'agenda_date' => $today->toDateString(), 'title' => $title],
                [
                    'description' => $description,
                    'start_time' => $time,
                    'priority' => 'medium',
                    'status' => $status,
                ]
            );
        }

        Agenda::updateOrCreate(
            ['user_id' => $users['Salsabila Putri']->id, 'agenda_date' => $today->copy()->addDay()->toDateString(), 'title' => 'Presentasi progres modul absensi'],
            [
                'description' => 'Diminta pembimbing untuk menyiapkan demo singkat modul absensi.',
                'start_time' => '09:00',
                'priority' => 'high',
                'status' => 'planned',
                'reviewer_id' => $users['Fikri Ananta']->id,
            ]
        );
    }

    private function seedNotificationsAndAudit(array $users): void
    {
        $salsabilaReport = DailyReport::where('user_id', $users['Salsabila Putri']->id)->whereDate('report_date', today())->first();
        $dimasReport = DailyReport::where('user_id', $users['Dimas Ilham']->id)->whereDate('report_date', today())->first();

        WorkspaceNotification::firstOrCreate([
            'user_id' => $users['Fikri Ananta']->id,
            'type' => 'report.submitted',
            'title' => 'Ringkasan kerja dari Salsabila Putri',
        ], [
            'body' => 'Menunggu review · '.today()->translatedFormat('d M Y'),
            'link' => $salsabilaReport ? route('reviews.show', $salsabilaReport) : null,
        ]);

        WorkspaceNotification::firstOrCreate([
            'user_id' => $users['Fikri Ananta']->id,
            'type' => 'report.submitted',
            'title' => 'Agenda pagi dari Dimas Ilham',
        ], [
            'body' => '2 agenda dikirim untuk '.today()->translatedFormat('d M Y'),
            'link' => $dimasReport ? route('reviews.show', $dimasReport) : null,
        ]);

        WorkspaceNotification::firstOrCreate([
            'user_id' => $users['Salsabila Putri']->id,
            'type' => 'agenda.created',
            'title' => 'Agenda baru dari Fikri Ananta',
        ], [
            'body' => 'Presentasi progres modul absensi · besok',
            'link' => route('agenda.index', ['date' => today()->copy()->addDay()->toDateString()]),
        ]);

        WorkspaceNotification::firstOrCreate([
            'user_id' => $users['Alya Taufik']->id,
            'type' => 'report.reminder',
            'title' => 'Pengingat ringkasan kerja',
        ], [
            'body' => 'Sudah pukul 18.00. Kirim ringkasan kerja hari ini sebelum hari berganti.',
            'link' => route('reports.today'),
        ]);

        if ($salsabilaReport) {
            AuditLog::firstOrCreate([
                'action' => 'report.summary_submitted',
                'entity_type' => DailyReport::class,
                'entity_id' => $salsabilaReport->id,
            ], [
                'user_id' => $users['Salsabila Putri']->id,
                'metadata' => ['owner' => 'Salsabila Putri', 'items' => 3],
                'ip_address' => '127.0.0.1',
            ]);
        }

        if ($dimasReport) {
            AuditLog::firstOrCreate([
                'action' => 'report.morning_submitted',
                'entity_type' => DailyReport::class,
                'entity_id' => $dimasReport->id,
            ], [
                'user_id' => $users['Dimas Ilham']->id,
                'metadata' => ['owner' => 'Dimas Ilham', 'items' => 2],
                'ip_address' => '127.0.0.1',
            ]);
        }

        AuditLog::firstOrCreate([
            'action' => 'auth.login',
            'entity_type' => User::class,
            'entity_id' => $users['Andi Ramadhan']->id,
        ], [
            'user_id' => $users['Andi Ramadhan']->id,
            'metadata' => ['email' => 'andi@ptinovasiabsen.test'],
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::firstOrCreate([
            'action' => 'agenda.created',
            'entity_type' => Agenda::class,
            'entity_id' => Agenda::where('title', 'Presentasi progres modul absensi')->value('id'),
        ], [
            'user_id' => $users['Fikri Ananta']->id,
            'metadata' => ['title' => 'Presentasi progres modul absensi', 'owner' => 'Salsabila Putri'],
            'ip_address' => '127.0.0.1',
        ]);
    }
}
