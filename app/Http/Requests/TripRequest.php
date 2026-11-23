<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\ClientGood;
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
            'destination_client_id' => [
                'nullable', 'integer', Rule::exists('clients', 'id'),
            ],
            'destination_client_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'no_surat_jalan' => ['required', 'string', 'max:255', Rule::unique('trips', 'no_surat_jalan')->ignore($this->route('trip'))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.client_good_id' => [
                'nullable', 'sometimes', 'integer',
                Rule::exists('client_goods', 'id'),
            ],
            'items.*.jenis_barang' => ['required', 'string', 'max:255', 'distinct'],
            'items.*.qty_barang' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'items.*.total_harga' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'category' => ['sometimes', Rule::in(['kontrak', 'outsourcing'])],
            'tujuan' => ['nullable', 'string', 'max:255', 'required_if:category,outsourcing'],
        ];
    }

    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->input('category') ?? 'kontrak';

                // Require client_name for outsourcing category
                if ($category === 'outsourcing') {
                    $clientName = trim((string) $this->input('client_name'));
                    if (empty($clientName)) {
                        $validator->errors()->add('client_name', 'Client wajib diisi untuk outsourcing.');

                        return;
                    }
                }

                // Validate items - clear errors for empty client_good_id (will create new)
                $items = $this->input('items', []);
                foreach ($items as $index => $item) {
                    if (empty($item['client_good_id'])) {
                        $validator->errors()->forget("items.{$index}.client_good_id");
                    }
                }

                // For kontrak category, destination client is required
                if ($category !== 'outsourcing') {
                    $destinationClientId = $this->input('destination_client_id');
                    $destinationClientName = trim((string) $this->input('destination_client_name'));

                    // Require destination client for kontrak
                    if (empty($destinationClientId) && empty($destinationClientName)) {
                        $validator->errors()->add('destination_client_id', 'The destination client id field is required when category is kontrak.');

                        return;
                    }

                    if ($validator->errors()->hasAny([
                        'client_id',
                        'client_name',
                        'destination_client_id',
                        'destination_client_name',
                    ])) {
                        return;
                    }

                    $clientName = trim((string) $this->input('client_name'));
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

                    // Validate client_good_id belongs to the correct client based on category
                    $correctClientId = $category === 'kontrak' ? $destinationClientId : $clientId;
                    foreach ($items as $index => $item) {
                        $goodId = $item['client_good_id'] ?? null;
                        if (! empty($goodId)) {
                            $good = ClientGood::query()->find($goodId);
                            // Normalize to int for comparison (form sends strings via Inertia)
                            $correctClientIdNormalized = $correctClientId !== null ? (int) $correctClientId : null;
                            if ($good && $correctClientIdNormalized !== null && (int) $good->client_id !== $correctClientIdNormalized) {
                                $validator->errors()->add(
                                    "items.{$index}.client_good_id",
                                    'Jenis barang tidak sesuai dengan client yang tepat.',
                                );
                            }
                        }
                    }
                }

                // Validate client_good_id belongs to the correct client based on category
                $clientId = $this->input('client_id');
                $clientName = trim((string) $this->input('client_name'));
                if ($clientId === null && $clientName !== '') {
                    $clientId = Client::query()->where('nama', $clientName)->value('id');
                }

                $destinationClientId = $this->input('destination_client_id');
                $destinationClientName = trim((string) $this->input('destination_client_name'));
                if ($destinationClientId === null && $destinationClientName !== '') {
                    $destinationClientId = Client::query()->where('nama', $destinationClientName)->value('id');
                }

                // Determine which client owns the goods based on category
                $correctClientId = $category === 'kontrak' ? $destinationClientId : $clientId;

                foreach ($items as $index => $item) {
                    $goodId = $item['client_good_id'] ?? null;
                    if (! empty($goodId)) {
                        $good = ClientGood::query()->find($goodId);
                        if ($good && $good->client_id !== $correctClientId) {
                            $validator->errors()->add(
                                "items.{$index}.client_good_id",
                                'Jenis barang tidak sesuai dengan client yang tepat.',
                            );
                        }
                    }
                }
            },
        ];
    }
}
