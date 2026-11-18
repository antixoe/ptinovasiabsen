@extends('layouts.app')

@section('title', $student->name)
@section('breadcrumb', 'Siswa bimbingan')

@php
    $month = $calendar['month'];
    $monthParam = request('month', $month->format('Y-m'));
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Profil siswa bimbingan</p>
            <h1>{{ $student->name }}</h1>
            <p class="sub">{{ $student->role?->name }} · {{ $student->department ?? 'Tanpa departemen' }} · pembimbing {{ $student->mentor?->name ?? 'belum ditentukan' }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('students.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" /> Kembali</a>
            <a href="{{ route('agenda.create', ['member' => $student->id]) }}" class="btn btn-primary" data-modal="{{ route('agenda.create', ['member' => $student->id]) }}"><x-icon name="plus" /> Tambah agenda</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body pad">
            <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
                <div class="avatar-row">
                    <x-avatar :user="$student" size="xl" />
                    <div>
                        <b style="font-size:14px">{{ $student->name }}</b>
                        <small>{{ $student->email }}</small>
                        <div class="flex gap-2 mt-2" style="flex-wrap:wrap">
                            <span class="badge violet">{{ $student->level?->name ?? 'Tanpa level' }}</span>
                            @if ($student->phone)
                                <span class="badge">{{ $student->phone }}</span>
                            @endif
                            <span class="badge {{ $student->is_active ? 'success' : 'danger' }}">{{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                    </div>
                </div>
                <div class="detail-grid profile-stats">
                    <div class="detail"><span>Laporan</span><b>{{ $stats['reports'] }}</b></div>
                    <div class="detail"><span>Menunggu review</span><b>{{ $stats['pending'] }}</b></div>
                    <div class="detail"><span>Agenda</span><b>{{ $stats['agendas'] }}</b></div>
                    <div class="detail"><span>Selesai</span><b>{{ $stats['done'] }}</b></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        @unless (request()->boolean('modal'))
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Kalender {{ $month->translatedFormat('F Y') }}</h2>
                    <p>Riwayat agenda dan laporan siswa</p>
                </div>
                <form method="GET" action="{{ route('students.show', $student) }}">
                    <input type="month" name="month" value="{{ $monthParam }}" class="input month-input" onchange="this.form.submit()">
                </form>
            </div>
            <div class="table-wrap" style="padding:0 14px 14px">
                <table class="calendar" style="font-size:11px">
                    <thead>
                        <tr>@foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)<th>{{ $day }}</th>@endforeach</tr>
                    </thead>
                    <tbody>
                        @foreach ($calendar['weeks'] as $week)
                            <tr>
                                @foreach ($week as $day)
                                    <td class="{{ $day['inMonth'] ? '' : 'other' }} {{ $day['isToday'] ? 'today' : '' }}" style="height:76px">
                                        <span class="day-num">{{ $day['date']->format('j') }}</span>
                                        @foreach (array_slice($day['agendas'], 0, 2) as $event)
                                            <a href="{{ route('agenda.index', ['date' => $day['date']->toDateString(), 'member' => $student->id]) }}"
                                               class="cal-event {{ $event->isDone() ? 'done' : '' }}" title="{{ $event->title }}">
                                                {{ $event->title }}
                                            </a>
                                        @endforeach
                                        @if (count($day['agendas']) > 2)
                                            <span class="tiny muted">+{{ count($day['agendas']) - 2 }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endunless

        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Riwayat laporan</h2>
                    <p>Urut dari yang terbaru</p>
                </div>
            </div>
            @if ($reports->isEmpty())
                <div class="empty"><div class="empty-icon"><x-icon name="file-text" /></div><b>Belum ada laporan</b><p style="margin-bottom:0">Laporan akan muncul setelah siswa mengirim agenda pagi.</p></div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Tanggal</th><th>Agenda</th><th>Status</th><th style="text-align:right">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td>
                                        <b>{{ $report->report_date->translatedFormat('d M Y') }}</b>
                                        <span class="cell-sub">{{ $report->summary_submitted_at?->format('H:i') ?? 'belum dikirim' }}</span>
                                    </td>
                                    <td><b>{{ $report->done_agendas_count }}/{{ $report->agendas_count }}</b></td>
                                    <td><span class="badge {{ $report->statusTone() }}">{{ $report->statusLabel() }}</span></td>
                                    <td>
                                        <div class="actions">
                                            <a href="{{ route('reviews.show', $report) }}" class="btn btn-ghost btn-sm" data-modal="{{ route('reviews.show', $report) }}" data-modal-size="lg">Buka</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-pagination :paginator="$reports" />
            @endif
        </div>
    </div>
@endsection
