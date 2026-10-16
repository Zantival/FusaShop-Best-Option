@extends('layouts.app')
@section('title', e($profile->company_name) . ' — Tienda')
@section('content')

<div class="max-w-7xl mx-auto px-4 md:px-6 py-8">

  {{-- ─── Hero Banner Carousel ─────────────────────────── --}}
  @php
    $banners = $profile->banners_path;
    $hasBanners = is_array($banners) && count($banners) > 0;
  @endphp

  <div class="rounded-3xl overflow-hidden mb-8 shadow-sm bg-surface">
    @if($hasBanners)
      <div id="hero-carousel" class="relative w-full">
        <div class="grid w-full">
          @foreach($banners as $i => $b)
            @php
              $path     = is_array($b) ? ($b['path'] ?? '')     : $b;
              $title    = is_array($b) ? ($b['title'] ?? '')    : '';
              $subtitle = is_array($b) ? ($b['subtitle'] ?? '') : '';
            @endphp
            <div class="carousel-slide col-start-1 row-start-1 transition-opacity duration-700 {{ $i === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0' }}" data-index="{{ $i }}">
              <img src="{{ url('files/' . $path) }}" alt="{{ $title }}" class="w-full h-auto block">
              
              @if($title || $subtitle)
                <div class="absolute bottom-0 left-0 right-0 p-8 md:p-12 bg-gradient-to-t from-black/40 to-transparent pointer-events-none">
                  <div class="inline-block">
                    @if($title)<h2 class="text-2xl md:text-4xl font-black text-white drop-shadow-lg mb-2">{{ $title }}</h2>@endif
                    @if($subtitle)<p class="text-sm md:text-base text-white/90 font-medium">{{ $subtitle }}</p>@endif
                  </div>
                </div>
              @endif
            </div>
          @endforeach
        </div>
        @if(count($banners) > 1)
          <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
            @foreach($banners as $i => $b)
              <button onclick="goToSlide({{ $i }})" data-index="{{ $i }}" class="carousel-dot w-2 h-2 rounded-full bg-white/40 transition-all {{ $i === 0 ? '!bg-white w-6' : '' }}"></button>
            @endforeach
          </div>
          <button onclick="prevSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-all z-20">
            <span class="material-symbols-outlined">chevron_left</span>
          </button>
          <button onclick="nextSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-all z-20">
            <span class="material-symbols-outlined">chevron_right</span>
          </button>
        @endif
      </div>
    @else
      <div class="h-full bg-greenhouse-gradient flex items-center justify-center relative overflow-hidden">
        <span class="material-symbols-outlined absolute -bottom-8 -right-8 text-[250px] text-white/10">storefront</span>
        <p class="text-white font-black text-2xl relative z-10">{{ e($profile->company_name) }}</p>
      </div>
    @endif
  </div>

  {{-- ─── Merchant Profile Header ──────────────────────── --}}
  <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-surface-container mb-8">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
      <!-- Logo -->
      <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl overflow-hidden border-2 border-surface-container shrink-0">
        @if($profile->logo_path)
          <img src="{{ url('files/' . $profile->logo_path) }}" alt="Logo" class="w-full h-full object-cover">
        @else
          <div class="w-full h-full bg-greenhouse-gradient flex items-center justify-center text-white text-3xl font-black">
            {{ strtoupper(substr($profile->company_name, 0, 1)) }}
          </div>
        @endif
      </div>

      <!-- Info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-1">
          <h1 class="text-2xl font-black text-on-surface">{{ e($profile->company_name) }}</h1>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#6efcb9]/20 text-primary text-[10px] font-black rounded-full uppercase tracking-wider">
            <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1">verified</span> Verificada
          </span>
        </div>
        <p class="text-on-surface-variant text-sm leading-relaxed">{{ $profile->description ?: 'Empresa local de Fusagasugá comprometida con la calidad.' }}</p>
        @if($profile->address)
          <div class="flex items-center gap-1.5 mt-2 text-on-surface-variant text-xs">
            <span class="material-symbols-outlined text-[16px] text-primary">location_on</span>
            {{ $profile->address }}
          </div>
        @endif
      </div>

      <!-- Actions -->
      <div class="flex flex-col gap-2 w-full md:w-auto shrink-0">
        @if($profile->phone)
          <a href="tel:{{ $profile->phone }}" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:opacity-90 transition-all">
            <span class="material-symbols-outlined text-[18px]">phone</span> Llamar
          </a>
        @endif
        @php
          $waNumber = $profile->whatsapp ?: $profile->phone;
          $waClean = preg_replace('/\D/', '', $waNumber ?? '');
        @endphp
        @if($waClean)
          <a href="https://wa.me/57{{ $waClean }}?text={{ urlencode('Hola, vi tu tienda ' . $profile->company_name . ' en FusaShop y me gustaría saber más.') }}"
             target="_blank"
             class="flex items-center justify-center gap-2 px-5 py-2.5 bg-[#25D366] text-white text-sm font-bold rounded-xl hover:opacity-90 transition-all">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.507 3.927 1.397 5.591L0 24l6.545-1.714A11.943 11.943 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.844 0-3.579-.477-5.095-1.316L2 22l1.333-4.834A9.955 9.955 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
            WhatsApp
          </a>
        @endif
        @auth
          @if(!auth()->user()->isMerchant() && !auth()->user()->isAnalyst())
            <a href="{{ route('chat.show', $profile->merchant_id) }}"
               class="flex items-center justify-center gap-2 px-5 py-2.5 border-2 border-surface-container text-on-surface text-sm font-semibold rounded-xl hover:border-primary hover:text-primary transition-all">
              <span class="material-symbols-outlined text-[18px]">chat</span> Chat
            </a>
          @endif
        @endauth
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Product Grid -->
    <div class="lg:col-span-2">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-on-surface flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">grid_view</span>
          Productos ({{ $products->total() }})
        </h2>
      </div>
      @if($products->isEmpty())
        <div class="text-center py-20 bg-white rounded-2xl border-2 border-dashed border-surface-container-highest">
          <span class="material-symbols-outlined text-4xl text-on-surface-variant/30">inventory_2</span>
          <p class="font-bold text-on-surface mt-4 mb-1">Sin productos aún</p>
          <p class="text-sm text-on-surface-variant">Este comerciante está cargando su catálogo.</p>
        </div>
      @else
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          @foreach($products as $p)
            <a href="{{ route('consumer.product', $p->id) }}"
               class="bg-white rounded-2xl overflow-hidden shadow-sm border border-surface-container hover:shadow-md hover:-translate-y-0.5 transition-all group">
              <div class="aspect-square overflow-hidden bg-surface-container-low">
                <x-product-image :product="$p" class="w-full h-full object-cover group-hover:scale-[0.85] transition-transform duration-300"/>
              </div>
              <div class="p-4">
                <p class="text-[9px] font-black text-primary uppercase tracking-wider mb-1">{{ $p->category }}</p>
                <p class="font-bold text-sm text-on-surface line-clamp-2 leading-tight mb-2">{{ $p->name }}</p>
                <p class="text-lg font-black text-primary">${{ number_format($p->price, 0, ',', '.') }}</p>
              </div>
            </a>
          @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
      @endif
    </div>

    <!-- Sidebar: Map + Info -->
    <div class="space-y-6">

      {{-- Dynamic Google Map with Directions --}}
      @if($profile->latitude && $profile->longitude)
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-surface-container">
          <div class="p-4 border-b border-surface-container flex items-center justify-between">
            <h3 class="font-bold text-on-surface flex items-center gap-2">
              <span class="material-symbols-outlined text-primary text-[20px]">location_on</span> Ubicación
            </h3>
            <span id="location-status" class="text-[9px] font-bold uppercase text-on-surface-variant flex items-center gap-1"></span>
          </div>
          
          <div id="consumer-map" class="w-full aspect-video bg-surface-container-low"></div>
          
          <div class="p-4 space-y-4">
            <div id="route-info" class="hidden animate-in fade-in duration-500">
              <div class="flex items-center justify-between mb-3 p-3 bg-primary/5 rounded-xl border border-primary/10">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-sm">directions_car</span>
                  </div>
                  <div>
                    <p class="text-[10px] font-bold text-primary uppercase leading-tight">Tiempo estimado</p>
                    <p id="travel-time" class="text-sm font-black text-on-surface">-</p>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-[10px] font-bold text-primary uppercase leading-tight">Distancia</p>
                  <p id="travel-distance" class="text-sm font-black text-on-surface">-</p>
                </div>
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <button onclick="calculateRoute('DRIVING')" id="btn-route" 
                      class="w-full py-3 bg-primary-gradient text-white font-bold rounded-2xl shadow-lg shadow-primary/20 hover:opacity-95 transition-all flex items-center justify-center gap-2 text-sm">
                <span class="material-symbols-outlined text-[18px]">directions</span>
                ¿Cómo llegar ahora?
              </button>
              
              <div id="travel-modes" class="hidden grid grid-cols-3 gap-2">
                <button onclick="calculateRoute('DRIVING')" class="mode-btn active py-2 rounded-xl border-2 border-primary bg-primary/5 text-primary flex items-center justify-center" title="Carro">
                  <span class="material-symbols-outlined">directions_car</span>
                </button>
                <button onclick="calculateRoute('WALKING')" class="mode-btn py-2 rounded-xl border-2 border-surface-container text-on-surface-variant flex items-center justify-center" title="A pie">
                  <span class="material-symbols-outlined">directions_walk</span>
                </button>
                <button onclick="calculateRoute('TRANSIT')" class="mode-btn py-2 rounded-xl border-2 border-surface-container text-on-surface-variant flex items-center justify-center" title="Transporte">
                  <span class="material-symbols-outlined">directions_bus</span>
                </button>
              </div>

              <div class="grid grid-cols-2 gap-2 mt-1">
                @if($profile->phone)
                <a href="https://wa.me/57{{ ltrim($profile->phone, '+570') }}?text=Hola%2C%20vengo%20desde%20FusaShop.%20Me%20gustar%C3%ADa%20hacer%20una%20consulta." 
                   target="_blank"
                   class="w-full py-2.5 bg-[#25D366] text-white font-bold rounded-2xl hover:bg-[#1da851] transition-all flex items-center justify-center gap-2 text-xs shadow-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824zm-3.423-14.416c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm.029 18.88c-1.161 0-2.305-.292-3.318-.844l-3.677.964.984-3.595c-.607-1.052-.927-2.246-.926-3.468.005-5.804 4.729-10.528 10.539-10.528 5.808 0 10.538 4.724 10.538 10.528.001 5.804-4.724 10.528-10.538 10.528z"/></svg>
                  WhatsApp
                </a>
                @endif
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $profile->latitude }},{{ $profile->longitude }}" 
                   target="_blank"
                   class="w-full py-2.5 border-2 border-surface-container text-on-surface-variant font-bold rounded-2xl hover:bg-surface-container-low transition-all flex items-center justify-center gap-2 text-xs">
                  <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                  Maps App
                </a>
              </div>
            </div>
          </div>
        </div>
      @elseif($profile->address)
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-surface-container">
          <div class="aspect-video">
            <iframe src="https://maps.google.com/maps?q={{ urlencode($profile->address . ', Fusagasugá, Colombia') }}&t=&z=15&ie=UTF8&iwloc=&output=embed" class="w-full h-full border-0"></iframe>
          </div>
          <div class="p-4">
            <p class="text-xs text-on-surface-variant">{{ $profile->address }}</p>
          </div>
        </div>
      @endif

      {{-- Contact Info --}}
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-surface-container">
        <h3 class="font-bold text-on-surface mb-4">Contacto</h3>
        <div class="space-y-3">
          @if($profile->phone)
            <div class="flex items-center gap-3 text-sm">
              <div class="w-8 h-8 bg-surface-container-low rounded-lg flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-[16px]">phone</span>
              </div>
              <span class="text-on-surface-variant">{{ $profile->phone }}</span>
            </div>
          @endif
          @if($profile->address)
            <div class="flex items-center gap-3 text-sm">
              <div class="w-8 h-8 bg-surface-container-low rounded-lg flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-[16px]">location_on</span>
              </div>
              <span class="text-on-surface-variant">{{ $profile->address }}</span>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- WhatsApp FAB (mobile, always visible) -->
