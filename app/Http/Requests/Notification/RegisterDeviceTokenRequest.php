<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseRequest;

class RegisterDeviceTokenRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
        ];
    }
}
