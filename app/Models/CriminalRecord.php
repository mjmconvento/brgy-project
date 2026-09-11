<?php

namespace App\Models;

use Database\Factories\CriminalRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A criminal case filed against a constituent.
 *
 * @property int $id
 * @property int $constituent_id
 * @property string $case_name
 * @property string|null $details
 * @property Carbon $occurred_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Constituent $constituent
 */
#[Fillable(['constituent_id', 'case_name', 'details', 'occurred_at'])]
class CriminalRecord extends Model
{
    /** @use HasFactory<CriminalRecordFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Constituent, $this>
     */
    public function constituent(): BelongsTo
    {
        return $this->belongsTo(Constituent::class);
    }

    /**
     * Most recent incident first.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('occurred_at');
    }
}
