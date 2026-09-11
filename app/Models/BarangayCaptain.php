<?php

namespace App\Models;

use App\Models\Concerns\HasAddress;
use App\Models\Concerns\HasPersonName;
use Database\Factories\BarangayCaptainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A candidate a constituent may have voted for in the last election.
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Constituent> $constituents
 * @property-read int|null $constituents_count
 * @property-read string $full_name
 * @property-read string $street_address
 * @property-read string $full_address
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
])]
class BarangayCaptain extends Model
{
    use HasAddress, HasPersonName;

    /** @use HasFactory<BarangayCaptainFactory> */
    use HasFactory;

    /**
     * Constituents who voted for this captain.
     *
     * @return HasMany<Constituent, $this>
     */
    public function constituents(): HasMany
    {
        return $this->hasMany(Constituent::class);
    }
}
