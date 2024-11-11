<?php

namespace App\Services;

use App\Events\MessageTypingEvent;
use App\Events\NewMessageEvent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Pusher\Pusher;

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

        $chat = $message->chat;
        $recipientId = ($senderId == $chat->buyer_id) ? $chat->seller_id : $chat->buyer_id;
        $recipient = User::find($recipientId);

        if ($recipient && $recipient->fcm_token) {
            // Send FCM notification
            $this->sendFcmNotification($recipient->fcm_token, 'New Message', $message->message ?? 'You have a new image message');
        }
    }

    /**
     * Send FCM Notification
     */
    protected function sendFcmNotification($token, $title, $body)
    {
        $fcmUrl = 'https://fcm.googleapis.com/v1/projects/push-notification-a5f33/messages:send';
        $serverKey = 'ya29.a0AeDClZB6oFRPfYBoHuABjKgX6Mml4ocZx6Ymc80AELvwZyHsYumtZ2ToLh5V2xLO2FFAVUEDoXWptU-HgMYUHqSGF4bkn0LZDtjtGS6nFUYSHevXIjGARyJi2QzYbU4e-nSEnucqg0jZlk53ZPEDKgzM_iMDOtkxtQUI-7zIaCgYKARMSARISFQHGX2Mi5E24BSGNkUA0Y80VIGvt9Q0175';  // Replace with your FCM server key

        $notification = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));

        $result = curl_exec($ch);
        if ($result === FALSE) {
            Log::error('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
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

    public function getChatList(int $userId)
    {
        return Chat::where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->whereHas('messages') // Filter only chats with messages
            ->with(['service:id,name,description,price', 'latestMessage'])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('chat_id', 'chats.id')
                    ->latest()
                    ->take(1)
            )
            ->paginate(15, ['id', 'service_id']);
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

    public function generatePusherToken($user, $socketId, $channelName)
    {
        $pusher = new Pusher(
            config('broadcasting.connections.reverb.key'),
            config('broadcasting.connections.reverb.secret'),
            config('broadcasting.connections.reverb.app_id'),
            // [
            //     'cluster' => config('broadcasting.connections.reverb.options.cluster'),
            //     'useTLS' => true
            // ]
        );

        $auth = $pusher->socket_auth($channelName, $socketId);

        if(!$auth){
            return $auth;
        }
        return ($auth);
    }
}
