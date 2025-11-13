<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
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
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'type' => $this->type,
            'target_users' => $this->target_users,
            'target_topic' => $this->target_topic,
            'total_sent' => $this->total_sent,
            'total_success' => $this->total_success,
            'total_failed' => $this->total_failed,
            'devices_sent' => $this->devices_sent,
            'devices_success' => $this->devices_success,
            'devices_failed' => $this->devices_failed,
            'status' => $this->status,
            'error_details' => $this->error_details,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}