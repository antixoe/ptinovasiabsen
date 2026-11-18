@extends('layouts.app')

@section('title', 'Agenda')
@section('breadcrumb', 'Agenda')

@php
    $prevDate = $date->copy()->subDay();
    $nextDate = $date->copy()->addDay();
    $isToday = $date->isToday();
    $canPickMember = $members->count() > 1;
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Rencana & progres kerja</p>
            <h1>Agenda {{ $isToday ? 'hari ini' : $date->translatedFormat('d F') }}</h1>
            <p class="sub">{{ $date->translatedFormat('l, d F Y') }} · {{ $summary['total'] }} agenda terlihat olehmu.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('agenda.calendar') }}" class="btn btn-ghost"><x-icon name="calendar" /> Kalender</a>
            <a href="{{ route('agenda.create', ['date' => $date->toDateString()]) }}" class="btn btn-primary" data-modal="{{ route('agenda.create', ['date' => $date->toDateString()]) }}"><x-icon name="plus" /> Tambah agenda</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body pad">
            <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
                <div class="flex items-center gap-2">
                    <a href="{{ route('agenda.index', array_merge(request()->query(), ['date' => $prevDate->toDateString()])) }}" class="icon-btn" aria-label="Hari sebelumnya"><x-icon name="chevron-left" /></a>
                    <div class="date-nav">
                        <b style="font-size:13px">{{ $date->translatedFormat('l, d F Y') }}</b>
                        <div class="tiny muted">{{ $isToday ? 'Hari ini' : $date->diffForHumans() }}</div>
                    </div>
                    <a href="{{ route('agenda.index', array_merge(request()->query(), ['date' => $nextDate->toDateString()])) }}" class="icon-btn" aria-label="Hari berikutnya"><x-icon name="chevron-right" /></a>
                    @unless ($isToday)
                        <a href="{{ route('agenda.index') }}" class="btn btn-ghost btn-sm">Hari ini</a>
                    @endunless
                </div>

                <form method="GET" action="{{ route('agenda.index') }}" class="filter-bar" data-autosubmit>
                    <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Cari agenda…">
                    <select name="status" class="select">
                        <option value="">Semua status</option>
                        @foreach (['planned' => 'Terjadwal', 'in_progress' => 'Berjalan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($canPickMember)
                        <select name="member" class="select">
                            <option value="">Semua anggota</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected((string) ($filters['member'] ?? '') === (string) $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button type="submit" class="btn btn-ghost btn-sm"><x-icon name="filter" :size="13" /> Filter</button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid-4 mb-4">
        <div class="stat"><div class="stat-top"><span class="stat-label">Total agenda</span><span class="stat-icon violet"><x-icon name="calendar" /></span></div><div class="stat-value">{{ $summary['total'] }}</div><div class="stat-meta">Terlihat untuk peranmu</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Selesai</span><span class="stat-icon green"><x-icon name="check-circle" /></span></div><div class="stat-value">{{ $summary['done'] }}</div><div class="stat-meta">Sudah dicentang</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Berjalan</span><span class="stat-icon blue"><x-icon name="activity" /></span></div><div class="stat-value">{{ $summary['running'] }}</div><div class="stat-meta">Sedang dikerjakan</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Terjadwal</span><span class="stat-icon amber"><x-icon name="clock" /></span></div><div class="stat-value">{{ $summary['planned'] }}</div><div class="stat-meta">Belum dimulai</div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Daftar agenda</h2>
                <p>Urut berdasarkan jam mulai</p>
            </div>
            <span class="badge violet">{{ $summary['total'] }} item</span>
        </div>

        @if ($agendas->isEmpty())
            <div class="empty">
                <div class="empty-icon"><x-icon name="calendar" /></div>
                <b>Tidak ada agenda pada tanggal ini</b>
                <p>Ubah filter tanggal, atau tambahkan agenda baru untuk hari tersebut.</p>
                <a href="{{ route('agenda.create', ['date' => $date->toDateString()]) }}" class="btn btn-primary btn-sm" data-modal="{{ route('agenda.create', ['date' => $date->toDateString()]) }}"><x-icon name="plus" :size="13" /> Tambah agenda</a>
            </div>
        @else
            <div class="card-body">
                @foreach ($agendas as $agenda)
                    <div class="agenda-item">
                        <div style="width:50px;flex:none;text-align:right">
                            <b class="mono" style="font-size:11px;color:#4e5b6d">{{ $agenda->start_time ? \Illuminate\Support\Carbon::parse($agenda->start_time)->format('H:i') : '—' }}</b>
                            @if ($agenda->end_time)
                                <span class="tiny muted" style="display:block">{{ \Illuminate\Support\Carbon::parse($agenda->end_time)->format('H:i') }}</span>
                            @endif
                        </div>

                        @can('manage-agenda-for', $agenda->user)
                            <form method="POST" action="{{ route('agenda.toggle', $agenda) }}" class="agenda-check" data-ajax>
                                @csrf @method('PATCH')
                                <button type="submit" class="check-circle {{ $agenda->isDone() ? 'checked' : '' }}" title="{{ $agenda->isDone() ? 'Tandai belum selesai' : 'Tandai selesai' }}">
                                    <x-icon name="check" />
                                </button>
                            </form>
                        @else
                            <span class="agenda-check">
                                <span class="check-circle {{ $agenda->isDone() ? 'checked' : '' }}"><x-icon name="check" /></span>
                            </span>
                        @endcan

                        <div class="agenda-main">
                            <div class="flex items-center gap-2" style="flex-wrap:wrap">
                                <b>{{ $agenda->title }}</b>
                                <span class="badge {{ $agenda->statusTone() }}">{{ $agenda->statusLabel() }}</span>
                                @if ($agenda->priority === 'high')
                                    <span class="badge danger">Prioritas tinggi</span>
                                @endif
                            </div>
                            @if ($agenda->description)
                                <p>{{ $agenda->description }}</p>
                            @endif
                            @if ($agenda->summary)
                                <div class="summary-quote">{{ $agenda->summary }}</div>
                            @endif
                            <div class="agenda-meta">
                                <x-avatar :user="$agenda->user" size="sm" />
                                <span>{{ $agenda->user->name }}</span>
                                @if ($agenda->user->role)
                                    <span class="meta-separator">·</span><span>{{ $agenda->user->role->name }}</span>
                                @endif
                                @if ($agenda->reviewed_at)
                                    <span class="meta-separator">·</span>
                                    <span>Direview {{ $agenda->reviewed_at->translatedFormat('d M') }}</span>
                                @endif
                            </div>
                        </div>

                        @can('manage-agenda-for', $agenda->user)
                            <div class="agenda-actions">
                                <a href="{{ route('agenda.edit', $agenda) }}" class="btn btn-ghost btn-sm" data-modal="{{ route('agenda.edit', $agenda) }}"><x-icon name="edit" :size="13" /> Ubah</a>
                                <form method="POST" action="{{ route('agenda.destroy', $agenda) }}" data-confirm="Hapus agenda “{{ $agenda->title }}”? Tindakan ini tidak bisa dibatalkan.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus agenda"><x-icon name="trash" :size="13" /></button>
                                </form>
                            </div>
                        @endcan
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
