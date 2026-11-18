@extends('layouts.app')

@section('title', $user->exists ? 'Ubah Anggota' : 'Anggota Baru')
@section('breadcrumb', 'Administrasi · ' . ($user->exists ? 'Ubah anggota' : 'Anggota baru'))

@php $editing = $user->exists; @endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Administrasi workspace</p>
            <h1>{{ $editing ? 'Ubah '.$user->name : 'Tambah anggota' }}</h1>
            <p class="sub">
                @if ($editing)
                    Perubahan peran dan status langsung memengaruhi akses anggota.
                @else
                    Akun dibuat oleh admin; anggota masuk memakai email dan kata sandi yang kamu tetapkan.
                @endif
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" /> Kembali</a>
        </div>
    </div>

    <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid-2">
            <div class="card">
                <div class="card-head"><div><h2>Identitas anggota</h2><p>Informasi dasar dan kontak</p></div></div>
                <div class="card-body pad">
                    <div class="form-grid">
                        <label class="field span-2">
                            <span>Nama lengkap</span>
                            <input type="text" name="name" class="input" value="{{ old('name', $user->name) }}" maxlength="120" required>
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input type="email" name="email" class="input" value="{{ old('email', $user->email) }}" maxlength="150" required>
                        </label>
                        <label class="field">
                            <span>Nomor telepon</span>
                            <input type="text" name="phone" class="input" value="{{ old('phone', $user->phone) }}" maxlength="30" placeholder="0812…">
                        </label>
                        <label class="field span-2">
                            <span>Departemen / unit</span>
                            <input type="text" name="department" class="input" value="{{ old('department', $user->department) }}" maxlength="100" placeholder="Engineering, Design, People Ops…">
                        </label>
                    </div>
                </div>
            </div>

            <div class="stack">
                <div class="card">
                    <div class="card-head"><div><h2>Peran & akses</h2><p>Menentukan data yang bisa dilihat</p></div></div>
                    <div class="card-body pad">
                        <label class="field">
                            <span>Peran</span>
                            <select name="role_id" class="select" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field">
                            <span>Level</span>
                            <select name="level_id" class="select">
                                <option value="">Tanpa level</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}" @selected((string) old('level_id', $user->level_id) === (string) $level->id)>{{ $level->name }}</option>
                                @endforeach
                            </select>
                            <span class="hint">Level Students memakai alur laporan harian; Pembimbing dapat mereview.</span>
                        </label>
                        <label class="field">
                            <span>Pembimbing</span>
                            <select name="mentor_id" class="select">
                                <option value="">Belum ditentukan</option>
                                @foreach ($mentors as $mentor)
                                    <option value="{{ $mentor->id }}" @selected((string) old('mentor_id', $user->mentor_id) === (string) $mentor->id)>{{ $mentor->name }}</option>
                                @endforeach
                            </select>
                            <span class="hint">Wajib untuk siswa PKL agar laporan masuk ke antrean pembimbing.</span>
                        </label>
                        <label class="check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->exists ? $user->is_active : true))>
                            <span>Akun aktif dan dapat masuk</span>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head"><div><h2>Kata sandi</h2><p>{{ $editing ? 'Kosongkan bila tidak diubah' : 'Kata sandi awal akun' }}</p></div></div>
                    <div class="card-body pad">
                        @unless ($editing)
                            <div class="alert info" style="margin-bottom:12px">
                                <x-icon name="info" />
                                <span>Kata sandi default: <b>{{ config('auth.default_password') }}</b>. Anggota wajib menggantinya saat login pertama.</span>
                            </div>
                        @endunless
                        <div class="form-grid">
                            <label class="field">
                                <span>Kata sandi</span>
                                <input type="password" name="password" class="input" autocomplete="new-password" value="{{ $editing ? '' : old('password', config('auth.default_password')) }}">
                            </label>
                            <label class="field">
                                <span>Ulangi kata sandi</span>
                                <input type="password" name="password_confirmation" class="input" autocomplete="new-password" value="{{ $editing ? '' : old('password_confirmation', config('auth.default_password')) }}">
                            </label>
                        </div>
                        @unless ($editing)
                            <button type="button" class="btn btn-ghost btn-sm" data-use-default-password="{{ config('auth.default_password') }}"><x-icon name="lock" :size="12" /> Pakai kata sandi default</button>
                        @endunless
                    </div>
                </div>

                <div class="flex gap-2 justify-between">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost" data-modal-close>Batal</a>
                    <button type="submit" class="btn btn-primary"><x-icon name="check" :size="14" /> {{ $editing ? 'Simpan perubahan' : 'Buat akun' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection
