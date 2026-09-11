<?php

namespace App\Http\Requests;

use App\Enums\TaxStatus;
use App\Models\Constituent;
use App\Models\Tax;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates both creating and updating a tax record.
 *
 * A constituent may only have one record per billing period, which the
 * `taxes_period_unique` index enforces at the database level; the rule below
 * turns that into a readable validation error instead of a 500.
 */
class TaxRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_month' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('taxes', 'payment_month')
                    ->where(fn (Builder $query) => $query
                        ->where('constituent_id', $this->constituentId())
                        ->where('payment_year', $this->integer('payment_year')))
                    ->ignore($this->route('tax')),
            ],
            'payment_year' => ['required', 'integer', 'between:1900,'.(now()->year + 1)],
            'status' => ['required', Rule::enum(TaxStatus::class)],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_month.unique' => 'This constituent already has a tax record for that month and year.',
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
            'payment_month' => 'month',
            'payment_year' => 'year',
        ];
    }

    /**
     * The constituent this record belongs to, taken from the nested route on
     * create and from the bound record itself on update.
     */
    private function constituentId(): ?int
    {
        $constituent = $this->route('constituent');

        if ($constituent instanceof Constituent) {
            return $constituent->id;
        }

        $tax = $this->route('tax');

        return $tax instanceof Tax ? $tax->constituent_id : null;
    }
}
