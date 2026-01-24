<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_room_id',
        'sender_id',
        'sender_name',
        'sender_role',
        'type',
        'content',
    ];

    protected $casts = [
        'content' => 'json'
    ];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class);
    }

    // Optional: relationship to sender (User model)
    // Note: sender_id could correspond to User ID or Owner ID (which is also a User usually)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
