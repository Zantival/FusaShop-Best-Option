@extends('layouts.app')
@section('title', 'Editar Mi Perfil | FusaShop')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{
       avatarPreview: '{{ $user->avatarUrl() ?: '' }}',
       removeAvatar: false,
       showPasswordFields: false,
       handleAvatarChange(event) {
         const file = event.target.files[0];
         if (file) {
           this.removeAvatar = false;
           const newUrl = URL.createObjectURL(file);
           this.avatarPreview = newUrl;
           const img = document.getElementById('avatar-preview-img');
           if (img) {
             img.src = newUrl;
             img.classList.remove('hidden');
           }
           const initials = document.getElementById('avatar-fallback-initials');
           if (initials) initials.classList.add('hidden');
           const removeBtn = document.getElementById('btn-remove-avatar');
           if (removeBtn) removeBtn.classList.remove('hidden');
         }
       },
       clearAvatar() {
         this.removeAvatar = true;
         this.avatarPreview = '';
         const img = document.getElementById('avatar-preview-img');
         if (img) {
           img.src = '';
           img.classList.add('hidden');
         }
         const initials = document.getElementById('avatar-fallback-initials');
         if (initials) initials.classList.remove('hidden');
         const removeBtn = document.getElementById('btn-remove-avatar');
         if (removeBtn) removeBtn.classList.add('hidden');
         if (this.$refs.avatarInput) this.$refs.avatarInput.value = '';
         const fileInput = document.getElementById('avatar-input');
         if (fileInput) fileInput.value = '';
       }
     }">

  <!-- Header con botón de retorno a Mi Cuenta -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 bg-surface-container-lowest p-6 rounded-3xl shadow-card border border-outline-variant/20">
    <div class="flex items-center gap-4">
      <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary shrink-0 shadow-sm">
        <span class="material-symbols-outlined text-2xl">manage_accounts</span>
      </div>
      <div>
        <h1 class="text-2xl sm:text-3xl font-['Manrope'] font-black text-on-background">Editar Mi Perfil</h1>
        <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">Actualiza tu foto, número de teléfono, dirección de entrega y credenciales</p>
      </div>
    </div>

    <a href="{{ route('account.profile') }}" class="px-4 py-2.5 bg-surface-container text-on-surface hover:bg-surface-container-high rounded-xl text-xs font-bold transition-all flex items-center gap-2 self-start sm:self-auto border border-outline-variant/30 shadow-2xs">
      <span class="material-symbols-outlined text-sm">arrow_back</span>
      <span>Volver a Mi Cuenta</span>
    </a>
  </div>

  @if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-emerald-800 text-sm font-bold shadow-sm animate-in fade-in">
      <span class="material-symbols-outlined text-emerald-600 text-[24px]">check_circle</span>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if(session('error'))
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-3 text-rose-800 text-sm font-bold shadow-sm animate-in fade-in">
      <span class="material-symbols-outlined text-rose-600 text-[24px]">error</span>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs font-bold text-rose-800 space-y-1">
      <div class="flex items-center gap-1.5 text-sm font-black text-rose-900">
        <span class="material-symbols-outlined text-base">error</span> Por favor corrige los siguientes campos:
      </div>
      <ul class="list-disc list-inside space-y-0.5 pl-4 font-medium">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PUT')
    <input type="hidden" name="remove_avatar" :value="removeAvatar ? '1' : '0'">

    <!-- 1. TARJETA DE FOTO DE PERFIL / AVATAR -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-card border border-outline-variant/20 space-y-6">
      <div class="border-b border-outline-variant/20 pb-4">
        <h2 class="text-base font-['Manrope'] font-black text-on-background flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">add_a_photo</span>
          Foto de Perfil (Avatar)
        </h2>
        <p class="text-xs text-on-surface-variant mt-0.5">Esta fotografía se reflejará en la píldora superior de la barra de navegación y en tu panel de bienvenida junto a tu nombre.</p>
      </div>

      <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <!-- Visualizador de Imagen / Iniciales -->
        <div class="relative w-28 h-28 rounded-3xl overflow-hidden bg-slate-100 border-2 border-primary/25 shrink-0 flex items-center justify-center shadow-md">
          <img id="avatar-preview-img"
               src="{{ $user->avatarUrl() ?: '' }}"
               :src="avatarPreview ? avatarPreview : '{{ $user->avatarUrl() }}'"
               alt="{{ $user->name }}"
               class="w-full h-full object-cover {{ !$user->avatarUrl() ? 'hidden' : '' }}"
               :class="{ 'hidden': !avatarPreview }">
          
          <div id="avatar-fallback-initials"
               class="w-full h-full bg-gradient-to-tr from-[#10B981] to-[#047857] text-white flex items-center justify-center font-black text-3xl {{ $user->avatarUrl() ? 'hidden' : '' }}"
               :class="{ 'hidden': avatarPreview }">
            {{ strtoupper(substr($user->name, 0, 2)) }}
          </div>

          <div class="absolute bottom-1.5 right-1.5 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white" title="Activo"></div>
        </div>

        <!-- Botones de Acción para la Imagen -->
        <div class="flex-1 text-center sm:text-left space-y-3">
          <div>
            <h3 class="text-sm font-bold text-on-surface">{{ $user->name }}</h3>
            <span class="inline-block mt-0.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/30 text-[#006c47] border border-[#6efcb9] uppercase tracking-wider">
              @if($user->isMerchant()) Comerciante / Vendedor @elseif($user->isAnalyst()) Administrador @else Comprador Sumapaz @endif
            </span>
          </div>

          <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 pt-1">
            <input type="file" x-ref="avatarInput" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" @change="handleAvatarChange($event)" class="hidden">
            
            <label for="avatar-input" class="cursor-pointer px-4 py-2.5 bg-primary text-white rounded-xl text-xs font-bold hover:opacity-95 active:scale-95 transition-all inline-flex items-center gap-1.5 shadow-sm">
              <span class="material-symbols-outlined text-[18px]">photo_camera</span>
              <span>Subir Fotografía</span>
            </label>

            <button type="button"
                    id="btn-remove-avatar"
                    x-show="Boolean(avatarPreview)"
                    @click="clearAvatar()"
                    class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1 active:scale-95 cursor-pointer {{ !$user->avatarUrl() ? 'hidden' : '' }}"
                    :class="{ 'hidden': !avatarPreview }">
              <span class="material-symbols-outlined text-[18px]">delete</span>
              <span>Quitar Foto</span>
            </button>
          </div>

          <p class="text-[11px] text-on-surface-variant/70">Formatos permitidos: JPG, PNG, WEBP o GIF. Tamaño máximo: 4 MB.</p>
        </div>
      </div>
    </div>

    <!-- 2. TARJETA DE DATOS PERSONALES Y CONTACTO -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-card border border-outline-variant/20 space-y-6">
      <div class="border-b border-outline-variant/20 pb-4">
        <h2 class="text-base font-['Manrope'] font-black text-on-background flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">person</span>
          Datos Personales y Contacto
        </h2>
        <p class="text-xs text-on-surface-variant mt-0.5">Información utilizada para facturación electrónica DIAN y entrega de compras</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
            Nombre Completo <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">badge</span>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full bg-surface-container-low pl-10 pr-3 py-3 rounded-xl border border-outline-variant/30 text-xs font-semibold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                   placeholder="Tu nombre y apellidos">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
            Correo Electrónico <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">mail</span>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="w-full bg-surface-container-low pl-10 pr-3 py-3 rounded-xl border border-outline-variant/30 text-xs font-semibold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                   placeholder="correo@ejemplo.com">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
            Número de Teléfono / WhatsApp <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">call</span>
            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required
                   class="w-full bg-surface-container-low pl-10 pr-3 py-3 rounded-xl border border-outline-variant/30 text-xs font-semibold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                   placeholder="Ej. 310 123 4567">
          </div>
          <p class="text-[11px] text-on-surface-variant/70 mt-1">Requerido para la emisión de guías en Envia.com y notificaciones de entrega.</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
            Dirección Principal de Entrega
          </label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">home_pin</span>
            <input type="text" name="address" value="{{ old('address', $user->address) }}"
                   class="w-full bg-surface-container-low pl-10 pr-3 py-3 rounded-xl border border-outline-variant/30 text-xs font-semibold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                   placeholder="Ej. Calle 11 # 6-25, Barrio Centro, Fusagasugá">
          </div>
          <p class="text-[11px] text-on-surface-variant/70 mt-1">Dirección sugerida automáticamente en tus próximas compras.</p>
        </div>
      </div>
    </div>

    <!-- 3. TARJETA DE SEGURIDAD Y CONTRASEÑA -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-card border border-outline-variant/20 space-y-4">
      <div class="flex items-center justify-between cursor-pointer select-none" @click="showPasswordFields = !showPasswordFields">
        <div>
          <h2 class="text-base font-['Manrope'] font-black text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">lock_reset</span>
            Cambiar Contraseña de Acceso
          </h2>
          <p class="text-xs text-on-surface-variant mt-0.5">Opcional. Deja estos campos en blanco si no deseas cambiar tu contraseña.</p>
        </div>
        <button type="button" class="p-1 rounded-full hover:bg-surface-container text-on-surface-variant transition-transform duration-200" :class="showPasswordFields ? 'rotate-180' : ''">
          <span class="material-symbols-outlined">expand_more</span>
        </button>
      </div>

      <div x-show="showPasswordFields" style="display: none;" class="pt-4 border-t border-outline-variant/20 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Contraseña Actual</label>
          <input type="password" name="current_password" autocomplete="current-password" placeholder="Tu clave actual"
                 class="w-full bg-surface-container-low px-3 py-2.5 rounded-xl border border-outline-variant/30 text-xs font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary/20">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Nueva Contraseña (8+ caracteres)</label>
          <input type="password" name="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres"
                 class="w-full bg-surface-container-low px-3 py-2.5 rounded-xl border border-outline-variant/30 text-xs font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary/20">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Confirmar Nueva Contraseña</label>
          <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repite la nueva clave"
                 class="w-full bg-surface-container-low px-3 py-2.5 rounded-xl border border-outline-variant/30 text-xs font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary/20">
        </div>
      </div>
    </div>

    <!-- 4. BARRA DE ACCIÓN Y GUARDADO -->
    <div class="flex items-center justify-between pt-4">
      <a href="{{ route('account.profile') }}" class="px-5 py-3 rounded-2xl bg-surface-container text-on-surface font-bold text-xs hover:bg-surface-container-high transition-all flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm">cancel</span>
        <span>Cancelar</span>
      </a>

      <button type="submit" id="save-btn" class="px-8 py-3.5 bg-primary text-white font-bold text-xs rounded-2xl hover:opacity-95 active:scale-95 transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2 cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">save</span>
        <span>Guardar Cambios</span>
      </button>
    </div>
  </form>
</div>
@endsection
