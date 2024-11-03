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
use Livewire\WithFileUploads;

class ChatComponent extends Component
{
    use WithFileUploads;

    public $message = "";
    public $chats = [];
    public $serviceId;
    public $chatId;
    public $image;

    protected $rules = [
        'image' => 'nullable|image|max:1024', // 1MB Max
    ];

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

    public function submitMessage(ChatService $chatService) {
        // Use the chatService for the message submission
        if ($this->image) {
            $imagePath = $this->image->store('chat_images', 'public');
            $chatService->sendMessage($this->chatId, auth()->id(), null, $imagePath);
            $this->image = null;
        } else {
            $chatService->sendMessage($this->chatId, auth()->id(), $this->message);
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
    public function render() {
        return view('livewire.chat-component');
    }
}
