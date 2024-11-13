<?php

namespace App\Services;

use App\Events\MessageTypingEvent;
use App\Events\NewMessageEvent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Pusher\Pusher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
    public function sendMessage($chatId, $senderId, $messageText = null, $imagePath = null)
    {
        // Create the message
        $message = Message::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'message' => $messageText,
            'image_path' => $imagePath,
        ]);

        // Trigger the NewMessageEvent
        event(new NewMessageEvent($message));

        $chat = $message->chat;
        $recipientId = ($senderId == $chat->buyer_id) ? $chat->seller_id : $chat->buyer_id;
        $recipient = User::find($recipientId);

        if ($recipient && $recipient->fcm_token) {
            // Send FCM notification
            $this->sendFcmNotification(
                $recipient->fcm_token,
                'New Message',
                $messageText ??  'You have a new image message'
            );
        }
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

    /**
     * Find Chat
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


    /**
     * Create chat
    */
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


    /**
     * Chat list
    */

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

    /**
     * Send message
    */
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

        $chat = $message->chat;
        $recipientId = ($senderId == $chat->buyer_id) ? $chat->seller_id : $chat->buyer_id;
        $recipient = User::find($recipientId);

        if ($recipient && $recipient->fcm_token) {
            // Send FCM notification
            $this->sendFcmNotification(
                $recipient->fcm_token,
                'New Message',
                $messageContent ?? 'You have a new image message'
            );
        }

        return $message;
    }

    /**
     * Get messages
    */

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

    private function sendFcmNotification($token, $title, $body)
    {
        $fcmUrl = 'https://fcm.googleapis.com/v1/projects/' . config('services.fcm.project_id') . '/messages:send';
        $accessToken = $this->getAccessTokenFromServiceAccount();

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $notificationData = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));

        $result = curl_exec($ch);
        // dd($result);
        if ($result === FALSE) {
            Log::error('FCM Send Error: ' . curl_error($ch));
        } else {
            Log::info('FCM Notification sent: ' . $result);
        }
        curl_close($ch);
    }

    private function getAccessTokenFromServiceAccount()
    {
        // Check if the access token is cached
        if (Cache::has('fcm_access_token')) {
            return Cache::get('fcm_access_token');
        }

        // Load service account credentials
        $credentialsPath = config('services.fcm.credentials_path');
        $credentials = json_decode(file_get_contents($credentialsPath), true);

        // Build the JWT header and payload for Google OAuth 2.0
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $now = time();
        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600, // Token expiration time (1 hour)
            'iat' => $now,
        ];

        // Encode header and payload to Base64
        $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        // Sign the JWT using the private key
        $signature = '';
        openssl_sign(
            $base64UrlHeader . "." . $base64UrlPayload,
            $signature,
            $credentials['private_key'],
            'sha256'
        );
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        // Construct the JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        // Exchange the JWT for an access token
        $postData = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

        $result = curl_exec($ch);
        if ($result === FALSE) {
            Log::error('Error fetching access token: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        $data = json_decode($result, true);
        curl_close($ch);

        if (isset($data['access_token'])) {
            // Cache the access token for an hour minus a safety buffer
            Cache::put('fcm_access_token', $data['access_token'], $data['expires_in'] - 60);
            return $data['access_token'];
        }

        Log::error('Failed to obtain access token. Response: ' . json_encode($data));
        return null;
    }
}
