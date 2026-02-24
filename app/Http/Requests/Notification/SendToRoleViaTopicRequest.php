<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseRequest;

class SendToRoleViaTopicRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'required|string|exists:roles,name',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:general,promotion,news,system',
        ];
    }
}
