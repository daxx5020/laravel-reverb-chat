<div class="p-4 bg-white rounded-lg shadow-md m-4">
    <h2 class="text-xl font-semibold mb-4">Chat Conversations</h2>
    
    <div class="space-y-4">
        @forelse($chats as $chat)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg shadow-sm hover:bg-gray-100">
                <!-- Chat Info Section -->
                <div class="flex flex-col w-2/3">
                    <p class="text-gray-800 font-semibold">
                        Service: {{ $chat->service->name }}
                    </p>
                    <p class="text-gray-600 text-sm">
                        Seller: {{ $chat->seller->name }} | Price: ${{ number_format($chat->service->price, 2) }}
                    </p>
                </div>

                <!-- Latest Message Section -->
                <div class="flex-1 px-4 text-gray-700 text-sm truncate">
                    {{ $chat->latestMessage?->message ?? 'No messages yet.' }}
                </div>
                
                <!-- View Chat Button Section -->
                <div class="flex-shrink-0">
                    <a href="{{ auth()->user()->role == 'seller' ? route('test.seller-chat', $chat) : route('test.chat', $chat->service) }}" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600" wire:navigate>
                        View Chat
                    </a>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center">No chats found.</p>
        @endforelse
    </div>
</div>
