@extends('layouts.auth')
@section('title', 'Verifica tu cuenta con código de 6 dígitos | FusaShop')

@section('styles')
<style>
.bg-gradient { background: linear-gradient(135deg, #006c47 0%, #00b67a 50%, #003d28 100%); }
.otp-input {
    width: 3.2rem;
    height: 4rem;
    font-size: 1.75rem;
    font-weight: 800;
    text-align: center;
    border-radius: 0.75rem;
    border: 2px solid #cbd5e1;
    background: #f8fafc;
    transition: all 0.2s ease;
}
.otp-input:focus {
    outline: none;
    border-color: #10b981;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
}
@media (max-width: 420px) {
    .otp-input {
        width: 2.6rem;
        height: 3.4rem;
        font-size: 1.4rem;
    }
}
</style>
@endsection

@section('content')
<div class="min-h-screen flex flex-col lg:flex-row">
  <!-- Left Panel: Animated Brand Area -->
  <div class="bg-gradient lg:w-5/12 xl:w-1/2 flex flex-col items-center justify-center p-8 lg:p-16 py-16 relative overflow-hidden">
    <div class="absolute top-1/4 -left-20 w-64 h-64 bg-white/5 rounded-full"></div>
    <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-white/5 rounded-full"></div>
    
    <div class="text-center relative z-10">
      <div class="inline-flex bg-white rounded-2xl px-5 py-3 shadow-lg mb-4">
        <x-logo size="xl" :href="route('consumer.home')" />
      </div>
      <h2 class="text-white text-2xl font-extrabold tracking-tight mt-3">Seguridad en cada paso</h2>
      <p class="text-white/80 text-sm font-medium max-w-xs text-center leading-relaxed mt-1">
        Validamos tu identidad para que tus compras, ventas y reservas en Fusagasugá sean 100% confiables.
      </p>
    </div>
  </div>

  <!-- Right Panel: Verify OTP Code -->
  <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
    <div class="w-full max-w-lg" x-data="{
        digits: ['', '', '', '', '', ''],
        get code() { return this.digits.join(''); },
        handleInput(index, event) {
            const val = event.target.value.replace(/[^0-9]/g, '');
            if (val.length > 1) {
                // Si el usuario pegó el código completo
                const chars = val.slice(0, 6).split('');
                chars.forEach((c, i) => {
                    if (i < 6) this.digits[i] = c;
                });
                const nextFocus = Math.min(chars.length, 5);
                this.$refs['digit' + nextFocus]?.focus();
                return;
            }
            this.digits[index] = val;
            if (val && index < 5) {
                this.$refs['digit' + (index + 1)]?.focus();
            }
        },
        handleKeyDown(index, event) {
            if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                this.$refs['digit' + (index - 1)]?.focus();
            }
        },
        handlePaste(event) {
            event.preventDefault();
            const pasteData = (event.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            if (pasteData) {
                pasteData.split('').forEach((c, i) => {
                    this.digits[i] = c;
                });
                const targetIdx = Math.min(pasteData.length, 5);
                this.$refs['digit' + targetIdx]?.focus();
            }
        }
    }">
      
      <!-- Brand & Header -->
      <div class="mb-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-2xl flex items-center justify-center shadow-xs">
            <span class="material-symbols-outlined text-2xl">verified_user</span>
          </div>
          <div>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider {{ auth()->user()->isMerchant() ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
              {{ auth()->user()->isMerchant() ? 'Cuenta Comerciante' : 'Cuenta Comprador' }}
            </span>
            <p class="text-xs text-slate-500 font-medium">Verificación de correo en 1 paso</p>
          </div>
        </div>

        <h1 class="text-2xl sm:text-3xl font-['Manrope'] font-extrabold text-slate-900 tracking-tight">
          Ingresa tu código de 6 números
        </h1>
        <p class="text-slate-600 text-sm mt-2 leading-relaxed">
          Enviamos un código de seguridad al correo <strong class="text-slate-900 font-semibold">{{ auth()->user()->email }}</strong>. Revisa tu bandeja de entrada o spam y digítalo a continuación.
        </p>
      </div>

      <!-- Alerts -->
      @if (session('status') == 'verification-code-sent' || session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3">
          <span class="material-symbols-outlined text-emerald-600 text-xl mt-0.5">mark_email_read</span>
          <div>
            <p class="text-sm text-emerald-900 font-bold">¡Nuevo código enviado!</p>
            <p class="text-xs text-emerald-700 mt-0.5">Hemos enviado un nuevo código de 6 números a tu correo. Válido por 30 minutos.</p>
          </div>
        </div>
      @endif

      @if ($errors->has('code'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
          <span class="material-symbols-outlined text-rose-600 text-xl mt-0.5">error</span>
          <div>
            <p class="text-sm text-rose-900 font-bold">Código no válido</p>
            <p class="text-xs text-rose-700 mt-0.5">{{ $errors->first('code') }}</p>
          </div>
        </div>
      @endif

      <!-- OTP Form -->
      <form method="POST" action="{{ route('verification.verify-code') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="code" :value="code">

        <div class="space-y-2">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 text-center">
            Código de Verificación
          </label>
          
          <!-- 6-digit visual inputs -->
          <div class="flex items-center justify-center gap-2 sm:gap-3" @paste="handlePaste($event)">
            <template x-for="(d, i) in 6" :key="i">
              <input type="text"
                     inputmode="numeric"
                     pattern="[0-9]*"
                     maxlength="1"
                     :value="digits[i]"
                     :x-ref="'digit' + i"
                     @input="handleInput(i, $event)"
                     @keydown="handleKeyDown(i, $event)"
                     class="otp-input"
                     autocomplete="one-time-code"
                     required />
            </template>
          </div>
          <p class="text-center text-[11px] text-slate-400 mt-2">
            Puedes pegar los 6 dígitos directamente en cualquier casilla.
          </p>
        </div>

        <!-- Submit Button -->
        <button type="submit"
                :disabled="code.length < 6"
                class="w-full py-3.5 px-6 rounded-xl font-bold text-sm text-white bg-[#006c47] hover:bg-[#10B981] disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-[#006c47]/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
          <span class="material-symbols-outlined text-lg">check_circle</span>
          <span>Verificar Cuenta y Continuar</span>
        </button>
      </form>

      <!-- Resend & Logout Footer -->
      <div class="mt-8 pt-6 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
        <form method="POST" action="{{ route('verification.resend-code') }}">
          @csrf
          <button type="submit" class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline flex items-center gap-1 cursor-pointer">
            <span class="material-symbols-outlined text-sm">refresh</span>
            <span>¿No recibiste el código? Reenviar</span>
          </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="text-slate-500 hover:text-slate-800 font-medium hover:underline flex items-center gap-1 cursor-pointer">
            <span class="material-symbols-outlined text-sm">logout</span>
            <span>Cerrar sesión</span>
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
@endsection
