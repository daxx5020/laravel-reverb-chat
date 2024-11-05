<?php

namespace App\Livewire;

use App\Models\Chat;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ChatListComponent extends Component
{
    public $chats;

    public function mount()
    {
        // Load all chats associated with the authenticated user as buyer or seller
        $this->chats = Chat::with(['buyer', 'seller', 'service'])
            ->whereHas('messages')
            ->where('buyer_id', auth()->id())
            ->orWhere('seller_id', auth()->id())
            ->get();
    }

    /**
     * Get the channels the event should broadcast on.
    */
    public function getListeners()
    {
        $authId = auth()->id();

        return [
            "echo-private:user.{$authId},NewMessageEvent" => 'listenForMessage',
        ];
    }

    public function listenForMessage($data) {
        $this->chats = Chat::with(['buyer', 'seller', 'service'])
        ->whereHas('messages')
        ->where('buyer_id', auth()->id())
        ->orWhere('seller_id', auth()->id())
        ->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.chat-list-component');
    }
}
