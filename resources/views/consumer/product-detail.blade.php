@extends('layouts.app')
@section('title', $product->name)

@php
use Illuminate\Support\Facades\Storage;

$cats = [
    'Ropa'        => ['#7c3aed','#a855f7','rgba(124,58,237,.18)','rgba(168,85,247,.35)'],
    'Electrónica' => ['#1d4ed8','#06b6d4','rgba(29,78,216,.18)','rgba(6,182,212,.35)'],
    'Hogar'       => ['#d97706','#f59e0b','rgba(217,119,6,.18)','rgba(245,158,11,.35)'],
    'Deportes'    => ['#16a34a','#22c55e','rgba(22,163,74,.18)','rgba(34,197,94,.35)'],
    'Alimentos'   => ['#ca8a04','#84cc16','rgba(202,138,4,.18)','rgba(132,204,22,.35)'],
    'Belleza'     => ['#db2777','#f43f5e','rgba(219,39,119,.18)','rgba(244,63,94,.35)'],
    'Juguetes'    => ['#ea580c','#fb923c','rgba(234,88,12,.18)','rgba(251,146,60,.35)'],
    'Libros'      => ['#0f766e','#14b8a6','rgba(15,118,110,.18)','rgba(20,184,166,.35)'],
];
$c    = $cats[$product->category] ?? ['#006c47','#00b67a','rgba(0,108,71,.18)','rgba(0,182,122,.35)'];
$from = $c[0]; $to = $c[1]; $glow1 = $c[2]; $glow2 = $c[3];

$mainImageUrl = $product->image ? url('storage/' . $product->image) : null;
$hasImage     = !!$mainImageUrl;
$avgRating    = $product->reviews->avg('rating') ?? 0;
$icon = ['Ropa'=>'checkroom','Electrónica'=>'devices','Hogar'=>'chair','Deportes'=>'sports_soccer',
         'Alimentos'=>'lunch_dining','Belleza'=>'spa','Juguetes'=>'toys','Libros'=>'menu_book'][$product->category] ?? 'inventory_2';

$specIcons = [
    'material'   => 'texture', 'gender' => 'person', 'brand' => 'branding_watermark',
    'model'      => 'tag', 'screen' => 'screenshot_monitor', 'ram' => 'memory',
    'storage'    => 'storage', 'processor' => 'cpu', 'os' => 'settings_input_component',
    'weight'     => 'weight', 'dims' => 'straighten', 'exp' => 'event',
    'origin'     => 'public', 'age' => 'child_care', 'batteries' => 'battery_charging_full'
];

$specLabels = [
    'material'  => 'Material', 'gender' => 'Género', 'fit' => 'Corte',
    'brand'     => 'Marca', 'model' => 'Modelo', 'screen' => 'Pantalla',
    'ram'       => 'Memoria RAM', 'storage' => 'Almacenamiento', 'os' => 'S. Operativo',
    'exp'       => 'Vencimiento', 'origin' => 'Origen', 'dims' => 'Dimensiones',
    'age'       => 'Edad', 'batteries' => 'Baterías'
];
@endphp

@section('content')

