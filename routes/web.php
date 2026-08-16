<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    // Page d'onboarding
    Route::livewire('/onboarding', 'pages::onboarding')->name('onboarding');

    // Page de jeu (accès restreint si l'onboarding n'est pas complété)
    Route::middleware(['onboarding.completed'])->group(function () {
        Route::livewire('/game', 'pages::game')->name('game');
        Route::livewire('/game/shop', 'pages::shop')->name('shop');
    });
});

require __DIR__.'/settings.php';
