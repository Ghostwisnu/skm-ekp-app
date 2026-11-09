<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const PPN_DEFAULT = 'ppn_default';

    public const COMPANY_NAME = 'company_name';

    public const COMPANY_ADDRESS = 'company_address';

    public const COMPANY_BANK_NAME = 'company_bank_name';

    public const COMPANY_BANK_ACCOUNT = 'company_bank_account';

    public const FALLBACK_PPN = 11.0;

    public static function ppnDefault(): float
    {
        $value = static::query()->where('key', self::PPN_DEFAULT)->value('value');

        return $value === null ? self::FALLBACK_PPN : (float) $value;
    }

    public static function updatePpnDefault(float $value): void
    {
        static::query()->updateOrCreate(['key' => self::PPN_DEFAULT], ['value' => (string) $value]);
    }

    /**
     * @return array{company_name: string, company_address: string, company_bank_name: string, company_bank_account: string}
     */
    public static function companyDetails(): array
    {
        $settings = static::query()
            ->whereIn('key', [
                self::COMPANY_NAME,
                self::COMPANY_ADDRESS,
                self::COMPANY_BANK_NAME,
                self::COMPANY_BANK_ACCOUNT,
            ])
            ->pluck('value', 'key');

        return [
            'company_name' => (string) ($settings[self::COMPANY_NAME] ?? ''),
            'company_address' => (string) ($settings[self::COMPANY_ADDRESS] ?? ''),
            'company_bank_name' => (string) ($settings[self::COMPANY_BANK_NAME] ?? ''),
            'company_bank_account' => (string) ($settings[self::COMPANY_BANK_ACCOUNT] ?? ''),
        ];
    }

    /**
     * @param  array<string, string>  $details
     */
    public static function updateCompanyDetails(array $details): void
    {
        foreach ($details as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
