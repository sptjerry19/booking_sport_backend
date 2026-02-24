<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'booking_date' => 'sometimes|date|after_or_equal:today',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
            'notes' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'payment_method' => 'sometimes|string|in:cash,transfer,vnpay,momo,zalopay',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'booking_date.date' => 'Ngày đặt sân không hợp lệ.',
            'booking_date.after_or_equal' => 'Ngày đặt sân phải từ hôm nay trở đi.',
            'start_time.date_format' => 'Giờ bắt đầu không đúng định dạng (H:i:s).',
            'end_time.date_format' => 'Giờ kết thúc không đúng định dạng (H:i:s).',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ];
    }
}
