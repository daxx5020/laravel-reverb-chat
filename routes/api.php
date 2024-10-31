<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/create-chat', [ChatController::class, 'createChat']);
Route::post('/send-message', [ChatController::class, 'sendMessage']);
Route::get('/chat-list', [ChatController::class, 'chatList']);
Route::get('/get-message', [ChatController::class, 'getMessages']);