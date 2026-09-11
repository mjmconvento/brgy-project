<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates both creating and updating a constituent — the rules are identical
 * for either verb, so one request class serves both controller actions.
 */
class ConstituentRequest extends FormRequest
{
    use ValidatesAddress;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'barangay_captain_id' => [
                'nullable',
                'integer',
                Rule::exists('barangay_captains', 'id'),
            ],
            ...$this->addressRules(),
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
            'barangay_captain_id' => 'barangay captain',
            ...$this->addressAttributes(),
        ];
    }
}
