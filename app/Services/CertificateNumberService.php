<?php

namespace App\Services;

use App\Models\Certificate;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CertificateNumberService
{
    private const ROMAN_MONTHS = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    /**
     * Generate the next certificate number for the given issue date, e.g.
     * "001/LOGISTAX/INTERN/V/2026". Counter resets every calendar year and is
     * based on COUNT(certificates) whose issued_date falls in that year (not
     * created_at). Wrapped in a transaction with lockForUpdate to avoid race
     * conditions when two certificates are generated at the same time.
     *
     * Locking note: the range is expressed as whereBetween on indexed date
     * boundaries (not whereYear(...)) so MySQL/InnoDB can take a proper
     * next-key lock on that year's date range under REPEATABLE READ, instead
     * of a full-table scan/lock that a function-wrapped WHERE would force.
     */
    public function generate(CarbonInterface $issuedDate): string
    {
        return DB::transaction(function () use ($issuedDate) {
            $year = (int) $issuedDate->format('Y');

            $count = Certificate::query()
                ->whereBetween('issued_date', ["{$year}-01-01", "{$year}-12-31"])
                ->lockForUpdate()
                ->count();

            $counter = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
            $romanMonth = self::ROMAN_MONTHS[(int) $issuedDate->format('n')];

            return "{$counter}/LOGISTAX/INTERN/{$romanMonth}/{$year}";
        });
    }

    /**
     * Dummy number for GET /api/certificates/preview/{internId} — never
     * touches the database or the yearly counter.
     */
    public function previewNumber(CarbonInterface $date): string
    {
        $romanMonth = self::ROMAN_MONTHS[(int) $date->format('n')];
        $year = $date->format('Y');

        return "PREVIEW/LOGISTAX/INTERN/{$romanMonth}/{$year}";
    }
}
