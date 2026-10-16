@extends('layouts.auth')
@section('title', 'Verifica tu correo electrónico')

@section('styles')
<style>
.bg-gradient { background: linear-gradient(135deg, #006c47 0%, #00b67a 50%, #003d28 100%); }
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
      <p class="text-white/80 text-lg font-medium max-w-xs text-center leading-relaxed">Solo un paso más para comenzar.</p>
    </div>
  </div>

  <!-- Right Panel: Verify Email Info -->
  <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
    <div class="w-full max-w-md">
      <div class="mb-8">
        <div class="w-16 h-16 bg-primary/10 rounded-2xl flex items-center justify-center mb-6">
          <span class="material-symbols-outlined text-primary text-3xl">mark_email_read</span>
        </div>
        <h1 class="text-3xl font-black text-on-surface tracking-tight mb-4">Verifica tu correo</h1>
        <p class="text-on-surface-variant leading-relaxed">
          ¡Gracias por registrarte! Antes de comenzar, por favor verifica tu dirección de correo electrónico haciendo clic en el enlace que te acabamos de enviar. Si no recibiste el correo, con gusto te enviaremos otro.
        </p>
      </div>

      @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
          <span class="material-symbols-outlined text-emerald-600">check_circle</span>
          <p class="text-sm text-emerald-800 font-medium">Se ha enviado un nuevo enlace de verificación a la dirección de correo electrónico que proporcionaste.</p>
        </div>
      @endif

      <div class="flex flex-col gap-4 mt-8">
        <form method="POST" action="{{ route('verification.send') }}">
          @csrf
          <button type="submit" class="w-full btn-primary py-4 text-sm shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">send</span> Reenviar correo de verificación
          </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="w-full py-4 text-sm font-bold text-on-surface-variant bg-surface-container-low hover:bg-surface-container rounded-xl border border-surface-container-highest transition-colors">
            Cerrar sesión
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
