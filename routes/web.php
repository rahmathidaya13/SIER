<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Channel\ChannelController;
use App\Http\Controllers\Leads\LeadActivityController;
use App\Http\Controllers\Leads\LeadConversionController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Sales\SaleController;

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
// Route::get('/leads', function () {
//     return Inertia::render('Test/page');
// })->name('leads');

Route::middleware(['auth', 'verified', 'check.status.active'])->group(function () {
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::controller(HomeController::class)->group(function () {
        Route::get('/home',  'index')->name('home');
    });
    Route::controller(LeadController::class)->group(function () {
        Route::get('/leads/list', 'index')->name('leads.index');
        Route::get('/leads/show/{id}', 'show')->name('leads.show');
        Route::post('/leads/store', 'store')->name('leads.store');
        Route::put('/leads/update/{id}', 'update')->name('leads.update');
        Route::delete('/leads/delete/{leads}', 'destroy')->name('leads.delete');
    });
    Route::controller(LeadActivityController::class)->group(function () {
        Route::put('/leads/activity/update/{id}', 'update')->name('leads.activity.update');
    });
    Route::controller(LeadConversionController::class)->group(function () {
        Route::post('/leads/convert/{leads}', 'store')->name('leads.convert');
    });
    Route::controller(UserController::class)->group(function () {
        Route::get('/employee/list', 'index')->name('employee.index');
        Route::post('/employee/store', 'store')->name('employee.store');
        Route::put('/employee/update/{id}', 'update')->name('employee.update');
        Route::delete('/employee/delete/{id}', 'destroy')->name('employee.delete');
    });

    Route::controller(ProductController::class)->group(function () {
        Route::get('/product/list', 'index')->name('product.index');
        Route::get('/product/show/{product}', 'show')->name('product.show');
        Route::get('/product/keyword', 'productSearch')->name('product.search');
        Route::post('/product/store', 'store')->name('product.store');
        Route::put('/product/update/{product}', 'update')->name('product.update');
        Route::delete('/product/delete/{product}', 'destroy')->name('product.delete');
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
        Route::get('/sales/show/{sales}', 'show')->name('sales.show');
        Route::post('/sales/pay/{sales}', 'payRemaining')->name('sales.pay');
    });
});

Route::middleware(['guest'])->group(function () {
    Route::controller(LoginController::class)->group(function () {
        Route::get('/login', 'login')->name('login');
        Route::post('/login/store', 'store')->name('login.store');
    });

    Route::controller(RegisterController::class)->group(function () {
        Route::get('/register', 'register')->name('register');
        Route::post('/register/store', 'store')->name('register.store');
    });

    Route::controller(ForgotPasswordController::class)->group(function () {
        Route::get('/forgot-password', 'create')->name('forgot.create');
        Route::post('/forgot-password/store', 'store')->name('forgot.store');
    });
    Route::controller(ResetPasswordController::class)->group(function () {
        Route::get('/reset-password/{token}', 'create')->name('password.reset');
        Route::post('/reset-password/store', 'store')->name('password.store');
    });
});
