<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/kata-sandi', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/kata-sandi', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/akun', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/akun', [AccountController::class, 'update'])->name('account.update');

        Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
        Route::get('/agenda/create', [AgendaController::class, 'create'])->name('agenda.create');
        Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');
        Route::get('/agenda/{agenda}/edit', [AgendaController::class, 'edit'])->name('agenda.edit');
        Route::put('/agenda/{agenda}', [AgendaController::class, 'update'])->name('agenda.update');
        Route::delete('/agenda/{agenda}', [AgendaController::class, 'destroy'])->name('agenda.destroy');
        Route::patch('/agenda/{agenda}/toggle', [AgendaController::class, 'toggle'])->name('agenda.toggle');
        Route::get('/kalender', [AgendaController::class, 'calendar'])->name('agenda.calendar');

        Route::get('/laporan', [DailyReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/hari-ini', [DailyReportController::class, 'today'])->name('reports.today');
        Route::post('/laporan/hari-ini/pagi', [DailyReportController::class, 'storeMorning'])->name('reports.morning');
        Route::post('/laporan/hari-ini/sore', [DailyReportController::class, 'storeSummary'])->name('reports.summary');
        Route::get('/laporan/{report}', [DailyReportController::class, 'show'])->name('reports.show');
        Route::delete('/laporan/{report}', [DailyReportController::class, 'destroy'])->name('reports.destroy');

        Route::middleware('can:review-reports')->group(function () {
            Route::get('/review', [ReviewController::class, 'index'])->name('reviews.index');
            Route::get('/review/{report}', [ReviewController::class, 'show'])->name('reviews.show');
            Route::patch('/review/{report}', [ReviewController::class, 'update'])->name('reviews.update');
        });

        Route::get('/bimbingan', [StudentController::class, 'index'])->name('students.index');
        Route::get('/bimbingan/{student}', [StudentController::class, 'show'])->name('students.show');

        Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('notifications.read');
        Route::patch('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::middleware('can:manage-users')->group(function () {
                Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
                Route::resource('users', UserController::class)->except(['show']);
            });

            Route::middleware('can:manage-roles')->group(function () {
                Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
                Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
                Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
                Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
                Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
                Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
            });

            Route::middleware('can:view-audit')->group(function () {
                Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
            });
        });
    });
});
