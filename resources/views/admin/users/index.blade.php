@extends('layouts.app')

@section('title', 'Anggota')
@section('breadcrumb', 'Administrasi · Anggota')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administrasi workspace</p>
            <h1>Anggota</h1>
            <p class="sub">Kelola akun, peran, level, dan penugasan pembimbing siswa PKL.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost"><x-icon name="shield" /> Peran & izin</a>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary" data-modal="{{ route('admin.users.create') }}" data-modal-size="lg"><x-icon name="plus" /> Tambah anggota</a>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Daftar anggota</h2>
                <p>{{ $users->total() }} akun terdaftar</p>
            </div>
        </div>

        <div class="card-body pad" style="padding-bottom:0">
            <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar" data-autosubmit>
                <div class="search-box">
                    <x-icon name="search" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email…">
                </div>
                <select name="role" class="select">
                    <option value="">Semua peran</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) request('role') === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <select name="level" class="select">
                    <option value="">Semua level</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected((string) request('level') === (string) $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="select">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
                <noscript><button type="submit" class="btn btn-ghost btn-sm">Terapkan</button></noscript>
            </form>
        </div>

        @if ($users->isEmpty())
            <div class="empty"><div class="empty-icon"><x-icon name="users" /></div><b>Tidak ada anggota</b><p style="margin-bottom:0">Ubah filter atau tambahkan anggota baru.</p></div>
        @else
            <div class="table-wrap mt-3">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Anggota</th>
                            <th>Peran</th>
                            <th>Level</th>
                            <th>Pembimbing</th>
                            <th>Status</th>
                            <th>Login terakhir</th>
                            <th style="text-align:right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $member)
                            <tr>
                                <td>
                                    <div class="row-main">
                                        <x-avatar :user="$member" size="sm" />
                                        <div>
                                            <b>{{ $member->name }}</b>
                                            <span class="cell-sub">{{ $member->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge violet">{{ $member->role?->name ?? '—' }}</span></td>
                                <td>{{ $member->level?->name ?? '—' }}</td>
                                <td>{{ $member->mentor?->name ?? '—' }}</td>
                                <td><span class="badge {{ $member->is_active ? 'success' : 'danger' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="tiny muted">{{ $member->last_login_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                                <td>
                                    <div class="actions">
                                        <a href="{{ route('admin.users.edit', $member) }}" class="btn btn-ghost btn-sm" data-modal="{{ route('admin.users.edit', $member) }}" data-modal-size="lg"><x-icon name="edit" :size="12" /> Ubah</a>
                                        <form method="POST" action="{{ route('admin.users.reset-password', $member) }}" data-confirm="Reset kata sandi {{ $member->name }} ke kata sandi default? Anggota wajib menggantinya saat login berikutnya.">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm" title="Reset kata sandi ke default"><x-icon name="lock" :size="12" /> Reset</button>
                                        </form>
                                        @if ($member->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $member) }}" data-confirm="Hapus akun {{ $member->name }}? Data agenda dan laporan ikut terhapus.">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"><x-icon name="trash" :size="12" /></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$users" />
        @endif
    </div>
@endsection
