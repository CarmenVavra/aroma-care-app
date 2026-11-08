<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\ProductManager;
use App\Livewire\Profile;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/anmelden', Login::class)->name('login');
    Route::get('/registrieren', Register::class)->name('register');
    Route::get('/passwort-vergessen', ForgotPassword::class)->name('password.request');
    Route::get('/passwort-zuruecksetzen/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/', ProductManager::class)->name('stock');
    Route::get('/profil', Profile::class)->name('profile');
});
