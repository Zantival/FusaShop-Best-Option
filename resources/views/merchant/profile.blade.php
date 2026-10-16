@extends('layouts.app')
@section('title', 'Perfil Corporativo')
@section('content')
<div class="max-w-4xl mx-auto px-4 md:px-6 py-8">

  <div class="mb-8">
    <h1 class="text-3xl font-black text-on-surface tracking-tight flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-3xl">business</span>
      Identidad Corporativa (KYC)
    </h1>
    <p class="text-on-surface-variant mt-1">Completa tu perfil para ser verificado por el equipo analista.</p>
  </div>

  @if($profile?->kyc_status === 'pending')
    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-3">
      <span class="material-symbols-outlined text-amber-600">hourglass_empty</span>
      <p class="text-sm font-bold text-amber-800">Perfil en revisión — Aún no puedes publicar nuevos productos.</p>
    </div>
  @elseif($profile?->kyc_status === 'approved')
    <div class="mb-6 p-4 bg-[#6efcb9]/20 border border-primary/20 rounded-xl flex items-center gap-3">
      <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1">verified</span>
      <p class="text-sm font-bold text-primary">Perfil Aprobado — Puedes vender y actualizar tu información libremente.</p>
    </div>
  @elseif($profile?->kyc_status === 'rejected')
    <div class="mb-6 p-4 bg-[#ffdad6] border border-[#ba1a1a]/20 rounded-xl flex items-center gap-3">
      <span class="material-symbols-outlined text-[#ba1a1a]">error</span>
      <p class="text-sm font-bold text-[#ba1a1a]">Verificación Rechazada — Vuelve a enviar tu documentación.</p>
    </div>
  @endif

  @if($errors->any())
    <div class="mb-6 p-4 bg-[#ffdad6] border border-[#ba1a1a]/10 rounded-xl">
      <ul class="space-y-1">
        @foreach($errors->all() as $err)
          <li class="text-sm text-[#ba1a1a] font-medium flex items-center gap-2"><span class="material-symbols-outlined text-[14px]">error</span>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('merchant.profile.store') }}" enctype="multipart/form-data" class="space-y-8">
    @csrf

    <!-- Información Básica -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-surface-container">
      <h2 class="font-bold text-on-surface mb-6 flex items-center gap-2 pb-4 border-b border-surface-container">
        <span class="material-symbols-outlined text-primary text-[20px]">info</span> Información de la Empresa
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Nombre del Representante Legal *</label>
          <input type="text" name="owner_name" value="{{ old('owner_name', Auth::user()->name) }}" required
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('owner_name') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all">
          @error('owner_name') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Razón Social *</label>
          <input type="text" name="company_name" value="{{ old('company_name', $profile->company_name ?? '') }}" required
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('company_name') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all">
          @error('company_name') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Tipo de Productos que vende *</label>
          <input type="text" name="business_type" value="{{ old('business_type', $profile->business_type ?? '') }}" placeholder="Ej. Ropa, Electrónica, Alimentos" required
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('business_type') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all">
          @error('business_type') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Teléfono *</label>
          <input type="text" name="phone" value="{{ old('phone', $profile->phone ?? '') }}" required
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('phone') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all">
          @error('phone') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Número de Empleados</label>
          <input type="number" name="employee_count" value="{{ old('employee_count', $profile->employee_count ?? '') }}" min="1" placeholder="Ej. 5"
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('employee_count') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all">
          @error('employee_count') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">WhatsApp (sin +57)</label>
          <input type="text" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp ?? '') }}" placeholder="3001234567"
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('whatsapp') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all">
          @error('whatsapp') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Dirección (para el mapa)</label>
          <input type="text" name="address" value="{{ old('address', $profile->address ?? '') }}" placeholder="Cra 10 # 3-45, Fusagasugá"
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('address') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all">
          @error('address') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Descripción Pública</label>
          <textarea name="description" rows="3"
            class="w-full bg-surface-container-low rounded-xl border-2 {{ $errors->has('description') ? 'border-[#ba1a1a]' : 'border-transparent focus:border-primary' }} p-4 outline-none text-on-surface text-sm transition-all resize-none">{{ old('description', $profile->description ?? '') }}</textarea>
          @error('description') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    <!-- KYC / Documentos -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-surface-container">
      <h2 class="font-bold text-on-surface mb-6 flex items-center gap-2 pb-4 border-b border-surface-container">
        <span class="material-symbols-outlined text-primary text-[20px]">description</span> Documentos Legales (KYC)
      </h2>
      <p class="text-xs text-on-surface-variant mb-6">Los documentos se guardan de forma privada y solo son accesibles por el equipo analista para verificar tu negocio.</p>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Registro Único Tributario (RUT) *</label>
          @if($profile?->rut_path)
            <input type="hidden" name="rut_exists" value="1">
          @endif
          <input type="file" name="rut_file" accept=".pdf" {{ $profile?->rut_path ? '' : 'required' }}
            class="w-full text-sm text-on-surface-variant file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:font-bold file:bg-primary file:text-white hover:file:opacity-90 file:cursor-pointer">
          @error('rut_file') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
          @if($profile?->rut_path)
            <p class="text-xs mt-3 text-primary font-bold flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px]">check_circle</span> RUT cargado correctamente
            </p>
          @endif
        </div>
        
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Cámara de Comercio *</label>
          @if($profile?->camara_comercio_path)
            <input type="hidden" name="camara_comercio_exists" value="1">
          @endif
          <input type="file" name="camara_comercio_file" accept=".pdf,image/*" {{ $profile?->camara_comercio_path ? '' : 'required' }}
            class="w-full text-sm text-on-surface-variant file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:font-bold file:bg-primary file:text-white hover:file:opacity-90 file:cursor-pointer">
          @error('camara_comercio_file') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
          @if($profile?->camara_comercio_path)
            <p class="text-xs mt-3 text-primary font-bold flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px]">check_circle</span> Cámara de Comercio cargada
            </p>
          @endif
        </div>
      </div>
    </div>

    <!-- Branding -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-surface-container">
      <h2 class="font-bold text-on-surface mb-6 flex items-center gap-2 pb-4 border-b border-surface-container">
        <span class="material-symbols-outlined text-primary text-[20px]">palette</span> Imagen de Marca
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">Logo de la Empresa</label>
          @if($profile?->logo_path)
            <div class="w-20 h-20 rounded-2xl overflow-hidden border border-surface-container mb-3">
              <img src="{{ Storage::url($profile->logo_path) }}" class="w-full h-full object-cover">
            </div>
          @endif
          <input type="file" name="logo" accept="image/*"
            class="w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-semibold file:bg-surface-container-low file:text-on-surface hover:file:opacity-90">
          @error('logo') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">Banners (múltiples)</label>
          @if($profile?->banners_path && is_array($profile->banners_path))
            <div class="flex gap-2 flex-wrap mb-3">
              @foreach($profile->banners_path as $bPath)
                @php $path = is_array($bPath) ? ($bPath['path'] ?? '') : $bPath; @endphp
                @if($path)
                  <img src="{{ Storage::url($path) }}" class="w-14 h-10 rounded-lg object-cover border border-surface-container">
                @endif
              @endforeach
            </div>
          @endif
          <input type="file" name="banners[]" accept="image/*" multiple
            class="w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-semibold file:bg-surface-container-low file:text-on-surface hover:file:opacity-90">
          @error('banners') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
          @error('banners.*') <p class="text-xs text-[#ba1a1a] mt-1">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-between mt-6 gap-4">
      @if(!$profile || $profile->kyc_status !== 'approved')
        <a href="#" onclick="event.preventDefault(); fetch('{{ route('logout') }}', { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} }).then(() => window.location.href='{{ route('register') }}');" class="btn-secondary w-full sm:w-auto">
          <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al registro
        </a>
      @else
        <a href="{{ route('merchant.dashboard') }}" class="btn-secondary w-full sm:w-auto">
          <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al panel
        </a>
      @endif
      <button type="submit" class="px-8 py-4 bg-greenhouse-gradient text-white font-bold rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 active:scale-97 transition-all flex items-center gap-2">
        <span class="material-symbols-outlined text-[20px]">save</span>
        {{ $profile ? 'Actualizar Perfil' : 'Enviar para Aprobación' }}
      </button>
    </div>
  </form>
</div>

<script>
document.querySelector('form').onsubmit = function() {
  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.classList.add('opacity-70', 'cursor-not-allowed');
  btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">sync</span> Guardando...';
};
</script>
@endsection
