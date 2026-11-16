<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = today();

        $visibleAgendas = Agenda::query()->visibleTo($user);

        $todayAgendas = (clone $visibleAgendas)
            ->with(['user.role', 'user.level', 'dailyReport'])
            ->whereDate('agenda_date', $today)
            ->orderBy('start_time')
            ->get();

        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();

        $weekTotal = (clone $visibleAgendas)->whereBetween('agenda_date', [$weekStart, $weekEnd])->count();
        $weekDone = (clone $visibleAgendas)
            ->whereBetween('agenda_date', [$weekStart, $weekEnd])
            ->whereIn('status', ['completed', 'reviewed'])
            ->count();

        $reviewQueue = $user->canReviewReports()
            ? DailyReport::query()
                ->visibleTo($user)
                ->with(['user.role', 'user.level'])
                ->where('status', 'summary')
                ->latest('summary_submitted_at')
                ->limit(5)
                ->get()
            : collect();

        $todayReport = $user->isStudent()
            ? DailyReport::query()
                ->with(['agendas', 'reviewer'])
                ->where('user_id', $user->id)
                ->whereDate('report_date', $today)
                ->first()
            : null;

        $recentReports = DailyReport::query()
            ->visibleTo($user)
            ->with(['user.role', 'user.level'])
            ->latest('report_date')
            ->limit(6)
            ->get();

        $activity = AuditLog::query()
            ->with('user')
            ->when(! $user->hasPermission('view-audit'), fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->limit(6)
            ->get();

        $weeklySeries = collect(range(6, 0))->map(function (int $daysAgo) use ($visibleAgendas, $today) {
            $date = $today->copy()->subDays($daysAgo);
            $dayAgendas = (clone $visibleAgendas)->whereDate('agenda_date', $date);

            return [
                'label' => $date->translatedFormat('D'),
                'date' => $date,
                'total' => (clone $dayAgendas)->count(),
                'done' => (clone $dayAgendas)->whereIn('status', ['completed', 'reviewed'])->count(),
            ];
        });

        $stats = [
            'agenda_today' => $todayAgendas->count(),
            'done_today' => $todayAgendas->whereIn('status', ['completed', 'reviewed'])->count(),
            'review_pending' => DailyReport::query()->visibleTo($user)->where('status', 'summary')->count(),
            'members' => $user->canViewAllAgendas() ? User::query()->where('is_active', true)->count() : $user->mentees()->count(),
            'week_total' => $weekTotal,
            'week_done' => $weekDone,
            'week_rate' => $weekTotal > 0 ? (int) round($weekDone / $weekTotal * 100) : 0,
            'mentees' => $user->mentees()->count(),
            'unread' => $user->unreadNotificationCount(),
        ];

        return view('dashboard', compact(
            'user', 'today', 'todayAgendas', 'reviewQueue', 'todayReport',
            'recentReports', 'activity', 'weeklySeries', 'stats'
        ));
    }
}
