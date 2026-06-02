<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
Route::get('/', function () {
    return view('welcome');
});

