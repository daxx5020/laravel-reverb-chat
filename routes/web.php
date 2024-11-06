<?php

use App\Livewire\ChatComponent;
use App\Livewire\ChatListComponent;
use App\Livewire\SellerChatComponent;
use App\Livewire\ServicesComponent;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware('auth')->group(function () {
    Route::get('/test/services', ServicesComponent::class)->name('test.services');
    Route::get('/test/chat/{service}', ChatComponent::class)->name('test.chat');
    Route::get('/test/chats', ChatListComponent::class)->name('test.chats');
    Route::get('/test/seller-chat/{chat}', SellerChatComponent::class)->name('test.seller-chat');
});

require __DIR__ . '/auth.php';
