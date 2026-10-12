<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Branch\BranchController;
use App\Http\Controllers\Channel\ChannelController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Leads\LeadActivityController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\VerifyByOtpController;
use App\Http\Controllers\Leads\LeadConversionController;
use App\Http\Controllers\Notification\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});


Route::middleware(['auth', 'check.status.active', 'idle.timeout', 'verified.email'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/activity/ping', [UserActivityController::class, 'ping'])->name('activity.ping');

    Route::controller(HomeController::class)->group(function () {
        Route::get('/home',  'index')->name('home');
    });
    Route::controller(LeadController::class)->group(function () {
        Route::get('/leads/list', 'index')->name('leads.index');
        Route::get('/leads/show/{id}', 'show')->name('leads.show');
        Route::get('/leads/activity/{id}', 'activities')->name('leads.activities');
        Route::post('/leads/store', 'store')->name('leads.store');
        Route::put('/leads/update/{id}', 'update')->name('leads.update');
        Route::delete('/leads/delete/{leads}', 'destroy')->name('leads.delete');
    });
    Route::controller(LeadActivityController::class)->group(function () {
        Route::post('/leads/activity/store/{id}', 'store')->name('leads.activity.store');
        Route::put('/leads/activity/update/{id}', 'update')->name('leads.activity.update');
    });
    Route::controller(LeadConversionController::class)->group(function () {
        Route::post('/leads/convert/{leads}', 'store')->name('leads.convert');
    });
    Route::controller(UserController::class)->group(function () {
        Route::get('/employee/list', 'index')->name('employee.index');
        Route::post('/employee/store', 'store')->name('employee.store');
        Route::get('/employee/show/{id}', 'show')->name('employee.show');
        Route::put('/employee/update/{id}', 'update')->name('employee.update');
        Route::delete('/employee/delete/{id}', 'destroy')->name('employee.delete');
    });

    Route::controller(ProductController::class)->group(function () {
        Route::get('/product/list', 'index')->name('product.index');
        Route::get('/product/show/{product}', 'show')->name('product.show');
        Route::get('/product/keyword', 'productSearch')->name('product.search');
        Route::post('/product/store', 'store')->name('product.store');
        Route::put('/product/update/{id}', 'update')->name('product.update');
        Route::delete('/product/delete/{id}', 'destroy')->name('product.delete');
    });

    Route::controller(ChannelController::class)->group(function () {
        Route::get('/channel/list', 'index')->name('channel.index');
        Route::post('/channel/store', 'store')->name('channel.store');
        Route::get('/channel/show/{id}', 'show')->name('channel.show');
        Route::put('/channel/update/{channel}', 'update')->name('channel.update');
        Route::delete('/channel/delete/{channel}', 'destroy')->name('channel.delete');
    });

    Route::controller(SaleController::class)->group(function () {
        Route::get('/sales/list', 'index')->name('sales.index');
        Route::get('/sales/show/{id}', 'show')->name('sales.show');
        Route::get('/sales/activities/{id}', 'activities')->name('sales.activities');
        Route::get('/sales/payment-history/{id}', 'paymentHistory')->name('sales.payment.history');
        Route::post('/sales/pay/{id}', 'payRemaining')->name('sales.pay');
        Route::post('/sales/cancel/{id}', 'payCancel')->name('sales.cancel');
    });
    Route::controller(BranchController::class)->group(function () {
        Route::get('/branch/list', 'index')->name('branch.index');
        Route::get('/branch/show/{id}', 'show')->name('branch.show');
        Route::post('/branch/store', 'store')->name('branch.store');
        Route::put('/branch/update/{id}', 'update')->name('branch.update');
        Route::delete('/branch/delete/{id}', 'destroy')->name('branch.delete');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile/detail/{id}', 'show')->name('profile.detail');
        Route::put('/profile/update/{id}', 'update')->name('profile.update');
    });
    Route::controller(NotificationController::class)->group(function () {
        Route::get('/notification', 'index')->name('notification.index');
        // Route untuk menandai SATU notifikasi
        Route::post('/notifications/read/{id}', 'markAsRead')->name('notifications.read');
        // Route untuk menandai SEMUA notifikasi
        Route::post('/notifications/read-all', 'markAllRead')->name('notifications.readAll');
    });
});

// akun yang sudah login dan diverifikasi emailnya
Route::controller(VerificationController::class)->group(function () {
    Route::get('/email/verify', 'notice')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', 'verify')->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', 'resend')->middleware(['throttle:6,1'])->name('verification.send');
});
Route::controller(VerifyByOtpController::class)->group(function () {
    Route::get('/verify-otp/show', 'showVerifyOtp')->name('verification.otp');
    Route::post('/verify-otp/verify', 'verifyOtp')
        ->name('verification.otp.verify');
    Route::post('/resend-otp', 'resendOtp')
        ->name('verification.otp.resend');
});

// akun yang belum login dan akan register
Route::middleware(['guest'])->group(function () {
    Route::controller(LoginController::class)->group(function () {
        Route::get('/login', 'login')->name('login');
        Route::post('/login/store', 'store')->name('login.store');
    });

    Route::controller(RegisterController::class)->group(function () {
        Route::get('/register', 'create')->name('register');
        Route::post('/register/store', 'store')->name('register.store');
    });

    Route::controller(ForgotPasswordController::class)->group(function () {
        Route::get('/forgot-password', 'showForgotPasswordForm')->name('forgot.create');
        Route::post('/forgot-password/store', 'store')->name('forgot.store');
    });
    Route::controller(ResetPasswordController::class)->group(function () {
        Route::get('/reset-password/{token}', 'showResetPasswordForm')->name('password.reset');
        Route::post('/reset-password/store', 'resetPassword')->name('password.store');
    });
});
