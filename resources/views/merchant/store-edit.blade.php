@extends('layouts.app')
@section('title', 'Mi Tienda - Personalización')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
  <form id="store-form" method="POST" action="{{ route('merchant.profile.store') }}" enctype="multipart/form-data" class="space-y-8">
    @csrf
    @if($profile && $profile->rut_path)
      <input type="hidden" name="rut_exists" value="1">
    @endif

    {{-- ─── Perfil Estilo Consumidor con Edición ────────────────────── --}}
    <div class="bg-white rounded-3xl overflow-hidden shadow-sm mb-10 transition-shadow hover:shadow-md relative group/profile">
      
      {{-- Banner Area --}}
      @php 
        $hasBanner = false;
        $banners = [];
        if ($profile && $profile->banners_path) {
            $banners = is_string($profile->banners_path) ? json_decode($profile->banners_path, true) : $profile->banners_path;
            if (is_array($banners) && count($banners) > 0) {
                $hasBanner = true;
                $firstBanner = $banners[0];
                $bannerUrl = Storage::url(is_array($firstBanner) ? ($firstBanner['path'] ?? '') : $firstBanner);
            }
        }
      @endphp

      <div id="banner-preview" class="h-48 md:h-72 w-full relative bg-cover bg-center transition-all duration-500 {{ !$hasBanner ? 'bg-primary-gradient' : '' }}" 
           style="{{ $hasBanner ? "background-image: url('$bannerUrl');" : "" }}">
        @if(!$hasBanner)
          <span class="material-symbols-outlined absolute -bottom-10 -right-10 text-[300px] text-white opacity-10">storefront</span>
        @endif
        
        <div class="absolute inset-0 bg-black/20 group-hover/profile:bg-black/50 transition-all flex items-center justify-center opacity-0 group-hover/profile:opacity-100">
          <label class="cursor-pointer bg-black/40 backdrop-blur-md text-white px-6 py-3 rounded-2xl font-bold border border-white/20 hover:bg-black/60 transition-all flex items-center gap-2 text-sm shadow-xl">
            <span class="material-symbols-outlined text-lg">add_a_photo</span>
            Actualizar Banners y Textos
            <input type="file" id="banners-input" name="banners[]" multiple accept="image/*" class="hidden" onchange="previewBanners(this)">
          </label>
        </div>
      </div>

      {{-- Header Details --}}
      <div class="px-8 pb-8 flex flex-col md:flex-row items-center md:items-start gap-6 relative">
        {{-- Logo --}}
        <div class="w-32 h-32 md:w-44 md:h-44 rounded-full border-4 border-white overflow-hidden bg-white shrink-0 -mt-16 md:-mt-22 shadow-lg relative group/logo">
          @if($profile && $profile->logo_path)
            <img id="logo-img" src="{{ Storage::url($profile->logo_path) }}" alt="Logo" class="w-full h-full object-cover">
          @else
            <div id="logo-placeholder" class="w-full h-full flex items-center justify-center bg-surface-container">
              <span class="material-symbols-outlined text-5xl text-on-surface-variant">storefront</span>
            </div>
          @endif
          
          <label class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center opacity-0 group-hover/logo:opacity-100 cursor-pointer transition-all text-white">
            <span class="material-symbols-outlined text-3xl mb-1">photo_camera</span>
            <span class="text-[10px] font-bold uppercase tracking-wider">Cambiar Logo</span>
            <input type="file" name="logo" accept="image/*" class="hidden" onchange="previewLogo(this)">
          </label>
        </div>

        {{-- Name and Description Inputs --}}
        <div class="text-center md:text-left mt-2 flex-1 w-full">
          <div class="relative group/name inline-block w-full">
            <input type="text" name="company_name" value="{{ old('company_name', $profile->company_name ?? '') }}"
                   class="w-full md:w-auto bg-transparent border-b-2 border-transparent hover:border-primary/30 focus:border-primary text-3xl font-['Manrope'] font-extrabold text-on-surface focus:ring-0 p-0 transition-all cursor-text"
                   placeholder="Nombre de tu tienda (Ej: El Oasis de las Flores)" required>
            <span class="material-symbols-outlined absolute -right-8 top-1/2 -translate-y-1/2 text-primary opacity-0 group-hover/name:opacity-100 text-sm">edit</span>
          </div>
          
          <div class="mt-2 flex flex-col md:flex-row items-center md:items-start gap-1 md:gap-4">
            <p class="text-on-surface-variant text-sm flex items-center gap-1 shrink-0">
              <span class="material-symbols-outlined text-sm align-middle text-primary">verified</span> Empresa verificada
            </p>
            <div class="flex items-center gap-2 group/owner">
              <span class="material-symbols-outlined text-sm text-primary">person</span>
              <input type="text" name="owner_name" value="{{ old('owner_name', auth()->user()->name) }}" 
                     class="bg-transparent border-none p-0 text-sm font-semibold text-on-surface-variant focus:ring-0 w-auto hover:bg-surface-container-low rounded px-1 transition-colors"
                     placeholder="Nombre del representante" required>
              <span class="material-symbols-outlined text-[10px] text-primary opacity-0 group-hover/owner:opacity-100">edit</span>
            </div>
          </div>

          <div class="mt-6 relative group/desc">
            <textarea name="description" rows="5"
                      class="w-full bg-surface-container-low border-none rounded-2xl p-4 text-on-surface-variant leading-relaxed focus:ring-2 focus:ring-primary/20 shadow-inner resize-none"
                      placeholder="Escribe aquí la historia o descripción de tu tienda para tus clientes...">{{ old('description', $profile->description ?? '') }}</textarea>
            <span class="material-symbols-outlined absolute right-4 top-4 text-primary opacity-30 text-sm">edit</span>
          </div>
        </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      {{-- Sidebar: Contact & Status --}}
      <div class="space-y-6">
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30">
          <h3 class="font-['Manrope'] font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">contact_support</span> Ubicación y Contacto
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Teléfono de Contacto</label>
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-primary text-sm">phone</span>
                <input type="text" name="phone" value="{{ old('phone', $profile->phone ?? '') }}"
                       class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/20"
                       placeholder="+57..." required>
              </div>
            </div>
            <div>
              <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">WhatsApp (Solo números)</label>
              <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#25D366] text-sm">chat</span>
                <input type="text" name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp ?? '') }}"
                       class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border-none rounded-xl text-sm focus:ring-2 focus:ring-[#25D366]/20"
                       placeholder="3001234567">
              </div>
            </div>

            <div class="pt-2">
              <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Mapa de Ubicación</label>
              <p class="text-[10px] text-on-surface-variant mb-2">Busca tu dirección o mueve el marcador para ubicar tu tienda.</p>
              
              <div class="relative mb-3">
                <span class="material-symbols-outlined absolute left-3 top-3 text-on-surface-variant text-sm z-10">search</span>
                <input id="pac-input" type="text" placeholder="Buscar dirección..." autocomplete="off"
                       class="w-full pl-10 pr-10 py-2.5 bg-surface-container-low border-none rounded-xl text-xs focus:ring-2 focus:ring-primary/20">
                <button type="button" id="gps-btn" title="Usar mi ubicación actual"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-primary hover:text-primary/70 transition-colors p-1 rounded-lg hover:bg-primary/10">
                  <span class="material-symbols-outlined text-sm">my_location</span>
                </button>
                {{-- Sugerencias Nominatim --}}
                <ul id="pac-suggestions" class="hidden absolute top-full left-0 w-full z-50 bg-white rounded-xl shadow-xl border border-outline-variant/20 mt-1 max-h-48 overflow-y-auto"></ul>
              </div>

              <div id="map" class="w-full h-64 rounded-2xl border border-surface-container overflow-hidden bg-surface-container-low"></div>
              
              <input type="hidden" name="latitude" id="lat-input" value="{{ old('latitude', $profile->latitude ?? '4.3361') }}">
              <input type="hidden" name="longitude" id="lng-input" value="{{ old('longitude', $profile->longitude ?? '-74.3638') }}">
              
              <div class="mt-3">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Dirección Escrita</label>
                <div class="relative">
                  <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-primary text-sm">location_on</span>
                  <input type="text" name="address" id="address-input" value="{{ old('address', $profile->address ?? '') }}"
                         class="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/20"
                         placeholder="Cra 10 # 3-45, Fusagasugá">
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Banner Request --}}
        <div class="bg-primary-gradient rounded-3xl p-6 text-white shadow-lg overflow-hidden relative">
          <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-8xl opacity-20">campaign</span>
          <h3 class="font-['Manrope'] font-bold text-lg mb-2">¿Quieres más visibilidad?</h3>
          <p class="text-white/80 text-sm mb-4">Solicita un banner premium en la página principal para atraer a más clientes.</p>
          <a href="{{ route('merchant.banner.request') }}" class="inline-flex w-full items-center justify-center gap-2 px-4 py-3 bg-white text-primary font-bold rounded-2xl hover:bg-opacity-90 transition-all shadow-md">
            <span class="material-symbols-outlined text-sm">add_photo_alternate</span> Solicitar Banner Global
          </a>
        </div>
      </div>

      {{-- Main: Gallery & Documents --}}
      <div class="lg:col-span-2 space-y-6">
        {{-- Secondary Banners Preview and Text Inputs --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30">
          <h3 class="font-['Manrope'] font-bold text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">gallery_thumbnail</span> Configuración de Banners
          </h3>
          
          {{-- Banner Deletion Option --}}
          @if($hasBanner)
          <label class="flex items-center gap-2 text-sm text-on-surface-variant cursor-pointer mb-6 bg-red-50 p-3 rounded-xl border border-red-200/50">
            <input type="checkbox" name="delete_banners" value="1" class="rounded text-red-600 focus:ring-red-500">
            <span class="font-bold text-red-700">Eliminar todos los banners y empezar de cero</span>
          </label>
          @endif

          <div id="banner-customizer" class="space-y-6">
            @forelse($banners as $index => $b)
              @php 
                $path = is_array($b) ? ($b['path'] ?? '') : $b;
                $title = is_array($b) ? ($b['title'] ?? '') : '';
                $subtitle = is_array($b) ? ($b['subtitle'] ?? '') : '';
              @endphp
              <div class="flex flex-col md:flex-row gap-4 p-4 rounded-2xl bg-surface-container-low border border-outline-variant/10">
                <div class="w-full md:w-40 h-24 rounded-lg bg-white overflow-hidden shrink-0 shadow-sm flex items-center justify-center">
                  <img src="{{ Storage::url($path) }}" class="max-w-full max-h-full object-contain">
                </div>
                <div class="flex-1 space-y-3">
                  <div class="text-xs font-bold text-primary uppercase">Banner #{{ $index + 1 }} - Especificaciones Actuales</div>
                  <input type="text" name="banner_titles[]" value="{{ $title }}" placeholder="Título del banner (ej: Gran Oferta de Mayo)" 
                         class="w-full bg-white border-0 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 shadow-sm">
                  <input type="text" name="banner_subtitles[]" value="{{ $subtitle }}" placeholder="Descripción corta (ej: Hasta 50% de descuento)" 
                         class="w-full bg-white border-0 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 shadow-sm">
                </div>
              </div>
            @empty
              <div id="empty-state" class="py-10 text-center bg-surface-container-low rounded-3xl border-2 border-dashed border-outline-variant">
                <span class="material-symbols-outlined text-on-surface-variant opacity-30 text-4xl">add_photo_alternate</span>
                <p class="text-on-surface-variant text-sm mt-2">No has configurado banners aún.</p>
              </div>
            @endforelse
          </div>
          
          <div id="new-banners-preview" class="mt-6 space-y-6 hidden">
             <div class="text-xs font-bold text-[#006c47] uppercase border-b border-primary/20 pb-2 mb-4">Nuevos Banners a Cargar</div>
             <div id="new-banners-list" class="space-y-4"></div>
          </div>
          
          <p class="text-[11px] text-on-surface-variant mt-6 italic">Nota: Al subir nuevas imágenes, estas reemplazarán por completo la galería actual. Máximo 3 banners.</p>
        </div>

        @if(!$profile || !$profile->rut_path)
          <div class="bg-secondary-container/20 rounded-3xl p-6 border border-secondary/20">
            <h3 class="font-['Manrope'] font-bold text-on-surface mb-2 flex items-center gap-2">
              <span class="material-symbols-outlined text-secondary">description</span> Verificación de Identidad (RUT)
            </h3>
            <p class="text-sm text-on-surface-variant mb-4">Para poder publicar productos, necesitamos validar la legalidad de tu empresa.</p>
            <input type="file" name="rut_file" accept=".pdf"
                   class="w-full text-sm text-on-surface-variant file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-secondary file:text-white hover:file:opacity-90 cursor-pointer" required>
          </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-4 pt-4">
          <button type="submit" class="flex-1 py-4 bg-primary-gradient text-white font-bold rounded-2xl shadow-xl shadow-primary/20 hover:opacity-95 active:scale-95 transition-all flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Guardar Cambios de la Tienda
          </button>
          <a href="{{ route('merchant.dashboard') }}" class="btn-secondary py-4 px-8 justify-center min-w-[140px]">
             Volver
          </a>
        </div>
      </div>
    </div>
  </form>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
// ─── Leaflet + OpenStreetMap (Gratuito, sin API key) ─────────────────────────
let map, marker;

function initMap() {
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  if (typeof L === 'undefined') { 
    mapEl.innerHTML = '<div class="p-4 text-error font-bold flex items-center gap-2"><span class="material-symbols-outlined">error</span> Leaflet.js no pudo cargar. Verifica la conexión o el AdBlock.</div>';
    console.error('Leaflet no cargó'); 
    return; 
  }

  try {

  const lat = parseFloat(document.getElementById('lat-input').value) || 4.3361;
  const lng = parseFloat(document.getElementById('lng-input').value) || -74.3638;

  map = L.map('map', { zoomControl: true }).setView([lat, lng], 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(map);

  const customIcon = L.divIcon({
    className: '',
    html: '<div style="width:36px;height:36px;background:linear-gradient(135deg,#006c47,#00b67a);border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 4px 12px rgba(0,108,71,.4);"></div>',
    iconSize: [36, 36],
    iconAnchor: [18, 36],
  });

  marker = L.marker([lat, lng], { draggable: true, icon: customIcon }).addTo(map);

  marker.on('dragend', function() {
    const pos = marker.getLatLng();
    updateCoords(pos.lat, pos.lng);
    reverseGeocode(pos.lat, pos.lng);
  });

  map.on('click', function(e) {
    marker.setLatLng(e.latlng);
    updateCoords(e.latlng.lat, e.latlng.lng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });

  // Fix: forzar render correcto después de que el contenedor sea visible
  setTimeout(function() { map.invalidateSize(); }, 200);

  } catch(e) {
    mapEl.innerHTML = '<div class="p-4 text-error font-bold flex items-center gap-2"><span class="material-symbols-outlined">error</span> Error del mapa: ' + e.message + '</div>';
    console.error('Map init error:', e);
  }
}

function updateCoords(lat, lng) {
  document.getElementById('lat-input').value = lat.toFixed(7);
  document.getElementById('lng-input').value = lng.toFixed(7);
}

function reverseGeocode(lat, lng) {
  fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&addressdetails=1', {
    headers: { 'Accept-Language': 'es' }
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data && data.display_name) {
      document.getElementById('address-input').value = data.display_name;
    }
  })
  .catch(function() {});
}

// ─── Autocomplete de búsqueda Nominatim ──────────────────────────────────────
var searchInput = document.getElementById('pac-input');
var suggestionsList = document.getElementById('pac-suggestions');
var nominatimTimer;

if (searchInput) {
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      var firstSuggestion = suggestionsList.querySelector('li');
      if (firstSuggestion && !suggestionsList.classList.contains('hidden')) {
        firstSuggestion.click();
      } else {
        var q = this.value.trim().replace(/#/g, ' ');
        if (q.length >= 3) {
          searchInput.disabled = true;
          fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&q=' + encodeURIComponent(q) + '&countrycodes=co&limit=1')
          .then(function(r){ return r.json(); })
          .then(function(results){
            searchInput.disabled = false;
            if (results.length > 0) {
              var lat = parseFloat(results[0].lat);
              var lng = parseFloat(results[0].lon);
              map.setView([lat, lng], 16);
              marker.setLatLng([lat, lng]);
              updateCoords(lat, lng);
              document.getElementById('address-input').value = results[0].display_name;
              searchInput.value = results[0].display_name.split(',')[0];
            } else {
              alert('No se encontró el lugar. Intenta mover el marcador manualmente.');
            }
          }).catch(function(){ searchInput.disabled = false; });
        }
      }
    }
  });

  searchInput.addEventListener('input', function() {
    clearTimeout(nominatimTimer);
    var q = this.value.trim();
    if (q.length < 3) { suggestionsList.innerHTML = ''; suggestionsList.classList.add('hidden'); return; }
    
    // Mejorar búsqueda para direcciones en Colombia (reemplazar # por espacio, que Nominatim entiende mejor)
    var searchQuery = q.replace(/#/g, ' ');

    nominatimTimer = setTimeout(function() {
      fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&q=' + encodeURIComponent(searchQuery) + '&countrycodes=co&limit=5&addressdetails=1', {
        headers: { 'Accept-Language': 'es' }
      })
      .then(function(r) { return r.json(); })
      .then(function(results) {
        suggestionsList.innerHTML = '';
        if (!results.length) { 
          suggestionsList.innerHTML = '<li class="px-4 py-3 text-sm text-error bg-error/10 font-bold">No se encontró la dirección exacta. Intenta buscar solo tu barrio o calle, y luego arrastra el marcador manualmente.</li>';
          suggestionsList.classList.remove('hidden');
          return; 
        }
        results.forEach(function(place) {
          var li = document.createElement('li');
          li.className = 'px-4 py-3 text-sm text-on-surface cursor-pointer hover:bg-surface-container flex items-center gap-2 transition-colors border-b border-outline-variant/10 last:border-0';
          li.innerHTML = '<span class="material-symbols-outlined text-primary text-sm shrink-0">location_on</span><span class="truncate">' + place.display_name + '</span>';
          li.addEventListener('click', function() {
            var lat = parseFloat(place.lat);
            var lng = parseFloat(place.lon);
            map.setView([lat, lng], 17);
            marker.setLatLng([lat, lng]);
            updateCoords(lat, lng);
            document.getElementById('address-input').value = place.display_name;
            searchInput.value = place.display_name.split(',')[0];
            suggestionsList.innerHTML = '';
            suggestionsList.classList.add('hidden');
          });
          suggestionsList.appendChild(li);
        });
        suggestionsList.classList.remove('hidden');
      })
      .catch(function() { suggestionsList.classList.add('hidden'); });
    }, 500);
  });

  document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !suggestionsList.contains(e.target)) {
      suggestionsList.classList.add('hidden');
    }
  });
}

