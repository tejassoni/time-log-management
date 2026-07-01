<?php

namespace App\Http\Requests;

use App\Rules\ValidTime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimeLogRequest extends FormRequest
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
        return [
            'work_date'   => ['required', 'date', 'before_or_equal:today'],
            'project_id'  => ['required', Rule::exists('projects', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'max:1000'],
            'time'        => ['required', 'string', new ValidTime()],
        ];
    }
}
