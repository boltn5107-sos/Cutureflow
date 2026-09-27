<?php

namespace App\View\Components;

use App\Enums\UserStatus;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public function __construct(
        public ?UserStatus $status = null,
        public ?string $icon = null,
    ) {
    }

    public function render(): View
    {
        return view('components.status-badge');
    }
}
