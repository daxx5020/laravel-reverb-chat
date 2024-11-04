<?php

namespace App\Livewire;

use App\Events\NewMessageEvent;
use App\Models\Chat;
use App\Models\Service;
use App\Models\User;
use App\Services\ChatService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatComponent extends Component
{
    public $message = "";
    public $chats = [];
    public $serviceId;
    public $chatId;

    public Service $service;
    private ChatService $chatService;
    private Chat $chat;

    public function mount(Service $service, ChatService $chatService)
    {
        // Assigning objects to private properties
        $this->service = $service;
        $this->chatService = $chatService;

        // Use IDs as public properties for Livewire compatibility
        $this->serviceId = $service->id;
        
        $this->chat = $this->chatService->findChat(
            $service->id,
            auth()->id(),
            $service->user_id
        );

        $this->chatId = $this->chat->id;
        $this->chats = $this->chatService->getMessages($this->chatId)->toArray();
    }

    public function updatedMessage(ChatService $chatService)
    {
        // Send typing event
        $chatService->sendMessageTyping($this->chatId, auth()->id());
    }
    

    public function submitMessage(ChatService $chatService) {
        // Use the chatService for the message submission
        $chatService->sendMessage($this->chatId, auth()->id(), $this->message);
        $this->message = "";
    }

    public function getListeners()
    {
        return [
            "echo-private:chat.{$this->chatId},NewMessageEvent" => 'listenForMessage',
            "echo-private:chat.{$this->chatId},MessageTypingEvent" => 'listenForTyping',
        ];
    }

    public function listenForMessage($data) {
        if(isset($data['message'])) {
            $this->chats[] = $data['message'];
        }
    }

    public function listenForTyping($data) {
        if(isset($data['senderId']) && $data['senderId'] != auth()->id()) {
            $this->dispatch('is-typing', isTyping: true); 
        }
    }

    #[Layout('layouts.app')]
    public function render() {
        return view('livewire.chat-component');
    }
}
