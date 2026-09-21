<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'Shreeji Infotech API',
        'status' => 'operational',
        'version' => '1.0.0',
    ]);
});
