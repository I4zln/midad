<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [QuoteController::class, 'index'])->name('home');
Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
Route::get('/about', [PageController::class, 'about'])->name('about');
// The former «عنّي» page is now the maker section of «من نحن»; keep old links working.
Route::permanentRedirect('/about-me', '/about#maker')->name('creator');
Route::get('/muallaqat', [PageController::class, 'muallaqat'])->name('muallaqat.index');
Route::get('/muallaqat/{slug}', [PageController::class, 'muallaqa'])->name('muallaqat.show');
Route::get('/poets', [PageController::class, 'poets'])->name('poets.index');
Route::get('/authors/{author}', [QuoteController::class, 'author'])->name('authors.show');
Route::middleware('auth')->group(function () {
    Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
    Route::post('/quotes', [QuoteController::class, 'store'])->middleware('throttle:10,1')->name('quotes.store');
    Route::get('/quotes/{quote}/edit', [QuoteController::class, 'edit'])->can('update', 'quote')->name('quotes.edit');
    Route::put('/quotes/{quote}', [QuoteController::class, 'update'])->can('update', 'quote')->name('quotes.update');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->can('delete', 'quote')->name('quotes.destroy');
});
Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
Route::get('/users/{user}', [PageController::class, 'profile'])->name('profile.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile/edit', [PageController::class, 'editProfile'])->name('profile.edit');
    Route::patch('/profile', [PageController::class, 'updateProfile'])->name('profile.update');
    Route::get('/favorites', [PageController::class, 'favorites'])->name('favorites');
    Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [PageController::class, 'markNotificationsRead'])->name('notifications.read');
    Route::post('/quotes/{quote}/like', [CommunityController::class, 'like'])->name('quotes.like');
    Route::post('/quotes/{quote}/favorite', [CommunityController::class, 'favorite'])->name('quotes.favorite');
    Route::post('/quotes/{quote}/comments', [CommunityController::class, 'comment'])->name('quotes.comments.store');
    Route::post('/users/{user}/follow', [CommunityController::class, 'follow'])->name('users.follow');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'verificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::delete('/quotes/{quote}', [AdminController::class, 'destroy'])->name('quotes.destroy');
});
