<?php

namespace App\Livewire;

use App\Models\Chat;
use App\Models\Service;
use App\Services\ChatService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class SellerChatComponent extends Component
{
    use WithFileUploads;

    public $message = "";
    public $chats = [];
    public $serviceId;
    public $chatId;
    public $image;

    protected $rules = [
        'image' => 'nullable|image|max:1024',  // Set max size to 1MB
    ];

    public Service $service;
    private ChatService $chatService;
    private Chat $chat;

    public function mount(Chat $chat, ChatService $chatService)
    {
        // Assigning objects to private properties
        $this->chatService = $chatService;
        $this->chat = $chat;

        $this->service = $this->chat->service;
        $this->chatId = $this->chat->id;
        $this->chats = $this->chatService->getMessages($this->chatId)->toArray();
    }

    public function submitMessage(ChatService $chatService) {
        // Use the chatService for the message submission
        if ($this->image) {
            // Store the image and get the path
            $imagePath = $this->image->store('chat_images', 'public');
            $chatService->sendMessage($this->chatId, auth()->id(), null, $imagePath);  // Send image path
            $this->image = null;  // Reset image
        } else {
            $chatService->sendMessage($this->chatId, auth()->id(), $this->message);  // Send text message
        }

        $this->message = "";
    }

    public function getListeners()
    {
        return [
            "echo-private:chat.{$this->chatId},NewMessageEvent" => 'listenForMessage',
        ];
    }

    public function listenForMessage($data) {
        if(isset($data['message'])) {
            $this->chats[] = $data['message'];
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.seller-chat-component');
    }
}
