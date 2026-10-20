@extends('layouts.app')
@section('title','Vitrina Principal — Sumapaz')
@section('content')

@php
  $topBannersList = isset($topBanners) ? $topBanners : (isset($globalBanners) ? $globalBanners->take(4)->values() : collect());
  $bottomBannersList = isset($bottomBanners) ? $bottomBanners : (isset($globalBanners) ? $globalBanners->slice(4, 4)->values() : collect());
@endphp

<!-- ========================================== -->
<!-- 1. ESPACIO DE BANNERS PUBLICITARIOS (1-4)  -->
<!-- ========================================== -->
@if($topBannersList->isNotEmpty())
<div id="top-banners-section" class="max-w-7xl mx-auto px-4 md:px-6 pt-4 pb-2">
  <div class="w-full relative bg-slate-950 overflow-hidden rounded-2xl md:rounded-3xl shadow-xl h-[230px] sm:h-[260px] md:h-[290px]" 
       x-data="{ currentSlide: 0, totalSlides: {{ $topBannersList->count() }}, timer: null }" 
       x-init="if (totalSlides > 1) { timer = setInterval(() => currentSlide = (currentSlide + 1) % totalSlides, 6000) }"
       @mouseenter="if (timer) clearInterval(timer)" 
       @mouseleave="if (totalSlides > 1) { timer = setInterval(() => currentSlide = (currentSlide + 1) % totalSlides, 6000) }">
    
    @foreach($topBannersList as $index => $banner)
      @php
        $bannerTargetUrl = $banner->link_url ?: route('consumer.catalog');
      @endphp
      <div x-show="currentSlide === {{ $index }}" 
           @if($index > 0) x-cloak style="display: none;" @endif
           class="absolute inset-0 flex items-center bg-gradient-to-r from-slate-950 via-slate-900/80 to-emerald-950/60 p-6 md:p-10 transition-opacity duration-500">
        
        <a href="{{ $bannerTargetUrl }}" class="absolute inset-0 z-0 block" aria-label="{{ $banner->title }}">
          @if($banner->is_video)
            <video src="{{ $banner->image_url }}" class="w-full h-full object-cover opacity-55" autoplay loop muted playsinline></video>
          @else
            <div class="w-full h-full bg-cover bg-center opacity-55 hover:scale-105 transition-transform duration-700" style="background-image: url('{{ $banner->image_url }}');"></div>
          @endif
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/65 to-transparent"></div>
        </a>

        <div class="w-full relative z-10 max-w-2xl space-y-2.5 pointer-events-none">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#10B981] text-white text-[10px] font-black rounded-full uppercase tracking-wider shadow">
              <span class="material-symbols-outlined text-[13px]">campaign</span> Espacio Publicitario
            </span>
            <span class="text-[10px] font-bold text-emerald-300 bg-emerald-950/80 px-2 py-0.5 rounded-full border border-emerald-800">
              Tienda Verificada FusaShop
            </span>
          </div>

          <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-white leading-tight font-heading drop-shadow">
            {{ $banner->title }}
          </h2>

          <p class="text-slate-200 text-xs sm:text-sm line-clamp-2 leading-relaxed">
            Haz clic en el banner para visitar directamente la tienda oficial y descubrir sus productos y promociones en Fusagasugá.
          </p>

          <div class="pt-2 flex items-center gap-3 pointer-events-auto">
            <a href="{{ $bannerTargetUrl }}" class="px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md transition-all inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px]">storefront</span> Ir a la Tienda / Oferta
            </a>
            <a href="{{ route('merchant.banner.request') }}" class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-xs rounded-xl backdrop-blur-sm transition-all hidden sm:inline-flex items-center gap-1">
              <span class="material-symbols-outlined text-[15px]">add_circle</span> Pautar Aquí
            </a>
          </div>
        </div>
      </div>
    @endforeach

    @if($topBannersList->count() > 1)
      <button type="button" 
              @click="currentSlide = (currentSlide - 1 + totalSlides) % totalSlides" 
              class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition-all"
              aria-label="Slide anterior">
        <span class="material-symbols-outlined text-lg">chevron_left</span>
      </button>

      <button type="button" 
              @click="currentSlide = (currentSlide + 1) % totalSlides" 
              class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition-all"
              aria-label="Slide siguiente">
        <span class="material-symbols-outlined text-lg">chevron_right</span>
      </button>

      <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5">
        <template x-for="i in totalSlides" :key="i">
          <button @click="currentSlide = i - 1" class="h-1.5 rounded-full transition-all duration-300" :class="currentSlide === (i - 1) ? 'w-6 bg-[#10B981]' : 'w-2 bg-white/40'"></button>
        </template>
      </div>
    @endif

  </div>
</div>
@endif

