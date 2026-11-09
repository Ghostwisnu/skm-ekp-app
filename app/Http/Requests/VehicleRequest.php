<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plat' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plat')->ignore($this->route('vehicle'))],
            'jenis_kendaraan' => ['required', 'string', 'max:100'],
            'model_kendaraan' => ['required', 'string', 'max:100'],
            'nama_supir' => ['required', 'string', 'max:255'],
            'no_hp_supir' => ['required', 'string', 'max:30'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
