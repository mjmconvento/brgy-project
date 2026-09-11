<?php

namespace App\Models;

use App\Enums\TaxStatus;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single monthly tax billing period for a constituent.
 *
 * @property int $id
 * @property int $constituent_id
 * @property string $amount Kept as a string by the `decimal:2` cast so money never round-trips through a float.
 * @property int $payment_month
 * @property int $payment_year
 * @property TaxStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Constituent $constituent
 * @property-read string $month_name
 * @property-read string $period_label
 */
#[Fillable(['constituent_id', 'amount', 'payment_month', 'payment_year', 'status'])]
class Tax extends Model
{
    /** @use HasFactory<TaxFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_month' => 'integer',
            'payment_year' => 'integer',
            'status' => TaxStatus::class,
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
     * Month options for `<select>` inputs, keyed by the stored integer.
     *
     * The legacy schema stored month names as free text; months are integers now
     * so periods sort and compare correctly.
     *
     * @return array<int, string>
     */
    public static function monthOptions(): array
    {
        static $months = null;

        if ($months === null) {
            $months = [];

            foreach (range(1, 12) as $month) {
                $months[$month] = Carbon::createFromDate(2000, $month, 1)->monthName;
            }
        }

        return $months;
    }

    /**
     * "January" … "December".
     *
     * @return Attribute<string, never>
     */
    protected function monthName(): Attribute
    {
        return Attribute::get(
            fn (): string => static::monthOptions()[$this->payment_month] ?? '',
        )->shouldCache();
    }

    /**
     * "January 2026".
     *
     * @return Attribute<string, never>
     */
    protected function periodLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => trim($this->month_name.' '.$this->payment_year),
        )->shouldCache();
    }

    /**
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function unpaid(Builder $query): void
    {
        $query->where('status', TaxStatus::Unpaid);
    }

    /**
     * Newest billing period first.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function latestPeriodFirst(Builder $query): void
    {
        $query->orderByDesc('payment_year')->orderByDesc('payment_month');
    }
}
