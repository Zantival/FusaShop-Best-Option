@extends('layouts.auth')
@section('title', 'Crear cuenta')

@section('styles')
<style>
.bg-gradient { background: linear-gradient(135deg, #006c47 0%, #00b67a 50%, #003d28 100%); }
@keyframes cart-float { 0%, 100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-16px) rotate(3deg); } }
@keyframes float-tag { 0%, 100% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-8px) rotate(-3deg); } }
@keyframes typewriter { from { clip-path: inset(0 100% 0 0); } to { clip-path: inset(0 0% 0 0); } }
@keyframes blink-cursor { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }

.cart-anim { animation: cart-float 3s ease-in-out infinite; }
.tag-anim   { animation: float-tag 2.5s ease-in-out infinite; }
.typewriter { font-family: 'Manrope', sans-serif; font-weight: 800; white-space: nowrap; overflow: hidden; animation: typewriter 2s steps(10) 0.5s both; }
.cursor::after { content: '|'; animation: blink-cursor 0.8s ease-in-out infinite; color: rgba(255,255,255,0.7); }
.form-enter { animation: fadeInUp 0.6s ease forwards; opacity: 0; }
.form-enter:nth-child(1) { animation-delay: 0.1s; }
.form-enter:nth-child(2) { animation-delay: 0.2s; }
.form-enter:nth-child(3) { animation-delay: 0.3s; }

.role-input:checked + .role-box {
  border-color: #006c47 !important;
  background-color: rgba(0, 108, 71, 0.05) !important;
  box-shadow: 0 0 0 4px rgba(0, 108, 71, 0.1) !important;
}
.role-input:checked + .role-box .role-icon {
  color: #006c47 !important;
}

