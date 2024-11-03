<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [ChatController::class, 'register']);
Route::post('/login', [ChatController::class, 'login']);
Route::middleware('auth:api')->get('/user', [ChatController::class, 'user']);


Route::post('/create-chat', [ChatController::class, 'createChat']);
Route::post('/send-message', [ChatController::class, 'sendMessage']);
Route::get('/chat-list', [ChatController::class, 'chatList']);
Route::get('/get-message', [ChatController::class, 'getMessages']);
