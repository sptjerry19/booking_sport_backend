<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class ChangeEmailRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'new_email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string',
        ];
    }
}
