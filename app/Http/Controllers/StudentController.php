<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\User;
use App\Services\CalendarBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canReviewReports() || $user->canViewAllReports(), 403);

        $students = User::query()
            ->whereHas('level', fn ($query) => $query->where('slug', 'students'))
            ->when(! $user->canViewAllReports(), fn ($query) => $query->where('mentor_id', $user->id))
            ->with(['role', 'level', 'mentor'])
            ->withCount([
                'dailyReports as pending_reports_count' => fn ($query) => $query->where('status', 'summary'),
                'dailyReports as reviewed_reports_count' => fn ($query) => $query->where('status', 'reviewed'),
            ])
            ->orderBy('name')
            ->get();

        $todayReports = DailyReport::query()
            ->whereIn('user_id', $students->pluck('id'))
            ->whereDate('report_date', today())
            ->get()
            ->keyBy('user_id');

        return view('students.index', compact('students', 'todayReports'));
    }

    public function show(Request $request, User $student): View
    {
        $user = $request->user();

        abort_unless($student->isStudent(), 404);
        abort_unless($user->oversees($student) && ($user->canReviewReports() || $user->canViewAllReports()), 403);

        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $calendar = CalendarBuilder::build($user, $request->input('month'), $student->id);

        $reports = DailyReport::query()
            ->where('user_id', $student->id)
            ->when($request->filled('month'), function ($query) use ($request) {
                $month = \Carbon\Carbon::createFromFormat('!Y-m', $request->input('month'));
                $query->whereBetween('report_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
            })
            ->withCount([
                'agendas',
                'agendas as done_agendas_count' => fn ($query) => $query->whereIn('status', ['completed', 'reviewed']),
            ])
            ->orderByDesc('report_date')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'pending' => DailyReport::where('user_id', $student->id)->where('status', 'summary')->count(),
            'reviewed' => DailyReport::where('user_id', $student->id)->where('status', 'reviewed')->count(),
            'reports' => DailyReport::where('user_id', $student->id)->count(),
            'agendas' => $student->agendas()->count(),
            'done' => $student->agendas()->whereIn('status', ['completed', 'reviewed'])->count(),
        ];

        return view('students.show', compact('student', 'calendar', 'reports', 'stats'));
    }
}
