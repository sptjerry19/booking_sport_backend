<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'nullable|array',
            'type' => 'nullable|string|in:general,booking,reminder,promo',
            'target_users' => 'nullable|array',
            'target_users.*' => 'integer|exists:users,id',
            'target_topic' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.string' => 'The title must be a string.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'body.required' => 'The body field is required.',
            'body.string' => 'The body must be a string.',
            'data.array' => 'The data must be an array.',
            'type.string' => 'The type must be a string.',
            'type.in' => 'The selected type is invalid.',
            'target_users.array' => 'The target users must be an array.',
            'target_users.*.integer' => 'Each target user ID must be an integer.',
            'target_users.*.exists' => 'One or more target user IDs do not exist.',
            'target_topic.string' => 'The target topic must be a string.',
            'target_topic.max' => 'The target topic may not be greater than 255 characters.',
        ];
    }
}
