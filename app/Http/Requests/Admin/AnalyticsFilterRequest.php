<?php

namespace App\Http\Requests\Admin;

use App\DTOs\Filters\AnalyticsFilter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and sanitizes analytics page filter parameters.
 */
class AnalyticsFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool  Always true; no authorization required for analytics filters.
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
            'metrics_from' => ['nullable', 'date'],
            'metrics_to' => ['nullable', 'date', 'after_or_equal:metrics_from'],
            'agg_from' => ['nullable', 'date'],
            'agg_to' => ['nullable', 'date', 'after_or_equal:agg_from'],
        ];
    }

    /**
     * Convert validated data to an AnalyticsFilter DTO.
     *
     * @return \App\DTOs\Filters\AnalyticsFilter
     */
    public function toFilter(): AnalyticsFilter
    {
        return new AnalyticsFilter(
            metricsFrom: $this->input('metrics_from'),
            metricsTo: $this->input('metrics_to'),
            aggFrom: $this->input('agg_from'),
            aggTo: $this->input('agg_to'),
        );
    }
}
