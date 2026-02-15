<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RefundBookingRequest extends FormRequest
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
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'refund_amount.numeric' => 'Số tiền hoàn lại phải là số.',
            'refund_amount.min' => 'Số tiền hoàn lại không được âm.',
            'refund_reason.max' => 'Lý do hoàn tiền không được vượt quá 500 ký tự.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ];
    }
}

