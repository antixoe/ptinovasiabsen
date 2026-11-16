@extends('layouts.app')

@section('title', 'Notifikasi')
@section('breadcrumb', 'Notifikasi')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Kotak masuk</p>
            <h1>Notifikasi</h1>
            <p class="sub">Pengingat laporan, agenda baru, dan hasil review dari pembimbing.</p>
        </div>
        <div class="page-actions">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-ghost"><x-icon name="check-circle" /> Tandai semua dibaca</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Semua notifikasi</h2>
                <p>{{ $notifications->total() }} notifikasi</p>
            </div>
            <span class="badge violet">{{ auth()->user()->unreadNotificationCount() }} belum dibaca</span>
        </div>

        @if ($notifications->isEmpty())
            <div class="empty">
                <div class="empty-icon"><x-icon name="bell" /></div>
                <b>Kotak masuk kosong</b>
                <p>Notifikasi akan muncul ketika ada agenda baru, pengingat laporan, atau hasil review.</p>
            </div>
        @else
            <div class="list" style="padding:0 20px 8px">
                @foreach ($notifications as $notification)
                    <div class="list-item" style="padding:14px 0;align-items:flex-start">
                        <span class="notif-icon" style="margin-top:2px">
                            <x-icon :name="$notification->type === 'report.reviewed' ? 'check-circle' : (str_starts_with($notification->type, 'agenda') ? 'calendar' : ($notification->type === 'report.reminder' ? 'bell' : 'file-text'))" :size="14" />
                        </span>
                        <div class="grow">
                            <div class="flex items-center gap-2" style="flex-wrap:wrap">
                                <b>{{ $notification->title }}</b>
                                @if (! $notification->read_at)
                                    <span class="badge violet">Baru</span>
                                @endif
                            </div>
                            <small style="margin-top:4px">{{ $notification->body }}</small>
                            <small class="tiny" style="color:#aab1bd">{{ $notification->created_at->translatedFormat('d M Y, H:i') }} · {{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-ghost btn-sm">
                                {{ $notification->link ? 'Buka' : 'Tandai dibaca' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            <x-pagination :paginator="$notifications" />
        @endif
    </div>
@endsection
