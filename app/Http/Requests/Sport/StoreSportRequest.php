<?php

namespace App\Http\Requests\Sport;

use Illuminate\Foundation\Http\FormRequest;

class StoreSportRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:sports,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'positions' => 'nullable|array',
            'positions.*' => 'string|max:100',
            'min_players' => 'required|integer|min:1',
            'max_players' => 'required|integer|min:1|gte:min_players',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên môn thể thao là bắt buộc.',
            'name.max' => 'Tên môn thể thao không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên môn thể thao đã tồn tại.',
            'min_players.required' => 'Số người chơi tối thiểu là bắt buộc.',
            'min_players.integer' => 'Số người chơi tối thiểu phải là số nguyên.',
            'min_players.min' => 'Số người chơi tối thiểu phải từ 1.',
            'max_players.required' => 'Số người chơi tối đa là bắt buộc.',
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
        $data['is_active'] = true; // Default active for new sports

        return $data;
    }
}
