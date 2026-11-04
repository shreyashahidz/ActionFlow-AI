<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    ImportController,
    MessageController,
    TaskController,
    AnalysisController
};

// 3 Dedicated Web Pages
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/tasks', [DashboardController::class, 'tasks'])->name('tasks');
Route::get('/import', [DashboardController::class, 'import'])->name('import');

// Backend APIs
Route::prefix('api')->group(function () {
    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/analytics', [DashboardController::class, 'analytics']);
    Route::get('/briefing', [DashboardController::class, 'briefing']);

    Route::post('/import', [ImportController::class, 'store']);
    Route::get('/import-logs', [ImportController::class, 'index']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{message}', [MessageController::class, 'show']);
    Route::post('/messages/{message}/analyze', [AnalysisController::class, 'analyze']);

    Route::get('/tasks/export', [TaskController::class, 'export']); // must come before {task}
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});
