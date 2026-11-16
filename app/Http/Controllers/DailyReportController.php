<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DailyReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403, 'Halaman laporan harian khusus untuk level siswa.');

        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $month = $request->input('month') ? Carbon::createFromFormat('!Y-m', $request->input('month')) : today()->startOfMonth();

        $reports = DailyReport::query()
            ->where('user_id', $user->id)
            ->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->withCount([
                'agendas',
                'agendas as done_agendas_count' => fn ($query) => $query->whereIn('status', ['completed', 'reviewed']),
            ])
            ->orderByDesc('report_date')
            ->paginate(12)
            ->withQueryString();

        $monthStats = [
            'reports' => DailyReport::where('user_id', $user->id)->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count(),
            'submitted' => DailyReport::where('user_id', $user->id)->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->whereNotNull('summary_submitted_at')->count(),
            'reviewed' => DailyReport::where('user_id', $user->id)->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->where('status', 'reviewed')->count(),
            'revision' => DailyReport::where('user_id', $user->id)->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->where('status', 'revision')->count(),
        ];

        return view('reports.index', compact('reports', 'month', 'monthStats'));
    }

    public function today(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403, 'Halaman agenda harian khusus untuk level siswa.');

        $report = DailyReport::query()
            ->where('user_id', $user->id)
            ->whereDate('report_date', today())
            ->with(['agendas' => fn ($query) => $query->orderBy('start_time')->orderBy('sort_order'), 'reviewer'])
            ->first();

        $deadline = today()->setTime(18, 0);

        return view('reports.today', compact('report', 'deadline'));
    }

    public function storeMorning(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:160'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.start_time' => ['nullable', 'date_format:H:i'],
            'items.*.end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:items.*.start_time'],
            'items.*.priority' => ['required', Rule::in(['low', 'medium', 'high'])],
        ]);

        $report = DailyReport::firstOrCreate(
            ['user_id' => $user->id, 'report_date' => today()->toDateString()],
            ['status' => 'draft']
        );

        if ($report->isMorningSubmitted()) {
            return back()->withErrors(['items' => 'Agenda pagi hari ini sudah dikirim. Gunakan halaman agenda untuk menambah atau mengubah item.']);
        }

        DB::transaction(function () use ($data, $report, $user) {
            $order = (int) $report->agendas()->max('sort_order');

            foreach ($data['items'] as $item) {
                $order++;
                $report->agendas()->create([
                    'user_id' => $user->id,
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'agenda_date' => $report->report_date,
                    'start_time' => $item['start_time'] ?? null,
                    'end_time' => $item['end_time'] ?? null,
                    'priority' => $item['priority'] ?? 'medium',
                    'status' => 'planned',
                    'sort_order' => $order,
                    'reviewer_id' => $user->mentor_id,
                    'morning_submitted_at' => now(),
                ]);
            }

            $report->update([
                'status' => 'morning',
                'morning_submitted_at' => now(),
            ]);

            AuditLogger::log('report.morning_submitted', $report, [
                'owner' => $user->name,
                'items' => count($data['items']),
            ]);

            if ($user->mentor) {
                NotificationService::send(
                    $user->mentor,
                    'report.submitted',
                    'Agenda pagi dari '.$user->name,
                    count($data['items']).' agenda dikirim untuk '.$report->report_date->translatedFormat('d M Y'),
                    route('reviews.show', $report)
                );
            }
        });

        return redirect()->route('reports.today')->with('status', 'Agenda pagi berhasil dikirim. Jangan lupa isi ringkasan kerja sore ini.');
    }

    public function storeSummary(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.summary' => ['nullable', 'string', 'max:2000'],
            'items.*.done' => ['nullable', 'boolean'],
            'new_items' => ['nullable', 'array'],
            'new_items.*.title' => ['nullable', 'string', 'max:160'],
            'new_items.*.summary' => ['nullable', 'string', 'max:2000'],
            'summary_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = DailyReport::query()
            ->where('user_id', $user->id)
            ->whereDate('report_date', today())
            ->with('agendas')
            ->firstOrFail();

        if (! $report->isMorningSubmitted()) {
            return back()->withErrors(['items' => 'Kirim agenda pagi terlebih dahulu sebelum mengisi ringkasan.']);
        }

        if ($report->reviewed_at) {
            return back()->withErrors(['items' => 'Laporan hari ini sudah direview dan tidak dapat diubah lagi.']);
        }

        DB::transaction(function () use ($data, $report, $user) {
            foreach ($data['items'] ?? [] as $agendaId => $payload) {
                $agenda = $report->agendas->firstWhere('id', (int) $agendaId);

                if (! $agenda) {
                    continue;
                }

                $done = ! empty($payload['done']);
                $agenda->update([
                    'summary' => $payload['summary'] ?? $agenda->summary,
                    'status' => $done ? 'completed' : 'in_progress',
                    'completed_at' => $done ? ($agenda->completed_at ?? now()) : null,
                    'summary_submitted_at' => now(),
                ]);
            }

            $order = (int) $report->agendas()->max('sort_order');

            foreach ($data['new_items'] ?? [] as $item) {
                if (empty(trim($item['title'] ?? ''))) {
                    continue;
                }

                $order++;
                $report->agendas()->create([
                    'user_id' => $user->id,
                    'title' => trim($item['title']),
                    'description' => 'Ditambahkan saat pengisian ringkasan sore.',
                    'agenda_date' => $report->report_date,
                    'priority' => 'medium',
                    'status' => 'completed',
                    'summary' => $item['summary'] ?? null,
                    'sort_order' => $order,
                    'reviewer_id' => $user->mentor_id,
                    'morning_submitted_at' => $report->morning_submitted_at,
                    'summary_submitted_at' => now(),
                    'completed_at' => now(),
                ]);
            }

            $report->update([
                'status' => 'summary',
                'summary_note' => $data['summary_note'] ?? null,
                'summary_submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_note' => null,
            ]);

            AuditLogger::log('report.summary_submitted', $report, [
                'owner' => $user->name,
                'items' => $report->agendas()->count(),
            ]);

            if ($user->mentor) {
                NotificationService::send(
                    $user->mentor,
                    'report.submitted',
                    'Ringkasan kerja dari '.$user->name,
                    'Menunggu review · '.$report->report_date->translatedFormat('d M Y'),
                    route('reviews.show', $report)
                );
            }
        });

        return redirect()->route('reports.today')->with('status', 'Ringkasan kerja berhasil dikirim ke pembimbing.');
    }

    public function show(Request $request, DailyReport $report): View
    {
        $user = $request->user();

        $allowed = $report->user_id === $user->id
            || $user->canViewAllReports()
            || ($user->isMentor() && $report->user->mentor_id === $user->id);

        abort_unless($allowed, 403);

        $report->load(['user.role', 'user.level', 'reviewer', 'agendas' => fn ($query) => $query->orderBy('start_time')]);

        return view('reports.show', compact('report'));
    }

    public function destroy(Request $request, DailyReport $report): RedirectResponse
    {
        abort_unless($report->user_id === $request->user()->id, 403);
        abort_if((bool) $report->reviewed_at, 403, 'Laporan yang sudah direview tidak dapat dihapus.');

        $date = $report->report_date->toDateString();
        AuditLogger::log('report.deleted', $report, ['owner' => $request->user()->name]);
        $report->delete();

        return redirect()->route('reports.index')->with('status', 'Draft laporan '.$date.' dihapus.');
    }
}
