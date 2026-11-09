<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TripRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'plat' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'client_name' => ['nullable', 'string', 'max:255', 'required_without:client_id'],
            'destination_client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'destination_client_name' => [
                'nullable',
                'string',
                'max:255',
                'required_without:destination_client_id',
            ],
            'no_surat_jalan' => ['required', 'string', 'max:255', Rule::unique('trips', 'no_surat_jalan')->ignore($this->route('trip'))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.client_good_id' => [
                'nullable',
                'integer',
                Rule::exists('client_goods', 'id')->where(
                    fn ($query) => $query->where('client_id', $this->input('client_id')),
                ),
            ],
            'items.*.jenis_barang' => ['required', 'string', 'max:255', 'distinct'],
            'items.*.qty_barang' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'items.*.total_harga' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny([
                    'client_id',
                    'client_name',
                    'destination_client_id',
                    'destination_client_name',
                ])) {
                    return;
                }

                $clientName = trim((string) $this->input('client_name'));
                $destinationClientName = trim((string) $this->input('destination_client_name'));
                $clientId = $this->input('client_id')
                    ?? Client::query()->where('nama', $clientName)->value('id');
                $destinationClientId = $this->input('destination_client_id')
                    ?? Client::query()->where('nama', $destinationClientName)->value('id');

                if (
                    ($clientId !== null && $destinationClientId !== null
                        && (string) $clientId === (string) $destinationClientId)
                    || ($clientId === null && $destinationClientId === null
                        && Str::lower($clientName) === Str::lower($destinationClientName))
                ) {
                    $errorKey = $this->filled('destination_client_id')
                        ? 'destination_client_id'
                        : 'destination_client_name';

                    $validator->errors()->add($errorKey, 'Client asal dan tujuan harus berbeda.');
                }
            },
        ];
    }
}
