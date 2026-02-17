<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Filters\HistoryFilter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and sanitizes history page filter parameters.
 */
class HistoryFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool  Always true; no authorization required for history filters.
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
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'min_spread' => ['nullable', 'numeric', 'min:0'],
            'max_spread' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Convert validated data to a HistoryFilter DTO.
     *
     * @return \App\DTOs\Filters\HistoryFilter
     */
    public function toFilter(): HistoryFilter
    {
        return HistoryFilter::fromRequest($this);
    }
}
