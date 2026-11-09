<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $clientId = $this->route('client') instanceof Client ? $this->route('client')->id : 0;

        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'kontak' => ['nullable', 'string', 'max:100'],
            'goods' => ['sometimes', 'array'],
            'goods.*.id' => [
                'sometimes',
                'integer',
                Rule::exists('client_goods', 'id')->where(fn ($query) => $query->where('client_id', $clientId)),
            ],
            'goods.*.jenis_barang' => ['required', 'string', 'max:255'],
            'goods.*.pricing_mode' => ['required', Rule::in(['per_qty', 'flat_trip'])],
            'goods.*.harga' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ];
    }
}
