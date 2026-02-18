<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class UpdateProfileRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $userId = $user ? $user->id : null;

        return [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $userId,
            'level' => 'nullable|string|in:beginner,intermediate,advanced',
            'preferred_sports' => 'nullable|array',
            'preferred_sports.*' => 'integer|exists:sports,id',
            'preferred_position' => 'nullable|array',
        ];
    }
}
