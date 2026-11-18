@extends('layouts.app')

@section('title', 'Review Laporan')
@section('breadcrumb', 'Review laporan')

@php
    $done = $report->agendas->filter->isDone()->count();
    $canAct = $report->isSummarySubmitted() && ! $report->reviewed_at;
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $report->report_date->translatedFormat('l, d F Y') }}</p>
            <h1>Review {{ $report->user->name }}</h1>
            <p class="sub">
                {{ $report->agendas->count() }} agenda · {{ $done }} selesai ·
                ringkasan dikirim {{ $report->summary_submitted_at?->format('H:i') ?? 'belum' }}
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reviews.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" /> Antrean review</a>
            <a href="{{ route('students.show', $report->user) }}" class="btn btn-ghost" data-modal="{{ route('students.show', $report->user) }}" data-modal-size="lg"><x-icon name="user" /> Profil siswa</a>
        </div>
    </div>

    @if ($report->reviewed_at)
        <div class="alert success">
            <x-icon name="check-circle" />
            <span>Laporan ini sudah direview oleh {{ $report->reviewer?->name ?? 'pembimbing' }} pada {{ $report->reviewed_at->translatedFormat('d M Y, H:i') }} dan dikunci.</span>
        </div>
    @elseif ($report->status === 'revision')
        <div class="alert warning">
            <x-icon name="alert" />
            <span>Revisi sudah diminta. Siswa dapat mengirim ulang ringkasan setelah memperbaiki catatan di bawah.</span>
        </div>
    @elseif (! $report->isSummarySubmitted())
        <div class="alert info">
            <x-icon name="info" />
            <span>Siswa baru mengirim agenda pagi. Review dapat dilakukan setelah ringkasan sore dikirim.</span>
        </div>
    @endif

    <div class="grid-2">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div class="avatar-row">
                        <x-avatar :user="$report->user" size="lg" />
                        <div>
                            <b>{{ $report->user->name }}</b>
                            <small>{{ $report->user->role?->name }} · {{ $report->user->department ?? 'Tanpa departemen' }}</small>
                        </div>
                    </div>
                    <span class="badge {{ $report->statusTone() }}">{{ $report->statusLabel() }}</span>
                </div>
                <div class="card-body">
                    <div class="detail-grid" style="grid-template-columns:1fr 1fr">
                        <div class="detail"><span>Pembimbing</span><b>{{ $report->user->mentor?->name ?? 'Belum ditentukan' }}</b></div>
                        <div class="detail"><span>Agenda pagi</span><b>{{ $report->morning_submitted_at?->format('H:i') ?? '—' }}</b></div>
                        <div class="detail"><span>Ringkasan sore</span><b>{{ $report->summary_submitted_at?->format('H:i') ?? 'Belum' }}</b></div>
                        <div class="detail"><span>Progres</span><b>{{ $done }}/{{ $report->agendas->count() }} selesai</b></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div>
                        <h2>Agenda & hasil kerja</h2>
                        <p>Cek kesesuaian rencana dengan hasil yang dilaporkan</p>
                    </div>
                </div>
                @if ($report->agendas->isEmpty())
                    <div class="empty"><b>Belum ada agenda</b><p style="margin-bottom:0">Siswa belum menambahkan agenda pada laporan ini.</p></div>
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
                                        @if ($agenda->priority === 'high')
                                            <span class="badge danger">Prioritas tinggi</span>
                                        @endif
                                    </div>
                                    @if ($agenda->description)
                                        <p>{{ $agenda->description }}</p>
                                    @endif
                                    @if ($agenda->summary)
                                        <div class="summary-quote">{{ $agenda->summary }}</div>
                                    @else
                                        <p class="tiny muted" style="margin:6px 0 0">Belum ada catatan hasil.</p>
                                    @endif
                                    <div class="agenda-meta">
                                        <span>{{ $agenda->start_time ? \Illuminate\Support\Carbon::parse($agenda->start_time)->format('H:i') : '—' }}{{ $agenda->end_time ? '–'.\Illuminate\Support\Carbon::parse($agenda->end_time)->format('H:i') : '' }}</span>
                                        @if ($agenda->completed_at)
                                            <span class="meta-separator">·</span><span>Selesai {{ $agenda->completed_at->format('H:i') }}</span>
                                        @endif
                                        @can('manage-agenda-for', $agenda->user)
                                            <span class="meta-separator">·</span>
                                            <a href="{{ route('agenda.edit', $agenda) }}" class="text-link" data-modal="{{ route('agenda.edit', $agenda) }}">Ubah agenda</a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><div><h2>Keputusan review</h2><p>Setujui atau minta revisi dengan catatan</p></div></div>
                <div class="card-body">
                    @if ($report->review_note)
                        <div class="review-quote mb-3">{{ $report->review_note }}</div>
                    @endif

                    @if ($canAct)
                        <form method="POST" action="{{ route('reviews.update', $report) }}">
                            @csrf @method('PATCH')

                            <label class="field">
                                <span>Catatan untuk siswa</span>
                                <textarea name="review_note" class="textarea" maxlength="2000"
                                          placeholder="Tuliskan apresiasi, koreksi, atau arahan lanjutan…">{{ old('review_note') }}</textarea>
                                <span class="hint">Wajib diisi bila meminta revisi.</span>
                            </label>

                            <div class="flex gap-2" style="flex-wrap:wrap">
                                <button type="submit" name="action" value="approve" class="btn btn-primary flex-1">
                                    <x-icon name="check-circle" :size="14" /> Setujui & kunci
                                </button>
                                <button type="submit" name="action" value="revision" class="btn btn-ghost flex-1">
                                    <x-icon name="edit" :size="14" /> Minta revisi
                                </button>
                            </div>
                        </form>
                    @elseif ($report->reviewed_at)
                        <div class="split-list"><span class="muted">Status</span><b>Disetujui</b></div>
                        <div class="split-list"><span class="muted">Reviewer</span><b>{{ $report->reviewer?->name }}</b></div>
                        <div class="split-list"><span class="muted">Waktu</span><b>{{ $report->reviewed_at->translatedFormat('d M Y, H:i') }}</b></div>
                    @else
                        <p class="small muted" style="margin:0">Form review aktif setelah siswa mengirim ringkasan sore.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-head"><div><h2>Catatan keseluruhan siswa</h2><p>Ditulis saat mengirim ringkasan</p></div></div>
                <div class="card-body">
                    @if ($report->summary_note)
                        <div class="summary-quote" style="margin-top:0">{{ $report->summary_note }}</div>
                    @else
                        <p class="small muted" style="margin:0">Siswa tidak menambahkan catatan keseluruhan.</p>
                    @endif
                </div>
            </div>

            <div class="reminder-note">
                <x-icon name="shield" />
                <span>Persetujuan akan mengunci laporan dan agenda dari perubahan, serta mengirim notifikasi ke siswa.</span>
            </div>
        </div>
    </div>
@endsection
