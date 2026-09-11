<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Shared behaviour for records stored as first/middle/last name columns.
 *
 * @phpstan-require-extends \Illuminate\Database\Eloquent\Model
 */
trait HasPersonName
{
    /**
     * Columns considered when searching a person record.
     *
     * Covers the name and address columns that `Constituent` and
     * `BarangayCaptain` both carry. A model without address columns (such as
     * `User`) overrides this method.
     *
     * This is a method rather than a static property on purpose: PHP raises a
     * fatal "definition differs and is considered incompatible" error when a
     * class redeclares a trait property with a different default, whereas a
     * class method simply wins over the trait's.
     *
     * @return list<string>
     */
    protected static function searchableColumns(): array
    {
        return [
            'first_name',
            'middle_name',
            'last_name',
            'house_number',
            'street',
            'barangay',
            'city',
            'country',
        ];
    }

    /**
     * "First Middle Last", skipping a missing middle name.
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])))->shouldCache();
    }

    /**
     * Filter by any part of the name or address. A blank term is a no-op so the
     * scope can be applied unconditionally from a controller.
     *
     * `orWhereLike(..., caseSensitive: false)` rather than `orWhere(..., 'like', ...)`:
     * on PostgreSQL, which this application runs on, plain LIKE is
     * case-sensitive — a staff member typing "cruz" would get "No constituents
     * match your search" for a resident recorded as "Cruz". (On MySQL the same
     * query appears to work, but only because of the column collation.) The
     * framework compiles this to ILIKE on PostgreSQL and to LIKE elsewhere, so
     * one spelling stays correct on SQLite too.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            foreach (static::searchableColumns() as $column) {
                $query->orWhereLike($column, '%'.$term.'%', caseSensitive: false);
            }
        });
    }

    /**
     * Alphabetical ordering used by every listing screen.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function orderedByName(Builder $query): void
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }
}
