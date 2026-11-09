<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function show(): RedirectResponse
    {
        return to_route('users.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ppn_default' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'company_address' => ['sometimes', 'required', 'string', 'max:1000'],
            'company_bank_name' => ['sometimes', 'required', 'string', 'max:100'],
            'company_bank_account' => ['sometimes', 'required', 'string', 'max:100'],
        ]);

        if (array_key_exists('ppn_default', $data)) {
            Setting::updatePpnDefault((float) $data['ppn_default']);
        }

        $companyDetails = array_intersect_key($data, array_flip([
            'company_name',
            'company_address',
            'company_bank_name',
            'company_bank_account',
        ]));

        if ($companyDetails !== []) {
            Setting::updateCompanyDetails($companyDetails);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengaturan diperbarui.']);

        return back();
    }
}
