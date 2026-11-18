@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('breadcrumb', 'Detail laporan')

@php
    $isOwner = $report->user_id === auth()->id();
    $canReview = auth()->user()->canReviewReports() && (auth()->user()->canViewAllReports() || $report->user->mentor_id === auth()->id());
    $done = $report->agendas->filter->isDone()->count();
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $report->report_date->translatedFormat('l, d F Y') }}</p>
            <h1>{{ $isOwner ? 'Laporan harianmu' : 'Laporan '.$report->user->name }}</h1>
            <p class="sub">{{ $report->agendas->count() }} agenda · {{ $done }} selesai · dikirim {{ $report->summary_submitted_at?->format('H:i') ?? '—' }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ $isOwner ? route('reports.index') : route('reviews.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" /> Kembali</a>
            @if ($canReview && $report->isSummarySubmitted())
                <a href="{{ route('reviews.show', $report) }}" class="btn btn-primary" data-modal="{{ route('reviews.show', $report) }}" data-modal-size="lg"><x-icon name="check-circle" /> Buka review</a>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body pad">
            <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
                <div class="avatar-row">
                    <x-avatar :user="$report->user" size="lg" />
                    <div>
                        <b>{{ $report->user->name }}</b>
                        <small>{{ $report->user->role?->name }}{{ $report->user->department ? ' · '.$report->user->department : '' }}</small>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge {{ $report->statusTone() }}">{{ $report->statusLabel() }}</span>
                    @if ($report->reviewed_at)
                        <span class="badge success">Direview {{ $report->reviewed_at->translatedFormat('d M, H:i') }}</span>
                    @endif
                </div>
            </div>

            <div class="detail-grid mt-4">
                <div class="detail"><span>Pembimbing</span><b>{{ $report->user->mentor?->name ?? 'Belum ditentukan' }}</b></div>
                <div class="detail"><span>Agenda pagi</span><b>{{ $report->morning_submitted_at?->format('H:i') ?? '—' }}</b></div>
                <div class="detail"><span>Ringkasan sore</span><b>{{ $report->summary_submitted_at?->format('H:i') ?? 'Belum dikirim' }}</b></div>
                <div class="detail"><span>Reviewer</span><b>{{ $report->reviewer?->name ?? 'Belum direview' }}</b></div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Agenda & hasil</h2>
                    <p>Rincian pekerjaan pada hari ini</p>
                </div>
                <span class="badge violet">{{ $done }}/{{ $report->agendas->count() }} selesai</span>
            </div>
            @if ($report->agendas->isEmpty())
                <div class="empty"><div class="empty-icon"><x-icon name="calendar" /></div><b>Belum ada agenda</b><p style="margin-bottom:0">Laporan ini belum memiliki item agenda.</p></div>
            @else
                <div class="card-body">
                    @foreach ($report->agendas as $agenda)
                        <div class="agenda-item">
                            <span class="agenda-check">
                                <span class="check-circle {{ $agenda->isDone() ? 'checked' : '' }}"><x-icon name="check" /></span>
                            </span>
                            <div class="agenda-main">
                                <div class="flex items-center gap-2" style="flex-wrap:wrap">
                                    <b>{{ $agenda->title }}</b>
                                    <span class="badge {{ $agenda->statusTone() }}">{{ $agenda->statusLabel() }}</span>
                                    <span class="badge">{{ $agenda->priorityLabel() }}</span>
                                </div>
                                @if ($agenda->description)
                                    <p>{{ $agenda->description }}</p>
                                @endif
                                @if ($agenda->summary)
                                    <div class="summary-quote">{{ $agenda->summary }}</div>
                                @endif
                                <div class="agenda-meta">
                                    <x-icon name="clock" />
                                    <span>{{ $agenda->start_time ? \Illuminate\Support\Carbon::parse($agenda->start_time)->format('H:i') : '—' }}{{ $agenda->end_time ? '–'.\Illuminate\Support\Carbon::parse($agenda->end_time)->format('H:i') : '' }}</span>
                                    @if ($agenda->reviewed_at)
                                        <span class="meta-separator">·</span>
                                        <span>Direview {{ $agenda->reviewed_at->translatedFormat('d M') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><div><h2>Catatan keseluruhan</h2><p>Ditulis saat ringkasan dikirim</p></div></div>
                <div class="card-body">
                    @if ($report->summary_note)
                        <div class="summary-quote" style="margin-top:0">{{ $report->summary_note }}</div>
                    @else
                        <p class="small muted" style="margin:0">Tidak ada catatan keseluruhan.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-head"><div><h2>Catatan review</h2><p>Dari pembimbing</p></div></div>
                <div class="card-body">
                    @if ($report->review_note)
                        <div class="review-quote">{{ $report->review_note }}</div>
                        <p class="tiny muted mt-2" style="margin-bottom:0">Oleh {{ $report->reviewer?->name ?? 'pembimbing' }} · {{ $report->reviewed_at?->translatedFormat('d M Y, H:i') ?? $report->updated_at->translatedFormat('d M Y, H:i') }}</p>
                    @elseif ($report->status === 'revision')
                        <div class="review-quote">Revisi diminta, namun catatan belum diisi.</div>
                    @else
                        <p class="small muted" style="margin:0">Belum ada catatan review.</p>
                    @endif
                </div>
            </div>

            @if ($isOwner && ! $report->reviewed_at)
                <form method="POST" action="{{ route('reports.destroy', $report) }}" data-confirm="Hapus laporan ini beserta seluruh agendanya?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block"><x-icon name="trash" :size="14" /> Hapus laporan</button>
                </form>
            @endif

            <div class="reminder-note">
                <x-icon name="shield" />
                <span>Setiap perubahan agenda dan review dicatat pada audit log untuk kebutuhan evaluasi.</span>
            </div>
        </div>
    </div>
@endsection
