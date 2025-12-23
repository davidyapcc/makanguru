<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// Admin login page
Route::get('/admin/login', function () {
    return view('admin.login');
})->name('admin.login');

// Admin authentication
Route::post('/admin/authenticate', function () {
    $adminKey = trim(config('app.admin_access_key'));
    $inputKey = trim(request('access_key'));

    // Trim both keys to avoid whitespace issues
    $adminKey = trim($adminKey);
    $inputKey = trim($inputKey);

    if ($inputKey === $adminKey) {
        session(['admin_authenticated' => true]);
        return redirect()->intended('/scraper');
    }

    return back()->withErrors(['access_key' => 'Invalid access key. Please check and try again.']);
})->name('admin.authenticate');

// Admin logout
Route::post('/admin/logout', function () {
    session()->forget('admin_authenticated');
    return redirect('/');
})->name('admin.logout');

// Protected admin routes
Route::middleware(\App\Http\Middleware\EnsureAdminAccess::class)->group(function () {
    Route::get('/scraper', function () {
        return view('scraper');
    })->name('scraper');

    Route::get('/restaurants', function () {
        return view('restaurants');
    })->name('restaurants');
});
