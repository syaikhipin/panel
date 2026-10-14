<?php

use App\Models\User;
use Livewire\Livewire;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('exposes the registration email and ip columns on the users table', function () {
    Livewire::test(ListUsers::class)
        ->assertTableColumnExists('registration_email')
        ->assertTableColumnExists('ip');
});

it('shows the registration email and ip on the edit form', function () {
    $user = User::factory()->create(['email' => 'dennis@example.com', 'ip' => '1.2.3.4']);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->assertFormFieldExists('registration_email')
        ->assertFormFieldExists('ip')
        ->assertFormSet([
            'registration_email' => 'dennis@example.com',
            'ip' => '1.2.3.4',
        ]);
});

it('does not overwrite the registration email when an admin changes the email', function () {
    $user = User::factory()->create(['email' => 'dennis@example.com']);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm(['email' => 'new@example.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->email->toBe('new@example.com')
        ->registration_email->toBe('dennis@example.com');
});
