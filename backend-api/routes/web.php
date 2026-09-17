<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::match(['get', 'post'], '/jalankan-cron-sinkron', function (\App\Services\GoogleSheetSyncService $syncService) {
    set_time_limit(180);
    $result = $syncService->syncAll();
    
    return response()->json([
        'status' => $result['status'] === 'success' ? 'success' : 'partial',
        'message' => 'Sinkronisasi Google Sheet ke SQLite berhasil dijalankan oleh Cron Job!',
        'result' => $result
    ])->header('ngrok-skip-browser-warning', 'true');
})->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
]);