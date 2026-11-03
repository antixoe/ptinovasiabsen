<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $agendas = Agenda::with(['user.role', 'user.level'])
            ->whereDate('agenda_date', $today)
            ->orderBy('start_time')
            ->get();

        $reviewQueue = Agenda::with('user')
            ->whereNotNull('summary_submitted_at')
            ->whereNull('reviewed_at')
            ->latest('summary_submitted_at')
            ->limit(5)
            ->get();

        $activeMembers = User::where('email', '!=', '')->count();

        return view('welcome', [
            'agendas' => $agendas,
            'reviewQueue' => $reviewQueue,
            'activeMembers' => $activeMembers,
            'agendaCount' => $agendas->count(),
            'reviewCount' => $reviewQueue->count(),
            'activities' => AuditLog::with('user')->latest()->limit(4)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'agenda_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $user = User::query()->firstOrFail();
        $agenda = $user->agendas()->create($data + ['status' => 'planned']);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'agenda.created',
            'entity_type' => Agenda::class,
            'entity_id' => $agenda->id,
            'metadata' => ['title' => $agenda->title],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Agenda berhasil ditambahkan.');
    }

    public function review(Request $request, Agenda $agenda): RedirectResponse
    {
        $reviewer = User::query()->firstOrFail();
        $agenda->update(['status' => 'reviewed', 'reviewed_at' => now(), 'reviewed_by' => $reviewer->id]);

        AuditLog::create([
            'user_id' => $reviewer->id,
            'action' => 'agenda.reviewed',
            'entity_type' => Agenda::class,
            'entity_id' => $agenda->id,
            'metadata' => ['title' => $agenda->title],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Agenda ditandai sudah direview.');
    }
}
