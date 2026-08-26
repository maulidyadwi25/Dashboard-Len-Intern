<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'Dashboard LEN Intern API',
        'status'  => 'running',
    ]);
});