<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StudentCompliance
{
    public const MISSING_MORNING = 'missing_morning';

    public const MISSING_SUMMARY = 'missing_summary';

    public const REVISION = 'revision';

    public const PENDING_REVIEW = 'pending_review';

    public const REVIEWED = 'reviewed';

    /**
     * Students whose daily reports this viewer is responsible for.
     *
     * @return Collection<int, User>
     */
    public static function students(User $viewer): Collection
    {
        if (! $viewer->canReviewReports() && ! $viewer->canViewAllReports()) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('level', fn ($query) => $query->where('slug', 'students'))
            ->when(! $viewer->canViewAllReports(), fn ($query) => $query->where('mentor_id', $viewer->id))
            ->with(['role', 'level', 'mentor'])
            ->withCount([
                'dailyReports as pending_reports_count' => fn ($query) => $query->where('status', 'summary'),
                'dailyReports as reviewed_reports_count' => fn ($query) => $query->where('status', 'reviewed'),
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Today's compliance rows for every student the viewer can monitor.
     * Each row: student, report, state, label, tone, needs_attention.
     *
     * @return Collection<int, array{student: User, report: ?DailyReport, state: string, label: string, tone: string, needs_attention: bool}>
     */
    public static function snapshot(User $viewer, ?CarbonInterface $date = null): Collection
    {
        $date = $date ? $date->copy() : today();
        $students = self::students($viewer);

        if ($students->isEmpty()) {
            return collect();
        }

        $reports = DailyReport::query()
            ->whereIn('user_id', $students->pluck('id'))
            ->whereDate('report_date', $date)
            ->withCount('agendas')
            ->get()
            ->keyBy('user_id');

        return $students
            ->map(function (User $student) use ($reports) {
                $report = $reports->get($student->id);
                $state = self::stateFor($report);

                return [
                    'student' => $student,
                    'report' => $report,
                    'state' => $state,
                    'label' => self::labelFor($state),
                    'tone' => self::toneFor($state),
                    'needs_attention' => in_array($state, [self::MISSING_MORNING, self::MISSING_SUMMARY, self::REVISION], true),
                ];
            })
            ->sortBy([
                fn ($a, $b) => self::priority($a['state']) <=> self::priority($b['state']),
                fn ($a, $b) => strcasecmp($a['student']->name, $b['student']->name),
            ])
            ->values();
    }

    public static function attentionCount(User $viewer): int
    {
        return self::snapshot($viewer)->where('needs_attention', true)->count();
    }

    public static function stateFor(?DailyReport $report): string
    {
        if (! $report || ! $report->isMorningSubmitted()) {
            return self::MISSING_MORNING;
        }

        if ($report->status === 'revision') {
            return self::REVISION;
        }

        if (! $report->isSummarySubmitted()) {
            return self::MISSING_SUMMARY;
        }

        return $report->status === 'reviewed' ? self::REVIEWED : self::PENDING_REVIEW;
    }

    public static function labelFor(string $state): string
    {
        return match ($state) {
            self::MISSING_MORNING => 'Belum isi agenda',
            self::MISSING_SUMMARY => 'Belum kirim ringkasan',
            self::REVISION => 'Perlu revisi',
            self::PENDING_REVIEW => 'Menunggu review',
            self::REVIEWED => 'Selesai',
            default => 'Belum ada laporan',
        };
    }

    public static function toneFor(string $state): string
    {
        return match ($state) {
            self::MISSING_MORNING, self::REVISION => 'danger',
            self::MISSING_SUMMARY => 'warning',
            self::PENDING_REVIEW => 'info',
            self::REVIEWED => 'success',
            default => 'neutral',
        };
    }

    private static function priority(string $state): int
    {
        return match ($state) {
            self::MISSING_MORNING => 0,
            self::MISSING_SUMMARY => 1,
            self::REVISION => 2,
            self::PENDING_REVIEW => 3,
            self::REVIEWED => 4,
            default => 5,
        };
    }
}
