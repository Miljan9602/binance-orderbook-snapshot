<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates kline data API request parameters.
 */
class KlineDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool  Always true; no authorization required for kline data requests.
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
        ];
    }

    /**
     * Get the validated interval or default to '1m'.
     *
     * @return string  The kline interval (e.g. '1m', '5m', '15m', '1h').
     */
    public function interval(): string
    {
        return $this->input('interval', '1m');
    }

    /**
     * Get requested technical indicators.
     *
     * @return array<string>
     */
    public function indicators(): array
    {
        $raw = $this->input('indicators', '');
        if (empty($raw)) {
            return [];
        }

        $valid = ['rsi', 'bb', 'ema', 'macd', 'taker'];
        return array_values(array_intersect(explode(',', $raw), $valid));
    }
}
