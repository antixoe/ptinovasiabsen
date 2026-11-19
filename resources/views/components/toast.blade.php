@props(['message' => null])

@php
    $flash = $message ?? session('status');
    $error = isset($errors) ? $errors->first() : null;
@endphp

@if ($flash || $error)
    <div class="toast-stack" id="toast-stack" role="status" aria-live="polite">
        @if ($error)
            <div class="toast-ios is-error" data-glass-toast role="alert">
                <span class="toast-ios-icon"><x-icon name="alert" :size="16" /></span>
                <div class="toast-ios-body">
                    <b>Gagal diproses</b>
                    <span>{{ $error }}</span>
                </div>
                <button type="button" class="toast-ios-close" data-toast-close aria-label="Tutup notifikasi">
                    <x-icon name="x" :size="11" />
                </button>
                <i class="toast-ios-progress" aria-hidden="true"></i>
            </div>
        @endif

        @if ($flash)
            <div class="toast-ios" data-glass-toast>
                <span class="toast-ios-icon"><x-icon name="check-circle" :size="16" /></span>
                <div class="toast-ios-body">
                    <b>Berhasil</b>
                    <span>{{ $flash }}</span>
                </div>
                <button type="button" class="toast-ios-close" data-toast-close aria-label="Tutup notifikasi">
                    <x-icon name="x" :size="11" />
                </button>
                <i class="toast-ios-progress" aria-hidden="true"></i>
            </div>
        @endif
    </div>
@endif
