<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by workflow services (Intern/Attendance/Evaluation/Certificate) when
 * a business rule blocks an action (e.g. "already approved", "not your
 * mentee"). Carries an HTTP-equivalent status so both the JSON API
 * controllers and the session-based web controllers can render it
 * appropriately without duplicating the underlying validation logic.
 */
class DomainActionException extends Exception
{
    public function __construct(string $message, public readonly int $status = 400)
    {
        parent::__construct($message);
    }
}
