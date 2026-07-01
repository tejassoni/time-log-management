<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust this based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $yearStart = Carbon::now()->startOfYear()->toDateString();
        $yearEnd   = Carbon::now()->endOfYear()->toDateString();

        return [
            'start_date' => ['required', 'date', "after_or_equal:{$yearStart}", "before_or_equal:{$yearEnd}"],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date', "before_or_equal:{$yearEnd}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $year = Carbon::now()->year;

        return [
            'start_date.after_or_equal'  => "Start date must be within the current year ({$year}).",
            'start_date.before_or_equal' => "Start date must be within the current year ({$year}).",
            'end_date.before_or_equal'   => "End date must be within the current year ({$year}).",
        ];
    }
}
