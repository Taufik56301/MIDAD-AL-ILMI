<?php

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['web', 'auth', 'role:owner,admin'])->group(function (): void {
    Route::get('/summary', [DashboardApiController::class, 'summary'])->name('api.v1.summary');
});

