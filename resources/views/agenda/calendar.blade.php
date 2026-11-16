@extends('layouts.app')

@section('title', 'Kalender')
@section('breadcrumb', 'Kalender')

@php
    $month = $calendar['month'];
    $prev = $month->subMonth()->format('Y-m');
    $next = $month->addMonth()->format('Y-m');
    $current = $month->format('Y-m');
    $range = session('range') ?? null;
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Riwayat & rencana</p>
            <h1>Kalender agenda</h1>
            <p class="sub">Lihat sebaran agenda per tanggal untuk dirimu{{ $memberId ? ' dan anggota terpilih' : '' }}.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('agenda.index') }}" class="btn btn-ghost"><x-icon name="file-text" /> Mode daftar</a>
            <a href="{{ route('agenda.create') }}" class="btn btn-primary"><x-icon name="plus" /> Tambah agenda</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body pad">
            <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
                <div class="flex items-center gap-2">
                    <a href="{{ route('agenda.calendar', array_filter(['month' => $prev, 'member' => $memberId])) }}" class="icon-btn" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" /></a>
                    <div style="min-width:170px;text-align:center">
                        <b style="font-size:13px">{{ $month->translatedFormat('F Y') }}</b>
                        <div class="tiny muted">{{ $month->isCurrentMonth() ? 'Bulan berjalan' : 'Arsip agenda' }}</div>
                    </div>
                    <a href="{{ route('agenda.calendar', array_filter(['month' => $next, 'member' => $memberId])) }}" class="icon-btn" aria-label="Bulan berikutnya"><x-icon name="chevron-right" /></a>
                    @unless ($month->isCurrentMonth())
                        <a href="{{ route('agenda.calendar') }}" class="btn btn-ghost btn-sm">Bulan ini</a>
                    @endunless
                </div>

                @if ($members->count() > 1)
                    <form method="GET" action="{{ route('agenda.calendar') }}" class="flex items-center gap-2" data-autosubmit>
                        <input type="hidden" name="month" value="{{ $current }}">
                        <select name="member" class="select" style="width:210px">
                            <option value="">Semua anggota yang terlihat</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected((string) $memberId === (string) $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="btn btn-ghost btn-sm">Terapkan</button></noscript>
                    </form>
                @endif
            </div>
        </div>

        <div class="table-wrap" style="padding:0 16px 16px">
            <table class="calendar">
                <thead>
                    <tr>
                        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                            <th>{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($calendar['weeks'] as $week)
                        <tr>
                            @foreach ($week as $day)
                                <td class="{{ $day['inMonth'] ? '' : 'other' }} {{ $day['isToday'] ? 'today' : '' }}">
                                    <a href="{{ route('agenda.index', ['date' => $day['date']->toDateString(), 'member' => $memberId]) }}" style="display:block">
                                        <span class="day-num">{{ $day['date']->format('j') }}</span>
                                    </a>
                                    @foreach (array_slice($day['agendas'], 0, 3) as $event)
                                        @php
                                            $eventClass = match (true) {
                                                $event->status === 'reviewed' => 'reviewed',
                                                $event->isDone() => 'done',
                                                $event->status === 'cancelled' => 'muted-event',
                                                $event->status === 'in_progress' => 'warn',
                                                default => '',
                                            };
                                        @endphp
                                        <a href="{{ route('agenda.index', ['date' => $day['date']->toDateString(), 'member' => $event->user_id]) }}"
                                           class="cal-event {{ $eventClass }}"
                                           title="{{ $event->start_time ? \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i').' · ' : '' }}{{ $event->title }} — {{ $event->user->name }}">
                                            {{ $event->title }}
                                        </a>
                                    @endforeach
                                    @if (count($day['agendas']) > 3)
                                        <span class="tiny muted" style="display:block;padding:3px 5px">+{{ count($day['agendas']) - 3 }} lainnya</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-foot flex items-center gap-3" style="flex-wrap:wrap">
            <span class="tiny muted">Keterangan:</span>
            <span class="badge info">Berjalan</span>
            <span class="badge success">Selesai</span>
            <span class="badge violet">Direview</span>
            <span class="badge">Dibatalkan</span>
        </div>
    </div>
@endsection
