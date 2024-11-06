<?php

namespace App\Services;

use App\Events\MessageTypingEvent;
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
        return Message::with('media') // Eager load media
            ->where('chat_id', $chatId)
            ->get();
    }

    /**
     * Send Message Typing Event
    */
    public function sendMessageTyping($chatId, $senderId)
    {
        // Trigger the event
        event(new MessageTypingEvent($chatId, $senderId));
    }



    /**
     * APIS
    */


    public function findChatApi($serviceId, $buyerId, $sellerId): Chat
    {
        // Check if chat already exists
        $chat = Chat::where('service_id', $serviceId)
                    ->where('buyer_id', $buyerId)
                    ->where('seller_id', $sellerId)
                    ->with('service')
                    ->first();

        // If found, return existing chat
        if ($chat) {
            $chat->wasRecentlyCreated = false;
            return $chat;
        }

        // If not found, create a new chat
        return $this->createChatApi($serviceId, $buyerId, $sellerId);
    }

    public function createChatApi($serviceId, $buyerId, $sellerId): Chat
    {
        $chat = Chat::create([
            'service_id' => $serviceId,
            'buyer_id' => $buyerId,
            'seller_id' => $sellerId,
        ]);

        $chat->wasRecentlyCreated = true;
        return $chat;
    }

    public function sendMessageApi($chatId, $senderId, $messageContent, $mediaPaths = []): Message
    {
        // Create the message
        $message = Message::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'message' => $messageContent,
        ]);

        // If there are media paths, associate them with the message
        if (!empty($mediaPaths)) {
            foreach ($mediaPaths as $mediaData) {
                $message->media()->create([
                    'file_path' => $mediaData['file_path'],
                    'file_type' => $mediaData['file_type'],
                    'mime_type' => $mediaData['mime_type'],
                    'size' => $mediaData['size'],
                ]);
            }
        }

        // Trigger the NewMessageEvent to broadcast the message in real-time
        event(new NewMessageEvent($message));

        return $message;
    }


    public function getMessagesApi($chatId)
    {
        return Message::with(['media', 'sender'])
            ->where('chat_id', $chatId)
            ->orderBy('created_at', 'desc')
            ->select('id', 'sender_id', 'message', 'created_at', 'chat_id')
            ->paginate(15);
    }
}