// ─── Botón GPS ────────────────────────────────────────────────────────────────
var gpsBtn = document.getElementById('gps-btn');
if (gpsBtn) {
  gpsBtn.addEventListener('click', function() {
    if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización.'); return; }
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">sync</span>';
    navigator.geolocation.getCurrentPosition(
      function(pos) {
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;
        map.setView([lat, lng], 17);
        marker.setLatLng([lat, lng]);
        updateCoords(lat, lng);
        reverseGeocode(lat, lng);
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">my_location</span>';
      },
      function() {
        alert('No se pudo obtener tu ubicación. Verifica los permisos del navegador.');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">my_location</span>';
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  });
}

// ─── Submit handler ───────────────────────────────────────────────────────────
var storeForm = document.querySelector('#store-form');
if (storeForm) {
  storeForm.onsubmit = function() {
    var btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.classList.add('opacity-70', 'cursor-not-allowed');
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">sync</span> Guardando...';
  };
}

// ─── Inicializar mapa cuando Leaflet esté listo ───────────────────────────────
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMap);
} else {
  initMap();
}

})(); // IIFE
</script>
@endpush

@push('scripts')
<script>
function previewLogo(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var img = document.getElementById('logo-img');
      var placeholder = document.getElementById('logo-placeholder');
      if (img) {
        img.src = e.target.result;
      } else if (placeholder) {
        var newImg = document.createElement('img');
        newImg.id = 'logo-img';
        newImg.src = e.target.result;
        newImg.className = 'w-full h-full object-cover';
        placeholder.parentNode.replaceChild(newImg, placeholder);
      }
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function previewBanners(input) {
  var container = document.getElementById('new-banners-preview');
  var list = document.getElementById('new-banners-list');
  var bannerPreviewHero = document.getElementById('banner-preview');
  if (input.files && input.files.length > 0) {
    container.classList.remove('hidden');
    list.innerHTML = '';
    Array.from(input.files).slice(0, 3).forEach(function(file, index) {
      var reader = new FileReader();
      reader.onload = function(e) {
        if (index === 0) {
          bannerPreviewHero.style.backgroundImage = "url('" + e.target.result + "')";
          bannerPreviewHero.classList.remove('bg-primary-gradient');
        }
        var item = document.createElement('div');
        item.className = 'flex flex-col md:flex-row gap-4 p-4 rounded-2xl bg-surface-container-low border border-outline-variant/10';
        item.innerHTML = '<div class="w-full md:w-40 h-24 rounded-lg bg-white overflow-hidden shrink-0 shadow-sm flex items-center justify-center"><img src="' + e.target.result + '" class="max-w-full max-h-full object-contain"></div><div class="flex-1 space-y-2"><div class="text-[10px] font-bold text-primary uppercase">Nuevo Banner #' + (index + 1) + '</div><input type="text" name="banner_titles[]" placeholder="Título" class="w-full bg-white border-0 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 shadow-sm"><input type="text" name="banner_subtitles[]" placeholder="Descripción" class="w-full bg-white border-0 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 shadow-sm"></div>';
        list.appendChild(item);
      };
      reader.readAsDataURL(file);
    });
  }
}
</script>
@endpush

<style>
.cursor-edit { cursor: pointer; border-radius: 4px; }
.cursor-edit:hover { background-color: rgba(0, 108, 71, 0.05); }
.cursor-edit:focus { background-color: white; outline: 1px solid #006c47; }
</style>
@endsection
