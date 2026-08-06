<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class FamilyForm extends Form
{
    public ?int $id = null;

    // Tab 1: Basic data
    public ?int $case_number = null;

    #[Validate('required|string')]
    public string $case_type = '';

    #[Validate('required|string|max:255')]
    public string $case_name = '';

    #[Validate('required|string')]
    public string $community = '';

    public string $detailed_address = '';

    #[Validate('required|string')]
    public string $phone = '';

    #[Validate('required|in:بسيطة,مركبة')]
    public string $family_type = 'بسيطة';

    // Tab 2: Members (stored as array, persisted separately)
    /** @var array<int, array<string, mixed>> */
    public array $members = [];

    // Tab 3: Income — pre-filled with all keys so wire:model works
    /** @var array<string, array<string, mixed>> */
    public array $incomeSources = [
        'government_salary' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'private_salary' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'government_pension' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'insurance_pension' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'social_security' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'dignity_allowance' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'agricultural_land' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'livestock' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'irregular_labor' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'own_business' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'charity_aid' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
        'other_income' => ['is_active' => false, 'amount' => 0, 'notes' => ''],
    ];

    /** @var array<string, array<string, mixed>> */
    public array $resources = [
        // Agricultural land — owned
        'land_owned_share' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'land_owned_qirat' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'land_owned_feddan' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        // Agricultural land — rented
        'land_rented_share' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'land_rented_qirat' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'land_rented_feddan' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        // Livestock
        'cows' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'buffalo' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'sheep' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'goats' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        // Business projects
        'business_commercial' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'business_industrial' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'business_craft' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
        'business_agricultural' => ['quantity' => 0, 'is_active' => false, 'notes' => ''],
    ];

    /** @var array<string, array<string, mixed>> */
    public array $burdens = [
        'loans' => ['amount' => 0, 'notes' => ''],
        'education' => ['amount' => 0, 'notes' => ''],
        'medical' => ['amount' => 0, 'notes' => ''],
        'surgery' => ['amount' => 0, 'notes' => ''],
        'other_burden' => ['amount' => 0, 'notes' => ''],
    ];

    // Tab 6: Aids (المساعدات المقترحة) — keyed by AidType enum value
    /** @var array<string, array<string, mixed>> */
    public array $aids = [
        'financial' => ['eligible' => false, 'reasons' => ''],
        'medical' => ['eligible' => false, 'reasons' => ''],
        'educational' => ['eligible' => false, 'reasons' => ''],
        'marriage' => ['eligible' => false, 'reasons' => ''],
        'housing_furniture' => ['eligible' => false, 'reasons' => ''],
    ];

    // Tab 5: Housing
    public ?string $housing_type = null;

    public string $housing_type_other = '';

    public string $residence_status = '';

    public ?int $floors_count = null;

    public ?int $rooms_count = null;

    public string $roof_type = '';

    public bool $has_water = false;

    public bool $has_electricity = false;

    public bool $has_sewage = false;

    public string $finishing_description = '';

    public string $electrical_appliances = '';

    public string $home_furniture = '';

    public string $other_equipment = '';

    /** Acknowledgement checkbox — required before final submit */
    public bool $acknowledged = false;
}
