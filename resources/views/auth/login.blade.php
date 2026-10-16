@extends('layouts.auth')
@section('title', 'Iniciar sesión')

@section('styles')
<style>
.bg-gradient { background: linear-gradient(135deg, #006c47 0%, #00b67a 50%, #003d28 100%); }
@keyframes cart-float { 0%, 100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-16px) rotate(3deg); } }
@keyframes wheel-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes items-bounce { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: 0.8; } }
@keyframes typewriter { from { clip-path: inset(0 100% 0 0); } to { clip-path: inset(0 0% 0 0); } }
@keyframes blink-cursor { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
@keyframes float-tag { 0%, 100% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-8px) rotate(-3deg); } }
@keyframes sparkle { 0%, 100% { transform: scale(0) rotate(0deg); opacity: 0; } 50% { transform: scale(1) rotate(180deg); opacity: 1; } }

.cart-anim { animation: cart-float 3s ease-in-out infinite; }
.wheel-anim { animation: wheel-spin 2s linear infinite; transform-origin: center; }
.items-anim { animation: items-bounce 2s ease-in-out infinite; animation-delay: 0.3s; }
.tag-anim   { animation: float-tag 2.5s ease-in-out infinite; }
.sparkle-anim { animation: sparkle 2s ease-in-out infinite; }
.sparkle-anim:nth-child(2) { animation-delay: 0.7s; }
.sparkle-anim:nth-child(3) { animation-delay: 1.4s; }

