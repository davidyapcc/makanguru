<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/scraper', function () {
    return view('scraper');
});

Route::get('/restaurants', function () {
    return view('restaurants');
});
