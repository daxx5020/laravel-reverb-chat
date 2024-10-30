<div class="max-w-md mx-auto mt-6 bg-white rounded-2xl shadow-sm">
    <!-- Back Button -->
    <div class="p-4 border-b">
        <a href="{{ url()->previous() }}" class="inline-flex items-center text-primary hover:text-primary/90 font-medium transition-colors" wire:navigate>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>

    <!-- Title -->
    <h5 class="px-4 py-3 text-xl font-semibold text-primary">Chat Messages</h5>

    <!-- Service Details -->
    <div class="mx-4 mb-4 p-4 bg-gray-50 rounded-xl">
        <!-- Service Name -->
        <h6 class="text-lg font-semibold text-gray-900">{{ $service->name }}</h6>

        <!-- Description -->
        <p class="text-gray-600 mt-1">{{ Str::limit($service->description, 60) }}</p>

        <!-- Seller Information and Price -->
        <div class="mt-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-gray-700/30 flex items-center justify-center">
                    <span class="text-sm font-medium text-primary">
                        {{ substr($service->user->name, 0, 2) }}
                    </span>
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
    <div class="px-4 space-y-4 h-[400px] overflow-y-auto border-t pt-4" id="chat-messages">

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
                <p class="text-sm">{{ $chat['message'] }}</p>
                <span class="text-xs mt-1 opacity-70">{{ \Carbon\Carbon::parse($chat['created_at'])->format('g:i A') }}</span>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-full text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <p class="text-sm font-medium">No messages yet</p>
                <p class="text-xs">Start the conversation!</p>
            </div>
        @endforelse
    </div>

    <!-- Message Input -->
    <div class="p-4 border-t mt-4">
        <form wire:submit.prevent="submitMessage" class="flex items-center gap-2">
            <input 
                wire:model="message" 
                type="text" 
                placeholder="Type your message..."
                class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-full text-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-colors"
            >
            <button 
                type="submit"
                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-gray-700 text-white hover:bg-indigo-100 text-gray-700/90 transition-colors rotate-90"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
    // Auto-scroll to bottom of chat messages
    document.addEventListener('livewire:load', function () {
        const messagesContainer = document.getElementById('chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        Livewire.hook('message.processed', () => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        });
    });
</script>