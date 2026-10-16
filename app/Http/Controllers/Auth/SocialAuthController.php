<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Check if user already exists
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Update provider info if empty
                if (!$user->provider_id) {
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                    ]);
                }
                
                Auth::login($user, true);
                return $this->redirectByRole($user);
            }

            // Create new consumer user by default if using social login
            $newUser = User::create([
                'name' => $socialUser->getName() ?? 'Usuario',
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'role' => 'consumer', // Default role for social register
                'password' => null, // No password
            ]);

            Cart::create(['user_id' => $newUser->id]);
            
            Auth::login($newUser, true);
            return redirect()->route('consumer.home')->with('success', '¡Cuenta creada exitosamente con ' . ucfirst($provider) . '!');

        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'No se pudo iniciar sesión con ' . ucfirst($provider) . '. ' . $e->getMessage()]);
        }
    }

    private function redirectByRole(User $user)
    {
        if ($user->role === 'merchant') {
            if (!$user->companyProfile) {
                return redirect()->route('merchant.subscription');
            }
            return redirect()->route('merchant.dashboard');
        }

        return match ($user->role) {
            'analyst'  => redirect()->route('analyst.dashboard'),
            default    => redirect()->route('consumer.home'),
        };
    }
}
