<?php

namespace App\Http\Requests\Sport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('sports', 'name')->ignore($this->route('sport')->id)
            ],
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'positions' => 'nullable|array',
            'positions.*' => 'string|max:100',
            'min_players' => 'sometimes|integer|min:1',
            'max_players' => 'sometimes|integer|min:1|gte:min_players',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Tên môn thể thao không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên môn thể thao đã tồn tại.',
            'min_players.integer' => 'Số người chơi tối thiểu phải là số nguyên.',
            'min_players.min' => 'Số người chơi tối thiểu phải từ 1.',
            'max_players.integer' => 'Số người chơi tối đa phải là số nguyên.',
            'max_players.gte' => 'Số người chơi tối đa phải lớn hơn hoặc bằng số tối thiểu.',
            'positions.*.max' => 'Tên vị trí không được vượt quá 100 ký tự.',
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
