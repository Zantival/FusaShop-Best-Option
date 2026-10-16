@extends('layouts.app')
@section('title','Vitrina Principal — Sumapaz')
@section('content')

<!-- ========================================== -->
<!-- 1. ESPACIO DE BANNERS PUBLICITARIOS         -->
<!-- ========================================== -->
<div class="max-w-7xl mx-auto px-4 md:px-6 pt-4 pb-2">
  @php
    $hasBanners = isset($globalBanners) && $globalBanners->isNotEmpty();
    $bannerCount = $hasBanners ? $globalBanners->count() : 4;
  @endphp

  <!-- Banner Container Principal -->
  <div class="w-full relative bg-slate-950 overflow-hidden rounded-2xl md:rounded-3xl shadow-xl h-[230px] sm:h-[260px] md:h-[290px]" 
       x-data="{ currentSlide: 0, totalSlides: {{ $bannerCount }}, timer: null }" 
       x-init="timer = setInterval(() => currentSlide = (currentSlide + 1) % totalSlides, 6000)"
       @mouseenter="clearInterval(timer)" 
       @mouseleave="timer = setInterval(() => currentSlide = (currentSlide + 1) % totalSlides, 6000)">
    
    @if($hasBanners)
      <!-- Render de Banners Publicitarios Reales desde Base de Datos ($globalBanners) -->
      @foreach($globalBanners as $index => $banner)
        <div x-show="currentSlide === {{ $index }}" 
             @if($index > 0) x-cloak style="display: none;" @endif
             class="absolute inset-0 flex items-center bg-gradient-to-r from-slate-950 via-slate-900/90 to-emerald-950/70 p-6 md:p-10 transition-opacity duration-500">
          
          @if($banner->is_video)
            <video src="{{ $banner->image_url }}" class="absolute inset-0 w-full h-full object-cover opacity-35 mix-blend-overlay" autoplay loop muted playsinline></video>
          @else
            <div class="absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay" style="background-image: url('{{ $banner->image_url }}');"></div>
          @endif

          <div class="w-full relative z-10 max-w-2xl space-y-2.5">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#10B981] text-white text-[10px] font-black rounded-full uppercase tracking-wider shadow">
                <span class="material-symbols-outlined text-[13px]">campaign</span> Espacio Publicitario
              </span>
              <span class="text-[10px] font-bold text-emerald-300 bg-emerald-950/80 px-2 py-0.5 rounded-full border border-emerald-800">
                Patrocinado FusaShop
              </span>
            </div>

            <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-white leading-tight font-heading">
              {{ $banner->title }}
            </h2>

            <p class="text-slate-300 text-xs sm:text-sm line-clamp-2 leading-relaxed">
              Descubre promociones exclusivas, productos certificados de Fusagasugá y despachos express en todo el territorio del Sumapaz con respaldo Factus DIAN.
            </p>

            <div class="pt-2 flex items-center gap-3">
              <a href="{{ $banner->link_url ?: route('consumer.catalog') }}" class="px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md transition-all inline-flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">shopping_bag</span> Ver Promoción
              </a>
              <a href="{{ route('merchant.banner.request') }}" class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-xs rounded-xl backdrop-blur-sm transition-all hidden sm:inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">add_circle</span> Pautar Aquí
              </a>
            </div>
          </div>
        </div>
      @endforeach
    @else
      <!-- Fallback de Banners Publicitarios & Campañas (Paleta 100% Verde Sumapaz) -->
      <!-- Slide 1: FusaCoins Dobles -->
      <div x-show="currentSlide === 0" 
           class="absolute inset-0 flex items-center bg-gradient-to-r from-slate-950 via-slate-900/95 to-emerald-950/80 p-6 md:p-10 transition-opacity duration-300">
        <div class="absolute inset-0 bg-cover bg-center opacity-25 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200&auto=format&fit=crop');"></div>
        <div class="w-full relative z-10 max-w-2xl space-y-2">
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#10B981] text-white text-[10px] font-black rounded-full uppercase tracking-wider shadow">
            <span class="material-symbols-outlined text-[13px]">stars</span> Campaña Exclusiva
          </span>
          <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-white leading-tight font-heading">
            Gana <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#10B981] to-emerald-300">2X FusaCoins</span> en Productos Locales
          </h2>
          <p class="text-slate-300 text-xs sm:text-sm line-clamp-2 leading-relaxed">
            Por cada $10.000 COP acumula 200 puntos redimibles directamente en tu carrito. Apoya a los caficultores, viveristas y artesanos de Fusagasugá.
          </p>
          <div class="pt-2 flex items-center gap-2.5">
            <a href="{{ route('consumer.catalog') }}" class="px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px]">shopping_basket</span> Comprar y Acumular
            </a>
            <a href="{{ route('account.profile') }}#loyalty-section" class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-xs rounded-xl backdrop-blur-sm transition-all">
              Conocer mi Saldo
            </a>
          </div>
        </div>
      </div>

      <!-- Slide 2: Ofertas del Día con Countdown (Verde) -->
      <div x-show="currentSlide === 1" 
           x-cloak 
           style="display: none;"
           class="absolute inset-0 flex items-center bg-gradient-to-r from-slate-950 via-slate-900/95 to-emerald-950/80 p-6 md:p-10 transition-opacity duration-300">
        <div class="absolute inset-0 bg-cover bg-center opacity-25 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1550009158-9ebf69173e03?q=80&w=1200&auto=format&fit=crop');"></div>
        <div class="w-full relative z-10 max-w-2xl space-y-2">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-[#ba1a1a] text-white text-[10px] font-black rounded-full uppercase tracking-wider animate-pulse">
              🔥 Ofertas del Día
            </span>
            <div class="flex items-center gap-1 text-[10px] font-mono font-bold text-amber-400 bg-slate-900/90 px-2 py-0.5 rounded-lg border border-slate-700">
              <span class="material-symbols-outlined text-xs">timer</span>
              <span id="countdown-clock">05:42:19</span>
            </div>
          </div>
          <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-white leading-tight font-heading">
            Hasta <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-amber-300">50% OFF</span> en Tecnología y Hogar
          </h2>
          <p class="text-slate-300 text-xs sm:text-sm line-clamp-2 leading-relaxed">
            Electrodomésticos, cómputo y audio con pago seguro vía Wompi hasta en 12 cuotas sin interés y despacho express en Fusagasugá.
          </p>
          <div class="pt-2">
            <a href="{{ route('consumer.catalog', ['category' => 'Electrónica']) }}" class="px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow transition-all inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px]">bolt</span> Ver Ofertas Flash
            </a>
          </div>
        </div>
      </div>

      <!-- Slide 3: Ruta Turística & Gastronómica O2O -->
      <div x-show="currentSlide === 2" 
           x-cloak 
           style="display: none;"
           class="absolute inset-0 flex items-center bg-gradient-to-r from-slate-950 via-slate-900/95 to-emerald-950/70 p-6 md:p-10 transition-opacity duration-300">
        <div class="absolute inset-0 bg-cover bg-center opacity-25 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1587061949409-02df41d5e562?q=80&w=1200&auto=format&fit=crop');"></div>
        <div class="w-full relative z-10 max-w-2xl space-y-2">
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#F97316] text-white text-[10px] font-black rounded-full uppercase tracking-wider shadow">
            <span class="material-symbols-outlined text-[13px]">holiday_village</span> Experiencias O2O
          </span>
          <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-white leading-tight font-heading">
            Ruta Turística & <span class="text-amber-400">Glampings en Chinauta</span>
          </h2>
          <p class="text-slate-300 text-xs sm:text-sm line-clamp-2 leading-relaxed">
            Reserva hoy tu estadía o mesa en los mejores restaurantes del Sumapaz con confirmación instantánea por código QR.
          </p>
          <div class="pt-2">
            <a href="{{ route('consumer.catalog', ['category' => 'Turismo']) }}" class="px-4 py-2 bg-[#F97316] hover:bg-orange-600 text-white font-bold text-xs rounded-xl shadow transition-all inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px]">travel_explore</span> Explorar Experiencias
            </a>
          </div>
        </div>
      </div>

      <!-- Slide 4: Portal Mayorista B2B (Verde) -->
      <div x-show="currentSlide === 3" 
           x-cloak 
           style="display: none;"
           class="absolute inset-0 flex items-center bg-gradient-to-r from-slate-950 via-slate-900/95 to-emerald-950/90 p-6 md:p-10 transition-opacity duration-300">
        <div class="absolute inset-0 bg-cover bg-center opacity-25 mix-blend-overlay" style="background-image: url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=1200&auto=format&fit=crop');"></div>
        <div class="w-full relative z-10 max-w-2xl space-y-2">
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#10B981] text-white text-[10px] font-black rounded-full uppercase tracking-wider shadow">
            <span class="material-symbols-outlined text-[13px]">hub</span> Ecosistema B2B
          </span>
          <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-white leading-tight font-heading">
            Precios Directos de <span class="text-emerald-400">Productor a Distribuidor</span>
          </h2>
          <p class="text-slate-300 text-xs sm:text-sm line-clamp-2 leading-relaxed">
            Insumos agrícolas, café de origen en bulto y plantas de vivero por estiba con facturación electrónica Factus DIAN.
          </p>
          <div class="pt-2">
            <a href="{{ route('consumer.catalog', ['category' => 'Mercado Mayorista (B2B)']) }}" class="px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow transition-all inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined text-white text-[16px]">inventory_2</span> Catálogo Mayorista
            </a>
          </div>
        </div>
      </div>
    @endif

    <!-- Flechas Prev / Next -->
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

    <!-- Indicadores del Slider -->
    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5">
      <template x-for="i in totalSlides" :key="i">
        <button @click="currentSlide = i - 1" class="h-1.5 rounded-full transition-all duration-300" :class="currentSlide === (i - 1) ? 'w-6 bg-[#10B981]' : 'w-2 bg-white/40'"></button>
      </template>
    </div>

  </div>

  <!-- Cinta informativa de Pauta Publicitaria -->
  <div class="mt-2.5 px-4 py-2 bg-emerald-50/70 border border-emerald-100 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
    <div class="flex items-center gap-2 text-emerald-900">
      <span class="material-symbols-outlined text-base text-[#10B981]">campaign</span>
      <span class="font-bold">Espacio de Banners Publicitarios FusaShop:</span>
      <span class="text-slate-600 hidden md:inline">Destaca tu negocio local ante miles de compradores en el Sumapaz.</span>
    </div>
    <a href="{{ route('merchant.banner.request') }}" class="font-extrabold text-[#047857] hover:text-emerald-800 hover:underline flex items-center gap-1 whitespace-nowrap">
      <span>Pautar mi banner comercial</span>
      <span class="material-symbols-outlined text-sm">arrow_forward</span>
    </a>
</div>

@php
  $rawPromoCards = \App\Models\Setting::getValue('promotional_cards');
  $activePromoCards = $rawPromoCards ? collect(json_decode($rawPromoCards, true))->where('is_active', true)->values() : collect();
@endphp

@if($activePromoCards->isNotEmpty())
<!-- ============================================================== -->
<!-- 2. TARJETAS PROMOCIONALES POR CATEGORÍA (GESTIONADAS POR ADMIN)-->
<!-- ============================================================== -->
<section class="max-w-7xl mx-auto px-4 md:px-6 pt-4 pb-2">
  <div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
      <span class="w-7 h-7 rounded-lg bg-emerald-100 text-[#006c47] flex items-center justify-center">
        <span class="material-symbols-outlined text-base">loyalty</span>
      </span>
      <h3 class="text-base font-extrabold font-heading text-slate-900">Promociones y Descuentos por Categoría</h3>
    </div>
    <span class="text-xs text-slate-500 font-semibold hidden sm:inline">Descuentos exclusivos de comerciantes del Sumapaz</span>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
    @foreach($activePromoCards->take(3) as $card)
      <a href="{{ route('consumer.catalog', ['category' => $card['category']]) }}" class="group block rounded-2xl p-5 bg-gradient-to-br {{ $card['bg_gradient'] ?? 'from-emerald-950 via-slate-900 to-emerald-900' }} text-white relative overflow-hidden shadow-md hover:scale-[1.01] transition-all">
        <div class="flex items-center justify-between gap-2 mb-2">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/20 backdrop-blur-sm border border-white/20">
            {{ $card['category'] }}
          </span>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-[#F97316] text-white shadow">
            {{ $card['discount_badge'] }}
          </span>
        </div>
        <h4 class="font-extrabold text-white text-base leading-snug font-heading mt-1 group-hover:text-emerald-300 transition-colors">
          {{ $card['title'] }}
        </h4>
        <p class="text-slate-300 text-xs line-clamp-2 mt-1 leading-relaxed">
          {{ $card['subtitle'] }}
        </p>
        <div class="mt-3.5 flex items-center justify-between text-xs text-emerald-300 font-bold">
          <span>Explorar {{ $card['category'] }}</span>
          <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
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
          Ofertas con Tiempo Límite — Estilo Alkosto & Mercado Libre
        </h3>
      </div>
      
      <a href="{{ route('consumer.catalog') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#10B981] hover:text-emerald-700 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-200 self-start sm:self-auto">
        <span>Ver todas las ofertas</span>
        <span class="material-symbols-outlined text-sm">arrow_forward</span>
      </a>
    </div>

    <!-- Rejilla de Ofertas Flash -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
      @foreach($featured->take(4) as $flashItem)
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
          <span class="material-symbols-outlined text-base">explore</span> Sumapaz Online-To-Offline (O2O)
        </span>
        <h3 class="text-2xl sm:text-4xl font-black text-white font-heading">
          Explora y Vive Fusa: Turismo, Glampings & Gastronomía
        </h3>
        <p class="text-slate-400 text-xs sm:text-sm mt-2">
          Reserva experiencias únicas en Chinauta, Arbeláez y Fusagasugá. Pagas en línea y recibes tu voucher con código QR para redimir presencialmente.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('consumer.catalog', ['category' => 'Turismo']) }}" class="px-5 py-2.5 bg-[#F97316] hover:bg-orange-600 text-white font-extrabold rounded-xl text-xs transition-all flex items-center gap-1.5 shadow-lg">
          <span class="material-symbols-outlined text-sm">holiday_village</span> Ver Glampings & Hoteles
        </a>
      </div>
    </div>

    <!-- Cards de Experiencias O2O -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- O2O 1: Glamping Chinauta Paradise -->
      <div class="bg-slate-800/90 border border-slate-700 rounded-2xl overflow-hidden flex flex-col group hover:border-[#10B981] transition-all">
        <div class="relative h-48 overflow-hidden">
          <img src="https://images.unsplash.com/photo-1587061949409-02df41d5e562?q=80&w=600&auto=format&fit=crop" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="Glamping Chinauta">
          <div class="absolute top-3 left-3 bg-emerald-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg">
            ⭐ 4.9 (Chinauta)
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
<!-- 5. BANNER PUBLICITARIO COMERCIAL DESTACADO -->
<!-- ========================================== -->
<section class="max-w-7xl mx-auto px-4 md:px-6 py-4">
  <div class="bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-900 border border-emerald-800/40 rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-xl">
    <div class="absolute -right-16 -bottom-16 w-64 h-64 rounded-full bg-[#10B981]/15 blur-2xl pointer-events-none"></div>
    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="space-y-1.5 text-center md:text-left">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-black rounded-full uppercase tracking-wider">
          <span class="material-symbols-outlined text-xs">campaign</span> Espacio Publicitario FusaShop
        </span>
        <h3 class="text-xl sm:text-2xl font-black font-heading text-white">
          ¿Tienes un Negocio o Marca en Fusagasugá?
        </h3>
        <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
          Pauta tu banner publicitario en la vitrina principal de FusaShop y llega a miles de compradores activos en todo Cundinamarca y Sumapaz.
        </p>
      </div>
      <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('merchant.banner.request') }}" class="px-5 py-2.5 bg-[#10B981] hover:bg-emerald-600 text-white font-extrabold text-xs rounded-xl shadow-lg transition-all flex items-center gap-2">
          <span class="material-symbols-outlined text-base">campaign</span> Pautar Mi Negocio
        </a>
      </div>
    </div>
  </div>
</section>

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
