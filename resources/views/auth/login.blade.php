<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="auth-page">
    <section class="auth-art">
        <div class="brand">
            <span class="brand-mark"><x-icon name="sparkles" /></span>
            <span>
                <b>Ruang Kerja</b>
                <small>PT Inovasi Absen</small>
            </span>
        </div>

        <h2>Agenda pagi, ringkasan sore,<br><span>satu alur kerja</span> yang jelas.</h2>
        <p>Workspace internal untuk mengelola agenda harian, membimbing siswa PKL, dan menjaga jejak audit setiap aktivitas.</p>

        <div class="auth-features">
            <div class="auth-feature">
                <x-icon name="sun" />
                <div>
                    <b>Agenda pagi & ringkasan sore</b>
                    <small>Siswa mengirim rencana, lalu melaporkan hasil di hari yang sama.</small>
                </div>
            </div>
            <div class="auth-feature">
                <x-icon name="check-circle" />
                <div>
                    <b>Review terarah dari pembimbing</b>
                    <small>Setujui laporan atau minta revisi dengan catatan yang jelas.</small>
                </div>
            </div>
            <div class="auth-feature">
                <x-icon name="shield" />
                <div>
                    <b>Akses berbasis peran</b>
                    <small>Setiap peran hanya melihat data yang memang menjadi tanggung jawabnya.</small>
                </div>
            </div>
        </div>

        <footer>© {{ now()->year }} PT Inovasi Absen · Workspace internal</footer>
    </section>

    <section class="auth-form-side">
        <div class="auth-box">
            <div class="auth-mobile-brand">
                <span class="brand-mark"><x-icon name="sparkles" /></span>
                <span>
                    <b>Ruang Kerja</b>
                    <small>PT Inovasi Absen</small>
                </span>
            </div>

            <p class="eyebrow">Selamat datang kembali</p>
            <h1>Masuk ke workspace</h1>
            <p class="sub">Gunakan akun yang diberikan admin untuk melanjutkan.</p>

            @if (session('status'))
                <div class="alert success">
                    <x-icon name="check-circle" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert error">
                    <x-icon name="alert" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <label class="field" for="email">
                    <span>Email</span>
                    <input id="email" type="email" name="email" class="input" value="{{ old('email') }}"
                           placeholder="nama@ptinovasiabsen.test" autocomplete="username" required autofocus>
                </label>

                <label class="field" for="password">
                    <span>Kata sandi</span>
                    <input id="password" type="password" name="password" class="input"
                           placeholder="••••••••" autocomplete="current-password" required>
                </label>

                <label class="check mb-4">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Ingat saya di perangkat ini</span>
                </label>

                <button type="submit" class="btn btn-primary btn-block">
                    Masuk <x-icon name="arrow-right" :size="15" />
                </button>
            </form>

            <p class="auth-copyright">Belum punya akses? Hubungi admin People Ops untuk pembuatan akun.</p>
        </div>
    </section>
</div>
</body>
</html>
