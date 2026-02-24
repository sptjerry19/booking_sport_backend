<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseRequest;

class SendNotificationToUsersRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:general,booking,reminder,promo',
        ];
    }
}
