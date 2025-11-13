<?php

namespace App\Http\Requests\Venue;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:100',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i|after:opening_time',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên venue là bắt buộc.',
            'name.max' => 'Tên venue không được vượt quá 255 ký tự.',
            'address.required' => 'Địa chỉ là bắt buộc.',
            'latitude.required' => 'Vĩ độ là bắt buộc.',
            'latitude.between' => 'Vĩ độ phải từ -90 đến 90.',
            'longitude.required' => 'Kinh độ là bắt buộc.',
            'longitude.between' => 'Kinh độ phải từ -180 đến 180.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'opening_time.required' => 'Giờ mở cửa là bắt buộc.',
            'opening_time.date_format' => 'Giờ mở cửa phải có định dạng H:i (VD: 08:00).',
            'closing_time.required' => 'Giờ đóng cửa là bắt buộc.',
            'closing_time.date_format' => 'Giờ đóng cửa phải có định dạng H:i (VD: 22:00).',
            'closing_time.after' => 'Giờ đóng cửa phải sau giờ mở cửa.',
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
        $this->merge([
            'slug' => \Illuminate\Support\Str::slug($this->name),
        ]);
    }

    /**
     * Get validated data with additional processing.
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();
        $data['owner_id'] = $this->user()->id;
        $data['status'] = 'pending'; // Default status for new venues

        return $data;
    }
}
