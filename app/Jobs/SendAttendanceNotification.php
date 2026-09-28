<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\AttendanceNotificationService;
use App\Services\FonnteService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Group WhatsApp message after a successful check-in / check-out.
 *
 * Dispatched with dispatchAfterResponse(): deliberately NOT ShouldQueue —
 * there's no queue worker on Railway, and after-response dispatch runs the
 * job in the same PHP process once the response has been sent.
 */
class SendAttendanceNotification
{
    use Dispatchable;

    public const CHECK_IN = 'check_in';

    public const CHECK_OUT = 'check_out';

    public function __construct(
        public readonly string $attendanceId,
        public readonly string $event,
    ) {}

    public function handle(FonnteService $fonnte, AttendanceNotificationService $messages): void
    {
        if (! $fonnte->isEnabled()) {
            return;
        }

        try {
            $attendance = Attendance::with('intern')->find($this->attendanceId);

            if (! $attendance || ! $attendance->intern) {
                return;
            }

            $message = $this->event === self::CHECK_OUT
                ? $messages->checkOutMessage($attendance)
                : $messages->checkInMessage($attendance);

            $fonnte->sendToGroup($message);
        } catch (Throwable $e) {
            // Runs after the response is sent — must never surface an error.
            Log::warning('Notifikasi absensi gagal disusun/dikirim.', [
                'attendance_id' => $this->attendanceId,
                'event' => $this->event,
                'error' => $e::class.': '.$e->getMessage(),
            ]);
        }
    }
}
