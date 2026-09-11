<?php

namespace App\Enums;

enum TaxStatus: string
{
    case Paid = 'paid';
    case Unpaid = 'unpaid';

    /**
     * Human readable label for form options and table cells.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Unpaid => 'Unpaid',
        };
    }

    /**
     * Tailwind utility classes for rendering this status as a pill badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Paid => 'bg-green-100 text-green-800 ring-green-600/20',
            self::Unpaid => 'bg-red-100 text-red-800 ring-red-600/20',
        };
    }

    /**
     * Options map for `<select>` inputs, keyed by the stored value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $status): array => $carry + [$status->value => $status->label()],
            [],
        );
    }
}
