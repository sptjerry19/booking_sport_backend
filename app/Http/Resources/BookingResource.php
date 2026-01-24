<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ],
            'court' => [
                'id' => $this->court->id,
                'name' => $this->court->name,
                'code' => $this->court->code,
                'venue' => [
                    'id' => $this->court->venue->id,
                    'name' => $this->court->venue->name,
                    'address' => $this->court->venue->address,
                ],
                'sport' => [
                    'id' => $this->court->sport->id,
                    'name' => $this->court->sport->name,
                ],
            ],
            'booking_date' => $this->booking_date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_in_minutes,
            'total_amount' => (float) $this->total_amount,
            'discount_amount' => (float) $this->discount_amount,
            'final_amount' => (float) $this->final_amount,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'is_cancellable' => $this->isCancellable(),
            'chat_room' => $this->chatRoom ? [
                'id' => $this->chatRoom->id,
                'uuid' => $this->chatRoom->uuid,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

