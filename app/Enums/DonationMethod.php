<?php

namespace App\Enums;

enum DonationMethod: string
{
    case Cash = 'cash';
    case EWallet = 'e_wallet';
    case Instapay = 'instapay';
    case BankAccount = 'bank_account';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::EWallet => 'محفظة إلكترونية',
            self::Instapay => 'إنستاباي',
            self::BankAccount => 'حساب بنكي',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'banknotes',
            self::EWallet => 'device-phone-mobile',
            self::Instapay => 'bolt',
            self::BankAccount => 'building-library',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::EWallet => 'info',
            self::Instapay => 'warning',
            self::BankAccount => 'neutral',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->toArray();
    }
}
