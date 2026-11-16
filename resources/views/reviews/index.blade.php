@extends('layouts.app')

@section('title', 'Review Laporan')
@section('breadcrumb', 'Review laporan')

@php
    $status = request('status');
    $monthOptions = collect(range(0, 5))->map(fn ($offset) => today()->startOfMonth()->subMonths($offset));
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Antrean pembimbing</p>
            <h1>Review laporan</h1>
            <p class="sub">Periksa ringkasan kerja siswa, beri persetujuan atau minta revisi.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('students.index') }}" class="btn btn-ghost"><x-icon name="users" /> Siswa bimbingan</a>
        </div>
    </div>

    <div class="grid-3 mb-4">
        <div class="stat">
            <div class="stat-top"><span class="stat-label">Menunggu review</span><span class="stat-icon amber"><x-icon name="clock" /></span></div>
            <div class="stat-value">{{ $counts['pending'] }}<small> laporan</small></div>
            <div class="stat-meta">Ringkasan sudah dikirim siswa</div>
        </div>
        <div class="stat">
            <div class="stat-top"><span class="stat-label">Perlu revisi</span><span class="stat-icon rose"><x-icon name="alert" /></span></div>
            <div class="stat-value">{{ $counts['revision'] }}<small> laporan</small></div>
            <div class="stat-meta">Menunggu perbaikan siswa</div>
        </div>
        <div class="stat">
            <div class="stat-top"><span class="stat-label">Sudah direview</span><span class="stat-icon green"><x-icon name="check-circle" /></span></div>
            <div class="stat-value">{{ $counts['reviewed'] }}<small> laporan</small></div>
            <div class="stat-meta">Total laporan disetujui</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Daftar laporan</h2>
                <p>{{ $reports->total() }} laporan ditemukan</p>
            </div>
        </div>

        <div class="card-body pad" style="padding-bottom:0">
            <div class="tabs">
                <a href="{{ route('reviews.index') }}" class="tab {{ ! $status ? 'active' : '' }}">Semua</a>
                <a href="{{ route('reviews.index', ['status' => 'summary']) }}" class="tab {{ $status === 'summary' ? 'active' : '' }}">Menunggu review</a>
                <a href="{{ route('reviews.index', ['status' => 'revision']) }}" class="tab {{ $status === 'revision' ? 'active' : '' }}">Perlu revisi</a>
                <a href="{{ route('reviews.index', ['status' => 'morning']) }}" class="tab {{ $status === 'morning' ? 'active' : '' }}">Agenda pagi</a>
                <a href="{{ route('reviews.index', ['status' => 'reviewed']) }}" class="tab {{ $status === 'reviewed' ? 'active' : '' }}">Sudah direview</a>
            </div>

            <form method="GET" action="{{ route('reviews.index') }}" class="flex items-center gap-2 mb-4" style="flex-wrap:wrap" data-autosubmit>
                @if ($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <select name="member" class="select" style="width:190px">
                    <option value="">Semua siswa</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected((string) request('member') === (string) $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                <select name="month" class="select" style="width:160px">
                    <option value="">Semua bulan</option>
                    @foreach ($monthOptions as $option)
                        <option value="{{ $option->format('Y-m') }}" @selected(request('month') === $option->format('Y-m'))>{{ $option->translatedFormat('F Y') }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-ghost btn-sm">Terapkan</button></noscript>
            </form>
        </div>

        @if ($reports->isEmpty())
            <div class="empty">
                <div class="empty-icon"><x-icon name="check-circle" /></div>
                <b>Tidak ada laporan pada filter ini</b>
                <p>Antrean review akan muncul ketika siswa mengirim agenda atau ringkasan.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Tanggal</th>
                            <th>Agenda</th>
                            <th>Terkirim</th>
                            <th>Status</th>
                            <th style="text-align:right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr>
                                <td>
                                    <div class="row-main">
                                        <x-avatar :user="$report->user" size="sm" />
                                        <div>
                                            <b>{{ $report->user->name }}</b>
                                            <span class="cell-sub">{{ $report->user->department ?? 'Siswa PKL' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <b>{{ $report->report_date->translatedFormat('d M Y') }}</b>
                                    <span class="cell-sub">{{ $report->report_date->translatedFormat('l') }}</span>
                                </td>
                                <td><b>{{ $report->agendas_count }}</b> <span class="muted">item</span></td>
                                <td>
                                    @if ($report->summary_submitted_at)
                                        <b>{{ $report->summary_submitted_at->format('H:i') }}</b>
                                        <span class="cell-sub">ringkasan</span>
                                    @elseif ($report->morning_submitted_at)
                                        <b>{{ $report->morning_submitted_at->format('H:i') }}</b>
                                        <span class="cell-sub">agenda pagi</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $report->statusTone() }}">{{ $report->statusLabel() }}</span></td>
                                <td>
                                    <div class="actions">
                                        <a href="{{ route('reviews.show', $report) }}" class="btn btn-soft btn-sm">
                                            {{ $report->status === 'summary' ? 'Review' : 'Detail' }}
                                        </a>
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
@endsection
