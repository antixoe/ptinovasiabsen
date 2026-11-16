@extends('layouts.app')

@section('title', 'Laporan Harian')
@section('breadcrumb', 'Laporan harian')

@php
    $prevMonth = $month->copy()->subMonth()->format('Y-m');
    $nextMonth = $month->copy()->addMonth()->format('Y-m');
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Riwayat pekerjaan</p>
            <h1>Laporan harian</h1>
            <p class="sub">Semua agenda pagi dan ringkasan sore yang kamu kirim ke pembimbing.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('reports.today') }}" class="btn btn-primary"><x-icon name="file-text" /> Laporan hari ini</a>
        </div>
    </div>

    <div class="grid-4 mb-4">
        <div class="stat"><div class="stat-top"><span class="stat-label">Laporan bulan ini</span><span class="stat-icon violet"><x-icon name="file-text" /></span></div><div class="stat-value">{{ $monthStats['reports'] }}</div><div class="stat-meta">{{ $month->translatedFormat('F Y') }}</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Terkirim</span><span class="stat-icon blue"><x-icon name="send" /></span></div><div class="stat-value">{{ $monthStats['submitted'] }}</div><div class="stat-meta">Sudah ada ringkasan</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Direview</span><span class="stat-icon green"><x-icon name="check-circle" /></span></div><div class="stat-value">{{ $monthStats['reviewed'] }}</div><div class="stat-meta">Disetujui pembimbing</div></div>
        <div class="stat"><div class="stat-top"><span class="stat-label">Perlu revisi</span><span class="stat-icon rose"><x-icon name="alert" /></span></div><div class="stat-value">{{ $monthStats['revision'] }}</div><div class="stat-meta">Ada catatan pembimbing</div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Riwayat {{ $month->translatedFormat('F Y') }}</h2>
                <p>Klik laporan untuk melihat detail agenda dan hasilnya.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.index', ['month' => $prevMonth]) }}" class="icon-btn" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" /></a>
                <form method="GET" action="{{ route('reports.index') }}">
                    <select name="month" class="select" onchange="this.form.submit()" style="width:150px">
                        @foreach (range(0, 11) as $offset)
                            @php $option = today()->startOfMonth()->subMonths($offset); @endphp
                            <option value="{{ $option->format('Y-m') }}" @selected($option->format('Y-m') === $month->format('Y-m'))>{{ $option->translatedFormat('F Y') }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('reports.index', ['month' => $nextMonth]) }}" class="icon-btn" aria-label="Bulan berikutnya"><x-icon name="chevron-right" /></a>
            </div>
        </div>

        @if ($reports->isEmpty())
            <div class="empty">
                <div class="empty-icon"><x-icon name="file-text" /></div>
                <b>Belum ada laporan di bulan ini</b>
                <p>Laporan terbentuk saat kamu mengirim agenda pagi pada halaman laporan hari ini.</p>
                <a href="{{ route('reports.today') }}" class="btn btn-primary btn-sm">Isi laporan hari ini</a>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Agenda</th>
                            <th>Ringkasan</th>
                            <th>Status</th>
                            <th style="text-align:right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            <tr>
                                <td>
                                    <b>{{ $report->report_date->translatedFormat('d M Y') }}</b>
                                    <span class="cell-sub">{{ $report->report_date->translatedFormat('l') }}</span>
                                </td>
                                <td>
                                    <b>{{ $report->done_agendas_count }}/{{ $report->agendas_count }}</b>
                                    <span class="cell-sub">agenda selesai</span>
                                </td>
                                <td>
                                    @if ($report->summary_submitted_at)
                                        <b>{{ $report->summary_submitted_at->format('H:i') }}</b>
                                        <span class="cell-sub">dikirim</span>
                                    @else
                                        <span class="badge warning">Belum dikirim</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $report->statusTone() }}">{{ $report->statusLabel() }}</span></td>
                                <td>
                                    <div class="actions">
                                        <a href="{{ route('reports.show', $report) }}" class="btn btn-ghost btn-sm"><x-icon name="eye" :size="13" /> Detail</a>
                                        @if (! $report->reviewed_at)
                                            <a href="{{ route('reports.today') }}" class="btn btn-soft btn-sm">Isi</a>
                                        @endif
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
