@php
    $user = auth()->user();
    $route = request()->route()?->getName() ?? 'dashboard';
    $unreadCount = $user->unreadNotificationCount();
    $latestNotifications = $user->workspaceNotifications()->latest()->limit(5)->get();
    $navNotifications = $route === 'notifications.index';
    $isStudent = $user->isStudent();
    $reviewAccess = $user->canReviewReports() || $user->canViewAllReports();

    $links = [
        ['label' => 'Utama', 'items' => array_values(array_filter([
            ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'agenda.index', 'match' => 'agenda.*', 'label' => $isStudent ? 'Agenda Saya' : 'Agenda', 'icon' => 'calendar'],
            ['route' => 'agenda.calendar', 'match' => 'agenda.calendar', 'label' => 'Kalender', 'icon' => 'clock'],
            $isStudent ? ['route' => 'reports.index', 'match' => 'reports.*', 'label' => 'Laporan Harian', 'icon' => 'file-text'] : null,
        ]))],
        ['label' => 'Bimbingan', 'items' => array_values(array_filter([
            $reviewAccess ? ['route' => 'reviews.index', 'match' => 'reviews.*', 'label' => 'Review Laporan', 'icon' => 'check-circle', 'count' => $user->canReviewReports() ? \App\Models\DailyReport::visibleTo($user)->where('status', 'summary')->count() : 0] : null,
            $reviewAccess ? ['route' => 'students.index', 'match' => 'students.*', 'label' => 'Siswa Bimbingan', 'icon' => 'users'] : null,
        ]))],
        ['label' => 'Administrasi', 'items' => array_values(array_filter([
            $user->hasPermission('manage-users') ? ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => 'Anggota', 'icon' => 'briefcase'] : null,
            $user->hasPermission('manage-roles') ? ['route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'label' => 'Peran & Izin', 'icon' => 'shield'] : null,
            $user->hasPermission('view-audit') ? ['route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'label' => 'Audit Log', 'icon' => 'activity'] : null,
        ]))],
    ];
    $links = array_values(array_filter($links, fn ($group) => count($group['items'])));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark"><x-icon name="sparkles" /></span>
            <span>
                <b>Ruang Kerja</b>
                <small>PT Inovasi Absen</small>
            </span>
        </a>

        <div class="workspace-chip">
            <span class="dot"></span>
            <div>
                <b>Workspace Internal</b>
                <small>Semua tim · {{ now()->translatedFormat('Y') }}</small>
            </div>
        </div>

        <nav class="flex-1" aria-label="Navigasi utama">
            @foreach ($links as $group)
                <div class="nav-group">
                    <p class="nav-label">{{ $group['label'] }}</p>
                    @foreach ($group['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="nav-item {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                            <x-icon :name="$item['icon']" />
                            <span>{{ $item['label'] }}</span>
                            @if (! empty($item['count']))
                                <span class="nav-count hot">{{ $item['count'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach

            <div class="nav-group">
                <p class="nav-label">Akun</p>
                <a href="{{ route('notifications.index') }}" class="nav-item {{ $navNotifications ? 'active' : '' }}">
                    <x-icon name="bell" />
                    <span>Notifikasi</span>
                    @if ($unreadCount)
                        <span class="nav-count hot">{{ $unreadCount }}</span>
                    @endif
                </a>
            </div>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <x-avatar :user="$user" size="sm" />
                <div class="flex-1 min-w-0">
                    <b class="truncate">{{ $user->name }}</b>
                    <small>{{ $user->role?->name ?? 'Tanpa peran' }}{{ $user->department ? ' · '.$user->department : '' }}</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="icon-btn" title="Keluar" style="width:28px;height:28px;background:transparent;border-color:transparent;color:#93a6c0">
                        <x-icon name="log-out" :size="15" />
                    </button>
                </form>
            </div>
            <div class="sidebar-note">
                <span class="status"></span> Sesi aman · {{ now()->format('H:i') }} WIB
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="flex items-center">
                <button type="button" class="icon-btn mobile-menu" id="sidebar-toggle" aria-label="Buka menu">
                    <x-icon name="menu" />
                </button>
                <div class="breadcrumb">
                    <span>{{ config('app.name') }}</span>
                    <x-icon name="chevron-right" />
                    <b>@yield('breadcrumb', 'Dashboard')</b>
                </div>
            </div>

            <div class="topbar-actions">
                <form action="{{ route('agenda.index') }}" method="GET" class="search-box" role="search">
                    <x-icon name="search" />
                    <input type="search" name="q" placeholder="Cari agenda…" value="{{ request('q') }}" aria-label="Cari agenda">
                </form>

                <div class="relative" data-dropdown>
                    <button type="button" class="icon-btn" data-dropdown-toggle aria-label="Notifikasi">
                        <x-icon name="bell" />
                        @if ($unreadCount)
                            <span class="dot"></span>
                        @endif
                    </button>
                    <div class="notif-pop" data-dropdown-menu>
                        <div class="notif-head">
                            <div>
                                <b>Notifikasi</b>
                                <small>{{ $unreadCount }} belum dibaca</small>
                            </div>
                            @if ($unreadCount)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-link">Tandai semua</button>
                                </form>
                            @endif
                        </div>
                        <div class="notif-list">
                            @forelse ($latestNotifications as $notification)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="notif-item {{ $notification->read_at ? '' : 'unread' }}">
                                        <span class="notif-icon">
                                            <x-icon :name="in_array($notification->type, ['report.reviewed']) ? 'check-circle' : (str_starts_with($notification->type, 'agenda') ? 'calendar' : ($notification->type === 'report.reminder' ? 'bell' : 'file-text'))" :size="14" />
                                        </span>
                                        <span class="notif-body">
                                            <b>{{ $notification->title }}</b>
                                            <small>{{ $notification->body }}</small>
                                            <em>{{ $notification->created_at->diffForHumans() }}</em>
                                        </span>
                                    </button>
                                </form>
                            @empty
                                <p class="notif-empty">Belum ada notifikasi.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}" class="notif-foot">Lihat semua notifikasi</a>
                    </div>
                </div>

                <div class="topbar-user">
                    <x-avatar :user="$user" />
                    <div>
                        <b>{{ $user->name }}</b>
                        <small>{{ $user->role?->name ?? 'Anggota' }}</small>
                    </div>
                </div>
            </div>
        </header>

        <main class="page">
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

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