.typewriter { font-family: 'Manrope', sans-serif; font-weight: 800; white-space: nowrap; overflow: hidden; animation: typewriter 2s steps(10) 0.5s both; }
.cursor::after { content: '|'; animation: blink-cursor 0.8s ease-in-out infinite; color: rgba(255,255,255,0.7); }
.form-enter { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
.form-enter:nth-child(1) { animation-delay: 0.1s; }
.form-enter:nth-child(2) { animation-delay: 0.2s; }
.form-enter:nth-child(3) { animation-delay: 0.3s; }

.input-wrap {
  display: flex; align-items: center;
  background: var(--surface-container); border-radius: 1rem; border: 2px solid transparent;
  transition: all 0.2s ease; overflow: hidden;
}
.input-wrap:focus-within { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(0,108,71,0.08); }
.input-wrap.valid   { border-color: #00b67a; }
.input-wrap.invalid { border-color: var(--error); background: #fff8f8; }
.input-icon-box {
  display: flex; align-items: center; justify-content: center;
  width: 3rem; min-width: 3rem; height: 100%;
  color: var(--on-surface-variant);
  border-right: 1.5px solid rgba(0,0,0,0.07);
  padding: 0 0.5rem;
  font-size: 20px;
  background: transparent;
  pointer-events: none;
  user-select: none;
}
.input-field {
  flex: 1; padding: 0.875rem 1rem;
  background: transparent; border: none;
  outline: none; font-size: 0.9rem;
  color: var(--on-surface); width: 100%;
}
</style>
@endsection

@section('content')
<div class="min-h-screen flex flex-col lg:flex-row">
  <!-- Left Panel: Animated Brand Area -->
  <div class="bg-gradient lg:w-5/12 xl:w-1/2 flex flex-col items-center justify-center p-8 lg:p-16 py-16 relative overflow-hidden">
    <div class="absolute top-1/4 -left-20 w-64 h-64 bg-white/5 rounded-full"></div>
    <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-white/5 rounded-full"></div>
    <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>

    <div class="cart-anim mb-8 relative z-10">
      <div class="tag-anim absolute -top-6 -right-8 bg-[#feb700] text-[#6b4b00] px-3 py-1 rounded-full text-xs font-black shadow-lg rotate-12">¡Oferta!</div>
      <svg width="140" height="120" viewBox="0 0 140 120" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="20" y="30" width="90" height="55" rx="8" fill="white" opacity="0.15" stroke="white" stroke-width="2"/>
        <path d="M10 15 L30 15 L20 25" stroke="white" stroke-width="3" stroke-linecap="round" fill="none"/>
        <rect x="5" y="12" width="30" height="6" rx="3" fill="white" opacity="0.9"/>
        <rect x="22" y="32" width="86" height="50" rx="7" fill="white" opacity="0.2"/>
        <g class="items-anim">
          <rect x="32" y="38" width="20" height="25" rx="4" fill="white" opacity="0.6"/>
          <rect x="58" y="42" width="20" height="21" rx="4" fill="white" opacity="0.5"/>
          <rect x="84" y="36" width="18" height="27" rx="4" fill="white" opacity="0.7"/>
        </g>
        <circle cx="42" cy="92" r="10" fill="white" opacity="0.9"/><circle cx="42" cy="92" r="5" fill="#006c47"/>
        <circle class="wheel-anim" cx="42" cy="92" r="7" stroke="white" stroke-width="1.5" stroke-dasharray="4 4" fill="none"/>
        <circle cx="90" cy="92" r="10" fill="white" opacity="0.9"/><circle cx="90" cy="92" r="5" fill="#006c47"/>
        <circle class="wheel-anim" cx="90" cy="92" r="7" stroke="white" stroke-width="1.5" stroke-dasharray="4 4" fill="none"/>
        <circle cx="110" cy="22" r="14" fill="#6efcb9"/>
        <path d="M103 22 L108 27 L118 17" stroke="#006c47" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
      </svg>
    </div>

    <div class="text-center relative z-10">
      <div class="typewriter cursor text-4xl xl:text-5xl text-white mb-4">FusaShop</div>
      <p class="text-white/80 text-lg font-medium max-w-xs text-center leading-relaxed">El mercado local de Fusagasugá, en tu mano.</p>
    </div>
  </div>

  <!-- Right Panel: Login Form -->
  <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
    <div class="w-full max-w-md">
      <div class="mb-8 form-enter">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">lock_person</span>
          </div>
          <a href="/" class="text-sm text-on-surface-variant hover:text-primary transition-colors font-medium">← Volver a la tienda</a>
        </div>
        <h1 class="text-3xl font-black text-on-surface tracking-tight mb-2">¡Bienvenido de vuelta!</h1>
        <p class="text-on-surface-variant">Inicia sesión para acceder a tu cuenta</p>
      </div>

      @if(session('info'))
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-3 form-enter">
          <span class="material-symbols-outlined text-amber-600 text-[20px]">info</span>
          <p class="text-sm text-amber-800 font-medium">{{ session('info') }}</p>
        </div>
      @endif

      @if($errors->any())
        <div class="mb-6 p-4 bg-error/10 border border-error/20 rounded-xl flex items-center gap-3 form-enter">
          <span class="material-symbols-outlined text-error text-[20px]">error</span>
          <p class="text-sm text-error font-medium">{{ $errors->first() }}</p>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="space-y-5" id="login-form">
        @csrf
        <div class="form-enter">
          <label class="block text-sm font-semibold text-on-surface mb-2">Correo electrónico</label>
          <div class="input-wrap" id="wrap-email">
            <span class="material-symbols-outlined input-icon-box">mail</span>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
              class="input-field" placeholder="tu@email.com"
              autocomplete="email"
              oninput="validateEmail(this)">
          </div>
        </div>

        <div class="form-enter">
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-semibold text-on-surface">Contraseña</label>
            <a href="{{ route('password.request.offline') }}" class="text-xs text-primary font-semibold hover:underline">¿Olvidaste tu contraseña?</a>
          </div>
          <div class="input-wrap" id="wrap-pwd">
            <span class="material-symbols-outlined input-icon-box">lock</span>
            <input type="password" name="password" id="password" required
              class="input-field" placeholder="Mínimo 8 caracteres"
              autocomplete="current-password"
              oninput="validatePassword(this)">
            <button type="button" onclick="togglePwd()" class="flex items-center justify-center w-10 min-w-10 text-on-surface-variant hover:text-primary transition-colors">
              <span class="material-symbols-outlined text-[20px]" id="eye-icon">visibility_off</span>
            </button>
          </div>
        </div>

        <div class="flex items-center gap-3 form-enter">
          <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary shadow-sm" {{ old('remember') ? 'checked' : '' }}>
          <label for="remember" class="text-sm text-on-surface-variant font-medium cursor-pointer select-none">Mantener sesión iniciada</label>
        </div>

        <div class="form-enter">
          <button type="submit" id="submit-btn" class="w-full btn-primary py-4 text-sm shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-[20px]">login</span> Iniciar Sesión
          </button>
          
          <div class="relative flex items-center justify-center my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-surface-container-highest"></div></div>
            <span class="relative px-4 bg-white text-[10px] font-black text-on-surface-variant uppercase tracking-widest">O continúa con</span>
          </div>

          <a href="{{ route('social.redirect', 'google') }}" class="w-full flex items-center justify-center gap-3 py-3.5 bg-white border-2 border-surface-container-highest rounded-2xl font-bold text-on-surface hover:bg-surface-container-low hover:border-primary/30 transition-all shadow-sm">
             <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google Logo">
             <span class="text-sm">Google</span>
          </a>

          <p class="text-center text-xs text-on-surface-variant font-medium pt-6 form-enter">
            ¿No tienes una cuenta? 
            <a href="{{ route('register') }}" class="text-primary font-bold hover:underline">Regístrate gratis</a>
          </p>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
// Función eliminada para evitar solapamiento visual
function togglePwd(){
  const i=document.getElementById('password'), e=document.getElementById('eye-icon');
  i.type = i.type==='password'?'text':'password';
  e.textContent = i.type==='password'?'visibility_off':'visibility';
}
function validateEmail(input) {
  const wrap = document.getElementById('wrap-email');
  const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
  if (input.value.length > 0) {
    wrap.classList.toggle('valid', isValid);
    wrap.classList.toggle('invalid', !isValid && input.value.length > 4);
  } else { wrap.classList.remove('valid','invalid'); }
}
function validatePassword(input) {
  const val = input.value;
}
document.getElementById('login-form').addEventListener('submit', function() {
  const btn = document.getElementById('submit-btn');
  // Pequeño retardo para que el navegador capture las credenciales
  setTimeout(() => {
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">sync</span> Iniciando...';
    btn.classList.add('opacity-50', 'cursor-wait');
  }, 10);
});
</script>
@endsection

