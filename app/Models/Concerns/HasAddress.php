<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Structured Philippine address stored as discrete columns.
 *
 * The 2015 schema kept one free-text `address` column, which made it impossible
 * to group by city or barangay, or to validate anything.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait HasAddress
{
    /**
     * Address columns, in display order.
     *
     * @return list<string>
     */
    public static function addressColumns(): array
    {
        return ['house_number', 'street', 'barangay', 'city', 'country'];
    }

    /**
     * "123 Mabini Street", or just the street when no house number is recorded.
     *
     * @return Attribute<string, never>
     */
    protected function streetAddress(): Attribute
    {
        return Attribute::get(fn (): string => trim(implode(' ', array_filter([
            $this->house_number,
            $this->street,
        ]))))->shouldCache();
    }

    /**
     * "123 Mabini Street, Brgy. San Antonio, Quezon City, Philippines".
     *
     * @return Attribute<string, never>
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::get(fn (): string => implode(', ', array_filter([
            $this->street_address,
            $this->barangay ? 'Brgy. '.$this->barangay : null,
            $this->city,
            $this->country,
        ])))->shouldCache();
    }
}
