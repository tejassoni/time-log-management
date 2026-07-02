<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final class Duration
{
    public const MAX_MINUTES = 600; // 10 hours

    private function __construct(public int $minutes)
    {
    }

    /**
     * Accepts: "2:30", "2h30m", "2h 30m", "2h", "30m", "2.5", "2.5h", "2".
     * A bare whole number is treated as hours ("2" = 2h = 120 minutes).
     * Returns total minutes.
     */
    public static function parse(string $input): self
    {
        $raw = trim(strtolower($input));

        if ($raw === '') {
            throw new InvalidArgumentException('Time is required.');
        }

        $minutes = match (true) {
            (bool) preg_match('/^(\d{1,2}):([0-5]?\d)$/', $raw, $m)
                => ((int) $m[1] * 60) + (int) $m[2],

            (bool) preg_match('/^(?:(\d{1,2})\s*h)?\s*(?:([0-5]?\d)\s*m)?$/', $raw, $m) && ($m[1] !== '' || ($m[2] ?? '') !== '')
                => ((int) ($m[1] ?? 0) * 60) + (int) ($m[2] ?? 0),

            // Hours as a decimal ("2.5") or explicit "h" suffix ("2.5h", "3h" handled above).
            (bool) preg_match('/^(\d+\.\d+)\s*h?$/', $raw, $m)
                => (int) round((float) $m[1] * 60),

            // A bare whole number is treated as hours ("2" = 120 minutes).
            (bool) preg_match('/^(\d{1,2})$/', $raw, $m)
                => (int) $m[1] * 60,

            default => throw new InvalidArgumentException('Invalid time format. Use 2:30, 2h30m, or 2.5h.'),
        };

        if ($minutes <= 0) {
            throw new InvalidArgumentException('Time must be greater than zero.');
        }

        return new self($minutes);
    }

    public static function tryParse(string $input): ?self
    {
        try {
            return self::parse($input);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function format(): string
    {
        return sprintf('%02d:%02d', intdiv($this->minutes, 60), $this->minutes % 60);
    }
}
