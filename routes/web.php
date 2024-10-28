<?php

use App\Livewire\ChatComponent;
use App\Livewire\ServicesComponent;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/test/services', ServicesComponent::class)->name('test.services');
Route::get('/test/chat/{service}', ChatComponent::class)->name('test.chat');

require __DIR__.'/auth.php';
