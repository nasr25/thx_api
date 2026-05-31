<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any?}', function () {
    return response()->json(['message' => 'Appreciation Platform API - Use /api prefix']);
})->where('any', '.*');
