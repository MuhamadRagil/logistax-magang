<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Intern;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds the WhatsApp group text for check-in / check-out. Pure text
 * assembly from the database — sends nothing, so it can be tested as-is.
 *
 * The roster is always for the attendance record's own `date` (not "now"),
 * and times are shown in WIB whatever the app timezone is (UTC locally,
 * Asia/Jakarta on Railway).
 */
class AttendanceNotificationService
{
    private const DISPLAY_TIMEZONE = 'Asia/Jakarta';

    public function checkInMessage(Attendance $attendance): string
    {
        $attendance->loadMissing('intern');

        $present = $this->recordsOn($attendance)
            ->whereNotNull('check_in_time')
            ->where('status', 'hadir')
            ->orderBy('check_in_time')
            ->get();

        return $this->compose(
            "✅ {$attendance->intern->full_name} masuk {$this->time($attendance->check_in_time)} WIB",
            'Hadir hari ini',
            $present,
            fn (Attendance $row) => $row->check_in_time,
        );
    }

    public function checkOutMessage(Attendance $attendance): string
    {
        $attendance->loadMissing('intern');

        $checkedOut = $this->recordsOn($attendance)
            ->whereNotNull('check_out_time')
            ->orderBy('check_out_time')
            ->get();

        return $this->compose(
            "🏁 {$attendance->intern->full_name} keluar {$this->time($attendance->check_out_time)} WIB",
            'Sudah checkout',
            $checkedOut,
            fn (Attendance $row) => $row->check_out_time,
        );
    }

    /**
     * @param  Collection<int, Attendance>  $rows
     * @param  callable(Attendance): CarbonInterface  $timeOf
     */
    private function compose(string $headline, string $listTitle, Collection $rows, callable $timeOf): string
    {
        $total = Intern::whereIn('status', ['active', 'extended'])->count();

        $lines = [$headline, '', "👥 {$listTitle} ({$rows->count()}/{$total}):"];

        foreach ($rows->values() as $index => $row) {
            $lines[] = ($index + 1).". {$row->intern->full_name} — {$this->time($timeOf($row))}";
        }

        return implode("\n", $lines);
    }

    private function recordsOn(Attendance $attendance)
    {
        // whereDate, not where('date', ...): the column is a DATE on MySQL but
        // the `date` cast stores "Y-m-d 00:00:00" text on SQLite (tests).
        return Attendance::query()
            ->with('intern')
            ->whereDate('date', $attendance->date->toDateString());
    }

    private function time(CarbonInterface $moment): string
    {
        return $moment->copy()->timezone(self::DISPLAY_TIMEZONE)->format('H:i');
    }
}
