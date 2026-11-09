<?php

namespace App\Http\Requests;

use App\Models\DailyCost;
use Illuminate\Foundation\Http\FormRequest;

class DailyCostRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['trip_id' => ['required', 'exists:trips,id']];

        foreach (DailyCost::COMPONENTS as $component) {
            $rules[$component] = ['required', 'integer', 'min:0', 'max:999999999999'];
        }

        return $rules;
    }
}
