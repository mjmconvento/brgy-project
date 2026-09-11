<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates both creating and updating a criminal record.
 */
class CriminalRecordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'case_name' => ['required', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Get custom attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'case_name' => 'case',
            'occurred_at' => 'date and time',
        ];
    }
}
