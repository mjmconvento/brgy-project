<?php

namespace Database\Factories\Support;

/**
 * Plausible Philippine address parts for factories and seeders.
 *
 * Faker's locale data produces US-style addresses, which look wrong in a
 * barangay records system, so the vocabulary is curated here instead.
 */
final class PhilippineAddress
{
    /**
     * @var list<string>
     */
    private const STREETS = [
        'Mabini Street', 'Rizal Avenue', 'Bonifacio Street', 'Aguinaldo Street',
        'Del Pilar Street', 'Luna Street', 'Quezon Avenue', 'Osmeña Street',
        'Roxas Boulevard', 'Magsaysay Street', 'Katipunan Avenue', 'Legarda Street',
        'Sampaguita Street', 'Ilang-Ilang Street', 'Narra Street', 'Acacia Street',
        'Mahogany Street', 'Kamagong Street', 'Molave Street', 'Yakal Street',
        'Purok 1 Road', 'Purok 4 Road', 'Riverside Street', 'Maharlika Highway',
        'San Jose Street', 'Santo Niño Street', 'Bayanihan Street', 'Masagana Street',
    ];

    /**
     * @var list<string>
     */
    private const BARANGAYS = [
        'San Antonio', 'Santo Domingo', 'San Isidro', 'Poblacion', 'Bagong Silang',
        'Maligaya', 'Mapayapa', 'Malinis', 'San Roque', 'Santa Cruz',
        'Bagumbayan', 'Pinagbuhatan', 'Kalayaan', 'Masagana', 'Malaya',
        'Tibag', 'Balibago', 'Sampaloc', 'Talipapa', 'Culiat',
    ];

    /**
     * City or municipality paired with its province-adjacent naming.
     *
     * @var list<string>
     */
    private const CITIES = [
        'Quezon City', 'Manila', 'Caloocan', 'Pasig', 'Taguig', 'Makati',
        'Parañaque', 'Las Piñas', 'Marikina', 'Muntinlupa', 'Valenzuela',
        'Antipolo', 'Bacoor', 'Dasmariñas', 'Imus', 'San Jose del Monte',
        'Cabanatuan', 'Malolos', 'Angeles', 'Lipa', 'Batangas City',
        'Naga', 'Iloilo City', 'Cebu City', 'Davao City', 'General Santos',
    ];

    /**
     * @return list<string>
     */
    public static function streets(): array
    {
        return self::STREETS;
    }

    /**
     * @return list<string>
     */
    public static function barangays(): array
    {
        return self::BARANGAYS;
    }

    /**
     * @return list<string>
     */
    public static function cities(): array
    {
        return self::CITIES;
    }

    /**
     * A complete set of address column values.
     *
     * Roughly one address in eight has no house number, mirroring rural and
     * informal settlements where none is recorded.
     *
     * @return array{house_number: string|null, street: string, barangay: string, city: string, country: string}
     */
    public static function random(): array
    {
        return [
            'house_number' => random_int(1, 8) === 1 ? null : self::randomHouseNumber(),
            'street' => self::STREETS[array_rand(self::STREETS)],
            'barangay' => self::BARANGAYS[array_rand(self::BARANGAYS)],
            'city' => self::CITIES[array_rand(self::CITIES)],
            'country' => 'Philippines',
        ];
    }

    /**
     * "12", "142-B", "7A", or "Blk 4 Lot 21".
     */
    private static function randomHouseNumber(): string
    {
        return match (random_int(1, 4)) {
            1 => (string) random_int(1, 250),
            2 => random_int(1, 250).'-'.chr(random_int(65, 70)),
            3 => random_int(1, 99).chr(random_int(65, 70)),
            default => 'Blk '.random_int(1, 40).' Lot '.random_int(1, 60),
        };
    }
}
