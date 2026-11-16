<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDailyReportReminders extends Command
{
    protected $signature = 'agenda:send-reminders {--date= : Tanggal yang diperiksa (Y-m-d)}';

    protected $description = 'Kirim pengingat sore ke siswa yang belum mengirim ringkasan kerja';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : today();

        $sent = 0;
        $checked = 0;

        User::query()
            ->where('is_active', true)
            ->whereHas('level', fn ($query) => $query->where('slug', 'students'))
            ->with('mentor')
            ->chunkById(100, function ($students) use ($date, &$sent, &$checked) {
                foreach ($students as $student) {
                    $checked++;

                    $report = DailyReport::query()
                        ->where('user_id', $student->id)
                        ->whereDate('report_date', $date)
                        ->first();

                    $alreadySubmitted = $report?->isSummarySubmitted()
                        || $report?->status === 'reviewed';

                    if ($alreadySubmitted) {
                        continue;
                    }

                    $alreadyReminded = $student->workspaceNotifications()
                        ->where('type', 'report.reminder')
                        ->whereDate('created_at', $date->toDateString())
                        ->exists();

                    if ($alreadyReminded) {
                        continue;
                    }

                    NotificationService::send(
                        $student,
                        'report.reminder',
                        'Pengingat ringkasan kerja',
                        'Sudah pukul 18.00. Kirim ringkasan kerja '.$date->translatedFormat('d M Y').' sebelum hari berganti.',
                        route('reports.today')
                    );

                    if ($student->mentor && $report) {
                        NotificationService::send(
                            $student->mentor,
                            'report.reminder',
                            'Siswa bimbingan belum mengirim ringkasan',
                            $student->name.' belum mengirim ringkasan kerja '.$date->translatedFormat('d M Y').'.',
                            route('reviews.index', ['status' => 'morning'])
                        );
                    }

                    $sent++;
                }
            });

        $this->info("Pemeriksaan selesai: {$checked} siswa diperiksa, {$sent} pengingat dikirim.");

        return self::SUCCESS;
    }
}
