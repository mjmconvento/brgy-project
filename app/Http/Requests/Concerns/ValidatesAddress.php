<?php

namespace App\Http\Requests\Concerns;

/**
 * Shared address rules for the person records that carry an address.
 */
trait ValidatesAddress
{
    /**
     * Rules for the structured address columns.
     *
     * `house_number` is the only optional part: rural and informal addresses
     * frequently have none.
     *
     * @return array<string, list<mixed>>
     */
    protected function addressRules(): array
    {
        return [
            'house_number' => ['nullable', 'string', 'max:30'],
            'street' => ['required', 'string', 'max:120'],
            'barangay' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function addressAttributes(): array
    {
        return [
            'house_number' => 'house number',
            'barangay' => 'barangay',
            'city' => 'city or municipality',
        ];
    }
}
