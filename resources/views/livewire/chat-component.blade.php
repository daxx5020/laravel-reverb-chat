<div class="max-w-md mx-auto mt-6 bg-white rounded-2xl shadow-sm">
    <!-- Back Button -->
    <div class="p-4 border-b">
        <a href="{{ url()->previous() }}"
            class="inline-flex items-center text-primary hover:text-primary/90 font-medium transition-colors"
            wire:navigate>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>

    <!-- Title -->
    <h5 class="px-4 py-3 text-xl font-semibold text-primary">Chat Messages</h5>

    <!-- Service Details -->
    <div class="mx-4 mb-4 p-4 bg-gray-50 rounded-xl">
        <h6 class="text-lg font-semibold text-gray-900">{{ $service->name }}</h6>
        <p class="text-gray-600 mt-1">{{ Str::limit($service->description, 60) }}</p>
        <div class="mt-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-gray-700/30 flex items-center justify-center">
                    <span class="text-sm font-medium text-primary">{{ substr($service->user->name, 0, 2) }}</span>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Seller</p>
                    <p class="text-sm font-medium text-gray-900">{{ $service->user->name }}</p>
                </div>
            </div>
            <div class="text-lg font-semibold text-primary">${{ number_format($service->price, 2) }}</div>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="px-4 space-y-4 h-[400px] overflow-y-auto border-t py-4" id="chat-messages">

        @php($chatDates = [])

        @forelse ($chats as $chat)
        @php($date = \Carbon\Carbon::parse($chat['created_at'])->format('d M Y'))

        @if (!in_array($date, $chatDates) || count($chatDates) == 0)
            <div class="text-center text-gray-500 text-xs mb-2 bg-gray-100 p-1 rounded-xl w-[90px] mx-auto">
                {{ $date }}
            </div>
            @php($chatDates[] = $date)
        @endif

        <div @class([
            'max-w-[85%] p-3 rounded-2xl break-words',
            auth()->id() == $chat['sender_id']
                ? 'ml-auto bg-indigo-100 text-gray-700 rounded-br-sm'
                : 'bg-gray-100 text-gray-700 rounded-bl-sm'
        ])>
            @if($chat['message'])
                <p class="text-sm">{{ $chat['message'] }}</p>
            @endif

            @if(isset($chat['media']) && is_array($chat['media']) && count($chat['media']) > 0)
                @foreach($chat['media'] as $mediaItem)
                    @if (str_ends_with($mediaItem['file_path'], '.mp4') || str_ends_with($mediaItem['file_path'], '.webm'))
                        <!-- Video Rendering -->
                        <video controls class="mt-2 max-w-full h-auto rounded-lg">
                            <source src="{{ asset('storage/' . $mediaItem['file_path']) }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    @elseif (str_ends_with($mediaItem['file_path'], '.pdf'))
                        <!-- PDF Rendering -->
                        <iframe src="{{ asset('storage/' . $mediaItem['file_path']) }}" class="mt-2 max-w-full h-96" frameborder="0"></iframe>
                        <p class="mt-2 text-sm text-gray-500">
                            <a href="{{ asset('storage/' . $mediaItem['file_path']) }}" target="_blank" class="text-indigo-600 underline">Download PDF</a>
                        </p>
                    @else
                        <!-- Image Rendering -->
                        <img src="{{ asset('storage/' . $mediaItem['file_path']) }}" alt="Chat Media" class="mt-2 max-w-full h-auto rounded-lg">
                    @endif
                @endforeach
            @endif

            <span class="text-xs mt-1 opacity-70">{{ \Carbon\Carbon::parse($chat['created_at'])->format('g:i A') }}</span>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center h-full text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2 text-gray-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-sm font-medium">No messages yet</p>
            <p class="text-xs">Start the conversation!</p>
        </div>
        @endforelse

        <!-- Typing Indicator -->
        <div x-data="{
                    show: false,
                    typing: false,
                    listenForTypingEvent() {
                        $wire.on('is-typing', (event) => {
                            if (event.isTyping) {
                                this.isTyping();
                            }
                        });
                    },
                    isTyping() {
                        this.show = true;
                        this.typing = true;
                        if (this.typing) {
                            setTimeout(() => {
                                this.typing = false;
                                this.show = false;
                            }, 2000);
                        }
                    }
                }" x-init="listenForTypingEvent">
            <div x-show="show" class="w-max p-3 rounded-2xl break-words bg-gray-100 text-gray-700 rounded-bl-sm">
                <p class="text-sm italic">Typing ...</p>
            </div>
        </div>

    </div>

    <!-- Message Input -->
    <div class="p-4 border-t">
        <form wire:submit.prevent="submitMessage" class="flex items-center gap-2">
            <input
                wire:model.live.debounce.250ms="message"
                type="text"
                placeholder="Type your message..."
                class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-colors"
            >

            <!-- File Input for Image Upload -->
            <input
                type="file"
                wire:model="image"
                class="hidden"
                id="imageUpload"
                accept="image/*"
            >

            <label for="imageUpload" class="cursor-pointer inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-gray-700 hover:bg-indigo-100 hover:text-gray-700/90 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16s1-2 4-2 5 1 9 1 6 3 6 3M4 4h16M4 8h16" />
                </svg>
            </label>

            <button
                type="submit"
                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-gray-700 text-white hover:bg-indigo-100 text-gray-700/90 transition-colors rotate-90"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>

        <!-- Display Uploaded Image Preview -->
        @if ($image)
            <div class="mt-2 text-center">
                <img src="{{ $image->temporaryUrl() }}" class="max-w-xs rounded-lg mx-auto">
            </div>
        @endif
    </div>
</div>

@script
<script>
    // Auto-scroll to bottom of chat messages
    document.addEventListener('livewire:load', function () {
        const messagesContainer = document.getElementById('chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        Livewire.hook('message.processed', () => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });

        Livewire.on('is-typing', (event) => {
            console.log("New Event:", event);
        });
    });

</script>
@endscript
