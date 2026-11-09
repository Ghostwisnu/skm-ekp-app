<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'destination_client_id' => ['required', 'different:client_id', 'exists:clients,id'],
            'tanggal_awal' => ['required', 'date'],
            'tanggal_akhir' => ['required', 'date', 'after_or_equal:tanggal_awal'],
            'no_invoice' => ['required', 'string', 'max:255', 'unique:invoices,no_invoice'],
            'ppn_persen' => ['required', 'numeric', 'min:0', 'max:100'],
            'prices' => ['sometimes', 'array'],
            'prices.*' => ['nullable', 'integer', 'min:1', 'max:999999999'],
        ];
    }
}
