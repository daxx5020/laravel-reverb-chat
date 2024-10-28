<?php

namespace App\Livewire;

use App\Events\NewMessageEvent;
use App\Models\Service;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatComponent extends Component
{
    public $message = "";
    public Service $service;
    public $chats = [];

    public function mount(Service $service)
    {
        $this->service = $service;
    }
    
    public function submitMessage(){
        event(new NewMessageEvent($this->message));
        $this->message = "";
    }

    #[On('echo:messages,NewMessageEvent')]
    public function listenForMessage($data){
       $this->chats[] = $data['message'];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.chat-component');
    }
}
