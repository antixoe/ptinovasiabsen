<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $request->validate([
            'status' => ['nullable', Rule::in(['summary', 'reviewed', 'revision', 'morning'])],
            'member' => ['nullable', 'integer', 'exists:users,id'],
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $query = DailyReport::query()
            ->visibleTo($user)
            ->with(['user.role', 'user.level'])
            ->withCount('agendas');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('member')) {
            $query->where('user_id', $request->integer('member'));
        }

        if ($request->filled('month')) {
            $month = Carbon::createFromFormat('!Y-m', $request->input('month'));
            $query->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
        }

        $reports = $query->orderByRaw("CASE status WHEN 'summary' THEN 0 WHEN 'revision' THEN 1 WHEN 'morning' THEN 2 WHEN 'reviewed' THEN 3 ELSE 4 END")
            ->orderByDesc('report_date')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'pending' => DailyReport::visibleTo($user)->where('status', 'summary')->count(),
            'revision' => DailyReport::visibleTo($user)->where('status', 'revision')->count(),
            'reviewed' => DailyReport::visibleTo($user)->where('status', 'reviewed')->count(),
        ];

        $members = $user->canViewAllReports()
            ? User::query()->whereHas('level', fn ($query) => $query->where('slug', 'students'))->orderBy('name')->get()
            : $user->mentees()->orderBy('name')->get();

        return view('reviews.index', compact('reports', 'counts', 'members'));
    }

    public function show(DailyReport $report): View
    {
        $this->authorizeReport($report);

        $report->load([
            'user.role',
            'user.level',
            'user.mentor',
            'reviewer',
            'agendas' => fn ($query) => $query->orderBy('start_time'),
        ]);

        return view('reviews.show', compact('report'));
    }

    public function update(Request $request, DailyReport $report): RedirectResponse
    {
        $this->authorizeReport($report);

        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'revision'])],
            'review_note' => ['nullable', 'string', 'max:2000', 'required_if:action,revision'],
        ]);

        if (! $report->isSummarySubmitted()) {
            return back()->withErrors(['review_note' => 'Siswa belum mengirim ringkasan kerja untuk hari ini.']);
        }

        DB::transaction(function () use ($data, $report, $request) {
            $approved = $data['action'] === 'approve';

            $report->update([
                'status' => $approved ? 'reviewed' : 'revision',
                'reviewed_at' => $approved ? now() : null,
                'reviewed_by' => $request->user()->id,
                'review_note' => $data['review_note'] ?? null,
            ]);

            if ($approved) {
                $report->agendas()->whereIn('status', ['completed', 'reviewed'])->update([
                    'status' => 'reviewed',
                    'reviewed_at' => now(),
                    'reviewed_by' => $request->user()->id,
                ]);
            }

            AuditLogger::log($approved ? 'report.approved' : 'report.revision_requested', $report, [
                'owner' => $report->user->name,
                'date' => $report->report_date->toDateString(),
                'note' => $data['review_note'] ?? null,
            ]);

            NotificationService::send(
                $report->user,
                $approved ? 'report.reviewed' : 'report.revision',
                $approved
                    ? 'Laporan harian kamu sudah direview'
                    : 'Laporan harian perlu revisi',
                $report->report_date->translatedFormat('d M Y').($data['review_note'] ? ' · '.$data['review_note'] : ''),
                route('reports.show', $report)
            );
        });

        return redirect()
            ->route('reviews.show', $report)
            ->with('status', $data['action'] === 'approve' ? 'Laporan disetujui dan dikunci.' : 'Permintaan revisi dikirim ke siswa.');
    }

    private function authorizeReport(DailyReport $report): void
    {
        $user = request()->user();
        $report->loadMissing('user');

        $allowed = $user->canReviewReports()
            && ($user->canViewAllReports() || $report->user->mentor_id === $user->id);

        abort_unless($allowed, 403);
    }
}
