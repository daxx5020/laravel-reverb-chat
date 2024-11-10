<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Service;
use App\Models\User;
use App\Models\Media;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

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

    public function createChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:services,id',
            'buyer_id' => 'required|integer|exists:users,id',
            'seller_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $serviceId = $request->input('service_id');
        $buyerId = $request->input('buyer_id');
        $sellerId = $request->input('seller_id');

        try {
            // Step 2: Try to find or create the chat
            $chat = $this->chatService->findChatApi($serviceId, $buyerId, $sellerId);

            // Step 3: Return successful response if chat is found or created
            return response()->json([
                'success' => true,
                'message' => $chat->wasRecentlyCreated
                    ? 'New chat created successfully.'
                    : 'Chat retrieved successfully.',
                'chat' => $chat,
            ], 200);

        } catch (\Exception $e) {
            // Step 4: Handle unexpected errors gracefully
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|integer|exists:chats,id',
            'sender_id' => 'required|integer|exists:users,id',
            'message' => 'required|string|max:5000',
            'media.*' => 'file|max:15120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chatId = $request->input('chat_id');
        $senderId = $request->input('sender_id');
        $messageContent = $request->input('message');

        $mediaPaths = [];

        // Step 2: Handle Media Files if Provided
        if ($request->hasFile('media')) {
            $files = is_array($request->file('media')) ? $request->file('media') : [$request->file('media')];

            foreach ($files as $file) {
                $fileType = $file->getMimeType();
                $fileExtension = $file->getClientOriginalExtension();

                // Determine the media type
                if (str_starts_with($fileType, 'image/')) {
                    $mediaType = 'image';
                } elseif (str_starts_with($fileType, 'video/')) {
                    $mediaType = 'video';
                } elseif (in_array($fileExtension, ['pdf', 'doc', 'docx'])) {
                    $mediaType = 'document';
                } else {
                    // Skip unsupported file types
                    continue;
                }

                // Store the file and get the path
                $storedPath = Media::storeFile($file, $mediaType);
                $mediaPaths[] = [
                    'file_path' => $storedPath,
                    'file_type' => $mediaType,
                    'mime_type' => $fileType,
                    'size' => $file->getSize(),
                ];
            }
        }

        try {
            $message = $this->chatService->sendMessageApi($chatId, $senderId, $messageContent, $mediaPaths);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => $message,
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the message.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function chatList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = $request->input('user_id');

        try {
            $chats = Chat::where('buyer_id', $userId)
                ->orWhere('seller_id', $userId)
                ->with(['service:id,name,description,price', 'latestMessage'])
                ->orderByDesc(
                    Message::select('created_at')
                        ->whereColumn('chat_id', 'chats.id')
                        ->latest()
                        ->take(1)
                )
                ->paginate(15, ['id', 'service_id']);

            return response()->json([
                'success' => true,
                'message' => 'Chat list retrieved successfully.',
                'data' => $chats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the chat list.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getMessages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|integer|exists:chats,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chatId = $request->input('chat_id');

        try {
            $messages = $this->chatService->getMessagesApi($chatId);

            $formattedMessages = $messages->transform(function ($message) {
                $media = $message->media->map(function ($mediaItem) {
                    return [
                        'file_path' => $mediaItem->url,
                        'file_type' => $mediaItem->file_type,
                        'mime_type' => $mediaItem->mime_type,
                        'size' => $mediaItem->size,
                    ];
                });

                return [
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'message' => $message->message,
                    'created_at' => $message->created_at->toIso8601String(),
                    'media' => $media,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Messages retrieved successfully.',
                'current_page' => $messages->currentPage(),
                'data' => $formattedMessages,
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving messages.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

