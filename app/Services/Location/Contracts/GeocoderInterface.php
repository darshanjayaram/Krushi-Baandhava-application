<?php

namespace App\Services\Location\Contracts;

interface GeocoderInterface
{
    /**
     * Reverse geocode geographic coordinates (latitude, longitude) into structured location details.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array{
     *     district: ?string,
     *     taluk: ?string,
     *     locality: ?string,
     *     state: ?string,
     *     country: ?string,
     *     postcode: ?string,
     *     display_name: string,
     *     latitude: float,
     *     longitude: float
     * }|null
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array;
}
