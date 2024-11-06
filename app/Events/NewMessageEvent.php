<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        $channels = [];

        $channels[] = new PrivateChannel('chat.'.$this->message->chat_id);
        if(isset($this->message->chat)) {
            $userId = ($this->message->sender_id == $this->message->chat->buyer_id ? $this->message->chat->seller_id : $this->message->chat->buyer_id);
            $channels[] = new PrivateChannel('user.'. $userId);
        }

        Log::info('Broadcasting NewMessageEvent', ['channels' => $channels]);

        return $channels;
    }
}
