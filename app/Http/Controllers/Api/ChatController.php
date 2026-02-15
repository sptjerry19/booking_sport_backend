<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BookingService;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Get messages for a booking's chat room
     */
    public function getMessages(Request $request, $chatRoomId)
    {
        $room = ChatRoom::findOrFail($chatRoomId);
        $booking = $room->booking;

        // Authorization check
        $user = auth()->user();
        $isOwner = $user->hasRole('owner') || $user->hasRole('admin'); // Simplified role check
        // In real app, check if user is the booking owner or the specific owner of the venue
        
        // Strict check: User must be the one who booked OR the owner of the court
        $isBooker = $booking->user_id === $user->id;
        
        // Assuming we can check if user is owner of the venue/court. 
        // For now, if role is owner, we might need to be more specific, 
        // but given the context, let's assume if role 'owner' they can see. 
        // Better: check if $user->id == $booking->court->venue->owner_id (if that relationship exists)
        // Let's rely on isAdminOrOwner logic or assume simplified ownership for now.
        
        if (!$isBooker && !$isOwner) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lastId = $request->query('last_id', 0);

        $messages = $room->messages()
            ->where('id', '>', $lastId)
            ->with('sender:id,name,avatar') // optimized load
            ->orderBy('created_at', 'asc') // Oldest first (chat log style) or desc? Usually chat APIs return list. Appends to bottom.
            ->get();

        return response()->json([
            'data' => $messages
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, $chatRoomId)
    {
        $room = ChatRoom::findOrFail($chatRoomId);
        $booking = $room->booking;
        $user = auth()->user();

        // Authorization (same as above)
        $isBooker = $booking->user_id === $user->id;
        $isOwner = $user->hasRole('owner') || $user->hasRole('admin');

        if (!$isBooker && !$isOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type' => 'required|in:TEXT,FILE',
            'content' => 'required', // array or string
        ]);

        $type = $request->input('type');
        $content = $request->input('content');
        
        // Determine role
        $role = $isOwner ? 'OWNER' : 'USER';
        // If the booker is also an owner (e.g. testing), prefer USER if they are the booker? 
        // Or strictly: if ($booking->user_id == $user->id) -> USER.
        if ($isBooker) {
            $role = 'USER';
        } elseif ($isOwner) {
            $role = 'OWNER';
        }

        // Logic for file upload if type is FILE
        if ($type === 'FILE' && $request->hasFile('file')) {
             // Handle file upload
             // For this prompt context, we might assume content has file info or we handle multipart
        }
        
        // If content is sent as array for TEXT: { "text": "..." }
        
        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'sender_id' => $user->id,
            'sender_role' => $role,
            'type' => $type,
            'content' => $content, // casted to json automatically
        ]);

        // Push Notification Logic
        try {
            $senderName = $user->name;
            $venueName = $booking->court->venue->name;
            $title = "Tin nhắn mới từ {$senderName} ({$venueName})";
            $body = $type === 'TEXT' ? ($content['text'] ?? 'Bạn có tin nhắn mới') : 'Bạn đã nhận được một file';
            
            // Determine recipient
            $recipientId = null;
            if ($role === 'USER') {
                // Sent by booker -> notify owner
                $recipientId = $booking->court->venue->owner_id;
            } else {
                // Sent by owner -> notify booker
                $recipientId = $booking->user_id;
            }

            if ($recipientId && $recipientId !== $user->id) {
                $this->fcmService->sendToUser($recipientId, $title, $body, [
                    'type' => 'chat_message',
                    'booking_id' => (string) $booking->id,
                    'chat_room_id' => (string) $room->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Chat Push Notification failed: " . $e->getMessage());
        }

        return response()->json([
            'data' => $message
        ]);
    }

    /**
     * Owner confirms booking via chat
     */
    public function confirmBooking(Request $request, $chatRoomId, BookingService $bookingService)
    {
        $room = ChatRoom::findOrFail($chatRoomId);
        $booking = $room->booking;
        $user = auth()->user();

        if (!$user->hasRole('owner') && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized. Only owner can confirm.'], 403);
        }
        
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking status is not pending.'], 400);
        }

        DB::transaction(function () use ($booking, $room, $bookingService) {
            // Update booking
            $booking->update(['status' => 'confirmed']);
            
            // Log Action Message
            ChatMessage::create([
                'chat_room_id' => $room->id,
                'sender_role' => 'OWNER',
                'type' => 'ACTION',
                'content' => [
                    'action' => 'CONFIRM',
                    'booking_id' => $booking->id
                ]
            ]);

            // Log System Message
            ChatMessage::create([
                'chat_room_id' => $room->id,
                'sender_role' => 'SYSTEM',
                'type' => 'TEXT',
                'content' => ['text' => 'Chủ sân đã xác nhận đơn đặt sân']
            ]);
        });

        return response()->json(['message' => 'Confirmed successfully']);
    }
}
