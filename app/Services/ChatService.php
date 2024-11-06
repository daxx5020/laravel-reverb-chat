<?php

namespace App\Services;

use App\Events\NewMessageEvent;
use App\Models\Chat;
use App\Models\Message;

class ChatService
{
    /**
     * Find a chat by id
    */
    public function findChatById($chatId)
    {
        return Chat::find($chatId);
    }

    /**
     * Find a chat by service id, buyer id, and seller id
    */
    public function findChat($serviceId, $buyerId, $sellerId): Chat
    {
        $auth = auth()->user();

        $chat = Chat::where('service_id', $serviceId)
            ->where('buyer_id', $buyerId)
            ->where('seller_id', $sellerId)
            ->first();


        if ($chat) {
            return $chat;
        }

        return $this->createChat($serviceId, $buyerId, $sellerId);
    }

    /**
     * Create a new chat
     */
    public function createChat($serviceId, $buyerId, $sellerId): Chat 
    {
        return Chat::create([
            'service_id' => $serviceId,
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId
        ]);
    }

    /**
     * Send Message
    */
    public function sendMessage($chatId, $senderId, $message)
    {
        // Create the message
        $message = Message::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'message' => $message
        ]);

        // Trigger the NewMessageEvent
        event(new NewMessageEvent($message));
    }

    /**
     * Get Messages
    */
    public function getMessages($chatId)
    {
        return Message::where('chat_id', $chatId)->get();
    }
}
