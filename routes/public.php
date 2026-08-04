<?php

use App\Http\Controllers\PublicApi\BooksController;
use App\Http\Controllers\PublicApi\ContactsController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::prefix('books')->controller(BooksController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{slug}', 'show');
    });
    Route::prefix('contacts')->controller(ContactsController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{uuid}', 'show');
        Route::post('/{uuid}', 'update');
        Route::delete('/{uuid}', 'destroy');
    });
});
