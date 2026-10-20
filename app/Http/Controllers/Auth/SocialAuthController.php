<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        if ($provider !== 'google') {
            abort(404);
        }

        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            return redirect()->route('login')->withErrors([
                'email' => 'El inicio de sesión con Google no está configurado. Agrega GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en el archivo .env.',
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            return $this->loginOrRegisterSocialUser(
                provider: $provider,
                providerId: (string) $socialUser->getId(),
                email: strtolower((string) $socialUser->getEmail()),
                name: $socialUser->getName() ?: ($socialUser->getNickname() ?: 'Usuario Google'),
                defaultRole: 'consumer'
            );
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'No se pudo iniciar sesión con ' . ucfirst($provider) . '. Verifica tus credenciales OAuth en .env (' . $e->getMessage() . ')',
            ]);
        }
    }

    private function loginOrRegisterSocialUser(
        string $provider,
        string $providerId,
        string $email,
        string $name,
        string $defaultRole = 'consumer'
    ) {
        $user = User::where('email', $email)->first();

        if ($user) {
            $updates = [];
            if (!$user->provider_id) {
                $updates['provider'] = $provider;
                $updates['provider_id'] = $providerId;
            }
            if (is_null($user->email_verified_at)) {
                $updates['email_verified_at'] = now();
            }
            if (!empty($updates)) {
                $user->forceFill($updates)->save();
            }

            Auth::login($user, true);
            return $this->redirectByRole($user);
        }

        $newUser = new User([
            'name'        => $name,
            'email'       => $email,
            'provider'    => $provider,
            'provider_id' => $providerId,
            'role'        => $defaultRole,
            'password'    => null,
        ]);
        $newUser->email_verified_at = now();
        $newUser->save();

        if ($newUser->role === 'consumer') {
            Cart::firstOrCreate(['user_id' => $newUser->id]);
        }

        try {
            $newUser->notify(new WelcomeNotification());
        } catch (\Throwable $e) {
            // No bloquear el inicio de sesión si el transporte de correo en sandbox restringe destinatarios
        }

        Auth::login($newUser, true);

        return $this->redirectByRole($newUser)
            ->with('success', '¡Cuenta autenticada exitosamente con ' . ucfirst($provider) . '!');
    }

    private function redirectByRole(User $user)
    {
        if ($user->role === 'merchant') {
            if (!$user->companyProfile) {
                return redirect()->route('merchant.profile');
            }
            return redirect()->route('merchant.dashboard');
        }

        return match ($user->role) {
            'analyst' => redirect()->route('analyst.dashboard'),
            default   => redirect()->route('consumer.home'),
        };
    }
}
