<div class="max-w-md mx-auto mt-6 p-6 bg-white border border-gray-300 shadow-lg rounded-lg">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ url()->previous() }}" class="inline-flex items-center text-indigo-500 hover:text-indigo-600 font-semibold transition duration-200" wire:navigate>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back
        </a>
    </div>

    <!-- Title -->
    <h5 class="text-xl font-semibold text-indigo-600 mb-4">Chat Messages</h5>
    
    <!-- Service Details -->
    <div class="mb-4 p-4 bg-gray-100 rounded-lg border border-gray-200">
        <!-- Service Name -->
        <h6 class="text-lg font-semibold text-gray-800">{{ $service->name }}</h6>

        <!-- Description -->
        <p class="text-gray-600 text-sm mt-1">{{ Str::limit($service->description, 60) }}</p>

        <!-- Seller Information and Price -->
        <div class="mt-2 flex justify-between items-center text-sm text-gray-500">
            <div>
                <span class="font-medium text-gray-700">Seller: </span>{{ $service->user->name }}
            </div>
            <div class="text-indigo-500 font-semibold">${{ number_format($service->price, 2) }}</div>
        </div>
    </div>
    <!-- End Service Details -->

    <!-- Chat box container -->
    <div class="chat-box bg-gray-50 h-80 p-4 mb-4 border border-gray-200 rounded-lg overflow-y-auto">
        @forelse ($chats as $chat)
            <div class="p-3 mb-3 bg-indigo-100 text-gray-700 rounded-lg shadow-sm">
                <span>{{ $chat }}</span>
            </div>
        @empty
            <p class="text-gray-500 text-center">No Chat Found!</p>
        @endforelse
    </div>
    
    <!-- Input and Submit button -->
    <div class="flex items-center">
        <input 
            wire:model="message" 
            type="text" 
            placeholder="Type your message..."
            class="flex-grow px-4 py-2 mr-2 bg-white border border-gray-300 rounded-full focus:outline-none focus:ring focus:ring-indigo-200"
        >
        <button 
            wire:click="submitMessage" 
            class="bg-indigo-500 text-white px-4 py-2 rounded-full hover:bg-indigo-600 transition duration-200"
        >
            Send
        </button>
    </div>
</div>