@if($waClean)
  <a href="https://wa.me/57{{ $waClean }}?text={{ urlencode('Hola, vi tu tienda ' . $profile->company_name . ' en FusaShop.') }}"
     target="_blank" class="whatsapp-fab md:hidden" title="WhatsApp" aria-label="Contactar por WhatsApp">
    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.507 3.927 1.397 5.591L0 24l6.545-1.714A11.943 11.943 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.844 0-3.579-.477-5.095-1.316L2 22l1.333-4.834A9.955 9.955 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
  </a>
@endif

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const dots = document.querySelectorAll('.carousel-dot');

function showSlide(n) {
  if (!slides.length) return;
  slides.forEach(s => { s.classList.add('opacity-0'); s.classList.remove('opacity-100'); });
  dots.forEach(d => { d.classList.remove('!bg-white', 'w-6'); d.classList.add('bg-white/40'); });
  currentSlide = (n + slides.length) % slides.length;
  slides[currentSlide].classList.remove('opacity-0'); slides[currentSlide].classList.add('opacity-100');
  if (dots[currentSlide]) { dots[currentSlide].classList.add('!bg-white', 'w-6'); dots[currentSlide].classList.remove('bg-white/40'); }
}

function nextSlide() { showSlide(currentSlide + 1); }
function prevSlide() { showSlide(currentSlide - 1); }
function goToSlide(n) { showSlide(n); }
if (slides.length > 1) setInterval(nextSlide, 5000);

