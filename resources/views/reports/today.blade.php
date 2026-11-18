@extends('layouts.app')

@section('title', 'Laporan Hari Ini')
@section('breadcrumb', 'Laporan hari ini')

@php
    $morningSubmitted = $report?->isMorningSubmitted() ?? false;
    $summarySubmitted = $report?->isSummarySubmitted() ?? false;
    $locked = (bool) $report?->reviewed_at;
    $isRevision = $report?->status === 'revision';
    $pastDeadline = now()->greaterThan($deadline);
    $oldItems = old('items', []);
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ today()->translatedFormat('l, d F Y') }}</p>
            <h1>{{ $summarySubmitted ? 'Laporan hari ini' : ($morningSubmitted ? 'Ringkasan sore' : 'Agenda pagi') }}</h1>
            <p class="sub">
                @if ($summarySubmitted)
                    Ringkasan sudah dikirim {{ $report->summary_submitted_at->format('H:i') }} dan menunggu pembimbing.
                @elseif ($morningSubmitted)
                    Agenda pagi terkirim {{ $report->morning_submitted_at->format('H:i') }}. Lengkapi hasil sebelum {{ $deadline->format('H:i') }}.
                @else
                    Tuliskan rencana kerja hari ini, lalu kirim ke pembimbing.
                @endif
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reports.index') }}" class="btn btn-ghost"><x-icon name="book" /> Riwayat laporan</a>
            @if ($summarySubmitted && $report)
                <a href="{{ route('reports.show', $report) }}" class="btn btn-primary" data-modal="{{ route('reports.show', $report) }}" data-modal-size="lg"><x-icon name="eye" /> Lihat laporan</a>
            @endif
        </div>
    </div>

    @unless (request()->boolean('modal'))
    <div class="insight-strip">
        <span class="insight-icon">
            <x-icon :name="$summarySubmitted ? 'check-circle' : ($morningSubmitted ? 'moon' : 'sun')" :size="16" />
        </span>
        <div class="flex-1">
            <b>{{ $summarySubmitted ? 'Terkirim' : ($morningSubmitted ? 'Batas pengiriman '.$deadline->format('H:i') : 'Mulai hari dengan rencana') }}</b>
            <span>
                @if ($summarySubmitted)
                    Pembimbing akan mereview laporanmu. Kamu masih bisa memperbaiki selama belum direview.
                @elseif ($morningSubmitted)
                    @if ($pastDeadline)
                        Waktu pengingat sudah lewat — segera kirim ringkasan sebelum hari berganti.
                    @else
                        Centang agenda yang selesai, isi catatan hasil, dan tambahkan pekerjaan yang muncul di luar rencana.
                    @endif
                @else
                    Agenda pagi yang kamu kirim akan muncul di antrean pembimbing dan menjadi dasar ringkasan sore.
                @endif
            </span>
        </div>
        <span class="strip-divider"></span>
        <span class="badge {{ $report?->statusTone() ?? 'neutral' }}">{{ $report?->statusLabel() ?? 'Belum diisi' }}</span>
    </div>
    @endunless

    @if ($isRevision && $report?->review_note)
        <div class="alert warning">
            <x-icon name="alert" />
            <span><b>Pembimbing meminta revisi:</b> {{ $report->review_note }}</span>
        </div>
    @endif

    @if ($locked)
        <div class="alert info">
            <x-icon name="lock" />
            <span>Laporan ini sudah direview pada {{ $report->reviewed_at->translatedFormat('d M Y, H:i') }} dan dikunci dari perubahan.</span>
        </div>
    @endif

    @if ($errors->any() && $morningSubmitted)
        <div class="alert error">
            <x-icon name="alert" />
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if (! $morningSubmitted)
        {{-- Morning agenda form --}}
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Rencana kerja pagi</h2>
                    <p>Minimal satu agenda. Kamu bisa menambah hingga beberapa item.</p>
                </div>
                <span class="badge warning">Belum dikirim</span>
            </div>
            <div class="card-body pad">
                <form method="POST" action="{{ route('reports.morning') }}">
                    @csrf
                    <div id="agenda-rows">
                        @php
                            $initialRows = count($oldItems) ? $oldItems : [[]];
                        @endphp
                        @foreach ($initialRows as $index => $item)
                            <div class="agenda-form-row" data-agenda-row style="border:1px solid #eef0f5;border-radius:10px;padding:14px;margin-bottom:12px;background:#fcfcfe">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="badge violet">Agenda <span data-row-number>{{ $index + 1 }}</span></span>
                                    <button type="button" class="btn btn-danger btn-sm" data-remove-row><x-icon name="trash" :size="12" /> Hapus</button>
                                </div>
                                <div class="form-grid">
                                    <label class="field span-2">
                                        <span>Judul agenda</span>
                                        <input type="text" name="items[{{ $index }}][title]" class="input" maxlength="160"
                                               value="{{ data_get($item, 'title') }}" placeholder="Contoh: Review komponen dashboard" required>
                                    </label>
                                    <label class="field span-2">
                                        <span>Deskripsi <em class="muted" style="font-style:normal;font-weight:400">(opsional)</em></span>
                                        <textarea name="items[{{ $index }}][description]" class="textarea" style="min-height:64px" maxlength="1000"
                                                  placeholder="Output yang diharapkan…">{{ data_get($item, 'description') }}</textarea>
                                    </label>
                                    <label class="field">
                                        <span>Jam mulai</span>
                                        <input type="time" name="items[{{ $index }}][start_time]" class="input" value="{{ data_get($item, 'start_time') }}">
                                    </label>
                                    <label class="field">
                                        <span>Jam selesai</span>
                                        <input type="time" name="items[{{ $index }}][end_time]" class="input" value="{{ data_get($item, 'end_time') }}">
                                    </label>
                                    <label class="field">
                                        <span>Prioritas</span>
                                        <select name="items[{{ $index }}][priority]" class="select">
                                            @foreach (['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi'] as $value => $label)
                                                <option value="{{ $value }}" @selected(data_get($item, 'priority', 'medium') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <template id="agenda-row-template">
                        <div class="agenda-form-row" data-agenda-row style="border:1px solid #eef0f5;border-radius:10px;padding:14px;margin-bottom:12px;background:#fcfcfe">
                            <div class="flex items-center justify-between mb-2">
                                <span class="badge violet">Agenda <span data-row-number></span></span>
                                <button type="button" class="btn btn-danger btn-sm" data-remove-row><x-icon name="trash" :size="12" /> Hapus</button>
                            </div>
                            <div class="form-grid">
                                <label class="field span-2">
                                    <span>Judul agenda</span>
                                    <input type="text" name="items[__INDEX__][title]" class="input" maxlength="160" placeholder="Contoh: Review komponen dashboard" required>
                                </label>
                                <label class="field span-2">
                                    <span>Deskripsi <em class="muted" style="font-style:normal;font-weight:400">(opsional)</em></span>
                                    <textarea name="items[__INDEX__][description]" class="textarea" style="min-height:64px" maxlength="1000" placeholder="Output yang diharapkan…"></textarea>
                                </label>
                                <label class="field">
                                    <span>Jam mulai</span>
                                    <input type="time" name="items[__INDEX__][start_time]" class="input">
                                </label>
                                <label class="field">
                                    <span>Jam selesai</span>
                                    <input type="time" name="items[__INDEX__][end_time]" class="input">
                                </label>
                                <label class="field">
                                    <span>Prioritas</span>
                                    <select name="items[__INDEX__][priority]" class="select">
                                        <option value="low">Rendah</option>
                                        <option value="medium" selected>Sedang</option>
                                        <option value="high">Tinggi</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </template>

                    <div class="flex items-center justify-between gap-3" style="flex-wrap:wrap">
                        <button type="button" id="add-agenda-row" class="btn btn-soft"><x-icon name="plus" :size="14" /> Tambah baris agenda</button>
                        <button type="submit" class="btn btn-primary"><x-icon name="send" :size="14" /> Kirim agenda pagi</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- Evening summary --}}
        <div class="grid-2">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h2>{{ $summarySubmitted ? 'Hasil kerja hari ini' : 'Isi ringkasan sore' }}</h2>
                        <p>{{ $report->agendas->count() }} agenda dari rencana pagi</p>
                    </div>
                    <span class="badge {{ $report->statusTone() }}">{{ $report->statusLabel() }}</span>
                </div>
                <div class="card-body pad">
                    <form method="POST" action="{{ route('reports.summary') }}">
                        @csrf

                        @foreach ($report->agendas as $agenda)
                            <div style="border:1px solid #eef0f5;border-radius:10px;padding:13px;margin-bottom:11px;background:#fcfcfe">
                                <div class="flex items-center justify-between gap-2" style="flex-wrap:wrap">
                                    <label class="check">
                                        <input type="hidden" name="items[{{ $agenda->id }}][done]" value="0">
                                        <input type="checkbox" name="items[{{ $agenda->id }}][done]" value="1"
                                               @checked($agenda->isDone() || old('items.'.$agenda->id.'.done')) @disabled($locked)>
                                        <span><b style="font-size:12px;color:#2e3b52">{{ $agenda->title }}</b></span>
                                    </label>
                                    <div class="flex gap-2 items-center">
                                        <span class="badge {{ $agenda->statusTone() }}">{{ $agenda->statusLabel() }}</span>
                                        @if ($agenda->start_time)
                                            <span class="tiny muted">{{ \Illuminate\Support\Carbon::parse($agenda->start_time)->format('H:i') }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if ($agenda->description)
                                    <p class="tiny muted" style="margin:6px 0 0">{{ $agenda->description }}</p>
                                @endif
                                <label class="field" style="margin:9px 0 0">
                                    <span>Catatan hasil <em class="muted" style="font-style:normal;font-weight:400">(opsional)</em></span>
                                    <textarea name="items[{{ $agenda->id }}][summary]" class="textarea" style="min-height:60px" maxlength="2000"
                                              placeholder="Apa yang selesai, kendala, atau hasil yang didapat…" @disabled($locked)>{{ old('items.'.$agenda->id.'.summary', $agenda->summary) }}</textarea>
                                </label>
                            </div>
                        @endforeach

                        @unless ($locked)
                            <div class="card" style="border-style:dashed;box-shadow:none;margin-bottom:14px">
                                <div class="card-body pad">
                                    <div class="flex items-center justify-between mb-2">
                                        <b style="font-size:12px">Pekerjaan tambahan</b>
                                        <span class="tiny muted">Di luar rencana pagi</span>
                                    </div>
                                    <div id="extra-rows"></div>
                                    <template id="extra-row-template">
                                        <div class="form-grid" data-extra-row style="gap:0 10px;margin-bottom:8px">
                                            <label class="field span-2" style="margin-bottom:6px">
                                                <input type="text" name="new_items[__INDEX__][title]" class="input" placeholder="Judul pekerjaan tambahan" maxlength="160">
                                            </label>
                                            <label class="field span-2" style="margin-bottom:8px">
                                                <textarea name="new_items[__INDEX__][summary]" class="textarea" style="min-height:54px" placeholder="Catatan hasil…" maxlength="2000"></textarea>
                                            </label>
                                            <button type="button" class="text-link" data-remove-row style="text-align:left;margin-bottom:8px">Hapus baris</button>
                                        </div>
                                    </template>
                                    <button type="button" id="add-extra-row" class="btn btn-soft btn-sm"><x-icon name="plus" :size="12" /> Tambah pekerjaan</button>
                                </div>
                            </div>

                            <label class="field">
                                <span>Catatan keseluruhan <em class="muted" style="font-style:normal;font-weight:400">(opsional)</em></span>
                                <textarea name="summary_note" class="textarea" maxlength="2000"
                                          placeholder="Ringkasan singkat, kendala, atau rencana besok…">{{ old('summary_note', $report->summary_note) }}</textarea>
                            </label>
                        @endunless

                        @if ($locked)
                            <div class="review-quote">{{ $report->review_note ?: 'Tidak ada catatan review.' }}</div>
                        @else
                            <button type="submit" class="btn btn-primary btn-block">
                                <x-icon name="send" :size="14" /> {{ $summarySubmitted ? 'Perbarui ringkasan' : 'Kirim ringkasan ke pembimbing' }}
                            </button>
                            <p class="tiny muted" style="text-align:center;margin:8px 0 0">Terakhir dikirim {{ $report->summary_submitted_at?->format('H:i') ?? '—' }} · dapat diubah sampai direview.</p>
                        @endif
                    </form>
                </div>
            </div>

            @unless (request()->boolean('modal'))
            <div class="stack">
                <div class="card">
                    <div class="card-head"><div><h2>Status laporan</h2><p>Alur pengiriman hari ini</p></div></div>
                    <div class="card-body">
                        <div class="split-list">
                            <span class="flex items-center gap-2"><x-icon name="sun" :size="14" /> Agenda pagi</span>
                            <b>{{ $report->morning_submitted_at?->format('H:i') ?? '—' }}</b>
                        </div>
                        <div class="split-list">
                            <span class="flex items-center gap-2"><x-icon name="moon" :size="14" /> Ringkasan sore</span>
                            <b>{{ $report->summary_submitted_at?->format('H:i') ?? 'Belum dikirim' }}</b>
                        </div>
                        <div class="split-list">
                            <span class="flex items-center gap-2"><x-icon name="check-circle" :size="14" /> Review</span>
                            <b>{{ $report->reviewed_at?->translatedFormat('d M, H:i') ?? 'Menunggu pembimbing' }}</b>
                        </div>
                        <div class="split-list">
                            <span class="flex items-center gap-2"><x-icon name="user" :size="14" /> Pembimbing</span>
                            <b>{{ $report->user->mentor?->name ?? auth()->user()->mentor?->name ?? 'Belum ditentukan' }}</b>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head"><div><h2>Ringkasan agenda</h2><p>Status seluruh item hari ini</p></div></div>
                    <div class="card-body">
                        <div class="split-list"><span class="flex items-center gap-2"><x-icon name="check-circle" :size="14" /> Selesai</span><b>{{ $report->agendas->filter->isDone()->count() }} item</b></div>
                        <div class="split-list"><span class="flex items-center gap-2"><x-icon name="activity" :size="14" /> Berjalan</span><b>{{ $report->agendas->where('status', 'in_progress')->count() }} item</b></div>
                        <div class="split-list"><span class="flex items-center gap-2"><x-icon name="clock" :size="14" /> Terjadwal</span><b>{{ $report->agendas->where('status', 'planned')->count() }} item</b></div>
                        <div class="split-list"><span class="flex items-center gap-2"><x-icon name="x" :size="14" /> Dibatalkan</span><b>{{ $report->agendas->where('status', 'cancelled')->count() }} item</b></div>
                    </div>
                </div>

                <div class="reminder-note">
                    <x-icon name="bell" />
                    <span>Pengingat otomatis dikirim setiap pukul 18.00 bila ringkasan belum masuk.</span>
                </div>
            </div>
            @endunless
        </div>
    @endif
@endsection
