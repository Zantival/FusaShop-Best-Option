@extends('layouts.app')
@section('title','Identidad Visual, Banners & Tarjetas Promocionales | FusaShop')
@section('content')

@php
  $activeTab = request('tab', 'logo');
  if (!in_array($activeTab, ['logo', 'banners', 'promos', 'guide'])) {
      $activeTab = 'logo';
  }
  $globalLogoPath = collect(\Illuminate\Support\Facades\Storage::disk('public')->files('system'))
    ->first(fn ($path) => preg_match('/^system\/global_logo\.(png|jpe?g|webp)$/i', $path));
  $discountedCount = isset($discountedProducts) ? $discountedProducts->count() : 0;
@endphp

<div class="max-w-6xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">

  <!-- ====================================================================== -->
  <!-- 1. ENCABEZADO Y SELECTOR DE 3 PESTAÑAS EXCLUSIVAS                      -->
  <!-- ====================================================================== -->
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold font-['Manrope'] text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#006c47] text-2xl">palette</span>
          <span>Gestión Visual: Logo, Banners & Promociones</span>
        </h1>
        <p class="text-xs text-slate-500">
          Haz clic en cualquiera de los 3 botones para ver <strong>únicamente</strong> la información que corresponde a esa sección.
        </p>
      </div>

      <!-- Indicador de Vitrina -->
      <a href="{{ route('consumer.home') }}" target="_blank" class="self-start md:self-auto px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#006c47] bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 flex items-center gap-1.5 transition-colors shadow-2xs">
        <span class="material-symbols-outlined text-base">storefront</span>
        <span>Ver Vitrina en Vivo</span>
      </a>
    </div>

    <!-- 3 BOTONES SUPERIORES DE NAVEGACIÓN DIRECTA -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-slate-100">
      
      <!-- BOTÓN 1: LOGO -->
      <button type="button" 
              id="tab-btn-logo"
              onclick="switchVisualTab('logo')" 
              class="p-4 rounded-2xl border flex items-center justify-between text-left transition-all cursor-pointer {{ $activeTab === 'logo' ? 'bg-[#006c47] text-white shadow-md border-[#006c47]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' }}">
        <div class="flex items-center gap-2.5">
          <span id="tab-icon-wrap-logo" class="p-2 rounded-xl {{ $activeTab === 'logo' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-[#006c47]' }}">
            <span class="material-symbols-outlined text-xl">image</span>
          </span>
          <div>
            <div class="font-extrabold text-sm leading-tight">1. Logo del Sistema</div>
            <div class="text-[11px] opacity-80">Identidad institucional única</div>
          </div>
        </div>
        <span id="tab-check-logo" class="material-symbols-outlined text-base" style="display: {{ $activeTab === 'logo' ? 'inline-block' : 'none' }};">check_circle</span>
      </button>

      <!-- BOTÓN 2: BANNERS -->
      <button type="button" 
              id="tab-btn-banners"
              onclick="switchVisualTab('banners')" 
              class="p-4 rounded-2xl border flex items-center justify-between text-left transition-all cursor-pointer {{ $activeTab === 'banners' ? 'bg-[#006c47] text-white shadow-md border-[#006c47]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' }}">
        <div class="flex items-center gap-2.5">
          <span id="tab-icon-wrap-banners" class="p-2 rounded-xl {{ $activeTab === 'banners' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-[#006c47]' }}">
            <span class="material-symbols-outlined text-xl">ad_units</span>
          </span>
          <div>
            <div class="font-extrabold text-sm leading-tight">2. Banners Publicitarios</div>
            <div class="text-[11px] opacity-80">{{ $banners->count() }} banners activos/pauta</div>
          </div>
        </div>
        <span id="tab-check-banners" class="material-symbols-outlined text-base" style="display: {{ $activeTab === 'banners' ? 'inline-block' : 'none' }};">check_circle</span>
      </button>

      <!-- BOTÓN 3: TARJETAS PROMOCIONALES -->
      <button type="button" 
              id="tab-btn-promos"
              onclick="switchVisualTab('promos')" 
              class="p-4 rounded-2xl border flex items-center justify-between text-left transition-all cursor-pointer relative {{ $activeTab === 'promos' ? 'bg-[#006c47] text-white shadow-md border-[#006c47]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' }}">
        <div class="flex items-center gap-2.5">
          <span id="tab-icon-wrap-promos" class="p-2 rounded-xl {{ $activeTab === 'promos' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-[#006c47]' }}">
            <span class="material-symbols-outlined text-xl">loyalty</span>
          </span>
          <div>
            <div class="font-extrabold text-sm leading-tight">3. Tarjetas Promocionales</div>
            <div class="text-[11px] opacity-80">Descuentos por categoría</div>
          </div>
        </div>
        <div class="flex items-center gap-1">
          @if($discountedCount > 0)
            <span class="px-2 py-0.5 rounded-full text-[10px] font-black leading-none bg-[#F97316] text-white shadow-xs">
              {{ $discountedCount }} ofertas
            </span>
          @endif
          <span id="tab-check-promos" class="material-symbols-outlined text-base" style="display: {{ $activeTab === 'promos' ? 'inline-block' : 'none' }};">check_circle</span>
        </div>
      </button>

      <!-- BOTÓN 4: GUÍA Y VIDEOS -->
      <button type="button" 
              id="tab-btn-guide"
              onclick="switchVisualTab('guide')" 
              class="p-4 rounded-2xl border flex items-center justify-between text-left transition-all cursor-pointer {{ $activeTab === 'guide' ? 'bg-[#006c47] text-white shadow-md border-[#006c47]' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' }}">
        <div class="flex items-center gap-2.5">
          <span id="tab-icon-wrap-guide" class="p-2 rounded-xl {{ $activeTab === 'guide' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }}">
            <span class="material-symbols-outlined text-xl">movie_edit</span>
          </span>
          <div>
            <div class="font-extrabold text-sm leading-tight">4. Guía y Videos</div>
            <div class="text-[11px] opacity-80">«¿No sabes cómo empezar?»</div>
          </div>
        </div>
        <span id="tab-check-guide" class="material-symbols-outlined text-base" style="display: {{ $activeTab === 'guide' ? 'inline-block' : 'none' }};">check_circle</span>
      </button>

    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- ÁREA 1: ÚNICAMENTE LOGO DEL SISTEMA                                    -->
  <!-- ====================================================================== -->
  <div id="tab-content-logo" class="space-y-6" style="display: {{ $activeTab === 'logo' ? 'block' : 'none' }};">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-6">
      
      <div class="border-b border-slate-100 pb-4">
        <h2 class="text-lg font-bold font-['Manrope'] text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#006c47]">brand_awareness</span>
          <span>Actualización Exclusiva del Logo Institucional</span>
        </h2>
        <p class="text-xs text-slate-500 mt-1">
          El logo que subas aquí reemplazará automáticamente la imagen oficial en el encabezado de la tienda pública, el panel administrativo, los correos transaccionales y las facturas electrónicas Factus.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        
        <!-- Formulario de Subida -->
        <div class="space-y-4 bg-slate-50 p-5 rounded-2xl border border-slate-200">
          <h3 class="text-sm font-extrabold text-slate-800">Cargar nuevo archivo de imagen</h3>
          
          <form method="POST" action="{{ route('analyst.global.logo.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">
                Selecciona archivo (PNG transparente, WEBP o JPG - Máx 2 MB)
              </label>
              <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required
                     class="w-full text-xs text-slate-700 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#006c47] file:text-white hover:file:bg-[#10B981] file:cursor-pointer bg-white rounded-xl border border-slate-200 p-1">
              @error('logo')
                <p class="mt-1.5 text-xs font-bold text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <div class="text-[11px] text-slate-500 space-y-1">
              <p class="flex items-center gap-1 font-semibold text-slate-600">
                <span class="material-symbols-outlined text-xs text-[#006c47]">check_circle</span> Formato recomendado: PNG con fondo transparente
              </p>
              <p class="flex items-center gap-1 font-semibold text-slate-600">
                <span class="material-symbols-outlined text-xs text-[#006c47]">check_circle</span> Dimensiones ideales: 400x120 px o proporción horizontal
              </p>
            </div>

            <button type="submit" class="w-full py-3 bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 text-xs cursor-pointer">
              <span class="material-symbols-outlined text-base">cloud_upload</span>
              <span>Guardar y Actualizar Logo Global</span>
            </button>
          </form>
        </div>

        <!-- Previsualización del Logo Actual -->
        <div class="space-y-4 bg-slate-50 p-5 rounded-2xl border border-slate-200">
          <h3 class="text-sm font-extrabold text-slate-800">Previsualización del Logo en Pantalla</h3>
          
          <div class="space-y-3">
            <div>
              <span class="text-[11px] font-bold text-slate-500 block mb-1">Sobre fondo claro (Encabezado Web):</span>
              <div class="p-4 bg-white rounded-xl border border-slate-200 flex items-center justify-center min-h-[90px]">
                @if($globalLogoPath)
                  <img src="{{ route('files.serve', ['disk' => 'system', 'path' => basename($globalLogoPath)]) }}?v={{ \Illuminate\Support\Facades\Storage::disk('public')->lastModified($globalLogoPath) }}" 
                       alt="Logo actual de FusaShop" 
                       class="h-10 max-w-[220px] object-contain">
                @else
                  <x-logo size="default" :with-microcopy="false" />
                @endif
              </div>
            </div>

            <div>
              <span class="text-[11px] font-bold text-slate-500 block mb-1">Sobre fondo oscuro (Pie de Página):</span>
              <div class="p-4 bg-slate-950 rounded-xl border border-slate-900 flex items-center justify-center min-h-[90px]">
                @if($globalLogoPath)
                  <img src="{{ route('files.serve', ['disk' => 'system', 'path' => basename($globalLogoPath)]) }}?v={{ \Illuminate\Support\Facades\Storage::disk('public')->lastModified($globalLogoPath) }}" 
                       alt="Logo actual de FusaShop" 
                       class="h-10 max-w-[220px] object-contain brightness-110">
                @else
                  <x-logo size="default" :with-microcopy="false" />
                @endif
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- ====================================================================== -->
  <!-- ÁREA 2: BANNERS PUBLICITARIOS (MÁX 8 ACTIVOS: 4 ARRIBA + 4 ABAJO)      -->
  <!-- ====================================================================== -->
  <div id="tab-content-banners" class="space-y-6" style="display: {{ $activeTab === 'banners' ? 'block' : 'none' }};">

    @php
      $activeBannersTotal = $activeBannersCount ?? $banners->where('is_active', true)->count();
      $bannerRequestsList = isset($pendingRequests)
          ? $pendingRequests->filter(fn($r) => in_array($r->target_type ?? 'store', ['store', 'products'], true))
          : collect();
      $promoCardRequestsList = isset($pendingRequests)
          ? $pendingRequests->filter(fn($r) => ($r->target_type ?? '') === 'promo_card')
          : collect();
    @endphp

    <!-- Bandeja de Mensajes y Solicitudes de Banners de Comerciantes (con Foto y Enlace a la Tienda) -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
        <div>
          <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-600">mark_email_unread</span>
            <span>Mensajes y Solicitudes de Banners de Comerciantes</span>
          </h3>
          <p class="text-xs text-slate-500">Aquí llegan las fotos del banner enviadas por los comerciantes junto con el enlace directo a su tienda oficial para publicarlas.</p>
        </div>
        <span class="text-xs font-black text-amber-800 bg-amber-100 px-3 py-1 rounded-full self-start sm:self-auto">
          {{ $bannerRequestsList->where('status', 'pending')->count() }} pendientes por aprobar
        </span>
      </div>

      @if($bannerRequestsList->isEmpty())
        <div class="text-center py-8 bg-slate-50 rounded-2xl border border-slate-200/80 text-slate-500">
          <span class="material-symbols-outlined text-4xl text-slate-300 mb-1">inbox</span>
          <p class="font-bold text-xs">No hay solicitudes de banners de comerciantes registradas todavía.</p>
        </div>
      @else
        <div class="space-y-4">
          @foreach($bannerRequestsList as $req)
            @php
              $merchantStoreUrl = route('consumer.merchant.profile', $req->user_id);
              $effectiveLinkUrl = $req->target_url ?: $merchantStoreUrl;
              $reqProducts = is_array($req->selected_products) ? $req->selected_products : [];
            @endphp
            <div class="p-4 rounded-2xl border {{ $req->status === 'pending' ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200 bg-slate-50/50' }} flex flex-col lg:flex-row gap-4 justify-between">
              <div class="flex flex-col sm:flex-row gap-4 flex-1 min-w-0">
                <!-- Foto o Video del Banner enviado por el comerciante -->
                <a href="{{ route('analyst.banner-requests.show', $req->id) }}" class="w-full sm:w-52 h-28 rounded-xl bg-slate-900 overflow-hidden shrink-0 border border-slate-200 relative group block">
                  @if($req->is_video)
                    <video src="{{ $req->image_url }}" class="w-full h-full object-cover" muted playsinline></video>
                  @else
                    <img src="{{ $req->image_url }}" alt="{{ $req->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                  @endif
                  <span class="absolute bottom-1.5 right-1.5 px-2 py-0.5 bg-black/70 text-white text-[9px] font-bold rounded-md flex items-center gap-1">
                    <span class="material-symbols-outlined text-[11px]">visibility</span> Ver Arte
                  </span>
                </a>

                <div class="space-y-1.5 min-w-0 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-slate-900 text-sm">{{ $req->user->companyProfile->company_name ?? $req->user->name }}</span>
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full {{ $req->status === 'approved' ? 'bg-emerald-100 text-[#006c47]' : ($req->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800') }}">
                      {{ $req->status === 'approved' ? 'Publicado' : ($req->status === 'rejected' ? 'Rechazado' : 'Pendiente') }}
                    </span>
                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                      Pagado: ${{ number_format($req->cost ?: $bannerFee, 0, ',', '.') }} COP
                    </span>
                  </div>

                  @if($req->title)
                    <p class="text-xs font-extrabold text-slate-800">Título: "{{ $req->title }}"</p>
                  @endif

                  <p class="text-xs text-slate-600 leading-relaxed bg-white/80 p-2.5 rounded-xl border border-slate-200/70">
                    <strong class="text-slate-800">Mensaje del comerciante:</strong> {{ $req->notes ?: 'Publicar banner con enlace directo a la tienda.' }}
                  </p>

                  <!-- Enlace directo a la tienda o productos -->
                  <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px]">
                    <span class="font-bold text-slate-500 flex items-center gap-1">
                      <span class="material-symbols-outlined text-xs text-[#006c47]">storefront</span>
                      Enlace al hacer clic en el banner:
                    </span>
                    <a href="{{ $effectiveLinkUrl }}" target="_blank" class="font-mono font-bold text-[#006c47] hover:underline bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200 truncate max-w-md">
                      {{ $effectiveLinkUrl }}
                    </a>
                  </div>
                </div>
              </div>

              <div class="flex sm:flex-row lg:flex-col items-stretch sm:items-center lg:items-end justify-center gap-2 shrink-0">
                <a href="{{ route('analyst.banner-requests.show', $req->id) }}" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition-colors flex items-center justify-center gap-1.5">
                  <span class="material-symbols-outlined text-sm">open_in_new</span>
                  <span>Ver Detalle y Enlace</span>
                </a>

                @if($req->status === 'pending')
                  <form method="POST" action="{{ route('analyst.banner-requests.approve', $req->id) }}">
                    @csrf
                    <input type="hidden" name="publish_as" value="banner">
                    <input type="hidden" name="selected_target_url" value="{{ $effectiveLinkUrl }}">
                    <button type="submit" class="w-full px-4 py-2 bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-extrabold rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                      <span class="material-symbols-outlined text-sm">check_circle</span>
                      <span>Aprobar y Publicar Banner</span>
                    </button>
                  </form>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <!-- Formulario para Subir Banner Directamente -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
          <h2 class="text-lg font-bold font-['Manrope'] text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">add_photo_alternate</span>
            <span>Subir Nuevo Banner Publicitario</span>
          </h2>
          <p class="text-xs text-slate-500">Se permiten máximo <strong>8 banners activos</strong> en la vitrina (los primeros 4 se muestran en el espacio de arriba y los otros 4 en el espacio de abajo).</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-extrabold {{ $activeBannersTotal >= 8 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-[#006c47]' }}">
          {{ $activeBannersTotal }} / 8 Banners Activos
        </span>
      </div>

      <form method="POST" action="{{ route('analyst.banners.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Título de la Campaña / Tienda (opcional)</label>
          <input type="text" name="title" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none" placeholder="Ej: Tienda Oficial Café del Sumapaz">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Enlace Directo a la Tienda o Promoción (URL)</label>
          <input type="url" name="link_url" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none" placeholder="{{ url('/tienda/1') }}">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Imagen o Video del Banner * (JPG, PNG, WEBP, MP4 - Máx 10 MB)</label>
          <input type="file" name="image" accept="image/*,video/*" required
                 class="w-full text-xs text-slate-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#006c47] file:text-white hover:file:bg-[#10B981] file:cursor-pointer bg-slate-50 rounded-xl border border-slate-200 p-1">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Orden de Posición (1-4: Espacio Arriba · 5-8: Espacio Abajo)</label>
          <input type="number" name="sort_order" value="{{ $banners->count() + 1 }}" min="0" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none">
        </div>

        <div class="md:col-span-2 pt-2">
          <button type="submit" class="px-6 py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold rounded-xl shadow-md transition-all flex items-center gap-2 text-xs cursor-pointer">
            <span class="material-symbols-outlined text-base">publish</span>
            <span>Publicar Banner en Vitrina</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Lista de Banners Activos y Distribución (4 Arriba + 4 Abajo) -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
        <div>
          <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-base text-[#006c47]">view_carousel</span>
            <span>Banners en Vitrina ({{ $activeBannersTotal }} de 8 activos)</span>
          </h3>
          <p class="text-xs text-slate-500">Si no hay banners activos para un espacio (arriba o abajo), ese espacio se oculta automáticamente en la tienda.</p>
        </div>
      </div>

      @if($banners->isEmpty())
        <div class="text-center py-12 bg-slate-50 rounded-2xl border border-slate-200 text-slate-500">
          <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">image_not_supported</span>
          <p class="font-bold text-sm">No hay banners activos publicados actualmente</p>
          <p class="text-xs text-slate-400 mt-0.5">Ambos espacios de banner en la vitrina están ocultos mientras no existan banners activos.</p>
        </div>
      @else
        @php $activePositionCounter = 0; @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @foreach($banners as $banner)
            @php
              if ($banner->is_active) {
                  $activePositionCounter++;
              }
              $slotLabel = !$banner->is_active
                  ? 'Pausado'
                  : ($activePositionCounter <= 4
                      ? "Espacio Superior (Slide #{$activePositionCounter})"
                      : ($activePositionCounter <= 8 ? "Espacio Inferior (Slide #" . ($activePositionCounter - 4) . ")" : 'En cola (>8)'));
            @endphp
            <div class="rounded-2xl border overflow-hidden transition-all {{ $banner->is_active ? 'border-emerald-200 bg-white shadow-2xs' : 'border-slate-200 bg-slate-50 opacity-60' }}">
              <div class="w-full h-36 bg-slate-900 flex items-center justify-center overflow-hidden relative">
                @php $isVideo = in_array(pathinfo($banner->image_path, PATHINFO_EXTENSION), ['mp4', 'webm', 'mov']); @endphp
                @if($isVideo)
                  <video src="{{ $banner->image_url }}" class="max-w-full max-h-full object-contain" autoplay loop muted playsinline></video>
                @else
                  <img src="{{ $banner->image_url }}" class="max-w-full max-h-full object-contain" alt="{{ $banner->title }}">
                @endif
                <span class="absolute top-2 left-2 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-900/80 text-emerald-300 border border-emerald-700/50">
                  {{ $slotLabel }}
                </span>
                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-black {{ $banner->is_active ? 'bg-emerald-500 text-white' : 'bg-slate-700 text-slate-300' }}">
                  {{ $banner->is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </div>

              <div class="p-4 space-y-2">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <h4 class="font-extrabold text-slate-900 text-sm truncate">{{ $banner->title ?: 'Banner sin título' }}</h4>
                    @if($banner->link_url)
                      <a href="{{ $banner->link_url }}" target="_blank" class="text-[11px] text-[#006c47] hover:underline truncate flex items-center gap-1 mt-0.5">
                        <span class="material-symbols-outlined text-xs">link</span>
                        <span class="truncate">{{ $banner->link_url }}</span>
                      </a>
                    @endif
                  </div>
                  <div class="text-right text-[11px] text-slate-500 shrink-0">
                    <span>Orden: #{{ $banner->sort_order }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                  <form method="POST" action="{{ route('analyst.banners.toggle', $banner) }}">
                    @csrf @method('PATCH')
                    <button class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors cursor-pointer {{ $banner->is_active ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-100 hover:bg-emerald-200 text-[#006c47]' }}">
                      {{ $banner->is_active ? 'Pausar' : 'Activar' }}
                    </button>
                  </form>

                  <form method="POST" action="{{ route('analyst.banners.delete', $banner) }}" onsubmit="return confirm('¿Eliminar este banner?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1.5 text-xs font-bold rounded-lg bg-red-50 hover:bg-red-100 text-red-700 transition-colors cursor-pointer">
                      Eliminar
                    </button>
                  </form>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

  </div>

  <!-- ====================================================================== -->
  <!-- ÁREA 3: TARJETAS PROMOCIONALES ÚNICAS Y POR CATEGORÍA                  -->
  <!-- ====================================================================== -->
  <div id="tab-content-promos" class="space-y-6" style="display: {{ $activeTab === 'promos' ? 'block' : 'none' }};">
    
    <!-- SOLICITUDES PAGADAS DE TARJETAS PROMOCIONALES ÚNICAS DE COMERCIANTES -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-200 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
        <div>
          <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#F97316]">loyalty</span>
            <span>Solicitudes de Tarjetas Promocionales Únicas (Pagadas por Comerciantes)</span>
          </h3>
          <p class="text-xs text-slate-500">El comerciante envía la foto del producto(s) y los productos que están en descuento para que los vincules y el consumidor al darle clic vaya directamente a esos productos.</p>
        </div>
        <span class="text-xs font-black text-orange-800 bg-orange-100 px-3 py-1 rounded-full self-start sm:self-auto">
          {{ $promoCardRequestsList->where('status', 'pending')->count() }} pendientes por vincular
        </span>
      </div>

      @if($promoCardRequestsList->isEmpty())
        <div class="text-center py-6 bg-slate-50 rounded-2xl border border-slate-200/80 text-slate-500">
          <span class="material-symbols-outlined text-3xl text-slate-300 mb-1">sell</span>
          <p class="font-bold text-xs">No hay solicitudes pendientes de tarjetas promocionales únicas de comerciantes.</p>
        </div>
      @else
        <div class="space-y-4">
          @foreach($promoCardRequestsList as $req)
            @php
              $reqProducts = is_array($req->selected_products) ? $req->selected_products : [];
            @endphp
            <div class="p-4 rounded-2xl border {{ $req->status === 'pending' ? 'border-orange-200 bg-orange-50/20' : 'border-slate-200 bg-slate-50/50' }} flex flex-col lg:flex-row gap-4 justify-between">
              <div class="flex flex-col sm:flex-row gap-4 flex-1 min-w-0">
                <!-- Foto del Producto enviada por el comerciante -->
                <a href="{{ route('analyst.banner-requests.show', $req->id) }}" class="w-full sm:w-40 h-32 rounded-xl bg-slate-900 overflow-hidden shrink-0 border border-slate-200 relative group block">
                  <img src="{{ $req->image_url }}" alt="{{ $req->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                  <span class="absolute bottom-1.5 right-1.5 px-2 py-0.5 bg-black/70 text-white text-[9px] font-bold rounded-md">
                    Foto Producto
                  </span>
                </a>

                <div class="space-y-2 min-w-0 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-extrabold text-slate-900 text-sm">{{ $req->user->companyProfile->company_name ?? $req->user->name }}</span>
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full {{ $req->status === 'approved' ? 'bg-emerald-100 text-[#006c47]' : ($req->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-800') }}">
                      {{ $req->status === 'approved' ? 'Vinculada y Activa' : ($req->status === 'rejected' ? 'Rechazada' : 'Pendiente de Vincular') }}
                    </span>
                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                      Pagado: ${{ number_format($req->cost ?: $bannerFee, 0, ',', '.') }} COP
                    </span>
                  </div>

                  @if($req->title)
                    <p class="text-xs font-extrabold text-slate-800">Título sugerido: "{{ $req->title }}"</p>
                  @endif

                  <p class="text-xs text-slate-600">
                    <strong class="text-slate-800">Mensaje:</strong> {{ $req->notes }}
                  </p>

                  <!-- Productos en descuento enviados por el comerciante -->
                  @if(!empty($reqProducts))
                    <div class="pt-1">
                      <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-1">Productos en descuento enviados para vincular ({{ count($reqProducts) }}):</span>
                      <div class="flex flex-wrap gap-1.5">
                        @foreach($reqProducts as $prodItem)
                          <a href="{{ $prodItem['url'] ?? route('consumer.product', $prodItem['id']) }}" target="_blank"
                             class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-orange-200 text-[11px] font-bold text-slate-800 hover:border-emerald-500 transition-colors">
                            <span class="material-symbols-outlined text-xs text-orange-600">sell</span>
                            <span>{{ $prodItem['name'] }}</span>
                            @if(!empty($prodItem['discount_percent']))
                              <span class="px-1.5 py-0.2 bg-rose-100 text-rose-700 rounded text-[9px] font-black">-{{ $prodItem['discount_percent'] }}% OFF</span>
                            @endif
                            <span class="text-emerald-700 font-extrabold">${{ number_format($prodItem['price'] ?? 0, 0, ',', '.') }}</span>
                          </a>
                        @endforeach
                      </div>
                    </div>
                  @endif
                </div>
              </div>

              <div class="flex sm:flex-row lg:flex-col items-stretch sm:items-center lg:items-end justify-center gap-2 shrink-0">
                <a href="{{ route('analyst.banner-requests.show', $req->id) }}" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition-colors flex items-center justify-center gap-1.5">
                  <span class="material-symbols-outlined text-sm">link</span>
                  <span>Revisar / Vincular Productos</span>
                </a>

                @if($req->status === 'pending')
                  <form method="POST" action="{{ route('analyst.banner-requests.approve', $req->id) }}">
                    @csrf
                    <input type="hidden" name="publish_as" value="promo_card">
                    <button type="submit" class="w-full px-4 py-2 bg-[#F97316] hover:bg-orange-600 text-white text-xs font-extrabold rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                      <span class="material-symbols-outlined text-sm">check_circle</span>
                      <span>Vincular y Publicar Tarjeta</span>
                    </button>
                  </form>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <!-- CREAR TARJETA PROMOCIONAL VINCULADA A PRODUCTOS EN DESCUENTO O APLICAR CAMPAÑA -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
      
      <!-- Panel A: Crear Tarjeta Promocional Única o por Categoría vinculando productos -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <div class="border-b border-slate-100 pb-3">
          <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[#006c47]">add_card</span>
            <span>Crear y Vincular Tarjeta Promocional</span>
          </h3>
          <p class="text-[11px] text-slate-500">Sube la foto del producto, selecciona los productos en descuento a vincular y publícala en la vitrina.</p>
        </div>

        <form method="POST" action="{{ route('analyst.promotional-cards.store') }}" enctype="multipart/form-data" class="space-y-3 text-xs">
          @csrf
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Categoría / Etiqueta *</label>
              <select name="category" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none bg-white">
                @foreach($categoriesList as $cat)
                  <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Insignia de Descuento *</label>
              <input type="text" name="discount_badge" required placeholder="Ej: -30% OFF" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none">
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Título de la Tarjeta Promocional *</label>
            <input type="text" name="title" required placeholder="Ej: Oferta Especial en Café & Orquídeas" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none">
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Foto del Producto o Promoción (Opcional)</label>
            <input type="file" name="image" accept="image/*"
                   class="w-full text-xs text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#006c47] file:text-white hover:file:bg-[#10B981] bg-slate-50 rounded-xl border border-slate-200 p-1">
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Vincular Productos en Descuento (Al hacer clic llevará a estos productos)</label>
            <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-2 bg-slate-50 space-y-1.5">
              @forelse($discountedProducts as $dp)
                <label class="flex items-center justify-between gap-2 p-1.5 rounded-lg bg-white border border-slate-100 cursor-pointer hover:border-emerald-300">
                  <div class="flex items-center gap-2 min-w-0">
                    <input type="checkbox" name="product_ids[]" value="{{ $dp->id }}" class="rounded text-emerald-600">
                    <span class="font-bold text-slate-800 truncate">{{ $dp->name }}</span>
                    @if($dp->hasDiscount())
                      <span class="px-1.5 py-0.2 bg-rose-100 text-rose-700 rounded text-[9px] font-black">-{{ $dp->discount_percent }}%</span>
                    @endif
                  </div>
                  <span class="text-[10px] font-extrabold text-emerald-700 shrink-0">${{ number_format($dp->effectivePrice(), 0, ',', '.') }}</span>
                </label>
              @empty
                <p class="text-[11px] text-slate-400 py-2 text-center">No hay productos con % de descuento activo en este momento.</p>
              @endforelse
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Descripción / Subtítulo</label>
            <textarea name="subtitle" rows="2" placeholder="Ej: Haz clic para ver los productos seleccionados con descuento directo." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none"></textarea>
          </div>

          <button type="submit" class="w-full py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold rounded-xl shadow transition-all flex items-center justify-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm">publish</span>
            <span>Vincular y Publicar Tarjeta Promocional</span>
          </button>
        </form>
      </div>

      <!-- Panel B: Aplicar Descuento de Campaña por Categoría (Rebajas Automáticas) -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <div class="border-b border-slate-100 pb-3">
          <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-amber-600">percent</span>
            <span>Aplicar Campaña de Descuento a Categoría</span>
          </h3>
          <p class="text-[11px] text-slate-500">Aplica un porcentaje de descuento a todos los productos de un rubro (conservando su precio real) y crea su tarjeta.</p>
        </div>

        <form method="POST" action="{{ route('analyst.promotional-cards.apply-discount') }}" onsubmit="return confirm('¿Aplicar descuento general y generar la tarjeta promocional?')" class="space-y-3 text-xs">
          @csrf
          <div>
            <label class="block font-bold text-slate-700 mb-1">Categoría a Descontar *</label>
            <select name="category" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none bg-white">
              @foreach($categoriesList as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Porcentaje de Descuento de Campaña (%) *</label>
            <div class="grid grid-cols-4 gap-2">
              <label class="p-2 border border-slate-200 rounded-xl text-center cursor-pointer hover:border-[#10B981]">
                <input type="radio" name="discount_pct" value="10" checked class="sr-only">
                <span class="font-extrabold block text-slate-800">10% OFF</span>
              </label>
              <label class="p-2 border border-slate-200 rounded-xl text-center cursor-pointer hover:border-[#10B981]">
                <input type="radio" name="discount_pct" value="15" class="sr-only">
                <span class="font-extrabold block text-slate-800">15% OFF</span>
              </label>
              <label class="p-2 border border-slate-200 rounded-xl text-center cursor-pointer hover:border-[#10B981]">
                <input type="radio" name="discount_pct" value="20" class="sr-only">
                <span class="font-extrabold block text-slate-800">20% OFF</span>
              </label>
              <label class="p-2 border border-slate-200 rounded-xl text-center cursor-pointer hover:border-[#10B981]">
                <input type="radio" name="discount_pct" value="30" class="sr-only">
                <span class="font-extrabold block text-slate-800">30% OFF</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Título Personalizado (opcional)</label>
            <input type="text" name="title" placeholder="Ej: Mega Promoción Sumapaz" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none">
          </div>

          <button type="submit" class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-xl shadow transition-all flex items-center justify-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm">sell</span>
            <span>Aplicar Rebaja y Publicar Tarjeta</span>
          </button>
        </form>
      </div>

    </div>

    <!-- REJILLA DE TARJETAS PROMOCIONALES VIGENTES -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div>
          <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[#006c47]">grid_view</span>
            <span>Tarjetas Promocionales en la Vitrina</span>
          </h3>
          <p class="text-xs text-slate-500">Si no hay tarjetas promocionales activas, la sección se oculta automáticamente en la página principal.</p>
        </div>
        <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full">
          {{ $activeCardsCount }} activas
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($promotionalCards as $card)
        <div class="rounded-2xl border overflow-hidden flex flex-col justify-between transition-all {{ !empty($card['is_active']) ? 'border-emerald-300 bg-white shadow-sm' : 'border-slate-200 bg-slate-50 opacity-60' }}">
          
          <!-- Previsualización de la Tarjeta Promocional -->
          <div class="p-5 bg-gradient-to-br {{ $card['bg_gradient'] ?? 'from-emerald-950 via-slate-900 to-emerald-900' }} text-white relative overflow-hidden">
            <div class="flex items-center justify-between gap-2 mb-2">
              <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-white/20 backdrop-blur-sm border border-white/20 truncate">
                {{ $card['merchant_name'] ?? ($card['category'] ?? 'Promoción') }}
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#F97316] text-white shadow shrink-0">
                {{ $card['discount_badge'] ?? 'OFERTA' }}
              </span>
            </div>

            <div class="flex items-start gap-3 mt-1">
              @if(!empty($card['image_url']))
                <img src="{{ $card['image_url'] }}" alt="{{ $card['title'] }}" class="w-14 h-14 rounded-xl object-cover border border-white/20 shrink-0 bg-white">
              @endif
              <div class="min-w-0">
                <h4 class="font-extrabold text-white text-base leading-snug font-heading line-clamp-2">
                  {{ $card['title'] }}
                </h4>
                <p class="text-slate-300 text-[11px] line-clamp-2 mt-1 leading-relaxed">
                  {{ $card['subtitle'] }}
                </p>
              </div>
            </div>

            @if(!empty($card['products']) && is_array($card['products']))
              <div class="mt-3 flex flex-wrap gap-1">
                @foreach(array_slice($card['products'], 0, 3) as $cp)
                  <span class="px-2 py-0.5 rounded bg-black/40 text-[9px] font-bold text-emerald-200">
                    {{ $cp['name'] ?? 'Producto' }}
                    @if(!empty($cp['discount_percent'])) (-{{ $cp['discount_percent'] }}%) @endif
                  </span>
                @endforeach
              </div>
            @endif

            <div class="mt-3 flex items-center justify-between text-[10px] text-emerald-300">
              <span>{{ $card['products_count'] ?? 0 }} producto(s) vinculado(s)</span>
              <a href="{{ $card['target_url'] ?? route('consumer.catalog', ['category' => $card['category'] ?? '']) }}" target="_blank" class="font-bold underline">Probar enlace →</a>
            </div>
          </div>

          <!-- Acciones de la Tarjeta -->
          <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full {{ !empty($card['is_active']) ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
              <span class="font-bold text-slate-700">{{ !empty($card['is_active']) ? 'Visible en Tienda' : 'Pausada' }}</span>
            </div>

            <div class="flex items-center gap-1.5">
              <form method="POST" action="{{ route('analyst.promotional-cards.toggle', $card['id']) }}">
                @csrf @method('PATCH')
                <button type="submit" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border border-slate-200 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer">
                  {{ !empty($card['is_active']) ? 'Pausar' : 'Activar' }}
                </button>
              </form>

              <form method="POST" action="{{ route('analyst.promotional-cards.delete', $card['id']) }}" onsubmit="return confirm('¿Eliminar esta tarjeta promocional?')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Eliminar tarjeta">
                  <span class="material-symbols-outlined text-base">delete</span>
                </button>
              </form>
            </div>
          </div>

        </div>
        @empty
        <div class="col-span-3 text-center py-10 bg-slate-50 rounded-2xl border border-slate-200 text-slate-500">
          <span class="material-symbols-outlined text-4xl text-slate-300 mb-1">loyalty</span>
          <p class="font-bold text-xs">No hay tarjetas promocionales activas — el espacio en la vitrina principal permanecerá oculto.</p>
        </div>
        @endforelse
      </div>
    </div>

  </div>

</div>

<!-- SCRIPT DIRECTO PARA CAMBIO DE PESTAÑAS INSTANTÁNEO Y SEGURO -->
  <!-- ====================================================================== -->
  <!-- ÁREA 4: GUÍA DE INICIO Y VIDEOS EXPLICATIVOS («¿NO SABES CÓMO EMPEZAR?») -->
  <!-- ====================================================================== -->
  <div id="tab-content-guide" class="space-y-6" style="display: {{ $activeTab === 'guide' ? 'block' : 'none' }};">
    @php
      $adminGuideData = \App\Models\Setting::howToStartGuide();
      $previewBuyerEmbed = \App\Models\Setting::formatVideoEmbedUrl($adminGuideData['buyer_video_url'] ?? null);
      $previewSellerEmbed = \App\Models\Setting::formatVideoEmbedUrl($adminGuideData['seller_video_url'] ?? null);
    @endphp

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-6">
      <div class="border-b border-slate-100 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-bold font-['Manrope'] text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">movie_edit</span>
            <span>Edición de la Guía «¿No sabes cómo empezar?» y Videos Tutoriales</span>
          </h2>
          <p class="text-xs text-slate-500 mt-1">
            Esta información se muestra en la vitrina principal para los visitantes antes de registrarse o iniciar sesión. Puedes cambiar los textos, los pasos explicativos, los beneficios de verificación y los enlaces de video (YouTube/Vimeo).
          </p>
        </div>
        <a href="{{ route('consumer.home') }}#tarjeta-como-empezar" target="_blank" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#006c47] bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 inline-flex items-center gap-1.5 self-start md:self-auto">
          <span class="material-symbols-outlined text-sm">visibility</span> Ver Tarjeta en la Tienda
        </a>
      </div>

      <form action="{{ route('analyst.how-to-start.update') }}" method="POST" class="space-y-6 text-xs">
        @csrf

        <!-- Bloque 1: Tarjeta en la Vitrina Principal -->
        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-black text-slate-900 text-sm flex items-center gap-2 text-[#006c47]">
              <span class="material-symbols-outlined text-base">style</span> 1. Tarjeta en la Vitrina Principal (Home)
            </h3>
            <span class="text-[10px] bg-emerald-100 text-[#006c47] font-extrabold px-2 py-0.5 rounded-full">Visible para visitantes</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Badge Superior</label>
              <input type="text" name="card_badge" value="{{ $adminGuideData['card_badge'] ?? '¿Nuevo en FusaShop?' }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-800">
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Texto del Botón</label>
              <input type="text" name="card_button_text" value="{{ $adminGuideData['card_button_text'] ?? 'Comenzar Aquí' }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-800">
            </div>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Título de la Tarjeta</label>
            <input type="text" name="card_title" value="{{ $adminGuideData['card_title'] ?? '¿No sabes cómo empezar?' }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-black text-sm text-slate-900">
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Subtítulo / Mensaje de Invitación</label>
            <textarea name="card_subtitle" rows="2" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-medium text-xs text-slate-800 resize-none">{{ $adminGuideData['card_subtitle'] ?? '' }}</textarea>
          </div>
        </div>

        <!-- Bloque 2: Apartado Compradores & Video -->
        <div class="p-5 bg-emerald-50/60 rounded-2xl border border-emerald-200 space-y-4">
          <h3 class="font-black text-emerald-950 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-[#006c47]">shopping_cart</span> 2. Apartado «¿Cómo Comprar?» y Video Tutorial
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Título de la Sección Comprador</label>
              <input type="text" name="buyer_title" value="{{ $adminGuideData['buyer_title'] ?? '¿Cómo Comprar en FusaShop?' }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-bold text-xs">
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">URL de Video Tutorial (YouTube / Vimeo / MP4)</label>
              <input type="text" name="buyer_video_url" value="{{ $adminGuideData['buyer_video_url'] ?? '' }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3.5 py-2.5 bg-white border border-emerald-300 rounded-xl font-semibold text-xs text-emerald-900">
              <span class="text-[10px] text-slate-500 mt-0.5 block">Pega el link de YouTube o Vimeo; el sistema lo incrusta automáticamente.</span>
            </div>
          </div>

          @if(!empty($previewBuyerEmbed))
            <div class="p-3 bg-white rounded-xl border border-emerald-200">
              <span class="text-[10px] font-bold text-emerald-800 block mb-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">visibility</span> Vista Previa del Video de Compradores:
              </span>
              <div class="aspect-video max-w-sm rounded-lg overflow-hidden bg-black shadow-sm">
                <iframe src="{{ $previewBuyerEmbed }}" title="Preview Comprador" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
              </div>
            </div>
          @endif

          <div>
            <label class="font-bold text-slate-700 block mb-1">Descripción Breve</label>
            <textarea name="buyer_description" rows="2" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-medium text-xs resize-none">{{ $adminGuideData['buyer_description'] ?? '' }}</textarea>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Pasos de Compra (Línea por línea)</label>
            <textarea name="buyer_steps" rows="4" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-medium text-xs">{{ $adminGuideData['buyer_steps'] ?? '' }}</textarea>
          </div>
        </div>

        <!-- Bloque 3: Apartado Vendedores, Formalización, Verificación & Video -->
        <div class="p-5 bg-orange-50/60 rounded-2xl border border-orange-200 space-y-4">
          <h3 class="font-black text-orange-950 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-[#F97316]">storefront</span> 3. Apartado «¿Cómo Vender, Formalizarse y Certificarse?»
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Título de la Sección Vendedor</label>
              <input type="text" name="seller_title" value="{{ $adminGuideData['seller_title'] ?? '¿Cómo Vender y Formalizar tu Negocio?' }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-bold text-xs">
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">URL de Video Tutorial (YouTube / Vimeo / MP4)</label>
              <input type="text" name="seller_video_url" value="{{ $adminGuideData['seller_video_url'] ?? '' }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3.5 py-2.5 bg-white border border-orange-300 rounded-xl font-semibold text-xs text-orange-950">
              <span class="text-[10px] text-slate-500 mt-0.5 block">Pega el link de YouTube o Vimeo de formalización comercial.</span>
            </div>
          </div>

          @if(!empty($previewSellerEmbed))
            <div class="p-3 bg-white rounded-xl border border-orange-200">
              <span class="text-[10px] font-bold text-orange-900 block mb-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">visibility</span> Vista Previa del Video de Vendedores:
              </span>
              <div class="aspect-video max-w-sm rounded-lg overflow-hidden bg-black shadow-sm">
                <iframe src="{{ $previewSellerEmbed }}" title="Preview Vendedor" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
              </div>
            </div>
          @endif

          <div>
            <label class="font-bold text-slate-700 block mb-1">Descripción Breve</label>
            <textarea name="seller_description" rows="2" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-medium text-xs resize-none">{{ $adminGuideData['seller_description'] ?? '' }}</textarea>
          </div>

          <!-- Cuadro de Explicación de Verificación -->
          <div class="p-4 bg-amber-100/70 border border-amber-300 rounded-2xl space-y-1.5">
            <label class="font-black text-amber-950 block text-xs flex items-center gap-1.5">
              <span class="material-symbols-outlined text-sm text-amber-600">verified</span>
              <span>⭐ ¿Por qué es necesario verificar tu negocio y en qué te ayuda? (Sección Clave)</span>
            </label>
            <textarea name="seller_why_verify" rows="3" class="w-full px-3.5 py-2 bg-white border border-amber-300 rounded-xl font-medium text-xs text-slate-800 leading-relaxed">{{ $adminGuideData['seller_why_verify'] ?? '' }}</textarea>
            <p class="text-[10px] text-amber-900">
              Explica a los emprendedores por qué deben certificar su RUT y Cámara de Comercio para ganar el chulito de verificado ✅ y vender más.
            </p>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Pasos de Formalización Comercial</label>
            <textarea name="seller_steps" rows="4" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-medium text-xs">{{ $adminGuideData['seller_steps'] ?? '' }}</textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Enlace Oficial DIAN (RUT)</label>
              <input type="text" name="dian_url" value="{{ $adminGuideData['dian_url'] ?? 'https://www.dian.gov.co' }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-semibold text-xs">
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Enlace Oficial RUES / Cámara de Comercio</label>
              <input type="text" name="rues_url" value="{{ $adminGuideData['rues_url'] ?? 'https://www.rues.org.co' }}" class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl font-semibold text-xs">
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
          <button type="submit" class="px-6 py-3 bg-[#006c47] hover:bg-[#10B981] text-white rounded-xl font-black text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-base">save</span>
            <span>Guardar y Publicar en la Vitrina</span>
          </button>
        </div>
      </form>
    </div>
  </div>

<script>
function switchVisualTab(tabName) {
  var tabs = ['logo', 'banners', 'promos', 'guide'];

  // 1. Alternar visualización exclusiva de los 3 paneles
  tabs.forEach(function(t) {
    var panel = document.getElementById('tab-content-' + t);
    if (panel) {
      panel.style.display = (t === tabName) ? 'block' : 'none';
    }

    var btn = document.getElementById('tab-btn-' + t);
    var check = document.getElementById('tab-check-' + t);
    var iconWrap = document.getElementById('tab-icon-wrap-' + t);
    var isSelected = (t === tabName);

    if (btn) {
      if (isSelected) {
        btn.classList.remove('bg-slate-50', 'hover:bg-slate-100', 'text-slate-700', 'border-slate-200');
        btn.classList.add('bg-[#006c47]', 'text-white', 'shadow-md', 'border-[#006c47]');
      } else {
        btn.classList.remove('bg-[#006c47]', 'text-white', 'shadow-md', 'border-[#006c47]');
        btn.classList.add('bg-slate-50', 'hover:bg-slate-100', 'text-slate-700', 'border-slate-200');
      }
    }

    if (check) {
      check.style.display = isSelected ? 'inline-block' : 'none';
    }

    if (iconWrap) {
      if (isSelected) {
        iconWrap.className = 'p-2 rounded-xl bg-white/20 text-white';
      } else {
        iconWrap.className = 'p-2 rounded-xl bg-emerald-100 text-[#006c47]';
      }
    }
  });

  // 2. Persistir parámetro en la URL sin recargar
  if (window.history && window.history.replaceState) {
    var url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
  }
}
</script>
@endsection
