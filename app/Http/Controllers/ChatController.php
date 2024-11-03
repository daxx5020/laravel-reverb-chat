<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Service;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService){
        $this->chatService = $chatService;
    }

    public function register(Request $request)
    {
        // Validate the incoming request, including the 'role' field
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:buyer,seller'
        ]);

        // Create the user with the validated data
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role, // Store the role in the database
        ]);

        // Generate an access token for the user
        $token = $user->createToken('AuthToken')->accessToken;

        // Return the generated token in the response
        return response()->json(['token' => $token], 201);
    }


    // User Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('AuthToken')->accessToken;

        return response()->json(['token' => $token], 200);
    }

    // Get Authenticated User
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function createChat(Request $request){

        $serviceId = $request->input('service_id');
        $buyerId = $request->input('buyer_id');
        $sellerId = $request->input('seller_id');

        $chat = $this->chatService->findChatApi($serviceId, $buyerId, $sellerId);

        return response()->json($chat, 200);
    }

    public function sendMessage(Request $request){
        $chatId = $request->input('chat_id');
        $senderId = $request->input('sender_id');
        $messageContent = $request->input('message');
        $imagePath = null;

        // Check if the request has an image file and store it
        if ($request->hasFile('image_path')) {
            $image = $request->file('image_path');
            $imagePath = $image->store('chat_images', 'public');
        }

        // Call the service to handle the message storage
        $message = $this->chatService->sendMessageApi($chatId, $senderId, $messageContent, $imagePath);

        return response()->json($message, 201);
    }

    public function chatList(Request $request){
        $userId = $request->input('user_id');

        $chats = Chat::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->with('service:id,name,description,price')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['id', 'service_id']);

        return response()->json($chats, 200);
    }

    public function getMessages(Request $request){
        $chatId = $request->input('chat_id');
        $messages = $this->chatService->getMessagesApi($chatId);

        $formattedMessages = $messages->getCollection()->transform(function ($message) {
            return [
                'sender_id' => $message->sender_id,
                'message' => $message->message,
                'created_at' => $message->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'current_page' => $messages->currentPage(),
            'data' => $formattedMessages,
            'last_page' => $messages->lastPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
        ], 200);
    }
}
