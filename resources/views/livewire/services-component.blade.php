<div class="container mx-auto py-8">
    <!-- Page Title -->
    <h1 class="text-3xl font-bold text-center text-indigo-600 mb-8">Available Services</h1>
    
    <!-- Services Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-5">
        @forelse ($services as $service)
            <!-- Service Card -->
            <div class="p-6 bg-white rounded-lg shadow-md border border-gray-200 flex flex-col justify-between h-full">
                <!-- Service Name -->
                <h2 class="text-xl font-semibold text-gray-800 mb-2">{{ $service->name }}</h2>
                
                <!-- Description -->
                <p class="text-gray-600 mb-4">{{ $service->description }}</p>

                <!-- Price -->
                <p class="text-lg font-bold text-indigo-500 mb-4">${{ number_format($service->price, 2) }}</p>

                <!-- Seller Information -->
                <div class="text-sm text-gray-500 mb-4">
                    <p>Seller: <span class="font-medium text-gray-800">{{ $service->user->name }}</span></p>
                    <p>Email: <span class="font-medium">{{ $service->user->email }}</span></p>
                </div>

                <!-- Start Chat Button -->
                <a href="{{ route('test.chat', $service) }}" class="inline-block w-full text-center bg-indigo-500 text-white py-2 rounded-lg font-semibold hover:bg-indigo-600 transition duration-200" wire:navigate>
                    Start Chat
                </a>
            </div>
        @empty
            <!-- No Services Found Message -->
            <p class="col-span-full text-center text-gray-500">No Services found!</p>
        @endforelse
    </div>
</div>
