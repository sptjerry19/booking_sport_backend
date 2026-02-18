<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class RevokeDeviceRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Assuming the route parameter is named 'tokenId' or similar.
        // Since we couldn't find the route, we'll try to get it from route('tokenId') or similar.
        // If the route param is just {id}, then use route('id').
        // Given the controller signature revokeDevice(Request $request, int $tokenId),
        // it suggests the route param might be implicit or explicit.
        // If implicit binding isn't used (it takes int $tokenId), it's likely just a parameter.
        // Let's assume it's passed as 'token_id' in body for now if route not found, 
        // OR if it was a route param, we'd need to know the name.
        // For safety, I'll validate 'token_id' from all inputs.

        // If this is a route param...
        if ($this->route('tokenId')) {
            $this->merge(['token_id' => $this->route('tokenId')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'token_id' => 'required|integer',
        ];
    }
}
