<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Service;
use App\Models\User;
use App\Services\ChatService;

class ChatController extends Controller
{
    private ChatService $chatService;
    
    public function __construct(ChatService $chatService){
        $this->chatService = $chatService;
    }

    public function createChat(Request $request){

        $serviceId = $request->input('service_id');
        $buyerId = $request->input('buyer_id');
        $sellerId = $request->input('seller_id');

        $chat = $this->chatService->findChatApi($serviceId, $buyerId, $sellerId);

        return response()->json($chat, 200);
    }

    public function sendMessage(Request $request){
        $chatId = $request->input('chat_id');
        $senderId = $request->input('sender_id');
        $messageContent = $request->input('message');

        $message = $this->chatService->sendMessage($chatId, $senderId, $messageContent);

        return response()->json($messageContent, 201);
    }

    public function chatList(Request $request){
        $userId = $request->input('user_id');

        $chats = Chat::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with('service:id,name,description,price')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['id', 'service_id']);

        return response()->json($chats, 200);
    }

    public function getMessages(Request $request){
        $chatId = $request->input('chat_id');
        $messages = $this->chatService->getMessagesApi($chatId);

        $formattedMessages = $messages->getCollection()->transform(function ($message) {
            return [
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'created_at' => $message->created_at->toIso8601String(),
            ];
        });
    
        return response()->json([
            'current_page' => $messages->currentPage(),
            'data' => $formattedMessages,
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
        ], 200);
    }
}
