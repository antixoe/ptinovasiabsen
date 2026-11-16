@extends('layouts.app')

@section('title', 'Audit Log')
@section('breadcrumb', 'Administrasi · Audit log')

@php
    $labels = [
        'auth.login' => 'Masuk',
        'auth.logout' => 'Keluar',
        'agenda.created' => 'Agenda dibuat',
        'agenda.updated' => 'Agenda diubah',
        'agenda.deleted' => 'Agenda dihapus',
        'agenda.completed' => 'Agenda selesai',
        'agenda.reopened' => 'Agenda dibuka',
        'report.morning_submitted' => 'Agenda pagi dikirim',
        'report.summary_submitted' => 'Ringkasan dikirim',
        'report.approved' => 'Laporan disetujui',
        'report.revision_requested' => 'Revisi diminta',
        'report.deleted' => 'Laporan dihapus',
        'user.created' => 'Anggota dibuat',
        'user.updated' => 'Anggota diubah',
        'user.deleted' => 'Anggota dihapus',
        'role.created' => 'Peran dibuat',
        'role.updated' => 'Peran diubah',
        'role.deleted' => 'Peran dihapus',
    ];
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Jejak aktivitas</p>
            <h1>Audit log</h1>
            <p class="sub">Rekam jejak perubahan data untuk keperluan evaluasi dan keamanan workspace.</p>
        </div>
        <div class="page-actions">
            <span class="badge info">{{ $logs->total() }} entri</span>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Aktivitas terbaru</h2>
                <p>Urut dari yang paling baru</p>
            </div>
        </div>

        <div class="card-body pad" style="padding-bottom:0">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="flex items-center gap-2" style="flex-wrap:wrap">
                <div class="search-box" style="width:210px">
                    <x-icon name="search" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari aksi / metadata…">
                </div>
                <select name="user" class="select" style="width:175px">
                    <option value="">Semua pengguna</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('user') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                <select name="action" class="select" style="width:190px">
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $labels[$action] ?? $action }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="input" style="width:145px">
                <input type="date" name="to" value="{{ request('to') }}" class="input" style="width:145px">
                <button type="submit" class="btn btn-ghost btn-sm"><x-icon name="filter" :size="13" /> Terapkan</button>
            </form>
        </div>

        @if ($logs->isEmpty())
            <div class="empty"><div class="empty-icon"><x-icon name="activity" /></div><b>Tidak ada aktivitas</b><p style="margin-bottom:0">Belum ada entri audit yang cocok dengan filter.</p></div>
        @else
            <div class="table-wrap mt-3">
                <table class="table">
                    <thead>
                        <tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Objek</th><th>Detail</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>
                                    <b class="mono" style="font-size:10px">{{ $log->created_at->format('d M Y') }}</b>
                                    <span class="cell-sub">{{ $log->created_at->format('H:i:s') }} · {{ $log->created_at->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <div class="row-main">
                                        @if ($log->user)
                                            <x-avatar :user="$log->user" size="sm" />
                                            <b>{{ $log->user->name }}</b>
                                        @else
                                            <span class="badge">Sistem</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="badge violet">{{ $labels[$log->action] ?? $log->action }}</span></td>
                                <td class="tiny muted">{{ class_basename($log->entity_type) }}{{ $log->entity_id ? ' #'.$log->entity_id : '' }}</td>
                                <td class="tiny" style="max-width:280px">
                                    @if ($log->metadata)
                                        <span style="color:#7b8597">
                                            @foreach ($log->metadata as $key => $value)
                                                <span class="mono" style="font-size:9px">{{ $key }}</span>: {{ is_array($value) ? json_encode($value) : $value }}@if (! $loop->last) · @endif
                                            @endforeach
                                        </span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="mono tiny muted">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$logs" />
        @endif
    </div>
@endsection
