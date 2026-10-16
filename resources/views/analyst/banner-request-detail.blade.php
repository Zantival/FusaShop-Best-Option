@extends('layouts.app')
@section('title', 'Detalle de Solicitud de Banner')
@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
  <div class="flex items-center gap-3 mb-8">
    <a href="{{ route('notifications.index') }}" class="text-on-surface-variant hover:text-primary transition-colors flex items-center justify-center p-2 rounded-full hover:bg-primary/10">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="text-3xl font-['Manrope'] font-bold text-on-background flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-3xl">image</span> Solicitud de Banner
    </h1>
  </div>

  <div class="bg-surface-container-lowest rounded-2xl shadow-card overflow-hidden">
    <div class="p-6 border-b border-outline-variant/20 flex items-start justify-between">
      <div>
        <h2 class="text-xl font-bold text-on-background mb-1">Comerciante: {{ $requestInfo->user->name ?? 'Desconocido' }}</h2>
        <p class="text-sm text-on-surface-variant">ID de Solicitud: #{{ $requestInfo->id }} &bull; Fecha: {{ $requestInfo->created_at->format('d M Y, h:i A') }}</p>
      </div>
      <div>
        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $requestInfo->status === 'pending' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
          {{ strtoupper($requestInfo->status) }}
        </span>
      </div>
    </div>
    
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
      <div>
        <h3 class="text-sm font-semibold text-on-surface-variant mb-3 uppercase tracking-wide">Propuesta Publicitaria</h3>
        @if($requestInfo->image_path)
          @php $isRequestVideo = in_array(pathinfo($requestInfo->image_path, PATHINFO_EXTENSION), ['mp4', 'webm', 'mov']); @endphp
          @if($isRequestVideo)
            <div class="border border-outline-variant/30 rounded-2xl overflow-hidden bg-black aspect-video flex items-center justify-center">
              <video src="{{ Storage::url($requestInfo->image_path) }}" class="w-full h-full object-contain" controls autoplay loop muted></video>
            </div>
          @else
            <a href="{{ Storage::url($requestInfo->image_path) }}" target="_blank" class="block border border-outline-variant/30 rounded-xl overflow-hidden hover:opacity-90 transition-opacity">
              <img src="{{ Storage::url($requestInfo->image_path) }}" class="w-full object-cover bg-surface-container-low" alt="Banner Solicitado">
            </a>
          @endif
        @else
          <p class="text-sm text-on-surface-variant italic">No se adjuntó propuesta.</p>
        @endif
      </div>

      <div class="space-y-6">
        <div>
          <h3 class="text-sm font-semibold text-on-surface-variant mb-2 uppercase tracking-wide">Estado del Pago</h3>
          <div class="flex items-center gap-2 p-3 bg-primary/10 text-primary rounded-xl font-bold">
            <span class="material-symbols-outlined">check_circle</span>
            Pago Procesado en Línea
          </div>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-on-surface-variant mb-2 uppercase tracking-wide">Notas del Comerciante</h3>
          <div class="p-4 bg-surface-container-low rounded-xl text-sm text-on-surface">
            {{ $requestInfo->notes ?: 'Sin notas adicionales.' }}
          </div>
        </div>

        <div class="pt-4">
          <form method="POST" action="{{ route('analyst.banner-requests.approve', $requestInfo->id) }}" class="space-y-6">
            @csrf
            @if($requestInfo->status === 'pending')
              <div class="bg-surface-container-low p-5 rounded-2xl border border-outline-variant/30 space-y-4" x-data="{ linkType: 'profile' }">
                <h4 class="text-sm font-bold text-on-background flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-primary text-[18px]">link</span>
                  Destino de Redirección del Banner
                </h4>
                
                <div class="flex flex-col gap-2.5">
                  <label class="inline-flex items-center gap-2 text-sm font-semibold text-on-surface cursor-pointer">
                    <input type="radio" name="link_type" value="profile" x-model="linkType" checked 
                           class="text-primary focus:ring-primary h-4 w-4 border-outline-variant">
                    <span>Perfil de la Empresa ({{ $requestInfo->user->companyProfile->company_name ?? 'Comerciante' }})</span>
                  </label>
                  
                  <label class="inline-flex items-center gap-2 text-sm font-semibold text-on-surface cursor-pointer">
                    <input type="radio" name="link_type" value="custom" x-model="linkType" 
                           class="text-primary focus:ring-primary h-4 w-4 border-outline-variant">
                    <span>Enlace Personalizado (Producto, Promoción o Publicidad)</span>
                  </label>
                </div>

                <!-- Info Perfil -->
                <div x-show="linkType === 'profile'" x-transition class="text-xs text-on-surface-variant bg-surface-container/60 p-3 rounded-xl border border-outline-variant/20 leading-relaxed">
                  El banner redirigirá automáticamente a la página de perfil público de la empresa en Fusashop.
                </div>

                <!-- Enlace personalizado input -->
                <div x-show="linkType === 'custom'" x-cloak x-transition class="space-y-1">
                  <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">URL de Destino *</label>
                  <input type="url" name="custom_link_url" placeholder="https://fusashop.com/shop/product/..."
                         class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/40 focus:outline-none transition-all">
                  <p class="text-[11px] text-on-surface-variant">Ingresa el enlace directo del producto en promoción o la landing page de la campaña publicitaria.</p>
                </div>
              </div>

              <div class="pt-2 flex gap-4">
                <button type="submit" class="w-full md:w-auto px-6 py-3 bg-primary text-white font-black rounded-xl shadow-md hover:opacity-90 active:scale-95 transition-all flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-[18px]">verified</span> 
                  Aprobar y Publicar Banner
                </button>
              </div>
            @else
              <div class="pt-2">
                <button type="button" disabled class="w-full md:w-auto px-6 py-3 bg-surface-container-high text-on-surface-variant font-bold rounded-xl shadow-sm opacity-80 cursor-not-allowed border border-outline-variant/30 flex items-center justify-center gap-2">
                  <span class="material-symbols-outlined text-[18px]">block</span> 
                  Banner ya procesado
                </button>
              </div>
            @endif
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
