<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class AidRequestForm extends Form
{
    public ?int $id = null;

    #[Validate('required|exists:families,id')]
    public ?int $family_id = null;

    #[Validate('required|string|max:255')]
    public string $source_type = 'الأسرة مباشرة';

    #[Validate('nullable|string|max:100')]
    public ?string $applicant_name = null;

    #[Validate('nullable|string|max:100')]
    public ?string $applicant_relation = null;

    #[Validate('nullable|string|max:20')]
    public ?string $applicant_phone = null;

    #[Validate('required|in:وقتية,دورية,طارئة')]
    public string $request_type = '';

    #[Validate('required|in:عادية,متوسطة,مرتفعة,عاجلة جداً')]
    public string $priority = '';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public ?string $description = null;

    #[Validate('nullable|date')]
    public ?string $needed_by = null;

    #[Validate('nullable|string')]
    public ?string $internal_notes = null;

    #[Validate('nullable|string')]
    public ?string $exception_reason = null;

    /** Acknowledgement checkbox - required before final submit */
    public bool $acknowledged = false;
}
