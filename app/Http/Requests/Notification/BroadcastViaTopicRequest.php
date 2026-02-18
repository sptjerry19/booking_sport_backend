<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseRequest;
use App\Models\Notification;

class BroadcastViaTopicRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:general,promotion,news,system', // Hardcoded for simplicity as ENUM values might not be available here directly without import
        ];
    }
}
