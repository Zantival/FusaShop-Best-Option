<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Recuperar Contraseña | FusaShop</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,0&display=swap" rel="stylesheet"/>
<script>
tailwind.config={theme:{extend:{colors:{"primary":"#006c47","primary-c":"#00b67a","surface":"#f7fcf8","sc-low":"#f1f8f3","sc-high":"#dcebe0","on-s":"#1b1c1c","on-sv":"#334b3d"},borderRadius:{xl:"0.75rem","2xl":"1rem"},fontFamily:{h:["Manrope"],b:["Inter"]}}}}
</script>
<style>
.mso{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
.grad{background:linear-gradient(135deg,#006c47,#00b67a)}
body{font-family:'Inter',sans-serif;background:#f7fcf8;color:#1b1c1c;min-height:100vh;display:flex;flex-direction:column}
h1,h2{font-family:'Manrope',sans-serif}
</style>
</head>
<body>
<header style="background:rgba(252,249,248,.9);backdrop-filter:blur(20px)" class="fixed top-0 w-full z-50 shadow-sm h-16 flex items-center justify-between px-6">
  <x-logo size="default" :href="route('consumer.home')" />
  <a href="{{ route('login') }}" class="text-sm text-[#006c47] font-semibold hover:underline">Volver a inicio</a>
</header>

<main class="flex-1 flex items-center justify-center pt-20 pb-10 px-4">
  <div class="w-full max-w-md">
    <div class="bg-white rounded-2xl p-8 shadow-[0_12px_40px_rgba(0,0,0,0.08)]">
      <div class="mb-7">
        <div class="w-12 h-12 grad rounded-xl flex items-center justify-center mb-4">
          <span class="mso material-symbols-outlined text-white">lock_reset</span>
        </div>
        <h1 class="text-2xl font-black text-[#1b1c1c]">Recuperar contraseña</h1>
        <p class="text-[#3c4a41] text-sm mt-1">Ingresa tu correo. Un analista revisará la solicitud y restablecerá tu acceso.</p>
      </div>

      @if($errors->any())
        <div class="bg-red-50 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-center gap-2">
          <span class="mso material-symbols-outlined text-sm">error</span>{{ $errors->first() }}
        </div>
      @endif
      @if(session('success'))
        <div class="bg-green-50 text-green-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-start gap-2">
          <span class="mso material-symbols-outlined text-sm mt-0.5">check_circle</span>
          <span>{{ session('success') }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('password.offline.post') }}" class="space-y-4">
        @csrf
        <div>
          <label class="block text-sm font-semibold text-[#1b1c1c] mb-1.5">Correo electrónico</label>
          <div class="relative">
            <span class="mso material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#3c4a41] text-sm">mail</span>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
              class="w-full pl-10 pr-4 py-3 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 focus:ring-[#006c47] text-[#1b1c1c]"
              placeholder="correo@ejemplo.com"/>
          </div>
        </div>
        <button type="submit" class="w-full py-3 grad text-white font-semibold rounded-xl hover:opacity-90 active:scale-95 transition-all shadow-md flex items-center justify-center gap-2 mt-4">
          <span class="mso material-symbols-outlined text-sm">send</span> Solicitar restablecimiento
        </button>
      </form>
      <p class="text-center text-sm text-[#3c4a41] mt-5">
        ¿Ya recuerdas tu clave? <a href="{{ route('login') }}" class="text-[#006c47] font-semibold hover:underline">Inicia sesión</a>
      </p>
    </div>
  </div>
</main>
</body>
</html>