// --- GEOLOCATION LOGIC (Leaflet + OpenStreetMap) ---
let map, merchantMarker, routeLayer;
const mLat = {{ $profile->latitude ?: 0 }};
const mLng = {{ $profile->longitude ?: 0 }};

function initStoreMap() {
  const mapEl = document.getElementById('consumer-map');
  if (!mapEl || mLat === 0 || mLng === 0) return;
  
  if (typeof L === 'undefined') {
    mapEl.innerHTML = '<div class="p-4 text-error font-bold text-center">No se pudo cargar el mapa.</div>';
    return;
  }

  // Inicializar mapa
  map = L.map('consumer-map', { zoomControl: false }).setView([mLat, mLng], 15);
  L.control.zoom({ position: 'bottomright' }).addTo(map);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
  }).addTo(map);

  // Custom Icon
  const customIcon = L.divIcon({
    className: '',
    html: '<div style="width:36px;height:36px;background:linear-gradient(135deg,#006c47,#00b67a);border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 4px 12px rgba(0,108,71,.4);"></div>',
    iconSize: [36, 36],
    iconAnchor: [18, 36],
  });

  merchantMarker = L.marker([mLat, mLng], { icon: customIcon }).addTo(map)
    .bindPopup('<b>{{ e($profile->company_name) }}</b>').openPopup();

  setTimeout(function() { map.invalidateSize(); }, 200);
}

