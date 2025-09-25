<?php

namespace App\Http\Requests\Court;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
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
            'venue_id' => 'required|exists:venues,id',
            'sport_id' => 'required|exists:sports,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courts,code',
            'description' => 'nullable|string',
            'surface_type' => 'required|string|max:100',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:1',
            'dimensions.width' => 'nullable|numeric|min:1',
            'dimensions.unit' => 'nullable|string|in:m,ft',
            'hourly_rate' => 'required|numeric|min:0',
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
            'venue_id.required' => 'Venue là bắt buộc.',
            'venue_id.exists' => 'Venue không tồn tại.',
            'sport_id.required' => 'Môn thể thao là bắt buộc.',
            'sport_id.exists' => 'Môn thể thao không tồn tại.',
            'name.required' => 'Tên sân là bắt buộc.',
            'name.max' => 'Tên sân không được vượt quá 255 ký tự.',
            'code.required' => 'Mã sân là bắt buộc.',
            'code.unique' => 'Mã sân đã tồn tại.',
            'surface_type.required' => 'Loại mặt sân là bắt buộc.',
            'hourly_rate.required' => 'Giá thuê theo giờ là bắt buộc.',
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

    /**
     * Get validated data with additional processing.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();
        $data['is_active'] = true; // Default active for new courts

        return $data;
    }
}
