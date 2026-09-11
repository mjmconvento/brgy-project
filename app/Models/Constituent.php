<?php

namespace App\Models;

use App\Enums\TaxStatus;
use App\Models\Concerns\HasAddress;
use App\Models\Concerns\HasPersonName;
use Database\Factories\ConstituentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A resident profiled by the barangay.
 *
 * @property int $id
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $house_number
 * @property string $street
 * @property string $barangay
 * @property string $city
 * @property string $country
 * @property int|null $barangay_captain_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read BarangayCaptain|null $barangayCaptain
 * @property-read EloquentCollection<int, CriminalRecord> $criminalRecords
 * @property-read EloquentCollection<int, Tax> $taxes
 * @property-read int|null $criminal_records_count
 * @property-read int|null $unpaid_taxes_count
 * @property-read string|null $unpaid_taxes_total
 * @property-read string $full_name
 * @property-read string $street_address
 * @property-read string $full_address
 * @property-read bool $has_criminal_record
 * @property-read bool $has_unpaid_taxes
 * @property-read float $outstanding_tax_total
 */
#[Fillable([
    'first_name',
    'middle_name',
    'last_name',
    'house_number',
    'street',
    'barangay',
    'city',
    'country',
    'barangay_captain_id',
])]
class Constituent extends Model
{
    use HasAddress, HasPersonName;

    /** @use HasFactory<ConstituentFactory> */
    use HasFactory;

    /**
     * The captain this constituent voted for, if any.
     *
     * @return BelongsTo<BarangayCaptain, $this>
     */
    public function barangayCaptain(): BelongsTo
    {
        return $this->belongsTo(BarangayCaptain::class);
    }

    /**
     * @return HasMany<CriminalRecord, $this>
     */
    public function criminalRecords(): HasMany
    {
        return $this->hasMany(CriminalRecord::class);
    }

    /**
     * @return HasMany<Tax, $this>
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    /**
     * Eager load everything the listing screen renders, including the derived
     * flags, so the table never triggers a query per row.
     *
     * Aliases are spelled out in full because `withCount`/`withSum` use an
     * explicit `as` alias verbatim rather than appending `_count`/`_sum_amount`.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function withListingAggregates(Builder $query): void
    {
        $unpaid = fn (Builder $query) => $query->where('status', TaxStatus::Unpaid);

        $query
            ->with('barangayCaptain')
            ->withCount([
                'criminalRecords',
                'taxes as unpaid_taxes_count' => $unpaid,
            ])
            ->withSum(['taxes as unpaid_taxes_total' => $unpaid], 'amount');
    }

    /**
     * Whether the constituent has at least one criminal record.
     *
     * The legacy schema kept a `has_record` boolean that application code had to
     * keep in sync by hand; it is now derived from the relation. Prefer the
     * `withListingAggregates` scope so this never costs an extra query.
     *
     * @return Attribute<bool, never>
     */
    protected function hasCriminalRecord(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->relationLoaded('criminalRecords')) {
                return $this->criminalRecords->isNotEmpty();
            }

            if (array_key_exists('criminal_records_count', $this->attributes)) {
                return (int) $this->attributes['criminal_records_count'] > 0;
            }

            return $this->criminalRecords()->exists();
        });
    }

    /**
     * Whether any tax record is still unpaid. Replaces the legacy
     * `has_unpaid_tax` column.
     *
     * @return Attribute<bool, never>
     */
    protected function hasUnpaidTaxes(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->relationLoaded('taxes')) {
                return $this->taxes->contains(
                    fn (Tax $tax): bool => $tax->status === TaxStatus::Unpaid,
                );
            }

            if (array_key_exists('unpaid_taxes_count', $this->attributes)) {
                return (int) $this->attributes['unpaid_taxes_count'] > 0;
            }

            return $this->unpaidTaxes()->exists();
        });
    }

    /**
     * Peso total still owed across all unpaid tax records.
     *
     * @return Attribute<float, never>
     */
    protected function outstandingTaxTotal(): Attribute
    {
        return Attribute::get(function (): float {
            if ($this->relationLoaded('taxes')) {
                return (float) $this->taxes
                    ->filter(fn (Tax $tax): bool => $tax->status === TaxStatus::Unpaid)
                    ->sum('amount');
            }

            // SUM over zero rows is NULL, so coalesce rather than cast blindly.
            if (array_key_exists('unpaid_taxes_total', $this->attributes)) {
                return (float) ($this->attributes['unpaid_taxes_total'] ?? 0);
            }

            return (float) $this->unpaidTaxes()->sum('amount');
        });
    }

    /**
     * @return HasMany<Tax, $this>
     */
    private function unpaidTaxes(): HasMany
    {
        return $this->taxes()->where('status', TaxStatus::Unpaid);
    }
}
