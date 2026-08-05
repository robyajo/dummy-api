<?php

use App\Http\Controllers\PublicApi\BooksController;
use App\Http\Controllers\PublicApi\ContactsController;
use App\Http\Controllers\PublicApi\PostCategoriController;
use App\Http\Controllers\PublicApi\PostController;
use App\Http\Controllers\PublicApi\PostTagController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::prefix('books')->controller(BooksController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{slug}', 'show');
    });
    Route::prefix('contacts')->controller(ContactsController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{uuid}', 'show');
    });
    Route::prefix('posts')->controller(PostController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{slug}', 'show');
    });
    Route::prefix('post-categories')->controller(PostCategoriController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{slug}', 'show');
    });
    Route::prefix('post-tags')->controller(PostTagController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{slug}', 'show');
    });
});
