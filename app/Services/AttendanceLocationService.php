<?php

namespace App\Services;

use App\Models\OfficeLocation;

class AttendanceLocationService
{
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Haversine distance between two coordinates, in meters.
     */
    public function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * True if the given coordinate falls within the radius of at least one
     * active office location.
     */
    public function isWithinAnyOfficeRadius(float $latitude, float $longitude): bool
    {
        return OfficeLocation::where('is_active', true)
            ->get()
            ->contains(fn (OfficeLocation $office) => $this->distanceInMeters(
                $latitude,
                $longitude,
                (float) $office->latitude,
                (float) $office->longitude,
            ) <= $office->radius_meters);
    }
}
