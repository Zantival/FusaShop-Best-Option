@extends('layouts.app')
@section('title','Identidad Visual, Banners & Tarjetas Promocionales | FusaShop')
@section('content')

@php
  $activeTab = request('tab', 'logo');
  if (!in_array($activeTab, ['logo', 'banners', 'promos'])) {
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-slate-100">
      
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
  <!-- ÁREA 2: ÚNICAMENTE BANNERS PUBLICITARIOS                              -->
  <!-- ====================================================================== -->
  <div id="tab-content-banners" class="space-y-6" style="display: {{ $activeTab === 'banners' ? 'block' : 'none' }};">
    
    <!-- Formulario para Subir Banner -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold font-['Manrope'] text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">add_photo_alternate</span>
            <span>Subir Nuevo Banner Publicitario</span>
          </h2>
          <p class="text-xs text-slate-500">Publica un banner en el slider principal del catálogo para compradores.</p>
        </div>
      </div>

      <form method="POST" action="{{ route('analyst.banners.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Título de la Campaña / Banner (opcional)</label>
          <input type="text" name="title" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none" placeholder="Ej: Festival del Sumapaz 2026">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">URL de Destino / Enlace (opcional)</label>
          <input type="url" name="link_url" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none" placeholder="https://fusashop.com/catalogo?category=...">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Imagen o Video Publicitario * (JPG, PNG, WEBP, MP4 - Máx 10 MB)</label>
          <input type="file" name="image" accept="image/*,video/*" required
                 class="w-full text-xs text-slate-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#006c47] file:text-white hover:file:bg-[#10B981] file:cursor-pointer bg-slate-50 rounded-xl border border-slate-200 p-1">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Orden de Prioridad (0 = primero)</label>
          <input type="number" name="sort_order" value="0" min="0" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none">
        </div>

        <div class="md:col-span-2 pt-2">
          <button type="submit" class="px-6 py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold rounded-xl shadow-md transition-all flex items-center gap-2 text-xs cursor-pointer">
            <span class="material-symbols-outlined text-base">publish</span>
            <span>Publicar Banner en Vitrina</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Lista de Banners Activos y Métricas -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-base text-[#006c47]">view_carousel</span>
          <span>Banners Publicados en el Sistema ({{ $banners->count() }})</span>
        </h3>
      </div>

      @if($banners->isEmpty())
        <div class="text-center py-12 bg-slate-50 rounded-2xl border border-slate-200 text-slate-500">
          <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">image_not_supported</span>
          <p class="font-bold text-sm">No hay banners publicados actualmente</p>
          <p class="text-xs text-slate-400 mt-0.5">Sube un banner arriba para mostrarlo en el carrusel principal.</p>
        </div>
      @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @foreach($banners as $banner)
          <div class="rounded-2xl border overflow-hidden transition-all {{ $banner->is_active ? 'border-emerald-200 bg-white shadow-2xs' : 'border-slate-200 bg-slate-50 opacity-60' }}">
            <div class="w-full h-36 bg-slate-900 flex items-center justify-center overflow-hidden relative">
              @php $isVideo = in_array(pathinfo($banner->image_path, PATHINFO_EXTENSION), ['mp4', 'webm', 'mov']); @endphp
              @if($isVideo)
                <video src="{{ asset('storage/'.$banner->image_path) }}" class="max-w-full max-h-full object-contain" autoplay loop muted playsinline></video>
              @else
                <img src="{{ asset('storage/'.$banner->image_path) }}" class="max-w-full max-h-full object-contain" alt="{{ $banner->title }}">
              @endif
              <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-black {{ $banner->is_active ? 'bg-emerald-500 text-white' : 'bg-slate-700 text-slate-300' }}">
                {{ $banner->is_active ? 'Activo' : 'Inactivo' }}
              </span>
            </div>

            <div class="p-4 space-y-2">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h4 class="font-extrabold text-slate-900 text-sm truncate max-w-[220px]">{{ $banner->title ?: 'Banner sin título' }}</h4>
                  @if($banner->link_url)
                    <a href="{{ $banner->link_url }}" target="_blank" class="text-[11px] text-[#006c47] hover:underline truncate block max-w-[220px]">
                      {{ $banner->link_url }}
                    </a>
                  @endif
                </div>
                <div class="text-right text-[11px] text-slate-500">
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

    <!-- Solicitudes de Pauta de Comerciantes -->
    @if(isset($pendingRequests) && $pendingRequests->isNotEmpty())
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-base text-amber-600">receipt_long</span>
          <span>Solicitudes de Pauta Publicitaria ($150.000 COP)</span>
        </h3>
        <span class="text-xs font-black text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-full">
          {{ $pendingRequests->where('status', 'pending')->count() }} pendientes
        </span>
      </div>

      <div class="space-y-3">
        @foreach($pendingRequests as $req)
        <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-extrabold text-slate-900 text-sm">{{ $req->user->companyProfile->company_name ?? $req->user->name }}</span>
              <span class="px-2 py-0.5 text-[10px] font-black rounded-full {{ $req->status === 'approved' ? 'bg-emerald-100 text-[#006c47]' : ($req->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800') }}">
                {{ ucfirst($req->status) }}
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">{{ $req->notes ?: 'Sin observaciones' }} · ${{ number_format($bannerFee, 0, ',', '.') }} COP</p>
          </div>

          @if($req->status === 'pending')
          <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('analyst.banner-requests.approve', $req->id) }}">
              @csrf
              <button class="px-3.5 py-1.5 bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-bold rounded-xl transition-colors cursor-pointer">
                Aprobar y Publicar
              </button>
            </form>
          </div>
          @endif
        </div>
        @endforeach
      </div>
    </div>
    @endif

  </div>

  <!-- ====================================================================== -->
  <!-- ÁREA 3: ÚNICAMENTE TARJETAS PROMOCIONALES (DESCUENTOS POR CATEGORÍA)  -->
  <!-- ====================================================================== -->
  <div id="tab-content-promos" class="space-y-6" style="display: {{ $activeTab === 'promos' ? 'block' : 'none' }};">
    
    <!-- NOTIFICACIÓN INTELIGENTE DEL SISTEMA AL ADMIN -->
    <div class="bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-900 text-white rounded-3xl p-6 shadow-md border border-emerald-800 relative overflow-hidden">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
        <div class="space-y-1.5 max-w-2xl">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-[#F97316] text-white text-[10px] font-black rounded-full uppercase tracking-wider flex items-center gap-1 shadow">
              <span class="material-symbols-outlined text-xs">notifications_active</span> Detección Automática de Ofertas
            </span>
            <span class="text-[10px] text-emerald-300 bg-emerald-950/80 px-2 py-0.5 rounded-full border border-emerald-700">
              Sistema FusaShop E-Commerce
            </span>
          </div>

          <h2 class="text-xl font-extrabold font-heading text-white">
            {{ $discountedCount > 0 ? "¡El sistema detectó {$discountedCount} productos con descuento u oferta activa!" : "Detector de Ofertas y Promociones Activo" }}
          </h2>

          <p class="text-xs text-slate-300 leading-relaxed">
            Los comerciantes de Fusagasugá registran productos con precios rebajados. Puedes agruparlos en **Tarjetas Promocionales por Categoría** para destacarlos en el catálogo principal, o crear campañas de descuento del sistema para incentivar las compras.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
          <form method="POST" action="{{ route('analyst.promotional-cards.auto-generate') }}">
            @csrf
            <button type="submit" class="w-full sm:w-auto px-4 py-2.5 bg-[#10B981] hover:bg-emerald-600 text-white font-extrabold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5 cursor-pointer">
              <span class="material-symbols-outlined text-sm">auto_awesome</span>
              <span>Auto-Generar Tarjetas</span>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- HERRAMIENTA DUAL: CREAR TARJETA MANUAL O APLICAR DESCUENTO DEL SISTEMA -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
      
      <!-- Panel A: Crear Tarjeta Promocional por Categoría -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <div class="border-b border-slate-100 pb-3">
          <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[#006c47]">add_card</span>
            <span>Nueva Tarjeta Promocional por Categoría</span>
          </h3>
          <p class="text-[11px] text-slate-500">Crea una tarjeta para destacar todos los productos de un rubro.</p>
        </div>

        <form method="POST" action="{{ route('analyst.promotional-cards.store') }}" class="space-y-3 text-xs">
          @csrf
          <div>
            <label class="block font-bold text-slate-700 mb-1">Categoría del Catálogo *</label>
            <select name="category" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none bg-white">
              @foreach($categoriesList as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Título de la Tarjeta Promocional *</label>
            <input type="text" name="title" required placeholder="Ej: Especial Tecnología — Hasta 30% OFF" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none">
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Insignia / Descuento Destacado *</label>
            <input type="text" name="discount_badge" required placeholder="Ej: 30% OFF o 2x1" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none">
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Descripción / Subtítulo</label>
            <textarea name="subtitle" rows="2" placeholder="Ej: Productos certificados de Fusagasugá con entrega express el mismo día." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-2 focus:ring-[#10B981] outline-none"></textarea>
          </div>

          <button type="submit" class="w-full py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold rounded-xl shadow transition-all flex items-center justify-center gap-1.5 cursor-pointer">
            <span class="material-symbols-outlined text-sm">publish</span>
            <span>Publicar Tarjeta Promocional</span>
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
          <p class="text-[11px] text-slate-500">Aplica un descuento general a todos los productos de un rubro y crea su tarjeta.</p>
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
            <span>Tarjetas Promocionales Vigentes en el Catálogo</span>
          </h3>
          <p class="text-xs text-slate-500">Previsualiza cómo se presentan en la vitrina de compras de Fusagasugá.</p>
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
              <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-white/20 backdrop-blur-sm border border-white/20">
                {{ $card['category'] }}
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#F97316] text-white shadow">
                {{ $card['discount_badge'] }}
              </span>
            </div>

            <h4 class="font-extrabold text-white text-base leading-snug font-heading mt-1">
              {{ $card['title'] }}
            </h4>

            <p class="text-slate-300 text-[11px] line-clamp-2 mt-1 leading-relaxed">
              {{ $card['subtitle'] }}
            </p>

            <div class="mt-3 flex items-center justify-between text-[10px] text-emerald-300">
              <span>{{ $card['products_count'] ?? 0 }} productos asociados</span>
              <span class="font-bold underline">Ver vitrina →</span>
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
          <p class="font-bold text-xs">No hay tarjetas promocionales creadas todavía</p>
        </div>
        @endforelse
      </div>
    </div>

  </div>

</div>

<!-- SCRIPT DIRECTO PARA CAMBIO DE PESTAÑAS INSTANTÁNEO Y SEGURO -->
<script>
function switchVisualTab(tabName) {
  var tabs = ['logo', 'banners', 'promos'];

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
