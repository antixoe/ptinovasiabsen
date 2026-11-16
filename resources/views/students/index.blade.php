@extends('layouts.app')

@section('title', 'Siswa Bimbingan')
@section('breadcrumb', 'Siswa bimbingan')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Bimbingan PKL</p>
            <h1>Siswa bimbingan</h1>
            <p class="sub">Pantau kepatuhan pengisian agenda dan progres harian siswa yang kamu bimbing.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reviews.index') }}" class="btn btn-primary"><x-icon name="check-circle" /> Review laporan</a>
        </div>
    </div>

    @if ($students->isEmpty())
        <div class="card">
            <div class="empty">
                <div class="empty-icon"><x-icon name="users" /></div>
                <b>Belum ada siswa bimbingan</b>
                <p>Hubungi admin untuk menetapkan siswa PKL dengan pembimbing akunmu.</p>
            </div>
        </div>
    @else
        <div class="grid-3">
            @foreach ($students as $student)
                @php $todayReport = $todayReports->get($student->id); @endphp
                <div class="card">
                    <div class="card-body pad">
                        <div class="flex items-center justify-between">
                            <x-avatar :user="$student" size="lg" />
                            <span class="badge {{ $todayReport?->statusTone() ?? 'neutral' }}">{{ $todayReport?->statusLabel() ?? 'Belum ada laporan' }}</span>
                        </div>
                        <div class="mt-3">
                            <b style="font-size:13px;color:#26324a">{{ $student->name }}</b>
                            <div class="tiny muted">{{ $student->department ?? 'Siswa PKL' }} · {{ $student->role?->name }}</div>
                        </div>
                        <div class="detail-grid mt-3" style="grid-template-columns:1fr 1fr">
                            <div class="detail"><span>Menunggu review</span><b>{{ $student->pending_reports_count }}</b></div>
                            <div class="detail"><span>Sudah direview</span><b>{{ $student->reviewed_reports_count }}</b></div>
                        </div>
                        <div class="split-list mt-2">
                            <span class="muted">Agenda hari ini</span>
                            <b>{{ $todayReport?->agendas()->count() ?? 0 }} item</b>
                        </div>
                        <div class="split-list">
                            <span class="muted">Ringkasan</span>
                            <b>{{ $todayReport?->summary_submitted_at?->format('H:i') ?? 'Belum' }}</b>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <a href="{{ route('students.show', $student) }}" class="btn btn-ghost btn-sm flex-1">Profil</a>
                            @if ($todayReport)
                                <a href="{{ route('reviews.show', $todayReport) }}" class="btn btn-soft btn-sm flex-1">Buka laporan</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