{{-- HERO --}}
<div class="pd-hero">
  <div class="pd-bg" style="background: radial-gradient(ellipse 55% 55% at 78% 48%, {{ $glow1 }} 0%, transparent 70%), radial-gradient(ellipse 35% 40% at 22% 75%, {{ $glow2 }} 0%, transparent 65%), linear-gradient(145deg,#0c0e0d 0%,#111a15 100%);"></div>
  <canvas id="pd-canvas" class="pd-canvas" aria-hidden="true"></canvas>

  <div class="max-w-7xl mx-auto px-4 md:px-6 py-10 relative z-10">
    <nav class="flex items-center gap-2 text-xs text-white/50 mb-8 font-medium">
      <a href="{{ route('consumer.home') }}" class="hover:text-white transition-colors flex items-center gap-1 font-bold">
        <span class="material-symbols-outlined text-[14px]">home</span> Inicio
      </a>
      <span class="material-symbols-outlined text-[13px]">chevron_right</span>
      <a href="{{ route('consumer.catalog') }}" class="hover:text-white/80 transition-colors">Catálogo</a>
      <span class="material-symbols-outlined text-[13px]">chevron_right</span>
      <span>{{ $product->category }}</span>
      <span class="material-symbols-outlined text-[13px]">chevron_right</span>
      <span class="text-white/70 truncate max-w-[200px]">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
      {{-- Imagen --}}
      <div class="pd-stage-wrap sticky top-24">
        <div class="pd-stage" id="pd-stage">
          <div class="pd-product-img" id="pd-product-img">
            @if($hasImage)
              <img id="main-img" src="{{ $mainImageUrl }}" alt="{{ e($product->name) }}" class="pd-img" loading="eager" fetchpriority="high" decoding="async" onerror="this.style.display='none';document.getElementById('pd-fallback').style.removeProperty('display')"/>
              <div id="pd-fallback" class="pd-fallback" style="display:none">
                <span class="material-symbols-outlined" style="font-size:6rem;color:white;font-variation-settings:'FILL' 1">{{ $icon }}</span>
              </div>
            @else
              <div class="pd-fallback"><span class="material-symbols-outlined" style="font-size:6rem;color:white;font-variation-settings:'FILL' 1">{{ $icon }}</span></div>
            @endif
          </div>
          <div class="pd-shadow" style="background:{{ $from }}"></div>
        </div>

        @php
           $galleryImages = $product->images ?? [];
           if($product->available_options) {
             foreach($product->available_options as $opt) {
               if(($opt['type'] ?? '') === 'color') {
                 foreach($opt['values'] as $v) { if(is_array($v) && !empty($v['image'])) $galleryImages[] = $v['image']; }
               }
             }
           }
           $galleryImages = array_unique($galleryImages);
        @endphp

        @if(count($galleryImages) > 0)
          <div class="flex gap-2 mt-4 justify-center pd-fi" style="--d:.4s">
            @if($mainImageUrl)
              <button onclick="changeMainImg('{{ $mainImageUrl }}')" class="w-14 h-14 rounded-lg border-2 border-white/20 overflow-hidden hover:border-white transition-all bg-white/5"><img src="{{ $mainImageUrl }}" class="w-full h-full object-cover"></button>
            @endif
            @foreach($galleryImages as $img)
              @php $galleryUrl = url('storage/' . $img); @endphp
              <button onclick="changeMainImg('{{ $galleryUrl }}')" class="w-14 h-14 rounded-lg border-2 border-white/10 overflow-hidden hover:border-white transition-all bg-white/5"><img src="{{ $galleryUrl }}" class="w-full h-full object-cover"></button>
            @endforeach
          </div>
        @endif
      </div>

      {{-- Info --}}
      <div class="pd-info">
        <div class="flex items-center gap-2 flex-wrap pd-fi" style="--d:.05s">
          <span class="pd-badge" style="background:{{ $glow1 }};border-color:{{ $glow2 }}">{{ $product->category }}</span>
          @if($product->stock <= 5 && $product->stock > 0)
            <span class="pd-badge" style="background:rgba(252,211,77,.12);border-color:rgba(252,211,77,.3);color:#fde68a">⚠ Solo {{ $product->stock }} disp.</span>
          @elseif($product->stock <= 0)
            <span class="pd-badge" style="background:rgba(186,26,26,.12);border-color:rgba(186,26,26,.3);color:#fca5a5">Agotado</span>
          @else
            <span class="pd-badge" style="background:rgba(110,252,185,.1);border-color:rgba(110,252,185,.25);color:#6efcb9">✓ En stock</span>
          @endif
        </div>

        <h1 class="text-3xl md:text-5xl font-black text-white leading-tight pd-fi" style="--d:.1s">{{ e($product->name) }}</h1>

        <div class="flex items-center gap-2 pd-fi" style="--d:.14s">
          @for($i=1;$i<=5;$i++)
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' {{ $i<=round($avgRating)?1:0 }};color:{{ $i<=round($avgRating)?'#feb700':'rgba(255,255,255,.2)' }}">star</span>
          @endfor
          <span class="text-sm font-bold text-white ml-1">{{ number_format($avgRating,1) }}</span>
          <span class="text-sm text-white/40">({{ $product->reviews->count() }} reseñas)</span>
        </div>

        <div class="pd-fi" style="--d:.18s">
          <div class="flex items-baseline gap-2">
            <span class="text-5xl font-black text-white">${{ number_format($product->price,0,',','.') }}</span>
            <span class="text-sm text-white/40">COP</span>
          </div>
        </div>

        <a href="{{ route('consumer.merchant.profile', $product->merchant_id) }}" class="pd-merchant pd-fi" style="--d:.21s">
          @if($product->merchant->companyProfile?->logo_path)
            <img src="{{ Storage::url($product->merchant->companyProfile->logo_path) }}" class="w-10 h-10 rounded-full object-cover">
          @else
            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-black" style="background:{{ $from }}">{{ strtoupper(substr($product->merchant->name,0,1)) }}</div>
          @endif
          <div class="flex-1 min-w-0">
            <p class="text-[9px] font-black text-white/30 uppercase tracking-widest">Vendido por</p>
            <p class="font-bold text-sm text-white truncate">{{ e($product->merchant->companyProfile?->company_name ?? $product->merchant->name) }}</p>
          </div>
          <span class="material-symbols-outlined text-white/40">chevron_right</span>
        </a>

        @if($product->description)
          <p class="text-white/60 leading-relaxed text-sm pd-fi" style="--d:.24s">{{ e($product->description) }}</p>
        @endif

        {{-- Opciones --}}
        @if($product->available_options && count($product->available_options) > 0)
          <div class="space-y-6 pd-fi" style="--d:.26s">
            @foreach($product->available_options as $opt)
              @php $type = $opt['type'] ?? 'text'; @endphp
              <div>
                <p class="text-[10px] font-black text-white/30 uppercase mb-3">{{ $opt['name'] }}</p>
                <div class="flex flex-wrap gap-3">
                  @foreach($opt['values'] as $val)
                    @php 
                      $isColor = $type === 'color';
                      $vName = $isColor ? (is_array($val) ? ($val['name'] ?? 'Color') : $val) : $val;
                      $vHex = $isColor ? (is_array($val) ? ($val['hex'] ?? '#808080') : '#808080') : null;
                      $vImg = $isColor && is_array($val) && !empty($val['image']) ? url('storage/'.$val['image']) : '';
                      
                      if($isColor && !is_array($val)) {
                          $vHex = match(strtolower($val)) { 'blanco'=>'#FFFFFF','negro'=>'#000000','rojo'=>'#FF0000','azul'=>'#0000FF','verde'=>'#008000','amarillo'=>'#FFFF00','gris'=>'#808080',default=>'#808080' };
                      }
                      $vId = 'opt-'.Str::slug($opt['name']).'-'.Str::slug($vName);
                    @endphp
                    <div class="relative">
                      <input type="radio" id="{{ $vId }}" name="options-ui[{{ $opt['name'] }}]" value="{{ $vName }}" class="hidden peer" {{ $loop->first ? 'checked' : '' }} data-image="{{ $vImg }}" onchange="updateVariant(this, '{{ $opt['name'] }}')">
                      
                      @if($isColor)
                        <label for="{{ $vId }}" class="block w-10 h-10 rounded-full border-4 border-white/10 p-0.5 transition-all peer-checked:border-white peer-checked:scale-110 shadow-lg cursor-pointer" title="{{ $vName }}">
                          <div class="w-full h-full rounded-full" style="background:{{ $vHex }}"></div>
                        </label>
                      @else
                        <label for="{{ $vId }}" class="variant-btn block px-5 py-2.5 bg-white/5 border border-white/10 rounded-xl text-xs font-bold text-white/80 cursor-pointer peer-checked:bg-white peer-checked:text-black peer-checked:border-white peer-checked:scale-105 transition-all">
                          {{ $vName }}
                        </label>
                      @endif
                    </div>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        @endif

        {{-- Especificaciones Técnicas --}}
        @if($product->specifications && count(array_filter($product->specifications)) > 0)
          <div class="space-y-4 pd-fi" style="--d:.28s">
            <h3 class="text-[10px] font-black text-white/30 uppercase tracking-widest flex items-center gap-2">
              <span class="material-symbols-outlined text-[14px]">analytics</span> Especificaciones Técnicas
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              @foreach($product->specifications as $key => $val)
                @if(!empty($val))
                  <div class="flex items-center gap-3 p-4 rounded-2xl bg-white/5 border border-white/10 group hover:bg-white/10 transition-all">
                    <span class="material-symbols-outlined text-white/40 group-hover:text-primary transition-colors">{{ $specIcons[$key] ?? 'info' }}</span>
                    <div>
                      <p class="text-[9px] font-black text-white/30 uppercase leading-none mb-1">{{ $specLabels[$key] ?? ucwords($key) }}</p>
                      <p class="text-xs font-bold text-white">{{ $val }}</p>
                    </div>
                  </div>
                @endif
              @endforeach
            </div>
          </div>
        @endif

        @if($product->stock > 0)
          @auth
            @if(auth()->user()->isConsumer())
              <form id="add-to-cart-form" method="POST" action="{{ route('consumer.cart.add') }}" class="pd-fi" style="--d:.3s">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div id="selected-options-inputs">
                  @if($product->available_options)
                    @foreach($product->available_options as $opt)
                      @php $firstVal = $opt['values'][0]; $valName = is_array($firstVal) ? $firstVal['name'] : $firstVal; @endphp
                      <input type="hidden" name="options[{{ $opt['name'] }}]" id="opt-input-{{ Str::slug($opt['name']) }}" value="{{ $valName }}">
                    @endforeach
                  @endif
                </div>
                <div class="flex items-center gap-3">
                  <div class="pd-qty">
                    <button type="button" onclick="const i=document.getElementById('qty');if(i.value>1)i.value--" class="pd-qty-btn"><span class="material-symbols-outlined text-[18px]">remove</span></button>
                    <input type="number" id="qty" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="w-12 text-center bg-transparent border-0 font-black text-white focus:ring-0 text-sm p-0 m-0 [appearance:textfield]">
                    <button type="button" onclick="const i=document.getElementById('qty');if(i.value<{{ $product->stock }})i.value++" class="pd-qty-btn"><span class="material-symbols-outlined text-[18px]">add</span></button>
                  </div>
                  <button type="submit" class="add-to-cart" style="--background: linear-gradient(135deg,{{ $from }},{{ $to }}); --shadow: {{ $glow2 }}">
                    <span class="btn-text">Añadir al carrito</span>
                    <span class="btn-cart"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></span>
                    <span class="btn-done"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>¡Listo!</span>
                  </button>
                </div>
              </form>
            @endif
          @else
            <div class="pd-fi"><a href="{{ route('login') }}?intended={{ urlencode(request()->fullUrl()) }}" class="add-to-cart no-underline" style="--background: linear-gradient(135deg,{{ $from }},{{ $to }}); --shadow: {{ $glow2 }}"><span class="btn-text">Añadir al carrito</span></a></div>
          @endauth
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Rediseño de Opiniones Premium --}}
<section class="relative bg-surface py-20 border-t border-surface-container-low overflow-hidden">
  {{-- Sutil resplandor de fondo verde similar a la foto de referencia --}}
  <div class="absolute top-0 left-0 w-[500px] h-[500px] rounded-full pointer-events-none opacity-20 filter blur-[80px]" style="background: radial-gradient(circle, rgba(110,252,185,0.4) 0%, transparent 70%);"></div>

  <div class="max-w-4xl mx-auto px-4 md:px-6 relative z-10">
    
    {{-- Cabecera de la Sección --}}
    <div class="text-center mb-16">
      <div class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-[#6efcb9]/15 text-[#006c47] rounded-full text-xs font-extrabold mb-4 uppercase tracking-wider animate-pulse-soft">
        Comunidad FusaShop
      </div>
      <h2 class="text-3xl md:text-5xl font-black text-on-surface leading-tight tracking-tight mb-4">
        Lo que dicen de nosotros
      </h2>
      <p class="text-sm md:text-base text-on-surface-variant max-w-xl mx-auto leading-relaxed">
        Descubre las historias reales de nuestra comunidad. Productos locales seleccionados con amor, pensados para un estilo de vida consciente y sostenible en Fusagasugá.
      </p>
    </div>

    {{-- Grid de Estadísticas (4 Tarjetas) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-16">
      <div class="bg-white dark:bg-surface-container-low border border-surface-container/60 rounded-2xl p-5 text-center shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-md transition-all duration-300">
        <p class="text-2xl md:text-3xl font-black text-[#006c47] tracking-tight mb-1">
          {{ $avgRating > 0 ? number_format($avgRating, 1) : '5.0' }}/5
        </p>
        <p class="text-[10px] md:text-xs font-bold text-on-surface-variant uppercase tracking-wider">
          Valoración Media
        </p>
      </div>

      <div class="bg-white dark:bg-surface-container-low border border-surface-container/60 rounded-2xl p-5 text-center shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-md transition-all duration-300">
        <p class="text-2xl md:text-3xl font-black text-[#006c47] tracking-tight mb-1">
          {{ max($product->reviews->count(), 0) }}
        </p>
        <p class="text-[10px] md:text-xs font-bold text-on-surface-variant uppercase tracking-wider">
          Clientes Felices
        </p>
      </div>

      <div class="bg-white dark:bg-surface-container-low border border-surface-container/60 rounded-2xl p-5 text-center shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-md transition-all duration-300">
        <p class="text-2xl md:text-3xl font-black text-[#006c47] tracking-tight mb-1">
          100%
        </p>
        <p class="text-[10px] md:text-xs font-bold text-on-surface-variant uppercase tracking-wider">
          Apoyo Local
        </p>
      </div>

      <div class="bg-white dark:bg-surface-container-low border border-surface-container/60 rounded-2xl p-5 text-center shadow-[0_4px_20px_rgba(0,0,0,0.02)] hover:shadow-md transition-all duration-300">
        <p class="text-2xl md:text-3xl font-black text-[#006c47] tracking-tight mb-1">
          24h
        </p>
        <p class="text-[10px] md:text-xs font-bold text-on-surface-variant uppercase tracking-wider">
          Envío Promedio
        </p>
      </div>
    </div>

    {{-- Lista de Opiniones (Testimonios) --}}
    <div class="space-y-6 mb-16">
      @forelse($product->reviews as $rev)
        @php
          // Generar avatar dinámico único basado en el nombre del usuario
          $hue = crc32($rev->user->name ?? 'A') % 360;
          $avatarBg = "hsl({$hue}, 40%, 93%)";
          $avatarText = "hsl({$hue}, 60%, 25%)";
        @endphp
        <div class="bg-white dark:bg-surface-container-low border border-surface-container/50 rounded-2xl p-6 md:p-8 shadow-[0_8px_30px_rgba(27,28,28,0.03)] hover:shadow-lg transition-all duration-300 relative group">
          
          {{-- Comillas estilizadas (Adorno de fondo en top right) --}}
          <div class="absolute top-6 right-8 text-5xl font-serif text-[#6efcb9]/25 select-none transition-transform duration-300 group-hover:scale-110">
            ”
          </div>

          {{-- Estrellas de la reseña --}}
          <div class="flex gap-0.5 mb-4">
            @for($i=1;$i<=5;$i++)
              <span class="material-symbols-outlined text-[20px]" 
                    style="font-variation-settings:'FILL' {{ $i<=$rev->rating?1:0 }}; color: {{ $i<=$rev->rating?'#feb700':'rgba(0,0,0,0.1)' }}">
                star
              </span>
            @endfor
          </div>

          {{-- Comentario / Texto de la reseña --}}
          <blockquote class="text-sm md:text-base text-on-surface font-medium leading-relaxed mb-6 italic">
            "{{ e($rev->comment) }}"
          </blockquote>

          {{-- Información de Autor --}}
          <div class="flex items-center gap-3.5 pt-4 border-t border-surface-container/40">
            @if($rev->user->avatar)
              <img src="{{ Storage::url($rev->user->avatar) }}" class="w-11 h-11 rounded-full object-cover shadow-inner">
            @else
              <div class="w-11 h-11 rounded-full flex items-center justify-center font-black text-sm shrink-0 shadow-inner"
                   style="background: {{ $avatarBg }}; color: {{ $avatarText }}">
                {{ strtoupper(substr($rev->user->name ?? 'A', 0, 1)) }}
              </div>
            @endif
            
            <div>
              <p class="font-black text-sm text-on-surface leading-tight">
                {{ e($rev->user->name) }}
              </p>
              <div class="flex items-center gap-1 mt-1 text-[10px] md:text-xs text-primary font-bold">
                <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'wght' 600">verified</span>
                Comprador Verificado
              </div>
            </div>
          </div>

        </div>
      @empty
        <div class="bg-white dark:bg-surface-container-low border border-dashed border-surface-container rounded-2xl py-12 text-center shadow-[0_4px_20px_rgba(0,0,0,0.01)]">
          <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-3" style="font-variation-settings:'FILL' 1">rate_review</span>
          <p class="text-sm text-on-surface-variant/60 font-semibold italic">Aún no hay reseñas. ¡Sé el primero en calificar este producto!</p>
        </div>
      @endforelse
    </div>

    {{-- Bloque CTA de Compartir Experiencia con Alpine.js para la reseña interactiva --}}
    <div x-data="{ openForm: false }" 
         class="border border-[#6efcb9]/20 rounded-3xl p-8 md:p-12 text-center shadow-xl relative overflow-hidden group"
         style="background: linear-gradient(135deg, #003822 0%, #002214 100%);">
      
      {{-- Decoración de Fondo --}}
      <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-[#6efcb9]/5 pointer-events-none filter blur-2xl transition-transform duration-500 group-hover:scale-125"></div>
      
      <div x-show="!openForm" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="max-w-lg mx-auto">
        <h3 class="text-xl md:text-3xl font-black text-white mb-3">
          ¿Quieres compartir tu experiencia?
        </h3>
        <p class="text-xs md:text-sm text-white/70 leading-relaxed mb-6">
          Tu opinión ayuda a otros miembros de nuestra comunidad a tomar decisiones más conscientes.
        </p>

        @auth
          @if($hasPurchased)
            <button @click="openForm = true" 
                    class="px-8 py-3 font-black rounded-full transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-md"
                    style="background-color: #6efcb9; color: #003822;">
              Escribir una reseña
            </button>
          @else
            <div class="inline-flex flex-col items-center gap-2 max-w-sm mx-auto">
              <span class="px-4 py-2 bg-white/5 border border-white/10 rounded-2xl text-[11px] md:text-xs text-white/80 font-bold leading-normal">
                ℹ Solo puedes calificar los productos que has comprado y recibido. ¡Apoya lo local comprando ahora!
              </span>
            </div>
          @endif
        @else
          <a href="{{ route('login') }}?intended={{ urlencode(request()->fullUrl()) }}" 
             class="inline-block px-8 py-3 font-black rounded-full transition-all duration-300 transform hover:scale-105 active:scale-95 no-underline shadow-md"
             style="background-color: #6efcb9; color: #003822;">
            Iniciar sesión para opinar
          </a>
        @endauth
      </div>

      {{-- Formulario Interactivo (Alpine.js) --}}
      @auth
        @if($hasPurchased)
          <div x-show="openForm" 
               x-transition:enter="transition ease-out duration-300" 
               x-transition:enter-start="opacity-0 scale-95" 
               x-transition:enter-end="opacity-100 scale-100" 
               class="max-w-lg mx-auto text-left"
               x-cloak>
            
            <div class="flex items-center justify-between mb-6 border-b border-white/10 pb-4">
              <h4 class="text-lg font-black text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-light">rate_review</span> Escribir una Reseña
              </h4>
              <button @click="openForm = false" class="text-white/60 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-lg">close</span>
              </button>
            </div>

            <form method="POST" action="{{ route('consumer.product.review', $product->id) }}" class="space-y-6">
              @csrf
              
              {{-- Selección de Calificación Interactiva con estrellas --}}
              <div class="space-y-2">
                <label class="block text-xs font-bold text-white/80 uppercase tracking-wider">Tu Calificación</label>
                <div x-data="{ rating: 5, hoverRating: 0 }" class="flex items-center gap-1.5 py-2">
                  <input type="hidden" name="rating" :value="rating">
                  <template x-for="i in 5">
                    <button type="button" 
                            @click="rating = i" 
                            @mouseover="hoverRating = i" 
                            @mouseleave="hoverRating = 0"
                            class="transition-transform duration-100 active:scale-90 hover:scale-110 focus:outline-none">
                      <span class="material-symbols-outlined text-3xl cursor-pointer"
                            :style="'font-variation-settings:\'FILL\' ' + (i <= (hoverRating || rating) ? 1 : 0) + '; color: ' + (i <= (hoverRating || rating) ? '#feb700' : 'rgba(255,255,255,0.25)')">
                        star
                      </span>
                    </button>
                  </template>
                  <span class="text-xs font-extrabold text-white/60 ml-2" x-text="rating + ' / 5 Estrellas'"></span>
                </div>
              </div>

              {{-- Comentario --}}
              <div class="space-y-2">
                <label for="comment" class="block text-xs font-bold text-white/80 uppercase tracking-wider">Tu Comentario</label>
                <textarea id="comment" name="comment" rows="4" required
                          class="w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white placeholder-white/40 focus:ring-2 focus:ring-[#6efcb9]/50 focus:border-[#6efcb9] focus:outline-none transition-all resize-none"
                          placeholder="Cuéntanos qué te pareció el producto, el envío o la atención..."></textarea>
              </div>

              <div class="flex items-center gap-3 pt-2">
                <button type="submit" 
                        class="flex-1 py-3 px-6 font-black rounded-full transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-md text-center text-sm"
                        style="background-color: #6efcb9; color: #003822;">
                  Enviar Valoración
                </button>
                <button type="button" @click="openForm = false" class="py-3 px-6 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold rounded-full transition-all text-sm">
                  Cancelar
                </button>
              </div>

            </form>
          </div>
        @endif
      @endauth

    </div>

  </div>
</section>

<style>
.pd-hero { position: relative; overflow: hidden; padding-bottom: 3rem; contain: layout style; }
.pd-bg { position: absolute; inset: 0; }
.pd-canvas { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; opacity: .6; }
.pd-stage-wrap { display: flex; flex-direction: column; align-items: center; }
.pd-stage { position: relative; width: min(420px, 92vw); aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; }
.pd-product-img { position: relative; z-index: 2; width: 80%; height: 80%; display: flex; align-items: center; justify-content: center; will-change: transform; animation: pd-img-float 5.5s ease-in-out infinite; filter: drop-shadow(0 24px 48px rgba(0,0,0,.45)); }
@keyframes pd-img-float { 0%,100% { transform: translateY(0) rotate(-1deg); } 50% { transform: translateY(-12px) rotate(1deg); } }
.pd-img { width:100%; height:100%; object-fit:contain; border-radius:.75rem; transition: opacity .3s; }
.pd-fallback { display:flex; align-items:center; justify-content:center; width:100%; height:100%; }
.pd-shadow { position: absolute; bottom: 5%; left: 50%; width: 46%; height: 14px; border-radius: 50%; filter: blur(14px); will-change: transform, opacity; animation: pd-shadow 5.5s ease-in-out infinite; transform: translateX(-50%); opacity: .4; }
@keyframes pd-shadow { 0%,100% { transform:translateX(-50%) scaleX(1); opacity:.4; } 50% { transform:translateX(-50%) scaleX(.72); opacity:.2; } }
.pd-info { display:flex; flex-direction:column; gap:1.5rem; }
.pd-badge { display: inline-flex; padding: .2rem .8rem; border-radius: 9999px; border: 1px solid; color: white; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
.pd-merchant { display: flex; align-items: center; gap: .75rem; padding: 1rem; border-radius: 1rem; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.07); text-decoration: none; transition: background .2s; }
.pd-qty { display: flex; align-items: center; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: .875rem; overflow: hidden; }
.pd-qty-btn { padding: .75rem 1rem; color: rgba(255,255,255,.6); background: transparent; border: none; cursor: pointer; }
/* Nuevo diseño premium para añadir al carrito */
.add-to-cart { display: inline-grid; place-items: center; position: relative; overflow: hidden; padding: 0 24px; background: var(--background); color: white; border-radius: .875rem; border: none; font-size: 16px; font-weight: 800; cursor: pointer; height: 50px; min-width: 180px; box-shadow: 0 8px 28px var(--shadow); transition: transform 0.2s ease, filter 0.2s; user-select: none; flex: 1; }
.add-to-cart:hover { transform: translateY(-2px); filter: brightness(1.05); }
.add-to-cart:active { transform: translateY(1px); }
.add-to-cart > span { grid-area: 1 / 1; display: flex; align-items: center; justify-content: center; width: 100%; gap: 6px; }
.btn-text { transform: translateX(0); opacity: 1; }
.btn-cart, .btn-done { transform: translateX(-150%); opacity: 0; }
.add-to-cart.added .btn-text { animation: textSequence 2.8s forwards; }
.add-to-cart.added .btn-cart { animation: cartSequence 2.8s forwards; }
.add-to-cart.added .btn-done { animation: doneSequence 2.8s forwards; }
@keyframes textSequence { 0% { transform: translateX(0); opacity: 1; animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1); } 15% { transform: translateX(150%); opacity: 0; } 84% { transform: translateX(150%); opacity: 0; } 85% { transform: translateX(-150%); opacity: 0; animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1); } 100% { transform: translateX(0); opacity: 1; } }
@keyframes cartSequence { 0% { transform: translateX(-150%); opacity: 0; } 14% { transform: translateX(-150%); opacity: 0; animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1); } 28% { transform: translateX(0); opacity: 1; animation-timing-function: linear; } 42% { transform: translateX(15px); opacity: 1; animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1); } 56% { transform: translateX(150%); opacity: 0; } 100% { transform: translateX(150%); opacity: 0; } }
@keyframes doneSequence { 0% { transform: translateX(-150%); opacity: 0; } 55% { transform: translateX(-150%); opacity: 0; animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1); } 69% { transform: translateX(0); opacity: 1; animation-timing-function: linear; } 83% { transform: translateX(0); opacity: 1; animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1); } 97% { transform: translateX(150%); opacity: 0; } 100% { transform: translateX(150%); opacity: 0; } }
.pd-fi { opacity: 0; transform: translateY(14px); animation: pd-fi-kf .5s cubic-bezier(.22,1,.36,1) var(--d,0s) forwards; }
@keyframes pd-fi-kf { to { opacity:1; transform:translateY(0); } }