function calculateRoute(mode = 'driving') {
  const btn = document.getElementById('btn-route');
  const status = document.getElementById('location-status');
  
  btn.disabled = true;
  btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[18px]">sync</span> Obteniendo ubicación...';
  status.innerHTML = '<span class="animate-pulse">Buscando GPS...</span>';

  if (!navigator.geolocation) {
    alert("Tu navegador no soporta geolocalización.");
    resetBtn();
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const uLat = position.coords.latitude;
      const uLng = position.coords.longitude;

      // OSRM Public API for routing (Free)
      // profiles: driving, walking, cycling
      let osrmProfile = 'driving';
      if (mode === 'WALKING') osrmProfile = 'foot';
      
      const url = `https://router.project-osrm.org/route/v1/${osrmProfile}/${uLng},${uLat};${mLng},${mLat}?overview=full&geometries=geojson`;

      fetch(url)
        .then(response => response.json())
        .then(data => {
          resetBtn();
          if (data.code === 'Ok' && data.routes.length > 0) {
            const route = data.routes[0];
            
            // Draw route on map
            if (routeLayer) map.removeLayer(routeLayer);
            
            routeLayer = L.geoJSON(route.geometry, {
              style: { color: '#006c47', weight: 6, opacity: 0.8 }
            }).addTo(map);

            // Fit bounds to show entire route
            map.fitBounds(routeLayer.getBounds(), { padding: [50, 50] });

            // User marker
            L.circleMarker([uLat, uLng], {
              radius: 8, fillColor: '#3b82f6', color: '#fff', weight: 3, opacity: 1, fillOpacity: 1
            }).addTo(map).bindPopup("Tu ubicación");

            // UI Updates
            document.getElementById('route-info').classList.remove('hidden');
            document.getElementById('travel-modes').classList.remove('hidden');
            document.getElementById('btn-route').classList.add('hidden');
            
            // Convert seconds to mins/hours
            let mins = Math.round(route.duration / 60);
            let timeTxt = mins < 60 ? mins + ' min' : Math.floor(mins/60) + 'h ' + (mins%60) + 'm';
            
            // Convert meters to km
            let distTxt = (route.distance / 1000).toFixed(1) + ' km';

            document.getElementById('travel-time').innerText = timeTxt;
            document.getElementById('travel-distance').innerText = distTxt;
            status.innerHTML = '<span class="text-primary">Ruta trazada</span>';

            // Update active button
            document.querySelectorAll('.mode-btn').forEach(b => {
               b.classList.remove('active', 'border-primary', 'bg-primary/5', 'text-primary');
               b.classList.add('border-surface-container', 'text-on-surface-variant');
               
               // Re-add active class to the button matching the selected mode
               if (b.getAttribute('onclick').includes(mode)) {
                 b.classList.add('active', 'border-primary', 'bg-primary/5', 'text-primary');
                 b.classList.remove('border-surface-container', 'text-on-surface-variant');
               }
            });
          } else {
            alert("No se pudo calcular la ruta desde tu ubicación.");
          }
        })
        .catch(err => {
          console.error(err);
          resetBtn();
          alert("Error interno al calcular la ruta.");
        });
    },
    (error) => {
      resetBtn();
      status.innerText = "";
      switch(error.code) {
        case error.PERMISSION_DENIED: alert("Por favor activa los permisos de ubicación."); break;
        case error.POSITION_UNAVAILABLE: alert("Ubicación no disponible."); break;
        case error.TIMEOUT: alert("Tiempo de espera agotado."); break;
      }
    },
    { enableHighAccuracy: true }
  );
}

function resetBtn() {
  const btn = document.getElementById('btn-route');
  btn.disabled = false;
  btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">directions</span> ¿Cómo llegar ahora?';
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initStoreMap);
} else {
  initStoreMap();
}
</script>
@endpush
@endsection
