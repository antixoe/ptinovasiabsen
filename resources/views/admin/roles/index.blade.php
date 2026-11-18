@extends('layouts.app')

@section('title', 'Peran & Izin')
@section('breadcrumb', 'Administrasi · Peran & izin')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Kontrol akses</p>
            <h1>Peran & izin</h1>
            <p class="sub">Peran sistem sudah ditetapkan. Admin dapat membuat peran tambahan dengan hak akses terbatas.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost"><x-icon name="users" /> Anggota</a>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary" data-modal="{{ route('admin.roles.create') }}" data-modal-size="lg"><x-icon name="plus" /> Peran baru</a>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Daftar peran</h2>
                <p>{{ $roles->count() }} peran · {{ $roles->sum('users_count') }} anggota</p>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Peran</th><th>Izin</th><th>Anggota</th><th style="text-align:right">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                <b>{{ $role->name }}</b>
                                <span class="cell-sub">{{ $role->description ?? $role->slug }}{{ $role->is_system ? ' · peran sistem' : '' }}</span>
                            </td>
                            <td>
                                @if (count($role->permissions ?? []) === 0)
                                    <span class="badge">Akses pribadi</span>
                                @elseif (count($role->permissions) === count($permissions))
                                    <span class="badge violet">Akses penuh</span>
                                @else
                                    <span class="badge info">{{ count($role->permissions) }} izin</span>
                                @endif
                            </td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-ghost btn-sm" data-modal="{{ route('admin.roles.edit', $role) }}" data-modal-size="lg">Ubah</a>
                                    @unless ($role->is_system)
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="Hapus peran {{ $role->name }}?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"><x-icon name="trash" :size="12" /></button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