/* CLASES DE APOYO PARA SELECCIÓN */
.variant-btn.selected {
  background-color: white !important;
  color: black !important;
  border-color: white !important;
  transform: scale(1.05);
}
</style>

<script>
(function() {
  const canvas = document.getElementById('pd-canvas');
  if (canvas) {
    const ctx = canvas.getContext('2d'), hero = canvas.parentElement;
    let rx = -999, ry = -999, px = -999, py = -999;
    function res() { canvas.width = hero.offsetWidth; canvas.height = hero.offsetHeight; }
    res(); new ResizeObserver(res).observe(hero);
    hero.addEventListener('mousemove', e => { const r = hero.getBoundingClientRect(); px = e.clientX-r.left; py = e.clientY-r.top; }, {passive:true});
    function d() { rx+=(px-rx)*0.1; ry+=(py-ry)*0.1; ctx.clearRect(0,0,canvas.width,canvas.height); if(rx>0){ const g = ctx.createRadialGradient(rx,ry,0,rx,ry,260); g.addColorStop(0,'{{ $glow2 }}'); g.addColorStop(0.5,'{{ $glow1 }}'); g.addColorStop(1,'transparent'); ctx.fillStyle=g; ctx.fillRect(0,0,canvas.width,canvas.height); } requestAnimationFrame(d); }
    d();
  }
  const stage = document.getElementById('pd-stage'), imgEl = document.getElementById('pd-product-img');
  if (stage && imgEl) {
    stage.addEventListener('mousemove', e => { const r = stage.getBoundingClientRect(); const x = (e.clientX-r.left-r.width/2)/(r.width/2), y = (e.clientY-r.top-r.height/2)/(r.height/2); imgEl.style.transform = `rotateY(${x*13}deg) rotateX(${y*-9}deg) translateY(-10px)`; }, {passive:true});
    stage.addEventListener('mouseleave', () => imgEl.style.transform='');
  }
  window.changeMainImg = u => { const m = document.getElementById('main-img'); if(m && u && u !== ''){ m.style.opacity='0'; setTimeout(()=> { m.src=u; m.style.opacity='1'; }, 200); } };
  
  window.updateVariant = (r, n) => { 
    // Sincronizar con el formulario oculto para el carrito
    const s = n.toLowerCase().replace(/[^a-z0-9]/g,'-');
    const h = document.getElementById('opt-input-'+s); 
    if(h) h.value=r.value; 
    
    // Cambiar imagen si existe
    const img = r.getAttribute('data-image');
    if(img && img !== '' && img !== 'null') {
      changeMainImg(img);
    }

    // ACTUALIZAR ESTADO VISUAL MANUALMENTE (Para mayor seguridad)
    const container = r.closest('.flex-wrap');
    if(container) {
       container.querySelectorAll('.variant-btn').forEach(btn => btn.classList.remove('selected'));
       const label = container.querySelector(`label[for="${r.id}"]`);
       if(label) label.classList.add('selected');
    }
  };

  // Inicializar el estado "selected" para los primeros elementos
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="radio"]:checked').forEach(r => {
      const container = r.closest('.flex-wrap');
      if(container) {
        const label = container.querySelector(`label[for="${r.id}"]`);
        if(label) label.classList.add('selected');
      }
    });
  });

  const cf = document.getElementById('add-to-cart-form');
  if (cf) { cf.addEventListener('submit', async e => { e.preventDefault(); const b = cf.querySelector('.add-to-cart'); if(b.classList.contains('added')) return; b.classList.add('added'); try { const r = await fetch(cf.action,{method:'POST',body:new FormData(cf),headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''}}); const d = await r.json(); if(d.success){ const ba = document.querySelector('.cart-badge'); if(ba) ba.textContent=d.cart_count; } setTimeout(()=>b.classList.remove('added'),2800); } catch(e){ b.classList.remove('added'); } }); }
})();
</script>
@endsection
