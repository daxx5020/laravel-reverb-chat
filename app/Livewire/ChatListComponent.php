<?php

namespace App\Livewire;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Contracts\Database\Eloquent\Builder;
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
            ->where(function (Builder $query) {
                $query->where('buyer_id', auth()->id())
                    ->orWhere('seller_id', auth()->id());
            })
            ->addSelect(['latest_message_date' => Message::select('created_at')
                ->whereColumn('chat_id', 'chats.id')
                ->latest('created_at')
                ->limit(1)
            ])
            ->orderByDesc('latest_message_date')
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
        ->where(function (Builder $query) {
            $query->where('buyer_id', auth()->id())
                ->orWhere('seller_id', auth()->id());
        })
        ->addSelect(['latest_message_date' => Message::select('created_at')
            ->whereColumn('chat_id', 'chats.id')
            ->latest('created_at')
            ->limit(1)
        ])
        ->orderByDesc('latest_message_date')
        ->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.chat-list-component');
    }
}
