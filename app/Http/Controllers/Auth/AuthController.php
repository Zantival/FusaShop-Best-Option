<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin(\Illuminate\Http\Request $request)
    {
        // Guardar URL de retorno si viene como query param
        if ($request->has('intended')) {
            redirect()->setIntendedUrl($request->input('intended'));
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Bloquear acceso si la cuenta está bloqueada
            if (Auth::user()->is_blocked) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors(['email' => 'Tu cuenta ha sido bloqueada. Contacta al administrador.'])->onlyInput('email');
            }

            return $this->redirectByRole(Auth::user());
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role'     => ['required', 'in:consumer,merchant'],
            'phone'    => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name'     => strip_tags($data['name']),
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'phone'    => $data['phone'] ?? null,
        ]);

        if ($user->isConsumer()) {
            Cart::create(['user_id' => $user->id]);
        }

        try {
            // Disparar el evento Registered para que Laravel envíe la notificación de verificación de correo
            event(new \Illuminate\Auth\Events\Registered($user));
            $user->notify(new \App\Notifications\WelcomeNotification());

            // Notificar a los analistas sobre la nueva cuenta
            $analysts = User::where('role', 'analyst')->get();
            foreach ($analysts as $analyst) {
                $analyst->notify(new \App\Notifications\NewUserNotification($user));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando correos durante el registro: ' . $e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectByRole(User $user)
    {
        // Si el usuario no tiene su correo verificado (y no es administrador), lo forzamos a la pantalla de verificación.
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->role === 'merchant') {
            if (!$user->companyProfile) {
                return redirect()->intended(route('merchant.profile'));
            }
            return redirect()->intended(route('merchant.dashboard'));
        }

        return match ($user->role) {
            'analyst'  => redirect()->route('analyst.dashboard'),
            default    => redirect()->intended(route('consumer.home')),
        };
    }

    public function showOfflinePasswordRequest()
    {
        return view('auth.forgot-password');
    }

    public function processOfflinePasswordRequest(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'No encontramos ninguna cuenta con ese correo electrónico.'
        ]);

        $user = User::where('email', $request->email)->first();

        \App\Models\PasswordResetOfflineRequest::create([
            'email' => $request->email,
        ]);

        $analysts = User::where('role', 'analyst')->get();
        foreach($analysts as $analyst) {
            $analyst->notify(new \App\Notifications\OfflinePasswordResetRequested($user));
        }

        return back()->with('success', 'Se ha notificado a nuestros analistas. Pronto se pondrán en contacto para restablecer tu contraseña.');
    }
}
