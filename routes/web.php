<?php

use Illuminate\Support\Facades\Route;

// Handle OPTIONS preflight requests globally (for all routes, including non-API)
Route::options('{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
        ->header('Access-Control-Max-Age', '86400');
})->where('any', '.*');

Route::get('/', function () {
    return view('welcome');
});
