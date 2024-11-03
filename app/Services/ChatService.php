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
    public function sendMessage($chatId, $senderId, $message = null, $imagePath = null)
    {
        // Create the message
        $message = Message::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'message' => $message,
            'image_path' => $imagePath
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


    /**
     * APIS
    */


    public function findChatApi($serviceId, $buyerId, $sellerId): Chat
    {
        $chat = Chat::where('service_id', $serviceId)
        ->where('buyer_id', $buyerId)
        ->where('seller_id', $sellerId)
        ->first();

        if ($chat) {
            return $chat;
        }

        return $this->createChat($serviceId, $buyerId, $sellerId);
    }

    public function createChatApi($serviceId, $buyerId, $sellerId): Chat
    {
        return Chat::create([
            'service_id' => $serviceId,
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId
        ]);
    }

    public function sendMessageApi($chatId, $senderId, $messageContent, $imagePath = null)
    {
        $message = Message::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'message' => $messageContent,
            'image_path' => $imagePath,
        ]);

        // Trigger the NewMessageEvent to broadcast the message in real-time
        event(new NewMessageEvent($message));

        return $message;
    }

    public function getMessagesApi($chatId)
    {
        return Message::where('chat_id', $chatId)
        ->orderBy('created_at', 'desc')
        ->select('sender_id', 'message', 'created_at')
        ->paginate(15);

    }
}
