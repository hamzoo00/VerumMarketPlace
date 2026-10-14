<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

/*
 * Served dynamically so the Sitemap directive carries an absolute URL,
 * which the robots.txt spec requires, without hardcoding the domain.
 */
Route::get('robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /dashboard',
        'Disallow: /settings',
        'Disallow: /horizon',
        'Disallow: /login',
        'Disallow: /register',
        'Disallow: /forgot-password',
        'Disallow: /reset-password',
        'Disallow: /two-factor-challenge',
        'Disallow: /confirm-password',
        '',
        'Sitemap: '.url('sitemap.xml'),
    ];

    return response(implode(PHP_EOL, $lines), 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');

require __DIR__.'/settings.php';
