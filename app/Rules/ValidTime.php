<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\ValueObjects\Duration;

class ValidTime implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $duration = Duration::tryParse((string) $value);

        if ($duration === null) {
            $fail('Enter a valid time, e.g. 2:30, 2h30m, or 2.5h.');

            return;
        }

        if ($duration->minutes > Duration::MAX_MINUTES) {
            $fail('A single task cannot exceed 10 hours.');
        }
    }
}
