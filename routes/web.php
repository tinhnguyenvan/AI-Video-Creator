<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VideoController::class, 'index'])->name('home');

Route::prefix('projects')->name('projects.')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/create', [ProjectController::class, 'create'])->name('create');
    Route::post('/', [ProjectController::class, 'store'])->name('store');
    Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
    Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
    Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
    Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
    Route::get('/{project}/merge', [ProjectController::class, 'merge'])->name('merge');
    Route::post('/{project}/merge', [ProjectController::class, 'executeMerge'])->name('execute-merge');
});

Route::prefix('videos')->name('videos.')->group(function () {
    Route::get('/', [VideoController::class, 'index'])->name('index');
    Route::get('/create', [VideoController::class, 'create'])->name('create');
    Route::post('/', [VideoController::class, 'store'])->name('store');
    Route::get('/{video}', [VideoController::class, 'show'])->name('show');
    Route::post('/{video}/check-status', [VideoController::class, 'checkStatus'])->name('check-status');
    Route::post('/{video}/retry', [VideoController::class, 'retry'])->name('retry');
    Route::delete('/{video}', [VideoController::class, 'destroy'])->name('destroy');
    Route::get('/{video}/download', [VideoController::class, 'download'])->name('download');
});

Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::post('/test-connection', [SettingsController::class, 'testConnection'])->name('test-connection');
});