@php
  $rawPromoCards = \App\Models\Setting::getValue('promotional_cards');
  $activePromoCards = $rawPromoCards ? collect(json_decode($rawPromoCards, true))->where('is_active', true)->values() : collect();
@endphp

@if($activePromoCards->isNotEmpty())
@guest
<!-- ============================================================== -->
<!-- TARJETA DESTACADA: ¿NO SABES CÓMO EMPEZAR? + MODAL INTERACTIVO  -->
<!-- Solo visible antes de iniciar sesión o registrarse (visitantes no autenticados)    -->
<!-- ============================================================== -->
@php
  $howToStart = \App\Models\Setting::howToStartGuide();
  $buyerVideoEmbed = \App\Models\Setting::formatVideoEmbedUrl($howToStart['buyer_video_url'] ?? null);
  $sellerVideoEmbed = \App\Models\Setting::formatVideoEmbedUrl($howToStart['seller_video_url'] ?? null);
@endphp
<section id="tarjeta-como-empezar" class="max-w-7xl mx-auto px-4 md:px-6 pt-3 pb-2" x-data="{ howToStartModal: false, startTab: 'buyer' }">
  <!-- Tarjeta Interactiva: ¿No sabes cómo empezar? -->
  <div @click="howToStartModal = true" class="group relative cursor-pointer overflow-hidden rounded-3xl bg-gradient-to-r from-slate-950 via-[#004d32] to-emerald-950 p-6 sm:p-8 border border-emerald-500/30 shadow-xl hover:shadow-2xl hover:border-emerald-400/60 transition-all duration-300">
    <div class="absolute -top-16 -right-16 w-64 h-64 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none group-hover:scale-125 transition-transform duration-700"></div>
    <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-orange-500/20 rounded-full blur-3xl pointer-events-none group-hover:scale-125 transition-transform duration-700"></div>

    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
      <div class="max-w-2xl space-y-2">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-black uppercase tracking-wider">
          <span class="material-symbols-outlined text-sm">help</span>
          <span>{{ $howToStart['card_badge'] ?? '¿Nuevo en FusaShop?' }}</span>
          <span class="ml-1 px-2 py-0.5 rounded-full bg-amber-500/30 text-amber-300 text-[10px] font-extrabold flex items-center gap-0.5">
            <span class="material-symbols-outlined text-[12px]">smart_display</span> Videos tutoriales incluidos
          </span>
        </div>

        <h3 class="text-2xl sm:text-3xl font-black font-heading text-white leading-tight group-hover:text-emerald-300 transition-colors">
          {{ $howToStart['card_title'] ?? '¿No sabes cómo empezar?' }}
        </h3>

        <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
          {{ $howToStart['card_subtitle'] ?? 'Haz clic aquí y te guiamos en 1 minuto: elige si deseas comprar productos locales o formalizar, vender y certificar tu negocio en Fusagasugá.' }}
        </p>
      </div>

      <div class="flex items-center gap-3 shrink-0">
        <button type="button" @click.stop="howToStartModal = true" class="px-6 py-3.5 bg-gradient-to-r from-[#10B981] to-emerald-600 hover:from-emerald-500 hover:to-emerald-700 text-white font-black rounded-2xl text-xs sm:text-sm transition-all shadow-lg hover:shadow-emerald-500/30 flex items-center gap-2 group-hover:scale-105 cursor-pointer">
          <span class="material-symbols-outlined text-lg">play_circle</span>
          <span>{{ $howToStart['card_button_text'] ?? 'Comenzar Aquí' }}</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Ventanita Modal Flotante: Para empezar, ¿qué deseas hacer? -->
  <div x-show="howToStartModal" 
       x-cloak
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
       role="dialog" 
       aria-modal="true"
       @keydown.escape.window="howToStartModal = false">
    
    <!-- Fondo oscuro backdrop -->
    <div x-show="howToStartModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="howToStartModal = false"
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

    <!-- Contenedor del Modal -->
    <div x-show="howToStartModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="relative bg-slate-900 border border-slate-700 rounded-3xl max-w-4xl w-full shadow-2xl text-white overflow-hidden max-h-[90vh] flex flex-col z-10">

      <!-- Header del Modal -->
      <div class="p-5 sm:p-6 bg-slate-950/70 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
            <span class="material-symbols-outlined text-2xl">explore</span>
          </div>
          <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400">Guía de Inicio FusaShop</span>
            <h4 class="text-lg sm:text-xl font-black font-heading text-white">Para empezar, ¿qué deseas hacer?</h4>
          </div>
        </div>

        <button type="button" @click="howToStartModal = false" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>

      <!-- Selector de Decisión Principal: ¿Comprar o Vender? -->
      <div class="p-4 sm:p-6 bg-slate-950/40 border-b border-slate-800">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-2xl mx-auto">
          <!-- Opción 1: Comprar -->
          <button type="button" 
                  @click="startTab = 'buyer'" 
                  :class="startTab === 'buyer' ? 'bg-[#10B981] text-white shadow-lg ring-2 ring-emerald-400/50' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                  class="p-4 rounded-2xl flex items-center gap-3.5 transition-all text-left cursor-pointer">
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-2xl">shopping_cart</span>
            </div>
            <div>
              <span class="font-black text-sm block">1. Quiero Comprar</span>
              <span class="text-xs opacity-90 block leading-tight">Comprar productos locales, FusaCoins y envíos</span>
            </div>
          </button>

          <!-- Opción 2: Vender -->
          <button type="button" 
                  @click="startTab = 'seller'" 
                  :class="startTab === 'seller' ? 'bg-[#F97316] text-white shadow-lg ring-2 ring-orange-400/50' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                  class="p-4 rounded-2xl flex items-center gap-3.5 transition-all text-left cursor-pointer">
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-2xl">storefront</span>
            </div>
            <div>
              <span class="font-black text-sm block">2. Quiero Vender y Certificarme</span>
              <span class="text-xs opacity-90 block leading-tight">Formalización, RUT, Cámara y Chulito ✅</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Contenido Scrollable del Modal -->
      <div class="p-5 sm:p-8 overflow-y-auto space-y-6 flex-1 custom-scrollbar">

        <!-- VISTA COMPRADOR -->
        <div x-show="startTab === 'buyer'" class="space-y-6">
          <div>
            <h5 class="text-xl font-black font-heading text-white flex items-center gap-2">
              <span class="material-symbols-outlined text-emerald-400">shopping_bag</span>
              {{ $howToStart['buyer_title'] ?? '¿Cómo Comprar en FusaShop?' }}
            </h5>
            <p class="text-xs sm:text-sm text-slate-300 mt-1 leading-relaxed">
              {{ $howToStart['buyer_description'] ?? 'Guía rápida para explorar comercios de Fusagasugá, pagar de forma 100% segura con Wompi, ganar FusaCoins y recibir en tu puerta.' }}
            </p>
          </div>

          <!-- ESPACIO DE VIDEO DEL COMPRADOR (MUY VISIBLE Y DESTACADO) -->
          <div class="p-4 bg-slate-950/70 rounded-2xl border border-emerald-500/30 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                  <span class="material-symbols-outlined text-base">smart_display</span>
                </span>
                <span class="text-xs font-black uppercase tracking-wider text-emerald-300">
                  Video Tutorial Oficial: ¿Cómo Comprar en FusaShop?
                </span>
              </div>
              <span class="text-[10px] text-emerald-300 bg-emerald-950/80 px-2.5 py-0.5 rounded-full border border-emerald-800 font-bold">
                Explicación en Video
              </span>
            </div>

            @if(!empty($buyerVideoEmbed))
              <div class="rounded-2xl overflow-hidden border-2 border-emerald-500/40 bg-black aspect-video w-full shadow-2xl relative">
                <iframe src="{{ $buyerVideoEmbed }}" title="Video Tutorial Comprador" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
              </div>
            @else
              <div class="rounded-2xl border-2 border-dashed border-emerald-500/40 bg-slate-950/90 p-8 text-center flex flex-col items-center justify-center gap-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                  <span class="material-symbols-outlined text-3xl">play_circle</span>
                </div>
                <div>
                  <h6 class="font-extrabold text-sm text-white">Espacio de Video Tutorial de Compra</h6>
                  <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto leading-relaxed">
                    El Administrador puede ingresar la URL de cualquier video de YouTube o Vimeo desde el panel <span class="text-emerald-400 font-bold">Gestión Visual → Guía y Videos</span>.
                  </p>
                </div>
              </div>
            @endif
          </div>

          <!-- Pasos de Compra -->
          <div class="bg-slate-950/60 rounded-2xl p-5 border border-slate-800 space-y-3">
            <h6 class="text-xs font-black uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-sm">checklist</span> Pasos para tu primera compra:
            </h6>
            <div class="space-y-2 text-xs text-slate-300 leading-relaxed whitespace-pre-line">
              {{ $howToStart['buyer_steps'] ?? "1. Registro con Código OTP\n2. Explora el Catálogo\n3. Aplica FusaCoins\n4. Paga con Wompi y Rastrea" }}
            </div>
          </div>

          <!-- Botones de Acción Comprador -->
          <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
            <a href="{{ route('register') }}" class="w-full sm:w-auto px-6 py-3 bg-[#10B981] hover:bg-emerald-600 text-white font-black rounded-xl text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2 no-underline">
              <span class="material-symbols-outlined text-base">how_to_reg</span> Registrarme como Comprador
            </a>
            <a href="{{ route('consumer.catalog') }}" class="w-full sm:w-auto px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs sm:text-sm transition-all flex items-center justify-center gap-2 no-underline">
              <span class="material-symbols-outlined text-base">storefront</span> Ver Catálogo de Productos
            </a>
          </div>
        </div>

        <!-- VISTA VENDEDOR & FORMALIZACIÓN -->
        <div x-show="startTab === 'seller'" x-cloak style="display: none;" class="space-y-6">
          <div>
            <h5 class="text-xl font-black font-heading text-white flex items-center gap-2">
              <span class="material-symbols-outlined text-orange-400">store</span>
              {{ $howToStart['seller_title'] ?? '¿Cómo Vender y Formalizar tu Negocio?' }}
            </h5>
            <p class="text-xs sm:text-sm text-slate-300 mt-1 leading-relaxed">
              {{ $howToStart['seller_description'] ?? 'Aprende a registrar tu tienda, formalizarte ante la DIAN y Cámara de Comercio y obtener tu Chulito de Comercio Verificado.' }}
            </p>
          </div>

          <!-- ESPACIO DE VIDEO DEL VENDEDOR (MUY VISIBLE Y DESTACADO) -->
          <div class="p-4 bg-slate-950/70 rounded-2xl border border-orange-500/30 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-orange-500/20 text-orange-400 flex items-center justify-center">
                  <span class="material-symbols-outlined text-base">smart_display</span>
                </span>
                <span class="text-xs font-black uppercase tracking-wider text-orange-300">
                  Video Tutorial Oficial: Formalización Comercial (DIAN & Cámara)
                </span>
              </div>
              <span class="text-[10px] text-orange-300 bg-orange-950/80 px-2.5 py-0.5 rounded-full border border-orange-800 font-bold">
                Formalización & DIAN
              </span>
            </div>

            @if(!empty($sellerVideoEmbed))
              <div class="rounded-2xl overflow-hidden border-2 border-orange-500/40 bg-black aspect-video w-full shadow-2xl relative">
                <iframe src="{{ $sellerVideoEmbed }}" title="Video Tutorial Comerciante" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
              </div>
            @else
              <div class="rounded-2xl border-2 border-dashed border-orange-500/40 bg-slate-950/90 p-8 text-center flex flex-col items-center justify-center gap-3">
                <div class="w-14 h-14 rounded-2xl bg-orange-500/20 text-orange-400 flex items-center justify-center">
                  <span class="material-symbols-outlined text-3xl">play_circle</span>
                </div>
                <div>
                  <h6 class="font-extrabold text-sm text-white">Espacio de Video Tutorial de Formalización</h6>
                  <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto leading-relaxed">
                    El Administrador puede ingresar la URL de cualquier video de YouTube o Vimeo desde el panel <span class="text-orange-400 font-bold">Gestión Visual → Guía y Videos</span>.
                  </p>
                </div>
              </div>
            @endif
          </div>

          <!-- CAJA DESTACADA: ¿Por qué es necesario verificar tu negocio y en qué ayuda? -->
          <div class="bg-gradient-to-r from-amber-500/15 via-emerald-500/10 to-transparent border-l-4 border-amber-400 p-5 rounded-2xl space-y-2">
            <div class="flex items-center gap-2 text-amber-300 font-extrabold text-sm">
              <span class="material-symbols-outlined text-lg">verified</span>
              <span>¿Por qué es necesario verificar tu negocio y en qué te ayuda?</span>
            </div>
            <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">
              {{ $howToStart['seller_why_verify'] ?? 'Obtener la certificación con RUT y Cámara de Comercio activa la insignia oficial de Comerciante Verificado ✅. Esto genera máxima confianza en los compradores, incrementa tus ventas hasta un 300%, te da acceso a banners publicitarios destacados en FusaShop y agiliza las dispersiones bancarias de tus ingresos directamente a tu cuenta bancaria sin retenciones cautelares.' }}
            </p>
          </div>

          <!-- Pasos de Formalización y Certificación -->
          <div class="bg-slate-950/60 rounded-2xl p-5 border border-slate-800 space-y-3">
            <h6 class="text-xs font-black uppercase tracking-wider text-orange-400 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-sm">assignment</span> Ruta de Formalización Paso a Paso:
            </h6>
            <div class="space-y-2 text-xs text-slate-300 leading-relaxed whitespace-pre-line">
              {{ $howToStart['seller_steps'] ?? "1. Registro Comercial\n2. Tramita tu RUT en la DIAN\n3. Matrícula en Cámara de Comercio\n4. Certificación y Chulito ✅" }}
            </div>

            <!-- Enlaces directos a entidades oficiales -->
            <div class="pt-3 border-t border-slate-800 flex flex-wrap gap-2">
              <a href="{{ $howToStart['dian_url'] ?? 'https://www.dian.gov.co' }}" target="_blank" class="px-3 py-1.5 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 rounded-xl text-xs font-bold inline-flex items-center gap-1 transition-all no-underline">
                <span class="material-symbols-outlined text-xs">open_in_new</span> Trámite RUT en la DIAN
              </a>
              <a href="{{ $howToStart['rues_url'] ?? 'https://www.rues.org.co' }}" target="_blank" class="px-3 py-1.5 bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 border border-blue-500/40 rounded-xl text-xs font-bold inline-flex items-center gap-1 transition-all no-underline">
                <span class="material-symbols-outlined text-xs">open_in_new</span> Consultar en RUES / Cámara de Comercio
              </a>
            </div>
          </div>

          <!-- Botón de Acción Vendedor -->
          <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
            <a href="{{ route('register') }}" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-orange-500 to-[#F97316] hover:from-orange-600 hover:to-orange-700 text-white font-black rounded-xl text-xs sm:text-sm transition-all shadow-md flex items-center justify-center gap-2 no-underline">
              <span class="material-symbols-outlined text-base">store</span> Registrarme como Comerciante
            </a>
            <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs sm:text-sm transition-all flex items-center justify-center gap-2 no-underline">
              <span class="material-symbols-outlined text-base">login</span> Ya tengo cuenta de Comerciante
            </a>
          </div>
        </div>

      </div>

      <!-- Footer del Modal -->
      <div class="p-4 bg-slate-950/70 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <span class="flex items-center gap-1">
          <span class="material-symbols-outlined text-sm text-emerald-400">verified</span>
          FusaShop Oficial · Fusagasugá & Sumapaz
        </span>
        <button type="button" @click="howToStartModal = false" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-bold transition-colors cursor-pointer">
          Cerrar
        </button>
      </div>

    </div>
  </div>
</section>
@endguest

<!-- ============================================================== -->
<!-- 2. TARJETAS PROMOCIONALES ÚNICAS Y POR CATEGORÍA               -->
<!-- ============================================================== -->
<section id="promo-cards-section" class="max-w-7xl mx-auto px-4 md:px-6 pt-4 pb-2">
  <div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
      <span class="w-7 h-7 rounded-lg bg-emerald-100 text-[#006c47] flex items-center justify-center">
        <span class="material-symbols-outlined text-base">loyalty</span>
      </span>
      <h3 class="text-base font-extrabold font-heading text-slate-900">Mira las promociones</h3>
    </div>
    <span class="text-xs text-slate-500 font-semibold hidden sm:inline">Haz clic para ver los productos en oferta</span>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
    @foreach($activePromoCards as $card)
      @php
        $cardTargetUrl = !empty($card['target_url'])
            ? $card['target_url']
            : route('consumer.catalog', array_filter(['category' => $card['category'] ?? null, 'on_sale' => 1]));
        $cardProducts = isset($card['products']) && is_array($card['products']) ? $card['products'] : [];
      @endphp
      <a href="{{ $cardTargetUrl }}" class="group block rounded-2xl p-5 bg-gradient-to-br {{ $card['bg_gradient'] ?? 'from-emerald-950 via-slate-900 to-emerald-900' }} text-white relative overflow-hidden shadow-md hover:scale-[1.01] transition-all">
        @if(!empty($card['image_url']))
          <div class="absolute inset-0 bg-cover bg-center opacity-25 group-hover:scale-105 transition-transform duration-500" style="background-image: url('{{ $card['image_url'] }}');"></div>
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-950/75 to-slate-900/60"></div>
        @endif

        <div class="relative z-10 flex flex-col h-full justify-between">
          <div>
            <div class="flex items-center justify-between gap-2 mb-2.5">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/20 backdrop-blur-sm border border-white/20 truncate max-w-[65%]">
                {{ $card['merchant_name'] ?? ($card['category'] ?? 'Promoción') }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-[#F97316] text-white shadow shrink-0">
                {{ $card['discount_badge'] ?? 'OFERTA' }}
              </span>
            </div>

            <div class="flex items-start gap-3">
              @if(!empty($card['image_url']))
                <img src="{{ $card['image_url'] }}" alt="{{ $card['title'] }}" class="w-16 h-16 rounded-xl object-cover border-2 border-white/20 shadow-md shrink-0 bg-white">
              @endif
              <div class="min-w-0 flex-1">
                <h4 class="font-extrabold text-white text-base leading-snug font-heading group-hover:text-emerald-300 transition-colors line-clamp-2">
                  {{ $card['title'] }}
                </h4>
                <p class="text-slate-300 text-xs line-clamp-2 mt-1 leading-relaxed">
                  {{ $card['subtitle'] }}
                </p>
              </div>
            </div>

            @if(!empty($cardProducts))
              <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach(array_slice($cardProducts, 0, 3) as $prodItem)
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-black/40 border border-white/15 text-[10px] font-bold text-emerald-200">
                    <span class="material-symbols-outlined text-[12px] text-[#10B981]">sell</span>
                    <span class="truncate max-w-[130px]">{{ $prodItem['name'] ?? 'Producto' }}</span>
                    @if(!empty($prodItem['discount_percent']))
                      <span class="text-amber-300 font-black">-{{ (int)$prodItem['discount_percent'] }}%</span>
                    @endif
                  </span>
                @endforeach
                @if(count($cardProducts) > 3)
                  <span class="px-2 py-0.5 rounded-md bg-white/10 text-[10px] font-bold text-white">+{{ count($cardProducts) - 3 }} más</span>
                @endif
              </div>
            @endif
          </div>

          <div class="mt-4 pt-2.5 border-t border-white/10 flex items-center justify-between text-xs text-emerald-300 font-bold">
            <span>{{ !empty($cardProducts) ? 'Ver productos en descuento' : 'Explorar ' . ($card['category'] ?? 'Promoción') }}</span>
            <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </div>
        </div>
      </a>
    @endforeach
  </div>
</section>
@endif

<!-- ========================================== -->
<!-- 3. SECCIÓN RELÁMPAGO (FLASH DEALS)         -->
<!-- ========================================== -->
<section class="max-w-7xl mx-auto px-4 md:px-6 py-12">
  <div class="bg-gradient-to-r from-red-500/10 via-amber-500/10 to-orange-500/10 border border-red-200/60 rounded-3xl p-6 sm:p-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 bg-[#ba1a1a] text-white text-xs font-black rounded-full uppercase tracking-wider flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">bolt</span> Relámpago FusaShop
          </span>
          <span class="text-xs font-bold text-slate-500">Stock Limitado</span>
        </div>
        <h3 class="text-2xl font-black text-slate-900 font-heading mt-2">
          Ofertas con Tiempo Límite
        </h3>
      </div>
      
      <a href="{{ route('consumer.catalog', ['on_sale' => 1]) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#10B981] hover:text-emerald-700 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 self-start sm:self-auto">
        <span>Ver todas las ofertas</span>
        <span class="material-symbols-outlined text-sm">arrow_forward</span>
      </a>
    </div>

    <!-- Rejilla de Ofertas Flash -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
      @foreach(($flashDeals ?? $featured->take(4)) as $flashItem)
        <x-product-card :product="$flashItem" :is-flash-deal="true" />
      @endforeach
    </div>
  </div>
</section>

<!-- ========================================== -->
<!-- 4. SECCIÓN O2O: "EXPLORA Y VIVE FUSA"      -->
<!-- ========================================== -->
<section class="max-w-7xl mx-auto px-4 md:px-6 py-8">
  <div class="bg-slate-900 text-white rounded-3xl p-6 sm:p-10 relative overflow-hidden shadow-2xl">
    <!-- Fondo decorativo -->
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-gradient-to-br from-[#10B981]/20 to-emerald-600/20 blur-3xl pointer-events-none"></div>
    
    <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
      <div class="max-w-xl">
        <span class="text-xs font-black uppercase tracking-widest text-[#10B981] flex items-center gap-1.5 mb-2">
          <span class="material-symbols-outlined text-base">explore</span> Experiencias en Fusagasugá
        </span>
        <h3 class="text-2xl sm:text-4xl font-black text-white font-heading">
          Explora y Vive Fusa: Hoteles, Gastronomía y Turismo
        </h3>
        <p class="text-slate-400 text-xs sm:text-sm mt-2">
          Reserva experiencias únicas en Fusagasugá. Pagas en línea y redimes con tu código QR presencialmente.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('consumer.catalog', ['category' => 'Turismo']) }}" class="px-5 py-2.5 bg-[#F97316] hover:bg-orange-600 text-white font-extrabold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-lg">
          <span class="material-symbols-outlined text-sm">holiday_village</span> Ver Hoteles, Gastronomía y Turismo
        </a>
      </div>
    </div>

    <!-- Cards de Experiencias O2O -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- O2O 1: Glamping Chinauta Paradise -->
      <div class="bg-slate-800/90 border border-slate-700 rounded-2xl overflow-hidden flex flex-col group hover:border-[#10B981] transition-all">
        <div class="relative h-48 overflow-hidden">
          <img src="https://images.unsplash.com/photo-1587061949409-02df41d5e562?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Glamping Fusagasugá">
          <div class="absolute top-3 left-3 bg-emerald-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg">
            ⭐ 4.9 (Fusagasugá)
          </div>
          <div class="absolute bottom-3 right-3 bg-slate-950/80 backdrop-blur-sm text-amber-400 text-[10px] font-extrabold px-2.5 py-1 rounded-lg">
            Pasadía o Noche
          </div>
        </div>
        <div class="p-5 flex-1 flex flex-col justify-between">
          <div>
            <h4 class="font-extrabold text-white text-base">Glamping Orquídeas del Sol</h4>
            <p class="text-slate-400 text-xs mt-1">Clima cálido, jacuzzi privado y sendero ecológico.</p>
            <div class="mt-3 flex items-center justify-between text-xs">
              <span class="text-slate-400">Desde:</span>
              <span class="text-xl font-black text-[#10B981] font-heading">$220.000 COP</span>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-700 flex items-center justify-between">
            <span class="text-[10px] text-amber-300 font-bold flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">qr_code</span> Voucher QR instantáneo
            </span>
            <a href="{{ route('consumer.catalog', ['category' => 'Turismo']) }}" class="text-xs font-bold text-white hover:text-[#10B981] flex items-center gap-1">
              Reservar <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
          </div>
        </div>
      </div>

      <!-- O2O 2: Restaurante Típico El Casona del Sumapaz -->
      <div class="bg-slate-800/90 border border-slate-700 rounded-2xl overflow-hidden flex flex-col group hover:border-[#F97316] transition-all">
        <div class="relative h-48 overflow-hidden">
          <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Restaurante Sumapaz">
          <div class="absolute top-3 left-3 bg-orange-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg">
            ⭐ 4.8 (Fusagasugá)
          </div>
          <div class="absolute bottom-3 right-3 bg-slate-950/80 backdrop-blur-sm text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-lg">
            Menú con QR
          </div>
        </div>
        <div class="p-5 flex-1 flex flex-col justify-between">
          <div>
            <h4 class="font-extrabold text-white text-base">La Casona Campestre Fusagasugá</h4>
            <p class="text-slate-400 text-xs mt-1">Parrillada al carbón, trucha arcoíris y postres típicos de la región.</p>
            <div class="mt-3 flex items-center justify-between text-xs">
              <span class="text-slate-400">Platos desde:</span>
              <span class="text-xl font-black text-[#F97316] font-heading">$35.000 COP</span>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-700 flex items-center justify-between">
            <span class="text-[10px] text-amber-300 font-bold flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">event_seat</span> Reserva de Mesa
            </span>
            <a href="{{ route('consumer.catalog', ['category' => 'Gastronomía']) }}" class="text-xs font-bold text-white hover:text-[#F97316] flex items-center gap-1">
              Reservar <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
          </div>
        </div>
      </div>

      <!-- O2O 3: Vivero Ornamental Sumapaz -->
      <div class="bg-slate-800/90 border border-slate-700 rounded-2xl overflow-hidden flex flex-col group hover:border-[#10B981] transition-all">
        <div class="relative h-48 overflow-hidden">
          <img src="https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Viveros Fusagasuga">
          <div class="absolute top-3 left-3 bg-emerald-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg">
            ⭐ 5.0 (Viveristas)
          </div>
          <div class="absolute bottom-3 right-3 bg-slate-950/80 backdrop-blur-sm text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-lg">
            Retiro o Envío
          </div>
        </div>
        <div class="p-5 flex-1 flex flex-col justify-between">
          <div>
            <h4 class="font-extrabold text-white text-base">Ruta de las Orquídeas Fusagasugá</h4>
            <p class="text-slate-400 text-xs mt-1">Tour guiado por viveros patrimoniales y venta directa de orquídeas.</p>
            <div class="mt-3 flex items-center justify-between text-xs">
              <span class="text-slate-400">Pase + Planta:</span>
              <span class="text-xl font-black text-emerald-400 font-heading">$45.000 COP</span>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-700 flex items-center justify-between">
            <span class="text-[10px] text-amber-300 font-bold flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">local_florist</span> Taller + Visita
            </span>
            <a href="{{ route('consumer.catalog', ['category' => 'Alimentos']) }}" class="text-xs font-bold text-white hover:text-emerald-400 flex items-center gap-1">
              Ver Viveros <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ========================================== -->
<!-- 5. ESPACIO DE BANNERS PUBLICITARIOS (5-8)  -->
<!-- ========================================== -->
@if($bottomBannersList->isNotEmpty())
<section id="bottom-banners-section" class="max-w-7xl mx-auto px-4 md:px-6 py-4">
  <div class="w-full relative bg-slate-950 overflow-hidden rounded-2xl md:rounded-3xl shadow-xl h-[230px] sm:h-[260px] md:h-[290px]" 
       x-data="{ currentSlide: 0, totalSlides: {{ $bottomBannersList->count() }}, timer: null }" 
       x-init="if (totalSlides > 1) { timer = setInterval(() => currentSlide = (currentSlide + 1) % totalSlides, 6000) }"
       @mouseenter="if (timer) clearInterval(timer)" 
       @mouseleave="if (totalSlides > 1) { timer = setInterval(() => currentSlide = (currentSlide + 1) % totalSlides, 6000) }">
    
    @foreach($bottomBannersList as $index => $banner)
      @php
        $bannerTargetUrl = $banner->link_url ?: route('consumer.catalog');
      @endphp
      <div x-show="currentSlide === {{ $index }}" 
           @if($index > 0) x-cloak style="display: none;" @endif
           class="absolute inset-0 flex items-center bg-gradient-to-r from-slate-950 via-slate-900/80 to-emerald-950/60 p-6 md:p-10 transition-opacity duration-500">
        
        <a href="{{ $bannerTargetUrl }}" class="absolute inset-0 z-0 block" aria-label="{{ $banner->title }}">
          @if($banner->is_video)
            <video src="{{ $banner->image_url }}" class="w-full h-full object-cover opacity-55" autoplay loop muted playsinline></video>
          @else
            <div class="w-full h-full bg-cover bg-center opacity-55 hover:scale-105 transition-transform duration-700" style="background-image: url('{{ $banner->image_url }}');"></div>
          @endif
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/65 to-transparent"></div>
        </a>

        <div class="w-full relative z-10 max-w-2xl space-y-2.5 pointer-events-none">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#10B981] text-white text-[10px] font-black rounded-full uppercase tracking-wider shadow">
              <span class="material-symbols-outlined text-[13px]">campaign</span> Espacio Publicitario Destacado
            </span>
            <span class="text-[10px] font-bold text-emerald-300 bg-emerald-950/80 px-2 py-0.5 rounded-full border border-emerald-800">
              Tienda Verificada FusaShop
            </span>
          </div>

          <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-white leading-tight font-heading drop-shadow">
            {{ $banner->title }}
          </h2>

          <p class="text-slate-200 text-xs sm:text-sm line-clamp-2 leading-relaxed">
            Haz clic en el banner para visitar directamente la tienda oficial y descubrir sus productos y promociones en Fusagasugá.
          </p>

          <div class="pt-2 flex items-center gap-3 pointer-events-auto">
            <a href="{{ $bannerTargetUrl }}" class="px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md transition-all inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px]">storefront</span> Ir a la Tienda / Oferta
            </a>
            <a href="{{ route('merchant.banner.request') }}" class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-xs rounded-xl backdrop-blur-sm transition-all hidden sm:inline-flex items-center gap-1">
              <span class="material-symbols-outlined text-[15px]">add_circle</span> Pautar Aquí
            </a>
          </div>
        </div>
      </div>
    @endforeach

    @if($bottomBannersList->count() > 1)
      <button type="button" 
              @click="currentSlide = (currentSlide - 1 + totalSlides) % totalSlides" 
              class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition-all"
              aria-label="Slide anterior">
        <span class="material-symbols-outlined text-lg">chevron_left</span>
      </button>

      <button type="button" 
              @click="currentSlide = (currentSlide + 1) % totalSlides" 
              class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center backdrop-blur-sm transition-all"
              aria-label="Slide siguiente">
        <span class="material-symbols-outlined text-lg">chevron_right</span>
      </button>

      <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5">
        <template x-for="i in totalSlides" :key="i">
          <button @click="currentSlide = i - 1" class="h-1.5 rounded-full transition-all duration-300" :class="currentSlide === (i - 1) ? 'w-6 bg-[#10B981]' : 'w-2 bg-white/40'"></button>
        </template>
      </div>
    @endif
  </div>
</section>
@endif


<!-- ========================================== -->
<!-- 6. CATÁLOGO COMPLETO & PRODUCTOS           -->
<!-- ========================================== -->
<section class="max-w-7xl mx-auto px-4 md:px-6 py-8">
  <div class="flex items-center justify-between mb-8">
    <div>
      <span class="text-xs font-bold text-[#10B981] uppercase tracking-wider">Lo Mejor de Fusagasugá</span>
      <h3 class="text-2xl sm:text-3xl font-black text-slate-900 font-heading mt-0.5">
        Catálogo General Destacado
      </h3>
    </div>
    <a href="{{ route('consumer.catalog') }}" class="text-xs font-bold text-[#10B981] hover:underline flex items-center gap-1">
      <span>Ver catálogo completo ({{ $featured->count() }}+ productos)</span>
      <span class="material-symbols-outlined text-sm">arrow_forward</span>
    </a>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
    @foreach($featured as $p)
      <x-product-card :product="$p" />
    @endforeach
  </div>
</section>

<!-- Script para el reloj contador -->
<script>
(function() {
  let secondsLeft = 5 * 3600 + 42 * 60 + 19;
  const clock = document.getElementById('countdown-clock');
  setInterval(() => {
    if (secondsLeft > 0) secondsLeft--;
    const h = Math.floor(secondsLeft / 3600);
    const m = Math.floor((secondsLeft % 3600) / 60);
    const s = secondsLeft % 60;
    if (clock) {
      clock.innerText = [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
    }
  }, 1000);
})();
</script>

@endsection
