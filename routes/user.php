<?php

use App\Http\Controllers\User\ContactController;
use App\Http\Controllers\User\PostCategoriController;
use App\Http\Controllers\User\PostController;
use App\Http\Controllers\User\PostStatusController;
use App\Http\Controllers\User\PostTagController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt', 'verified', 'user'])->prefix('user')->name('user.')->group(function () {
    Route::prefix('contacts')->controller(ContactController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{uuid}', 'show');
        Route::post('/{uuid}', 'update');
        Route::delete('/{uuid}', 'destroy');
    });
    Route::prefix('posts')->controller(PostController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{uuid}', 'show');
        Route::post('/{uuid}', 'update');
        Route::delete('/{uuid}', 'destroy');
    });
    Route::prefix('post-categories')->controller(PostCategoriController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{uuid}', 'show');
        Route::post('/{uuid}', 'update');
        Route::delete('/{uuid}', 'destroy');
    });
    Route::prefix('post-statuses')->controller(PostStatusController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{uuid}', 'show');
        Route::post('/{uuid}', 'update');
        Route::delete('/{uuid}', 'destroy');
    });
    Route::prefix('post-tags')->controller(PostTagController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{uuid}', 'show');
        Route::post('/{uuid}', 'update');
        Route::delete('/{uuid}', 'destroy');
    });
});
