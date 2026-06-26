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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::controller(HomeController::class)->group(function () {
        Route::get('/home', action: 'index')->name('home');
    });
    Route::controller(LeadController::class)->group(function () {
        Route::get('/leads/list', 'index')->name('leads.index');
    });
    Route::controller(UserController::class)->group(function () {
        Route::get('/employee/list', 'index')->name('employee.index');
        Route::post('/employee/store', 'store')->name('employee.store');
        Route::put('/employee/update/{id}', 'update')->name('employee.update');
        Route::delete('/employee/delete/{id}', 'destroy')->name('employee.delete');
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
