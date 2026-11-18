@extends('layouts.app')

@section('title', 'Ganti Kata Sandi')
@section('breadcrumb', 'Akun · Ganti kata sandi')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Keamanan akun</p>
            <h1>Ganti kata sandi</h1>
            <p class="sub">Akunmu masih memakai kata sandi default. Buat kata sandi baru sebelum melanjutkan.</p>
        </div>
    </div>

    <div class="card" style="max-width:480px">
        <div class="card-head"><div><h2>Kata sandi baru</h2><p>Minimal 8 karakter dan berbeda dari kata sandi default</p></div></div>
        <div class="card-body pad">
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')

                <label class="field">
                    <span>Kata sandi baru</span>
                    <input type="password" name="password" class="input" autocomplete="new-password" required autofocus>
                </label>
                <label class="field">
                    <span>Ulangi kata sandi baru</span>
                    <input type="password" name="password_confirmation" class="input" autocomplete="new-password" required>
                </label>

                <div class="flex gap-2 justify-between">
                    <button type="submit" form="logout-form" class="btn btn-ghost">Keluar</button>
                    <button type="submit" class="btn btn-primary"><x-icon name="lock" :size="14" /> Simpan kata sandi</button>
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}" id="logout-form" hidden>
                @csrf
            </form>
        </div>
    </div>
@endsection
