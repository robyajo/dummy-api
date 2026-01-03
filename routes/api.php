<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');

    Route::middleware('jwt')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('update-password', [AuthController::class, 'updatePassword'])->name('update-password');
        Route::post('update/{uuid}', [AuthController::class, 'update'])->name('update');
        Route::get('session', [AuthController::class, 'session'])->name('session');
        Route::get('permission', [AuthController::class, 'permission'])->name('permission');
    });
});
