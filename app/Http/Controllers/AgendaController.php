<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\DailyReport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CalendarBuilder;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendaController extends Controller
{
    private const STATUSES = ['planned', 'in_progress', 'completed', 'cancelled'];

    public function index(Request $request): View
    {
        $user = $request->user();

        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'member' => ['nullable', 'integer', 'exists:users,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $date = isset($filters['date']) ? Carbon::parse($filters['date']) : today();

        $query = Agenda::query()
            ->visibleTo($user)
            ->with(['user.role', 'user.level', 'dailyReport'])
            ->whereDate('agenda_date', $date);

        if (! empty($filters['member'])) {
            $member = User::findOrFail($filters['member']);
            abort_unless($user->oversees($member), 403);
            $query->where('user_id', $member->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $query->where(function ($inner) use ($filters) {
                $inner->where('title', 'like', '%'.$filters['q'].'%')
                    ->orWhere('description', 'like', '%'.$filters['q'].'%');
            });
        }

        $agendas = $query->orderBy('start_time')->orderBy('sort_order')->get();

        $summary = [
            'total' => $agendas->count(),
            'done' => $agendas->filter->isDone()->count(),
            'running' => $agendas->where('status', 'in_progress')->count(),
            'planned' => $agendas->where('status', 'planned')->count(),
        ];

        return view('agenda.index', [
            'agendas' => $agendas,
            'date' => $date,
            'summary' => $summary,
            'filters' => $filters,
            'members' => $this->assignableMembers($user),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        return view('agenda.form', [
            'agenda' => new Agenda([
                'agenda_date' => $request->filled('date') ? Carbon::parse($request->input('date')) : today(),
                'user_id' => $request->integer('member') ?: $user->id,
                'priority' => 'medium',
                'status' => 'planned',
            ]),
            'members' => $this->assignableMembers($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = User::findOrFail($request->integer('user_id'));
        Gate::authorize('manage-agenda-for', $owner);

        $data = $this->validated($request);

        $report = DailyReport::firstOrCreate(
            ['user_id' => $owner->id, 'report_date' => $data['agenda_date']],
            ['status' => 'draft']
        );

        $this->ensureReportEditable($report, $request->user());

        $agenda = DB::transaction(function () use ($data, $owner, $report, $request) {
            $agenda = $owner->agendas()->create($data + [
                'daily_report_id' => $report->id,
                'sort_order' => (int) $report->agendas()->max('sort_order') + 1,
                'morning_submitted_at' => $report->morning_submitted_at,
            ]);

            AuditLogger::log('agenda.created', $agenda, [
                'title' => $agenda->title,
                'owner' => $owner->name,
                'date' => $agenda->agenda_date->toDateString(),
            ]);

            if ($owner->id !== $request->user()->id) {
                NotificationService::send(
                    $owner,
                    'agenda.created',
                    'Agenda baru dari '.$request->user()->name,
                    $agenda->title.' · '.$agenda->agenda_date->translatedFormat('d M Y'),
                    route('agenda.index', ['date' => $agenda->agenda_date->toDateString()])
                );
            }

            return $agenda;
        });

        return redirect()
            ->route('agenda.index', ['date' => $agenda->agenda_date->toDateString()])
            ->with('status', 'Agenda "'.$agenda->title.'" berhasil ditambahkan.');
    }

    public function edit(Request $request, Agenda $agenda): View
    {
        Gate::authorize('manage-agenda-for', $agenda->user);
        $this->ensureReportEditable($agenda->dailyReport, $request->user());

        return view('agenda.form', [
            'agenda' => $agenda,
            'members' => $this->assignableMembers($request->user()),
        ]);
    }

    public function update(Request $request, Agenda $agenda): RedirectResponse
    {
        Gate::authorize('manage-agenda-for', $agenda->user);
        $this->ensureReportEditable($agenda->dailyReport, $request->user());

        $data = $this->validated($request);
        $agenda->update($data);

        AuditLogger::log('agenda.updated', $agenda, [
            'title' => $agenda->title,
            'owner' => $agenda->user->name,
        ]);

        if ($agenda->user_id !== $request->user()->id) {
            NotificationService::send(
                $agenda->user,
                'agenda.updated',
                'Agenda kamu diperbarui oleh '.$request->user()->name,
                $agenda->title.' · '.$agenda->agenda_date->translatedFormat('d M Y'),
                route('agenda.index', ['date' => $agenda->agenda_date->toDateString()])
            );
        }

        return redirect()
            ->route('agenda.index', ['date' => $agenda->agenda_date->toDateString()])
            ->with('status', 'Agenda berhasil diperbarui.');
    }

    public function destroy(Request $request, Agenda $agenda): RedirectResponse
    {
        Gate::authorize('manage-agenda-for', $agenda->user);
        $this->ensureReportEditable($agenda->dailyReport, $request->user());

        $date = $agenda->agenda_date->toDateString();

        AuditLogger::log('agenda.deleted', $agenda, [
            'title' => $agenda->title,
            'owner' => $agenda->user->name,
        ]);

        $agenda->delete();

        return redirect()
            ->route('agenda.index', ['date' => $date])
            ->with('status', 'Agenda berhasil dihapus.');
    }

    public function toggle(Request $request, Agenda $agenda): RedirectResponse
    {
        Gate::authorize('manage-agenda-for', $agenda->user);
        $this->ensureReportEditable($agenda->dailyReport, $request->user());

        $done = ! $agenda->isDone();
        $agenda->update([
            'status' => $done ? 'completed' : 'planned',
            'completed_at' => $done ? now() : null,
        ]);

        AuditLogger::log($done ? 'agenda.completed' : 'agenda.reopened', $agenda, [
            'title' => $agenda->title,
        ]);

        return back()->with('status', $done ? 'Agenda ditandai selesai.' : 'Agenda dibuka kembali.');
    }

    public function calendar(Request $request): View
    {
        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'member' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $memberId = $request->integer('member') ?: null;

        if ($memberId) {
            abort_unless($user->oversees(User::findOrFail($memberId)), 403);
        }

        $calendar = CalendarBuilder::build($user, $request->input('month'), $memberId);

        return view('agenda.calendar', [
            'calendar' => $calendar,
            'members' => $this->assignableMembers($user),
            'memberId' => $memberId,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'agenda_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);
    }

    /**
     * Members whose agenda the current user may manage.
     *
     * @return Collection<int, User>
     */
    private function assignableMembers(User $user): Collection
    {
        if ($user->canManageAllAgendas()) {
            return User::query()->where('is_active', true)->orderBy('name')->get();
        }

        if ($user->isMentor()) {
            return $user->mentees()->where('is_active', true)->orderBy('name')->get()
                ->prepend($user)
                ->unique('id')
                ->values();
        }

        return collect([$user]);
    }

    private function ensureReportEditable(?DailyReport $report, User $actor): void
    {
        if ($report?->reviewed_at && ! $actor->isSuperadmin()) {
            abort(403, 'Laporan harian ini sudah direview dan dikunci untuk menjaga audit trail.');
        }
    }
}
