<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentVerificationController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PartnerOperationsController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RatingReviewController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SecurityEventController;
use App\Http\Controllers\Admin\SecuritySettingsController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WalletController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::get('/admin/forgot-password', [AuthController::class, 'showForgotPassword'])->name('admin.password.request');
Route::post('/admin/forgot-password', [AuthController::class, 'sendResetLink'])->name('admin.password.email');
Route::get('/admin/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('admin.password.reset');
Route::post('/admin/reset-password', [AuthController::class, 'resetPassword'])->name('admin.password.update');

// Admin protected routes
Route::prefix('admin')->name('admin.')->middleware(['admin', 'admin.audit'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Administrator account and active sessions
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'destroySession'])->name('profile.sessions.destroy');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('admin.permission:dashboard.view')->name('dashboard');

    // Roles and permissions
    Route::middleware('admin.permission:roles.manage')->group(function () {
        Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::get('/roles-permissions/{adminRole}/edit', [RolePermissionController::class, 'edit'])->name('roles.edit');
        Route::put('/roles-permissions/{adminRole}', [RolePermissionController::class, 'update'])->name('roles.update');
    });

    // Users
    Route::middleware('admin.permission:users.manage')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    Route::middleware('admin.permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    // Partners
    Route::middleware('admin.permission:partners.manage')->group(function () {
        Route::get('/partners/create', [PartnerController::class, 'create'])->name('partners.create');
        Route::post('/partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::get('/partners/{partner}', [PartnerController::class, 'show'])->name('partners.show');
        Route::get('/partners/{partner}/edit', [PartnerController::class, 'edit'])->name('partners.edit');
        Route::put('/partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::post('/partners/{partner}/toggle', [PartnerController::class, 'toggleStatus'])->name('partners.toggle');
        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');

        // Partner menus and staff
        Route::get('/partners/{partner}/menu', [PartnerOperationsController::class, 'menu'])->name('partners.menu.index');
        Route::post('/partners/{partner}/menu/categories', [PartnerOperationsController::class, 'storeCategory'])->name('partners.menu.categories.store');
        Route::put('/partners/{partner}/menu/categories/{category}', [PartnerOperationsController::class, 'updateCategory'])->name('partners.menu.categories.update');
        Route::delete('/partners/{partner}/menu/categories/{category}', [PartnerOperationsController::class, 'destroyCategory'])->name('partners.menu.categories.destroy');
        Route::post('/partners/{partner}/menu/items', [PartnerOperationsController::class, 'storeItem'])->name('partners.menu.items.store');
        Route::put('/partners/{partner}/menu/items/{item}', [PartnerOperationsController::class, 'updateItem'])->name('partners.menu.items.update');
        Route::delete('/partners/{partner}/menu/items/{item}', [PartnerOperationsController::class, 'destroyItem'])->name('partners.menu.items.destroy');
        Route::get('/partners/{partner}/staff', [PartnerOperationsController::class, 'staff'])->name('partners.staff.index');
        Route::post('/partners/{partner}/staff', [PartnerOperationsController::class, 'storeStaff'])->name('partners.staff.store');
        Route::put('/partners/{partner}/staff/{staff}', [PartnerOperationsController::class, 'updateStaff'])->name('partners.staff.update');
        Route::delete('/partners/{partner}/staff/{staff}', [PartnerOperationsController::class, 'destroyStaff'])->name('partners.staff.destroy');
    });

    // Orders
    Route::middleware('admin.permission:orders.view')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
        ->middleware('admin.permission:orders.manage')->name('orders.status');

    // Drivers
    Route::middleware('admin.permission:drivers.manage')->group(function () {
        Route::get('/drivers/create', [DriverController::class, 'create'])->name('drivers.create');
        Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::get('/drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
        Route::get('/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
        Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::post('/drivers/{driver}/toggle', [DriverController::class, 'toggleStatus'])->name('drivers.toggle');
    });

    // Services
    Route::middleware('admin.permission:services.manage')->group(function () {
        Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::post('/services/{service}/toggle', [ServiceController::class, 'toggleStatus'])->name('services.toggle');
    });

    // Payments
    Route::middleware('admin.permission:payments.view')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    });
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'recordRefund'])
        ->middleware('admin.permission:payments.manage')->name('payments.refund');
    Route::post('/payments/{payment}/reconcile', [PaymentController::class, 'reconcile'])
        ->middleware('admin.permission:payments.manage')->name('payments.reconcile');

    // Wallets
    Route::middleware('admin.permission:wallets.view')->group(function () {
        Route::get('/wallets', [WalletController::class, 'index'])->name('wallets.index');
        Route::get('/wallet-top-ups', [WalletController::class, 'topUps'])->name('wallets.top-ups.index');
        Route::get('/wallets/{wallet}', [WalletController::class, 'show'])->name('wallets.show');
        Route::get('/wallets/{wallet}/transactions', [WalletController::class, 'transactions'])->name('wallets.transactions');
    });
    Route::post('/wallets/{wallet}/adjustments', [WalletController::class, 'adjust'])
        ->middleware('admin.permission:wallets.manage')->name('wallets.adjust');
    Route::post('/wallet-top-ups/{topUp}/review', [WalletController::class, 'reviewTopUp'])
        ->middleware('admin.permission:wallets.manage')->name('wallets.top-ups.review');

    // Support Tickets
    Route::middleware('admin.permission:tickets.manage')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/messages', [TicketController::class, 'storeMessage'])->name('tickets.messages.store');
    });

    // Ratings and reviews
    Route::middleware('admin.permission:ratings.manage')->group(function () {
        Route::get('/ratings', [RatingReviewController::class, 'index'])->name('ratings.index');
        Route::get('/ratings/{review}', [RatingReviewController::class, 'show'])->name('ratings.show');
        Route::patch('/ratings/{review}', [RatingReviewController::class, 'update'])->name('ratings.update');
    });

    // Notifications
    Route::middleware('admin.permission:notifications.manage')->group(function () {
        Route::get('/notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
        Route::post('/notifications', [NotificationController::class, 'store'])->name('notifications.store');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/{notification}/retry', [NotificationController::class, 'retry'])->name('notifications.retry');
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // FAQ knowledge base
    Route::middleware('admin.permission:faq.manage')->group(function () {
        Route::get('/faq/categories', [FaqController::class, 'categoryIndex'])->name('faq.categories');
        Route::get('/faq/categories/create', [FaqController::class, 'categoryCreate'])->name('faq.categories.create');
        Route::post('/faq/categories', [FaqController::class, 'categoryStore'])->name('faq.categories.store');
        Route::get('/faq/categories/{category}/edit', [FaqController::class, 'categoryEdit'])->name('faq.categories.edit');
        Route::put('/faq/categories/{category}', [FaqController::class, 'categoryUpdate'])->name('faq.categories.update');
        Route::delete('/faq/categories/{category}', [FaqController::class, 'categoryDestroy'])->name('faq.categories.destroy');
        Route::get('/faq/articles', [FaqController::class, 'articleIndex'])->name('faq.articles');
        Route::get('/faq/articles/create', [FaqController::class, 'articleCreate'])->name('faq.articles.create');
        Route::post('/faq/articles', [FaqController::class, 'articleStore'])->name('faq.articles.store');
        Route::get('/faq/articles/{article}', [FaqController::class, 'articleShow'])->name('faq.articles.show');
        Route::get('/faq/articles/{article}/edit', [FaqController::class, 'articleEdit'])->name('faq.articles.edit');
        Route::put('/faq/articles/{article}', [FaqController::class, 'articleUpdate'])->name('faq.articles.update');
        Route::delete('/faq/articles/{article}', [FaqController::class, 'articleDestroy'])->name('faq.articles.destroy');
    });

    // Document Verifications
    Route::middleware('admin.permission:verifications.manage')->group(function () {
        Route::get('/document-verifications', [DocumentVerificationController::class, 'index'])->name('document-verifications.index');
        Route::get('/document-verifications/{documentVerification}', [DocumentVerificationController::class, 'show'])->name('document-verifications.show');
        Route::patch('/document-verifications/{documentVerification}', [DocumentVerificationController::class, 'update'])->name('document-verifications.update');
    });

    // Security events
    Route::middleware('admin.permission:security.manage')->group(function () {
        Route::get('/security', [SecuritySettingsController::class, 'index'])->name('security-settings.index');
        Route::patch('/security/settings/{setting}', [SecuritySettingsController::class, 'updateSetting'])->name('security-settings.settings.update');
        Route::patch('/security/rate-limits/{rateLimit}', [SecuritySettingsController::class, 'updateRateLimit'])->name('security-settings.rate-limits.update');
        Route::post('/security/ip-rules', [SecuritySettingsController::class, 'storeIpRule'])->name('security-settings.ip-rules.store');
        Route::patch('/security/ip-rules/{list}/{ruleId}', [SecuritySettingsController::class, 'toggleIpRule'])->name('security-settings.ip-rules.toggle');
        Route::delete('/security/ip-rules/{list}/{ruleId}', [SecuritySettingsController::class, 'destroyIpRule'])->name('security-settings.ip-rules.destroy');
        Route::patch('/security/retention/{retentionPolicy}', [SecuritySettingsController::class, 'updateRetention'])->name('security-settings.retention.update');
        Route::get('/security-events', [SecurityEventController::class, 'index'])->name('security-events.index');
        Route::get('/security-events/{securityEvent}', [SecurityEventController::class, 'show'])->name('security-events.show');
        Route::patch('/security-events/{securityEvent}', [SecurityEventController::class, 'update'])->name('security-events.update');
    });

    // Audit Logs
    Route::middleware('admin.permission:audit.view')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });

    // Analytics and reports
    Route::middleware('admin.permission:reports.view')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
        Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/{date}', [ReportController::class, 'daily'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')
            ->name('reports.daily');
    });
});

// Redirect root to admin
Route::get('/', fn () => redirect()->route('admin.login'));
