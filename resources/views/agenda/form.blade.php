@extends('layouts.app')

@section('title', $agenda->exists ? 'Ubah Agenda' : 'Agenda Baru')
@section('breadcrumb', $agenda->exists ? 'Ubah agenda' : 'Agenda baru')

@php
    $editing = $agenda->exists;
    $start = old('start_time', $agenda->start_time ? \Illuminate\Support\Carbon::parse($agenda->start_time)->format('H:i') : '');
    $end = old('end_time', $agenda->end_time ? \Illuminate\Support\Carbon::parse($agenda->end_time)->format('H:i') : '');
    $dateValue = old('agenda_date', $agenda->agenda_date?->toDateString() ?? today()->toDateString());
    $canAssignOthers = $members->count() > 1;
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $editing ? 'Perbarui rencana' : 'Rencanakan pekerjaan' }}</p>
            <h1>{{ $editing ? 'Ubah agenda' : 'Agenda baru' }}</h1>
            <p class="sub">
                @if ($editing)
                    Perubahan akan tercatat pada audit log dan siswa akan diberi notifikasi bila diubah pembimbing.
                @else
                    Agenda yang dibuat untuk siswa oleh pembimbing akan otomatis mengirim notifikasi.
                @endif
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('agenda.index', ['date' => $dateValue]) }}" class="btn btn-ghost"><x-icon name="arrow-left" /> Kembali</a>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Detail agenda</h2>
                    <p>Isi informasi pekerjaan yang akan dilakukan</p>
                </div>
            </div>
            <div class="card-body pad">
                <form method="POST" action="{{ $editing ? route('agenda.update', $agenda) : route('agenda.store') }}">
                    @csrf
                    @if ($editing) @method('PUT') @endif

                    <div class="form-grid">
                        <label class="field">
                            <span>Pemilik agenda</span>
                            @if ($canAssignOthers)
                                <select name="user_id" class="select" required>
                                    @foreach ($members as $member)
                                        <option value="{{ $member->id }}" @selected((string) old('user_id', $agenda->user_id) === (string) $member->id)>
                                            {{ $member->name }}{{ $member->level ? ' · '.$member->level->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="input" value="{{ $members->first()?->name ?? auth()->user()->name }}" disabled>
                                <input type="hidden" name="user_id" value="{{ $members->first()?->id ?? auth()->id() }}">
                            @endif
                        </label>

                        <label class="field">
                            <span>Tanggal</span>
                            <input type="date" name="agenda_date" class="input" value="{{ $dateValue }}" required>
                        </label>

                        <label class="field span-2">
                            <span>Judul agenda</span>
                            <input type="text" name="title" class="input" value="{{ old('title', $agenda->title) }}"
                                   placeholder="Contoh: Review komponen dashboard kehadiran" maxlength="160" required>
                        </label>

                        <label class="field span-2">
                            <span>Deskripsi <em class="muted" style="font-style:normal;font-weight:400">(opsional)</em></span>
                            <textarea name="description" class="textarea" maxlength="2000"
                                      placeholder="Tujuan, output yang diharapkan, atau catatan dari pembimbing…">{{ old('description', $agenda->description) }}</textarea>
                        </label>

                        <label class="field">
                            <span>Jam mulai</span>
                            <input type="time" name="start_time" class="input" value="{{ $start }}">
                        </label>

                        <label class="field">
                            <span>Jam selesai</span>
                            <input type="time" name="end_time" class="input" value="{{ $end }}">
                        </label>

                        <label class="field">
                            <span>Prioritas</span>
                            <select name="priority" class="select" required>
                                @foreach (['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', $agenda->priority ?? 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="field">
                            <span>Status</span>
                            <select name="status" class="select" required>
                                @foreach (['planned' => 'Terjadwal', 'in_progress' => 'Berjalan', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $agenda->status ?? 'planned') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('agenda.index', ['date' => $dateValue]) }}" class="btn btn-ghost">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <x-icon :name="$editing ? 'check' : 'plus'" :size="14" />
                            {{ $editing ? 'Simpan perubahan' : 'Tambah agenda' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><div><h2>Alur agenda</h2><p>Bagaimana item ini dipakai</p></div></div>
                <div class="card-body">
                    <div class="split-list"><span class="flex items-center gap-2"><x-icon name="sun" :size="14" /> Pagi</span><b>Rencana dikirim ke pembimbing</b></div>
                    <div class="split-list"><span class="flex items-center gap-2"><x-icon name="activity" :size="14" /> Siang</span><b>Status dicentang saat selesai</b></div>
                    <div class="split-list"><span class="flex items-center gap-2"><x-icon name="moon" :size="14" /> Sore</span><b>Ringkasan dikirim sebelum 18.00</b></div>
                    <div class="split-list"><span class="flex items-center gap-2"><x-icon name="check-circle" :size="14" /> Review</span><b>Pembimbing menyetujui</b></div>
                </div>
            </div>

            <div class="reminder-note">
                <x-icon name="info" />
                <span>Agenda pada laporan yang sudah direview dikunci. Hanya superadmin yang masih dapat mengubahnya demi menjaga audit trail.</span>
            </div>

            @if ($editing && $agenda->reviewed_at)
                <div class="alert warning" style="margin:0">
                    <x-icon name="lock" />
                    <span>Agenda ini sudah direview pada {{ $agenda->reviewed_at->translatedFormat('d M Y, H:i') }}.</span>
                </div>
            @endif
        </div>
    </div>
@endsection
