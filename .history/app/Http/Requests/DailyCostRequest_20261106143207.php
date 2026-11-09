<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyCostRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array

        return [
            'tanggal' => ['required', 'date'],
            'no_plat' => ['required', 'string', 'max:30'],
            'no_surat_jalan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('daily_costs', 'no_surat_jalan')->ignore($this->route('dailyCost')),
            ],
            'uang_jalan' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'no_plat' => mb_strtoupper(trim((string) $this->input('no_plat'))),
            'no_surat_jalan' => mb_strtoupper(trim((string) $this->input('no_surat_jalan'))),
        ]);
    }
}