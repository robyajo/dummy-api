<?php

use App\Http\Controllers\User\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt', 'verified', 'user'])->prefix('user')->name('user.')->group(function () {
    Route::prefix('contacts')->controller(ContactController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{uuid}', 'show');
        Route::post('/{uuid}', 'update');
        Route::delete('/{uuid}', 'destroy');
    });
});
