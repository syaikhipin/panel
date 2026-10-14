<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLastLoginIp
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        if (! $ip = request()->ip()) {
            return;
        }

        $event->user->forceFill(['ip' => $ip])->saveQuietly();
    }
}
