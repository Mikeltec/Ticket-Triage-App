<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\StatsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString()
    ]);
});

// Ticket routes
Route::apiResource('tickets', TicketController::class);

// Additional ticket routes
Route::post('tickets/{id}/classify', [TicketController::class, 'classify'])
    ->name('tickets.classify');

// Stats route for dashboard
Route::get('stats', [StatsController::class, 'index'])
    ->name('stats.index');

// Get available ticket statuses and categories
Route::get('meta', function () {
    return response()->json([
        'statuses' => \App\Models\Ticket::STATUSES,
        'categories' => \App\Models\Ticket::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values()
            ->toArray(),
    ]);
})->name('meta.index');