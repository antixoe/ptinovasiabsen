@extends('layouts.app')

@section('title', 'Pengaturan Akun')
@section('breadcrumb', 'Akun · Pengaturan')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Akun & keamanan</p>
            <h1>Pengaturan akun</h1>
            <p class="sub">Perbarui profil yang tampil di workspace dan kelola kata sandi akunmu.</p>
        </div>
    </div>

    <div class="profile-strip card">
        <x-avatar :user="$user" size="xl" />
        <div class="profile-stats">
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->role?->name ?? 'Tanpa peran' }}{{ $user->department ? ' · '.$user->department : '' }}</p>
            <span class="badge {{ $user->is_active ? 'success' : 'danger' }}">{{ $user->is_active ? 'Akun aktif' : 'Akun nonaktif' }}</span>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-head"><div><h2>Profil</h2><p>Nama dan warna avatar terlihat oleh rekan tim</p></div></div>
            <div class="card-body pad">
                <form method="POST" action="{{ route('account.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-grid">
                        <label class="field span-2">
                            <span>Nama lengkap</span>
                            <input type="text" name="name" class="input" value="{{ old('name', $user->name) }}" maxlength="120" required>
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input type="email" class="input" value="{{ $user->email }}" disabled>
                            <span class="hint">Email login dikelola admin.</span>
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

                    <fieldset class="avatar-picker">
                        <legend>Warna avatar</legend>
                        @foreach ($avatarColors as $color)
                            <label class="avatar-choice" title="{{ ucfirst($color) }}">
                                <input type="radio" name="avatar_color" value="{{ $color }}" @checked(old('avatar_color', $user->avatar_color ?: 'violet') === $color)>
                                <span class="avatar {{ $color }}">{{ $user->initials() }}</span>
                                <em>{{ ucfirst($color) }}</em>
                            </label>
                        @endforeach
                    </fieldset>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary"><x-icon name="check" :size="14" /> Simpan profil</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head"><div><h2>Ringkasan akun</h2><p>Data yang diatur admin</p></div></div>
                <div class="card-body pad">
                    <dl class="kv-list">
                        <div><dt>Peran</dt><dd>{{ $user->role?->name ?? '—' }}</dd></div>
                        <div><dt>Level</dt><dd>{{ $user->level?->name ?? '—' }}</dd></div>
                        <div><dt>Pembimbing</dt><dd>{{ $user->mentor?->name ?? '—' }}</dd></div>
                        <div><dt>Login terakhir</dt><dd>{{ $user->last_login_at?->translatedFormat('d M Y · H:i') ?? 'Belum tercatat' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><div><h2>Kata sandi</h2><p>Minimal 8 karakter, berbeda dari kata sandi default</p></div></div>
                <div class="card-body pad">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <label class="field">
                            <span>Kata sandi baru</span>
                            <input type="password" name="password" class="input" autocomplete="new-password" required>
                        </label>
                        <label class="field">
                            <span>Ulangi kata sandi baru</span>
                            <input type="password" name="password_confirmation" class="input" autocomplete="new-password" required>
                        </label>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-soft"><x-icon name="lock" :size="14" /> Perbarui kata sandi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
