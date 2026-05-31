<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Employee\EmployeeController;
use App\Http\Controllers\API\Appreciation\AppreciationController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\Admin\DashboardController;
use App\Http\Controllers\API\Admin\SettingController;
use App\Http\Controllers\API\Admin\ActivityLogController;
use App\Http\Controllers\API\Admin\AppreciationManagementController;
use App\Http\Controllers\API\Admin\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| /api/auth/windows  → Protected by IIS Windows Authentication.
|                       IIS sets LOGON_USER; our WindowsAuthMiddleware reads
|                       it, resolves the AD user, then the controller issues
|                       a Bearer token back to the SPA.
|
| /api/auth/admin/login → Standard form-based login for admin users.
|
*/

// ─── Windows Auth (IIS-protected endpoint) ──────────────────────────────────
// WindowsAuthMiddleware reads LOGON_USER from IIS and authenticates the user.
// No Sanctum needed here — the Windows identity IS the credential.
Route::middleware(['App\Http\Middleware\WindowsAuthMiddleware'])
    ->prefix('auth')
    ->group(function () {
        Route::get('/windows', [AuthController::class, 'windowsAuth'])->name('auth.windows');
    });

// ─── Admin Form Login (no Windows Auth required) ────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])
        ->middleware('throttle:10,1')
        ->name('auth.admin.login');
});

// ─── Authenticated Routes (Bearer token from either flow) ───────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth utilities
    Route::prefix('auth')->group(function () {
        Route::post('/logout',   [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me',        [AuthController::class, 'me'])->name('auth.me');
        Route::post('/refresh',  [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::post('/language', [AuthController::class, 'updateLanguage'])->name('auth.language');
    });

    // Dashboard (stats for the current authenticated user)
    Route::get('/dashboard', [AppreciationController::class, 'dashboard'])->name('dashboard');

    // Employees
    Route::prefix('employees')->group(function () {
        Route::get('/',                       [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/search',                 [EmployeeController::class, 'search'])->name('employees.search');
        Route::get('/{id}',                   [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/{id}/appreciations',     [EmployeeController::class, 'appreciations'])->name('employees.appreciations');
    });

    // Appreciations
    Route::prefix('appreciations')->group(function () {
        Route::post('/',         [AppreciationController::class, 'send'])
            ->middleware('throttle:30,1')
            ->name('appreciations.send');
        Route::get('/received',  [AppreciationController::class, 'received'])->name('appreciations.received');
        Route::get('/sent',      [AppreciationController::class, 'sent'])->name('appreciations.sent');
        Route::get('/feed',      [AppreciationController::class, 'feed'])->name('appreciations.feed');
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',               [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/unread-count',   [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('/{id}/read',     [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/read-all',      [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });

    // ─── Admin Routes ──────────────────────────────────────────────────────
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {

        Route::get('/dashboard',            [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/settings',             [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings',             [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/logo',       [SettingController::class, 'uploadLogo'])->name('settings.logo');

        Route::get('/appreciations',        [AppreciationManagementController::class, 'index'])->name('appreciations.index');
        Route::delete('/appreciations/{id}',[AppreciationManagementController::class, 'destroy'])->name('appreciations.destroy');

        Route::get('/activity-logs',        [ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::get('/reports/analytics',    [ReportController::class, 'analytics'])->name('reports.analytics');
        Route::get('/reports/export',       [ReportController::class, 'export'])->name('reports.export');
    });
});
