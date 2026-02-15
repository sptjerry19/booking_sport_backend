<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class PublicChatController extends Controller
{
    protected FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Check if chat room exists and return basic info
     */
    public function check($uuid)
    {
        $room = ChatRoom::where('uuid', $uuid)->with(['booking.court.venue'])->firstOrFail();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $room->id, // Guest shouldn't necessarily know internal ID, but useful for API consistency
                'uuid' => $room->uuid,
                'venue_name' => $room->booking->court->venue->name,
                'court_name' => $room->booking->court->name,
                'booking_status' => $room->booking->status
            ]
        ]);
    }

    /**
     * Get messages for public chat room
     */
    public function getMessages(Request $request, $uuid)
    {
        $room = ChatRoom::where('uuid', $uuid)->firstOrFail();
        $lastId = $request->query('last_id', 0);

        $messages = $room->messages()
            ->where('id', '>', $lastId)
            ->with('sender:id,name') // Load sender info for UI
            ->orderBy('created_at', 'asc')
            ->get();

        // Transform to show sender name if guest
        $data = $messages->map(function ($msg) {
             // For guest messages, we use sender_name
             // For signed-in users, we might want to resolve name from sender_id if relation loaded
             return $msg;
        });

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Send message as guest
     */
    public function sendMessage(Request $request, $uuid)
    {
        $room = ChatRoom::where('uuid', $uuid)->firstOrFail();
        
        $request->validate([
            'type' => 'required|in:TEXT',
            'content' => 'required',
            'sender_name' => 'required|string|max:50'
        ]);

        $message = ChatMessage::create([
            'chat_room_id' => $room->id,
            'sender_id' => null, // Guest has no user ID
            'sender_name' => $request->sender_name,
            'sender_role' => 'GUEST',
            'type' => 'TEXT', // Guests only send text for now
            'content' => $request->content, // JSON cast handled by model
        ]);

        // Push Notification Logic for Guest message
        try {
            $booking = $room->booking;
            $venueName = $booking->court->venue->name;
            $title = "Khách vãng lai nhắn tin ({$venueName})";
            $body = "{$request->sender_name}: " . ($request->content['text'] ?? $request->content);
            
            // Recipients: Owner and Booker
            $recipientIds = array_filter([
                $booking->court->venue->owner_id,
                $booking->user_id
            ]);

            if (!empty($recipientIds)) {
                $this->fcmService->sendBatchNotification($recipientIds, $title, $body, [
                    'type' => 'chat_message',
                    'booking_id' => (string) $booking->id,
                    'chat_room_id' => (string) $room->id,
                    'is_guest' => 'true'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Guest Chat Push Notification failed: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }
}
