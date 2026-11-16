<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CalendarBuilder
{
    /**
     * Build a Monday-first calendar grid for a month, with agendas grouped by date.
     *
     * @return array{month: CarbonImmutable, weeks: array<int, array<int, array{date: CarbonImmutable, inMonth: bool, isToday: bool, agendas: array}>}
     */
    public static function build(User $user, ?string $month = null, ?int $memberId = null): array
    {
        $monthInput = $month ? CarbonImmutable::createFromFormat('!Y-m', $month) : CarbonImmutable::today()->startOfMonth();
        $monthStart = $monthInput->startOfMonth();
        $monthEnd = $monthInput->endOfMonth();

        $start = $monthStart->startOfWeek(CarbonInterface::MONDAY);
        $end = $monthEnd->endOfWeek(CarbonInterface::SUNDAY);

        $query = Agenda::query()
            ->visibleTo($user)
            ->with(['user.role', 'dailyReport'])
            ->whereBetween('agenda_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('start_time');

        if ($memberId && $user->oversees(User::findOrFail($memberId))) {
            $query->where('user_id', $memberId);
        }

        $grouped = $query->get()->groupBy(fn (Agenda $agenda) => $agenda->agenda_date->toDateString());

        $weeks = [];
        $cursor = $start;

        while ($cursor->lessThanOrEqualTo($end)) {
            $week = [];

            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->addDays($day);
                $week[] = [
                    'date' => $date,
                    'inMonth' => $date->month === $monthStart->month,
                    'isToday' => $date->isToday(),
                    'agendas' => $grouped->get($date->toDateString(), collect())->all(),
                ];
            }

            $weeks[] = $week;
            $cursor = $cursor->addWeek();
        }

        return [
            'month' => $monthStart,
            'weeks' => $weeks,
        ];
    }
}
