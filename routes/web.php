<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Api\DashboardDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/user-app', [HomeController::class, 'mobile'])->name('mobile.home');
Route::get('/download/mobile-app', function () {
    $apkPath = public_path('downloads/meras-user-app.apk');

    if (! file_exists($apkPath)) {
        return redirect()
            ->route('home')
            ->with('notice', 'The Android app download is not available yet. Please build the mobile app first.')
            ->with('noticeType', 'warning');
    }

    return response()->download($apkPath, 'meras-user-app.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->name('mobile.download');
Route::post('/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/add', [PosController::class, 'add'])->name('pos.add');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/cart/update', [PosController::class, 'updateCart'])->name('pos.updateCart');
    Route::post('/pos/cart/clear', [PosController::class, 'clearCart'])->name('pos.clearCart');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.store');

    Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::match(['post', 'patch'], '/inquiries/{inquiry}/toggle', [InquiryController::class, 'toggle'])->name('inquiries.toggle');
    Route::post('/inquiries/{inquiry}/respond', [InquiryController::class, 'respond'])->name('inquiries.respond');
    Route::delete('/inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/sales/{sale}/print', [PosController::class, 'printSale'])->name('sales.print');


    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/export', [SettingsController::class, 'export'])->name('settings.export');
    Route::post('/settings/action', [SettingsController::class, 'action'])->name('settings.action');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
