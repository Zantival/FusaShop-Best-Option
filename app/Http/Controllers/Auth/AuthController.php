<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailCodeMail;
use App\Models\Cart;
use App\Models\PasswordResetOfflineRequest;
use App\Models\User;
use App\Notifications\NewUserNotification;
use App\Notifications\OfflinePasswordResetRequested;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin(Request $request)
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

            // Si aún no está verificado, enviar o asegurar código activo
            if (! Auth::user()->hasVerifiedEmail()) {
                $user = Auth::user();
                if (empty($user->verification_code) || ($user->verification_code_expires_at && $user->verification_code_expires_at->isPast())) {
                    $code = $user->generateVerificationCode();
                    try {
                        Mail::to($user->email)->send(new VerifyEmailCodeMail($user, $code));
                    } catch (\Throwable $e) {
                        Log::error('Error enviando código de verificación al login: ' . $e->getMessage());
                    }
                }
                return redirect()->route('verification.notice');
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
            'phone'    => ['required', 'string', 'max:25'],
        ], [
            'phone.required' => 'El número de teléfono es obligatorio.',
        ]);

        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name'                         => strip_tags($data['name']),
            'email'                        => $data['email'],
            'password'                     => Hash::make($data['password']),
            'role'                         => $data['role'],
            'phone'                        => $data['phone'] ?? null,
            'verification_code'            => $code,
            'verification_code_expires_at' => now()->addMinutes(30),
        ]);

        if ($user->isConsumer()) {
            Cart::create(['user_id' => $user->id]);
        }

        try {
            // Enviar correo con el código de 6 dígitos con diseño oficial FusaShop
            Mail::to($user->email)->send(new VerifyEmailCodeMail($user, $code));
            $user->notify(new WelcomeNotification());

            // Notificar a los analistas sobre la nueva cuenta
            $analysts = User::where('role', 'analyst')->get();
            foreach ($analysts as $analyst) {
                $analyst->notify(new NewUserNotification($user));
            }
        } catch (\Throwable $e) {
            Log::error('Error enviando correos durante el registro: ' . $e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'Por favor digita los 6 números del código.',
            'code.digits'   => 'El código de verificación debe contener exactamente 6 dígitos.',
        ]);

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectByRole($user);
        }

        if (! $user->verifyCode($request->code)) {
            return back()->withErrors([
                'code' => 'El código de 6 números es incorrecto o ha vencido. Por favor solicita un nuevo código.'
            ]);
        }

        session()->flash('welcome_new_user', true);
        session()->flash('success', '¡Excelente! Tu correo electrónico ha sido verificado con éxito.');

        return $this->redirectByRole($user);
    }

    public function resendVerificationCode(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->redirectByRole($user);
        }

        $code = $user->generateVerificationCode();

        try {
            Mail::to($user->email)->send(new VerifyEmailCodeMail($user, $code));
        } catch (\Throwable $e) {
            Log::error('Error reenviando código de verificación: ' . $e->getMessage());
        }

        return back()->with('status', 'verification-code-sent');
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

        PasswordResetOfflineRequest::create([
            'email' => $request->email,
        ]);

        $analysts = User::where('role', 'analyst')->get();
        foreach($analysts as $analyst) {
            $analyst->notify(new OfflinePasswordResetRequested($user));
        }

        return back()->with('success', 'Se ha notificado a nuestros analistas. Pronto se pondrán en contacto para restablecer tu contraseña.');
    }
}
