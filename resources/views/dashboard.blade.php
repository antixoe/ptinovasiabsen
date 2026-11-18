@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@php
    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $firstName = explode(' ', $user->name)[0];
    $weekMax = max(1, $weeklySeries->max('total'));
    $roleLabel = $user->role?->name ?? 'Anggota';
    $isStudent = $user->isStudent();
@endphp

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">{{ $today->translatedFormat('l, d F Y') }}</p>
            <h1>{{ $greeting }}, {{ $firstName }} <span>✦</span></h1>
            <p class="sub">
                {{ $roleLabel }}{{ $user->department ? ' · '.$user->department : '' }} —
                @if ($isStudent)
                    kirim agenda pagi, lalu lengkapi ringkasan sebelum pukul 18.00.
                @elseif ($user->canReviewReports())
                    {{ $stats['review_pending'] }} laporan menunggu review hari ini.@if ($stats['attention']) {{ $stats['attention'] }} siswa belum lengkapi laporan.@endif
                @else
                    berikut ringkasan aktivitas workspace hari ini.
                @endif
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('agenda.calendar') }}" class="btn btn-ghost">
                <x-icon name="calendar" /> Kalender
            </a>
            @if ($isStudent)
                <a href="{{ route('reports.today') }}" class="btn btn-primary" data-modal="{{ route('reports.today') }}" data-modal-size="lg">
                    <x-icon name="file-text" /> {{ $todayReport?->isSummarySubmitted() ? 'Lihat laporan' : 'Isi laporan hari ini' }}
                </a>
            @else
                <a href="{{ route('agenda.create') }}" class="btn btn-primary" data-modal="{{ route('agenda.create') }}">
                    <x-icon name="plus" /> Tambah agenda
                </a>
            @endif
        </div>
    </div>

    <div class="insight-strip">
        <span class="insight-icon"><x-icon name="sparkles" :size="16" /></span>
        <div class="flex-1">
            <b>Ringkasan hari ini</b>
            <span>
                {{ $stats['agenda_today'] }} agenda terlihat, {{ $stats['done_today'] }} sudah selesai.
                @if ($stats['review_pending'])
                    Ada {{ $stats['review_pending'] }} laporan menunggu review.
                @else
                    Tidak ada laporan yang menunggu review.
                @endif
            </span>
        </div>
        <span class="strip-divider"></span>
        <div class="strip-note">
            <span class="pulse"></span>
            <span>Sinkron dengan database · {{ now()->format('H:i') }} WIB</span>
        </div>
    </div>

    @if ($attention->isNotEmpty())
        <div class="alert error attention-alert">
            <x-icon name="alert" />
            <div class="flex-1">
                <b>{{ $attention->count() }} siswa belum melengkapi laporan hari ini.</b>
                <span>
                    {{ $attention->take(4)->map(fn ($row) => $row['student']->name.' ('.$row['label'].')')->join(', ') }}@if ($attention->count() > 4) dan {{ $attention->count() - 4 }} lainnya @endif
                </span>
            </div>
            <a href="{{ route('students.index') }}" class="btn btn-ghost btn-sm">Pantau siswa</a>
        </div>
    @endif

    <div class="grid-4 mb-4">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Agenda hari ini</span>
                <span class="stat-icon violet"><x-icon name="calendar" /></span>
            </div>
            <div class="stat-value">{{ $stats['agenda_today'] }}<small> item</small></div>
            <div class="stat-meta">
                <b>{{ $stats['done_today'] }}</b> selesai · {{ max(0, $stats['agenda_today'] - $stats['done_today']) }} tersisa
            </div>
            <div class="stat-bar"><i style="width: {{ $stats['agenda_today'] ? round($stats['done_today'] / $stats['agenda_today'] * 100) : 0 }}%"></i></div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Menunggu review</span>
                <span class="stat-icon amber"><x-icon name="check-circle" /></span>
            </div>
            <div class="stat-value">{{ $stats['review_pending'] }}<small> laporan</small></div>
            <div class="stat-meta">
                @if ($stats['review_pending'])
                    <b class="warn">Perlu tindakan</b> pembimbing
                @else
                    <b>Semua bersih</b> · tidak ada antrean
                @endif
            </div>
            <div class="stat-bar"><i style="width: {{ min(100, $stats['review_pending'] * 20) }}%"></i></div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Penyelesaian minggu</span>
                <span class="stat-icon green"><x-icon name="trending-up" /></span>
            </div>
            <div class="stat-value">{{ $stats['week_rate'] }}<small>%</small></div>
            <div class="stat-meta">
                <b>{{ $stats['week_done'] }}</b> dari {{ $stats['week_total'] }} agenda minggu ini
            </div>
            <div class="stat-bar"><i style="width: {{ $stats['week_rate'] }}%"></i></div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">{{ $user->canViewAllAgendas() ? 'Anggota aktif' : ($isStudent ? 'Notifikasi baru' : 'Siswa bimbingan') }}</span>
                <span class="stat-icon blue"><x-icon name="{{ $isStudent ? 'bell' : 'users' }}" /></span>
            </div>
            @if ($isStudent)
                <div class="stat-value">{{ $stats['unread'] }}<small> notifikasi</small></div>
                <div class="stat-meta">Pengingat laporan & hasil review</div>
                <div class="stat-bar"><i style="width: {{ min(100, $stats['unread'] * 20) }}%"></i></div>
            @else
                <div class="stat-value">{{ $user->canViewAllAgendas() ? $stats['members'] : $stats['mentees'] }}<small> orang</small></div>
                <div class="stat-meta">
                    <b>{{ $stats['unread'] }}</b> notifikasi belum dibaca
                </div>
                <div class="avatar-stack mt-2">
                    @foreach (\App\Models\User::query()->where('is_active', true)->orderBy('id')->limit(4)->get() as $member)
                        <x-avatar :user="$member" size="sm" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Agenda hari ini</h2>
                    <p>{{ $today->translatedFormat('d F Y') }} · {{ $todayAgendas->count() }} item</p>
                </div>
                <a href="{{ route('agenda.index') }}" class="btn btn-ghost btn-sm">Semua agenda <x-icon name="arrow-right" :size="13" /></a>
            </div>

            @if ($todayAgendas->isEmpty())
                <div class="empty">
                    <div class="empty-icon"><x-icon name="calendar" /></div>
                    <b>Belum ada agenda hari ini</b>
                    <p>Tambahkan agenda agar progres kerja hari ini tercatat rapi.</p>
                    <a href="{{ route('agenda.create') }}" class="btn btn-primary btn-sm" data-modal="{{ route('agenda.create') }}"><x-icon name="plus" :size="13" /> Tambah agenda</a>
                </div>
            @else
                <div class="agenda-list">
                    @foreach ($todayAgendas as $agenda)
                        <div class="agenda-row">
                            <div class="agenda-time">
                                <b>{{ $agenda->start_time ? \Illuminate\Support\Carbon::parse($agenda->start_time)->format('H:i') : '—' }}</b>
                                <span>{{ $agenda->user->name }}</span>
                            </div>
                            <span class="timeline-dot {{ $agenda->status === 'completed' || $agenda->status === 'reviewed' ? 'done' : ($agenda->status === 'in_progress' ? 'progress' : 'planned') }}"></span>
                            <div class="agenda-content">
                                <div class="agenda-title">
                                    <b>{{ $agenda->title }}</b>
                                    <span class="badge {{ $agenda->statusTone() }} dot-badge">{{ $agenda->statusLabel() }}</span>
                                </div>
                                @if ($agenda->description)
                                    <p>{{ $agenda->description }}</p>
                                @endif
                                <div class="agenda-meta">
                                    <x-avatar :user="$agenda->user" size="sm" />
                                    <span>{{ $agenda->user->name }}</span>
                                    <span class="meta-separator">·</span>
                                    <span>Prioritas {{ $agenda->priorityLabel() }}</span>
                                    @if ($agenda->summary)
                                        <span class="meta-separator">·</span>
                                        <span>Ada catatan hasil</span>
                                    @endif
                                </div>
                            </div>
                            @can('manage-agenda-for', $agenda->user)
                                <a href="{{ route('agenda.edit', $agenda) }}" class="icon-btn" style="width:28px;height:28px" title="Ubah agenda" data-modal="{{ route('agenda.edit', $agenda) }}">
                                    <x-icon name="edit" :size="14" />
                                </a>
                            @endcan
                        </div>
                    @endforeach
                </div>
                <div class="card-foot">
                    <a href="{{ route('agenda.calendar') }}" class="btn btn-ghost btn-sm btn-block">Lihat kalender lengkap</a>
                </div>
            @endif
        </div>

        <div class="stack">
            @if ($isStudent)
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2>Laporan harian</h2>
                            <p>{{ $today->translatedFormat('d F Y') }}</p>
                        </div>
                        @if ($todayReport)
                            <span class="badge {{ $todayReport->statusTone() }}">{{ $todayReport->statusLabel() }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if (! $todayReport)
                            <div class="reminder-note mb-3">
                                <x-icon name="alert" />
                                <span>Kamu belum mengirim agenda pagi. Kirim sekarang agar pembimbing tahu rencana kerjamu.</span>
                            </div>
                            <a href="{{ route('reports.today') }}" class="btn btn-primary btn-block" data-modal="{{ route('reports.today') }}" data-modal-size="lg">Mulai isi agenda pagi</a>
                        @else
                            <div class="split-list">
                                <span class="muted">Agenda pagi</span>
                                <b>{{ $todayReport->morning_submitted_at?->format('H:i') ?? 'Belum' }}</b>
                            </div>
                            <div class="split-list">
                                <span class="muted">Ringkasan sore</span>
                                <b>{{ $todayReport->summary_submitted_at?->format('H:i') ?? 'Belum dikirim' }}</b>
                            </div>
                            <div class="split-list">
                                <span class="muted">Jumlah agenda</span>
                                <b>{{ $todayReport->agendas->count() }} item</b>
                            </div>
                            <div class="split-list">
                                <span class="muted">Pembimbing</span>
                                <b>{{ $todayReport->user->mentor?->name ?? $user->mentor?->name ?? 'Belum ditentukan' }}</b>
                            </div>
                            @if ($todayReport->review_note)
                                <div class="review-quote mt-3">{{ $todayReport->review_note }}</div>
                            @endif
                            <a href="{{ route('reports.today') }}" class="btn btn-primary btn-block mt-3" data-modal="{{ route('reports.today') }}" data-modal-size="lg">
                                {{ $todayReport->isSummarySubmitted() ? 'Lihat laporan hari ini' : 'Isi ringkasan sore' }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($attention->isNotEmpty())
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2>Perlu perhatian</h2>
                            <p>Belum isi agenda atau ringkasan hari ini</p>
                        </div>
                        <span class="badge danger">{{ $attention->count() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="list">
                            @foreach ($attention->take(5) as $row)
                                <a href="{{ route('students.show', $row['student']) }}" class="list-item" data-modal="{{ route('students.show', $row['student']) }}" data-modal-size="lg">
                                    <x-avatar :user="$row['student']" size="sm" />
                                    <div class="grow">
                                        <b>{{ $row['student']->name }}</b>
                                        <small>{{ $row['student']->department ?? 'Siswa PKL' }}@if ($row['report']?->morning_submitted_at) · agenda dikirim {{ $row['report']->morning_submitted_at->format('H:i') }}@endif</small>
                                    </div>
                                    <span class="badge {{ $row['tone'] }}">{{ $row['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-foot">
                        <a href="{{ route('students.index') }}" class="btn btn-ghost btn-sm btn-block">Lihat semua siswa bimbingan</a>
                    </div>
                </div>
            @endif

            @if ($user->canReviewReports())
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h2>Menunggu review</h2>
                            <p>Ringkasan yang siap diperiksa</p>
                        </div>
                        @if ($reviewQueue->count())
                            <span class="badge warning">{{ $reviewQueue->count() }}</span>
                        @endif
                    </div>
                    @if ($reviewQueue->isEmpty())
                        <div class="empty" style="padding:22px 20px">
                            <div class="empty-icon" style="width:36px;height:36px"><x-icon name="check-circle" :size="17" /></div>
                            <b>Antrean kosong</b>
                            <p style="margin-bottom:0">Semua laporan sudah ditindaklanjuti.</p>
                        </div>
                    @else
                        <div class="card-body">
                            <div class="list">
                                @foreach ($reviewQueue as $queueReport)
                                    <div class="list-item">
                                        <x-avatar :user="$queueReport->user" size="sm" />
                                        <div class="grow">
                                            <b>{{ $queueReport->user->name }}</b>
                                            <small>{{ $queueReport->report_date->translatedFormat('d M Y') }} · {{ $queueReport->statusLabel() }}</small>
                                        </div>
                                        <a href="{{ route('reviews.show', $queueReport) }}" class="btn btn-soft btn-sm" data-modal="{{ route('reviews.show', $queueReport) }}" data-modal-size="lg">Review</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-foot">
                            <a href="{{ route('reviews.index') }}" class="btn btn-ghost btn-sm btn-block">Buka antrean review</a>
                        </div>
                    @endif
                </div>
            @endif

            @if (! $isStudent && ! $user->canReviewReports())
                <div class="card">
                    <div class="card-head"><div><h2>Notifikasi terbaru</h2><p>Aktivitas yang melibatkanmu</p></div></div>
                    <div class="card-body">
                        <div class="list">
                            @forelse ($latestNotifications ?? [] as $notification)
                                <div class="list-item">
                                    <span class="notif-icon"><x-icon name="bell" :size="14" /></span>
                                    <div class="grow">
                                        <b>{{ $notification->title }}</b>
                                        <small>{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            @empty
                                <p class="small muted" style="margin:0">Belum ada notifikasi baru.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="lower-grid">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Aktivitas 7 hari terakhir</h2>
                    <p>Perbandingan agenda dibuat dan diselesaikan</p>
                </div>
                <span class="badge success">{{ $stats['week_rate'] }}% selesai</span>
            </div>
            <div class="card-body">
                <div class="bar-chart">
                    @foreach ($weeklySeries as $day)
                        <div class="bar-col">
                            <div class="bar-track" title="{{ $day['label'] }}: {{ $day['done'] }}/{{ $day['total'] }} selesai">
                                <i class="bar-total" style="height: {{ max(2, round($day['total'] / $weekMax * 100)) }}%"></i>
                                <i class="bar-done" style="height: {{ max(2, round($day['done'] / $weekMax * 100)) }}%"></i>
                            </div>
                            <span>{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="chart-key mt-3">
                    <span><i style="background:#ddd8fd"></i> Total agenda</span>
                    <span><i style="background:#6c5ce7"></i> Selesai</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2>{{ $isStudent ? 'Riwayat laporan' : 'Laporan terbaru' }}</h2>
                    <p>{{ $isStudent ? 'Laporan harianmu' : 'Aktivitas laporan tim' }}</p>
                </div>
                @if ($isStudent)
                    <a href="{{ route('reports.index') }}" class="btn btn-ghost btn-sm">Semua</a>
                @endif
            </div>
            @if ($recentReports->isEmpty())
                <div class="empty" style="padding:24px 20px">
                    <b>Belum ada laporan</b>
                    <p style="margin-bottom:0">Laporan akan muncul setelah agenda pagi dikirim.</p>
                </div>
            @else
                <div class="card-body">
                    <div class="list">
                        @foreach ($recentReports as $recent)
                            <a href="{{ $user->canReviewReports() && ! $isStudent ? route('reviews.show', $recent) : route('reports.show', $recent) }}" class="list-item" data-modal="{{ $user->canReviewReports() && ! $isStudent ? route('reviews.show', $recent) : route('reports.show', $recent) }}" data-modal-size="lg">
                                <x-avatar :user="$recent->user" size="sm" />
                                <div class="grow">
                                    <b>{{ $isStudent ? $recent->report_date->translatedFormat('l, d M') : $recent->user->name }}</b>
                                    <small>{{ $recent->report_date->translatedFormat('d M Y') }} · {{ $recent->agendas_count ?? '' }}{{ isset($recent->agendas_count) ? ' agenda' : '' }}</small>
                                </div>
                                <span class="badge {{ $recent->statusTone() }}">{{ $recent->statusLabel() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
