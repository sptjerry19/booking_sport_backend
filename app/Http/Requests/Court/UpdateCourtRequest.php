<?php

namespace App\Http\Requests\Court;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourtRequest extends FormRequest
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
            'venue_id' => 'sometimes|exists:venues,id',
            'sport_id' => 'sometimes|exists:sports,id',
            'name' => 'sometimes|string|max:255',
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('courts', 'code')->ignore($this->route('court')->id)
            ],
            'description' => 'nullable|string',
            'surface_type' => 'sometimes|string|max:100',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:1',
            'dimensions.width' => 'nullable|numeric|min:1',
            'dimensions.unit' => 'nullable|string|in:m,ft',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'venue_id.exists' => 'Venue không tồn tại.',
            'sport_id.exists' => 'Môn thể thao không tồn tại.',
            'name.max' => 'Tên sân không được vượt quá 255 ký tự.',
            'code.unique' => 'Mã sân đã tồn tại.',
            'hourly_rate.numeric' => 'Giá thuê theo giờ phải là số.',
            'hourly_rate.min' => 'Giá thuê theo giờ không được âm.',
            'dimensions.length.numeric' => 'Chiều dài phải là số.',
            'dimensions.width.numeric' => 'Chiều rộng phải là số.',
            'dimensions.unit.in' => 'Đơn vị phải là m hoặc ft.',
            'images.*.image' => 'File phải là hình ảnh.',
            'images.*.mimes' => 'Hình ảnh phải có định dạng: jpeg, png, jpg, gif.',
            'images.*.max' => 'Kích thước hình ảnh không được vượt quá 2MB.',
        ];
    }
}
