<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user(); // Simplified authorization for now
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|string|max:500',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:100',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'opening_time' => 'sometimes|date_format:H:i',
            'closing_time' => 'sometimes|date_format:H:i|after:opening_time',
            'status' => 'sometimes|in:active,inactive,pending,suspended',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Tên venue không được vượt quá 255 ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',
            'latitude.between' => 'Vĩ độ phải từ -90 đến 90.',
            'longitude.between' => 'Kinh độ phải từ -180 đến 180.',
            'email.email' => 'Email không đúng định dạng.',
            'opening_time.date_format' => 'Giờ mở cửa phải có định dạng H:i (VD: 08:00).',
            'closing_time.date_format' => 'Giờ đóng cửa phải có định dạng H:i (VD: 22:00).',
            'closing_time.after' => 'Giờ đóng cửa phải sau giờ mở cửa.',
            'status.in' => 'Trạng thái phải là: active, inactive, pending, suspended.',
            'images.*.image' => 'File phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'images.*.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name),
            ]);
        }
    }
}
