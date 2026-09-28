<?php

namespace Tests\Concerns;

use App\Models\Attendance;
use App\Models\Intern;
use Illuminate\Support\Carbon;

trait BuildsAttendanceFixtures
{
    private int $nimSequence = 0;

    protected function makeIntern(string $name, string $status = 'active', array $attributes = []): Intern
    {
        $this->nimSequence++;

        return Intern::create(array_merge([
            'full_name' => $name,
            'nim' => 'TEST'.str_pad((string) $this->nimSequence, 4, '0', STR_PAD_LEFT),
            'institution' => 'Universitas Uji',
            'major' => 'Akuntansi',
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
            'status' => $status,
            'registered_via' => 'admin',
        ], $attributes));
    }

    /**
     * Attendance for $date with times given in WIB, stored the way the app
     * stores now(): converted to the app timezone (UTC in tests).
     */
    protected function attend(Intern $intern, string $date, ?string $inWib, ?string $outWib = null, string $status = 'hadir'): Attendance
    {
        return Attendance::create([
            'intern_id' => $intern->id,
            'date' => $date,
            'check_in_time' => $inWib ? $this->wib($inWib) : null,
            'check_out_time' => $outWib ? $this->wib($outWib) : null,
            'status' => $status,
        ]);
    }

    protected function wib(string $dateTime): Carbon
    {
        return Carbon::parse($dateTime, 'Asia/Jakarta')->timezone(config('app.timezone'));
    }
}
