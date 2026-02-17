<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Filters\FuturesFilter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and sanitizes futures page filter parameters.
 */
class FuturesFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool  Always true; no authorization required for futures filters.
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
            'history_from' => ['nullable', 'date'],
            'history_to' => ['nullable', 'date', 'after_or_equal:history_from'],
            'oi_from' => ['nullable', 'date'],
            'oi_to' => ['nullable', 'date', 'after_or_equal:oi_from'],
        ];
    }

    /**
     * Convert validated data to a FuturesFilter DTO.
     *
     * @return \App\DTOs\Filters\FuturesFilter
     */
    public function toFilter(): FuturesFilter
    {
        return new FuturesFilter(
            historyFrom: $this->input('history_from'),
            historyTo: $this->input('history_to'),
            oiFrom: $this->input('oi_from'),
            oiTo: $this->input('oi_to'),
        );
    }
}
