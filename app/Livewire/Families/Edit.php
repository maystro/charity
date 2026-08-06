<?php

namespace App\Livewire\Families;

use App\Models\Family;
use Livewire\Attributes\Layout;

#[Layout('layouts.app', ['title' => 'تعديل أسرة'])]
class Edit extends Create
{
    public function mount(?Family $family = null): void
    {
        parent::mount($family);
    }
}
