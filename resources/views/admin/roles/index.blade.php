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
        </div>
    </div>

    <div class="grid-2">
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
                                        <a href="{{ route('admin.roles.index', ['edit' => $role->id]) }}" class="btn btn-ghost btn-sm">Ubah</a>
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

        <div class="card">
            <div class="card-head">
                <div>
                    <h2>{{ $editing ? 'Ubah peran '.$editing->name : 'Peran baru' }}</h2>
                    <p>{{ $editing ? 'Perbarui nama atau hak akses peran.' : 'Buat peran khusus sesuai kebutuhan tim.' }}</p>
                </div>
                @if ($editing)
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost btn-sm">Batal</a>
                @endif
            </div>
            <div class="card-body pad">
                <form method="POST" action="{{ $editing ? route('admin.roles.update', $editing) : route('admin.roles.store') }}">
                    @csrf
                    @if ($editing) @method('PUT') @endif

                    <label class="field">
                        <span>Nama peran</span>
                        <input type="text" name="name" class="input" value="{{ old('name', $editing->name ?? '') }}" maxlength="80" required>
                    </label>
                    <label class="field">
                        <span>Slug</span>
                        <input type="text" name="slug" class="input" value="{{ old('slug', $editing->slug ?? '') }}"
                               placeholder="otomatis dari nama" maxlength="80" @if ($editing?->is_system) disabled @endif>
                        @if ($editing?->is_system)
                            <span class="hint">Slug peran sistem tidak dapat diubah.</span>
                        @endif
                    </label>
                    <label class="field">
                        <span>Deskripsi</span>
                        <input type="text" name="description" class="input" value="{{ old('description', $editing->description ?? '') }}" maxlength="200">
                    </label>

                    <fieldset>
                        <legend>Hak akses</legend>
                        @foreach ($permissions as $key => $label)
                            @php $allowed = in_array($key, $assignablePermissions, true); @endphp
                            <label class="check mb-2 {{ $editing?->is_system ? 'muted' : '' }}">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                       @checked(in_array($key, old('permissions', $editing->permissions ?? []), true))
                                       @disabled(! $allowed || $editing?->is_system)>
                                <span>
                                    {{ $label }}
                                    @unless ($allowed)
                                        <em class="tiny" style="font-style:normal">(di luar wewenangmu)</em>
                                    @endunless
                                </span>
                            </label>
                        @endforeach
                        @if ($editing?->is_system)
                            <p class="hint" style="margin-top:8px">Peran sistem memakai izin bawaan dan hanya dapat diubah superadmin.</p>
                        @endif
                    </fieldset>

                    <button type="submit" class="btn btn-primary btn-block">
                        <x-icon name="shield" :size="14" /> {{ $editing ? 'Simpan peran' : 'Buat peran' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