.input-wrap {
  display: flex; align-items: center;
  background: var(--surface-container); border-radius: 1rem; border: 2px solid transparent;
  transition: all 0.2s ease; overflow: hidden;
}
.input-wrap:focus-within { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(0,108,71,0.08); }
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
  <!-- Left Panel -->
  <div class="bg-gradient lg:w-5/12 xl:w-1/2 flex flex-col items-center justify-center p-8 lg:p-16 py-16 relative overflow-hidden">
    <div class="absolute top-1/4 -left-20 w-64 h-64 bg-white/5 rounded-full"></div>
    <div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-white/5 rounded-full"></div>
    
    <div class="cart-anim mb-8 relative z-10">
      <div class="tag-anim absolute -top-6 -right-8 bg-[#feb700] text-[#6b4b00] px-3 py-1 rounded-full text-xs font-black shadow-lg rotate-12">¡Nuevo!</div>
      <svg width="120" height="100" viewBox="0 0 140 120" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 15 L30 15 L50 80 L110 80 L125 30 L40 30" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="55" cy="100" r="8" fill="white"/>
        <circle cx="105" cy="100" r="8" fill="white"/>
      </svg>
    </div>

    <div class="text-center relative z-10">
      <div class="typewriter cursor text-4xl xl:text-5xl text-white mb-4">Únete hoy</div>
      <p class="text-white/80 text-lg font-medium max-w-xs text-center leading-relaxed">Crea tu cuenta y empieza a comprar o vender en Fusagasugá.</p>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="flex-1 flex items-center justify-center p-6 lg:p-12 bg-surface-container-lowest">
    <div class="w-full max-w-lg">
      <div class="mb-6 form-enter">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">person_add</span>
          </div>
          <a href="{{ route('login') }}" class="text-sm text-on-surface-variant hover:text-primary transition-colors font-medium">← Ya tengo cuenta</a>
        </div>
        <h1 class="text-3xl font-black text-on-surface tracking-tight mb-1">Crea tu cuenta</h1>
        <p class="text-on-surface-variant">Regístrate para empezar a usar FusaShop</p>
      </div>

      @if($errors->any())
        <div class="mb-6 p-4 bg-error/10 border border-error/20 rounded-xl flex items-center gap-3 form-enter">
          <span class="material-symbols-outlined text-error text-[20px]">error</span>
          <div class="text-sm text-error font-medium">
            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
          </div>
        </div>
      @endif

      <form method="POST" action="{{ route('register') }}" class="space-y-4" id="register-form">
        @csrf
        
        <!-- Role Selection -->
        <div class="form-enter">
          <label class="block text-sm font-bold text-on-surface mb-3">¿Qué rol tendrás?</label>
          <div class="grid grid-cols-2 gap-3">
            <!-- Comprar -->
            <label class="relative cursor-pointer block h-full">
              <input type="radio" name="role" value="consumer" class="role-input absolute inset-0 w-full h-full opacity-0 z-20 cursor-pointer" {{ old('role','consumer')==='consumer' ? 'checked' : '' }} required/>
              <div class="role-box flex flex-col items-center gap-2 p-4 bg-surface-container rounded-2xl border-2 border-transparent transition-all h-full relative z-10">
                <span class="material-symbols-outlined role-icon text-on-surface-variant text-3xl">shopping_bag</span>
                <span class="text-sm font-bold text-on-surface">Comprar</span>
              </div>
            </label>
            <!-- Vender -->
            <label class="relative cursor-pointer block h-full">
              <input type="radio" name="role" value="merchant" class="role-input absolute inset-0 w-full h-full opacity-0 z-20 cursor-pointer" {{ old('role')==='merchant' ? 'checked' : '' }}/>
              <div class="role-box flex flex-col items-center gap-2 p-4 bg-surface-container rounded-2xl border-2 border-transparent transition-all h-full relative z-10">
                <span class="material-symbols-outlined role-icon text-on-surface-variant text-3xl">storefront</span>
                <span class="text-sm font-bold text-on-surface">Vender</span>
              </div>
            </label>
          </div>
        </div>



        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="form-enter md:col-span-2">
            <label class="block text-sm font-semibold text-on-surface mb-1.5">Nombre completo</label>
            <div class="input-wrap">
              <span class="material-symbols-outlined input-icon-box">person</span>
              <input type="text" name="name" value="{{ old('name') }}" required
                class="input-field" placeholder="Tu nombre">
            </div>
          </div>

          <div class="form-enter md:col-span-2">
            <label class="block text-sm font-semibold text-on-surface mb-1.5">Correo electrónico</label>
            <div class="input-wrap">
              <span class="material-symbols-outlined input-icon-box">mail</span>
              <input type="email" name="email" value="{{ old('email') }}" required
                class="input-field" placeholder="correo@ejemplo.com" autocomplete="email">
            </div>
          </div>

          <div class="form-enter">
            <label class="block text-sm font-semibold text-on-surface mb-1.5">Contraseña</label>
            <div class="input-wrap">
              <span class="material-symbols-outlined input-icon-box">lock</span>
              <input type="password" name="password" id="password" required
                class="input-field" placeholder="8+ caracteres" autocomplete="new-password">
              <button type="button" onclick="togglePwd('password', 'eye-pwd')" class="flex items-center justify-center w-10 min-w-10 text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[20px]" id="eye-pwd">visibility_off</span>
              </button>
            </div>
          </div>

          <div class="form-enter">
            <label class="block text-sm font-semibold text-on-surface mb-1.5">Confirmar</label>
            <div class="input-wrap">
              <span class="material-symbols-outlined input-icon-box">lock_reset</span>
              <input type="password" name="password_confirmation" id="password_confirmation" required
                class="input-field" placeholder="Repite contraseña">
              <button type="button" onclick="togglePwd('password_confirmation', 'eye-pwd-conf')" class="flex items-center justify-center w-10 min-w-10 text-on-surface-variant hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[20px]" id="eye-pwd-conf">visibility_off</span>
              </button>
            </div>
          </div>
        </div>

        <div class="form-enter pt-2">
          <button type="submit" id="reg-btn" class="w-full btn-primary py-4 text-sm shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined text-[20px]">how_to_reg</span> Crear mi cuenta
          </button>
          
          <div class="relative flex items-center justify-center my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-surface-container-highest"></div></div>
            <span class="relative px-4 bg-white text-[10px] font-black text-on-surface-variant uppercase tracking-widest">O regístrate con</span>
          </div>

          <a href="{{ route('social.redirect', 'google') }}" class="w-full flex items-center justify-center gap-3 py-3.5 bg-white border-2 border-surface-container-highest rounded-2xl font-bold text-on-surface hover:bg-surface-container-low hover:border-primary/30 transition-all shadow-sm">
             <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google Logo">
             <span class="text-sm">Google</span>
          </a>
        </div>

        <p class="text-center text-xs text-on-surface-variant font-medium pt-4 form-enter">
          Al registrarte, aceptas nuestros 
          <a href="#" class="text-primary hover:underline">Términos de Servicio</a> y 
          <a href="#" class="text-primary hover:underline">Privacidad</a>.
        </p>
      </form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
function togglePwd(inputId, iconId) {
  const i = document.getElementById(inputId);
  const e = document.getElementById(iconId);
  if (!i || !e) return;
  i.type = i.type === 'password' ? 'text' : 'password';
  e.textContent = i.type === 'password' ? 'visibility_off' : 'visibility';
}

document.getElementById('register-form').addEventListener('submit', function() {
  const btn = document.getElementById('reg-btn');
  btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[20px]">sync</span> Creando cuenta...';
  btn.disabled = true;
});
</script>
@endsection
