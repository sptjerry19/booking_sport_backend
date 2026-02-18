<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseRequest;

class RemoveDeviceTokenRequest extends BaseRequest
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
        ];
    }
}
