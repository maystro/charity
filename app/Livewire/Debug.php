<?php

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تصحيح الأخطاء'])]
class Debug extends Component
{
    public function render(): View
    {
        return view('livewire.pages.debug');
    }
}
