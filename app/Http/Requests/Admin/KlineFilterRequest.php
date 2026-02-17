<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Filters\KlineFilter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and sanitizes kline view page filter parameters.
 */
class KlineFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool  Always true; no authorization required for kline filters.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'interval' => ['nullable', 'in:1m,5m,15m,1h'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /**
     * Convert validated data to a KlineFilter DTO.
     *
     * @return \App\DTOs\Filters\KlineFilter
     */
    public function toFilter(): KlineFilter
    {
        return new KlineFilter(
            interval: $this->input('interval', '1m'),
            from: $this->input('from'),
            to: $this->input('to'),
        );
    }
}
