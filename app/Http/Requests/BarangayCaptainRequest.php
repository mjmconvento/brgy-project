<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAddress;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates both creating and updating a barangay captain candidate.
 */
class BarangayCaptainRequest extends FormRequest
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
        return $this->addressAttributes();
    }
}
