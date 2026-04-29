<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('panel.auth.login_identifier'))
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $data = $this->form->getState();
        $user = User::findByIdentifier($data['email'] ?? '');

        if (! $user || ! Hash::check($data['password'] ?? '', $user->password)) {
            $this->throwFailureValidationException();
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));

        if (
            $user instanceof FilamentUser
            && ! $user->canAccessPanel(Filament::getCurrentPanel())
        ) {
            Filament::auth()->logout();

            $ownedClient = $user->ownedClient;
            $isInactiveClientUser = in_array($user->role, [User::ROLE_CLIENTE, User::ROLE_DISTRIBUIDOR], true)
                && (! $ownedClient || ! $ownedClient->is_active);

            if ($isInactiveClientUser) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.email' => __('panel.auth.inactive_user'),
                ]);
            }

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
