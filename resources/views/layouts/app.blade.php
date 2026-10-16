<!DOCTYPE html>
<html lang="es" class="light">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','FusaShop') | FusaShop — El epicentro de compras, turismo y negocios del Sumapaz</title>

{{-- Optimizaciones de carga --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="{{ asset('assets/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}" defer></script>
<script src="{{ asset('assets/js/alpine.min.js') }}" defer></script>
<style>
  [x-cloak] { display: none !important; visibility: hidden !important; }
  body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
  h1, h2, h3, .font-heading { font-family: 'Plus Jakarta Sans', 'Manrope', sans-serif; }
  
  /* Brand colors */
  :root {
    --fusa-green: #10B981;
    --fusa-green-dark: #047857;
    --fusa-orange: #F97316;
  }
</style>
@stack('styles')
</head>
<body class="bg-[#f7fcf8] text-slate-800 min-h-screen flex flex-col antialiased" x-data="{ omniFilter: 'all' }">

@php
  $isAdmin = request()->is('admin*') || (auth()->check() && auth()->user()->isAnalyst() && !request()->routeIs('consumer.*') && !request()->is('catalogo*') && !request()->is('producto*') && !request()->is('carrito*') && !request()->is('checkout*'));
@endphp

<!-- ========================================== -->
<!-- 1. FRANJA SUPERIOR (TOP BAR)               -->
<!-- ========================================== -->
@if(!$isAdmin)
<div class="bg-slate-900 text-slate-300 text-[11px] py-1.5 px-4 md:px-6 border-b border-slate-800 relative z-[60]">
  <div class="max-w-7xl mx-auto flex items-center justify-between gap-3 flex-wrap">
    <!-- Selector de ubicación y cobertura -->
    <div class="flex items-center gap-2">
      <span class="material-symbols-outlined text-[15px] text-[#10B981]">location_on</span>
      <span class="text-slate-400">Cobertura Sumapaz:</span>
      <div id="location-selector" class="relative inline-block">
        <button id="location-selector-toggle" aria-expanded="false" aria-controls="location-selector-menu" type="button" class="font-bold text-white hover:text-[#10B981] flex items-center gap-1 transition-colors">
          <span id="current-location-text">Fusagasugá Centro</span>
          <span class="material-symbols-outlined text-[14px]">keyboard_arrow_down</span>
        </button>
        <div id="location-selector-menu" aria-hidden="true" style="display: none" class="absolute left-0 mt-1.5 w-60 bg-white text-slate-800 rounded-xl shadow-2xl border border-slate-100 py-2 z-[100] text-xs">
          <div class="px-3 py-1 font-bold text-[10px] text-slate-400 uppercase tracking-wider">Municipio / Sector de Entrega</div>
          <button type="button" data-location="Fusagasugá Centro" class="w-full text-left px-3 py-2 hover:bg-slate-50 font-semibold text-[#10B981] flex items-center justify-between">
            <span>📍 Fusagasugá Centro</span>
            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">Express 2h</span>
          </button>
          <button type="button" data-location="Chinauta" class="w-full text-left px-3 py-2 hover:bg-slate-50 flex items-center justify-between text-slate-700">
            <span>📍 Chinauta (Villas & Glamping)</span>
            <span class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">Mismo día</span>
          </button>
          <button type="button" data-location="Silvania" class="w-full text-left px-3 py-2 hover:bg-slate-50 text-slate-700">📍 Silvania</button>
          <button type="button" data-location="Arbeláez" class="w-full text-left px-3 py-2 hover:bg-slate-50 text-slate-700">📍 Arbeláez</button>
          <button type="button" data-location="Pasca" class="w-full text-left px-3 py-2 hover:bg-slate-50 text-slate-700">📍 Pasca</button>
          <button type="button" data-location="San Bernardo" class="w-full text-left px-3 py-2 hover:bg-slate-50 text-slate-700">📍 San Bernardo</button>
        </div>
      </div>
    </div>

    <!-- Acceso B2B -->
    <div class="flex items-center gap-4 ml-auto">
      <a href="{{ route('consumer.catalog', ['category' => 'Mercado Mayorista (B2B)']) }}" class="flex items-center gap-1 text-emerald-400 hover:text-white font-bold transition-colors">
        <span class="material-symbols-outlined text-[14px]">storefront</span>
        <span>Venta por Mayor / Proveedores (B2B)</span>
      </a>
    </div>
  </div>
</div>
@endif

<!-- ========================================== -->
<!-- 2. BARRA PRINCIPAL (MAIN NAVBAR PÚBLICA)   -->
<!-- ========================================== -->
@if(!$isAdmin)
<header id="main-header" class="bg-white border-b border-slate-200 sticky top-0 w-full z-50 shadow-sm transition-all duration-200">
    <!-- ============================================== -->
    <!-- NAVBAR NORMAL PARA CLIENTES / TIENDA PÚBLICA   -->
    <!-- ============================================== -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-2.5 flex items-center justify-between gap-3 md:gap-6">
      
      <!-- Logotipo Oficial Oficial -->
      <div class="flex items-center gap-3">
        <button onclick="toggleMobileMenu()" id="hamburger-btn" class="md:hidden p-1.5 text-slate-600 hover:text-slate-900 rounded-lg" aria-label="Abrir menú">
          <span class="material-symbols-outlined text-2xl">menu</span>
        </button>
        <x-logo size="default" :with-microcopy="false" />
      </div>

      <!-- Omnibar: Buscador Inteligente con predictivo y búsqueda avanzada -->
      <div class="flex-1 max-w-2xl hidden md:block">
        <form action="{{ route('consumer.catalog') }}" method="GET" class="relative flex items-center">
          <div class="relative flex-1 flex items-center bg-slate-100 rounded-2xl border border-slate-200 focus-within:border-[#10B981] focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100 transition-all overflow-hidden shadow-inner">
            <span class="material-symbols-outlined text-slate-400 pl-3.5 text-xl">search</span>
            <input type="text" 
                   name="search" 
                   id="omnibar-input"
                   value="{{ request('search') }}"
                   placeholder="Buscar productos, restaurantes, glampings o viveros del Sumapaz..." 
                   class="w-full bg-transparent border-none py-2.5 pl-2.5 pr-20 text-xs md:text-sm text-slate-800 placeholder-slate-400 focus:ring-0 outline-none">
            
            <!-- Botones de voz e imagen (Omnibar extras) -->
            <div class="absolute right-2 flex items-center gap-1">
              <button type="button" onclick="startVoiceSearch()" title="Búsqueda por voz" class="p-1 text-slate-400 hover:text-[#10B981] rounded-full hover:bg-slate-200 transition-colors">
                <span class="material-symbols-outlined text-base">mic</span>
              </button>
              <button type="button" onclick="openVisualSearchModal()" title="Búsqueda por imagen" class="p-1 text-slate-400 hover:text-[#F97316] rounded-full hover:bg-slate-200 transition-colors">
                <span class="material-symbols-outlined text-base">photo_camera</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Acciones Derechas Comprador -->
      <div class="flex items-center gap-2 sm:gap-3">
        
        @auth
          <!-- Campanita de Notificaciones -->
          <div id="notification-menu" class="relative">
            <button id="notification-menu-toggle" aria-expanded="false" aria-controls="notif-dropdown" type="button" class="relative p-2 rounded-full hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors" title="Notificaciones">
              <span class="material-symbols-outlined text-2xl">notifications</span>
              @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
              @if($unreadCount > 0)
                <span class="absolute top-1 right-1 bg-[#F97316] text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full border-2 border-white leading-none">
                  {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
              @endif
            </button>

            <!-- Dropdown Notificaciones con pestañas -->
            <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 z-[70] overflow-hidden">
              <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-[#10B981]">notifications_active</span> Notificaciones
                </h3>
                <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-[#10B981] hover:underline">Ver centro</a>
              </div>
              <div class="flex border-b border-slate-100 text-xs font-semibold bg-white">
                <button type="button" data-notification-tab="all" aria-selected="true" class="flex-1 py-2 text-center text-[#10B981] border-b-2 border-[#10B981]">Todos</button>
                <button type="button" data-notification-tab="orders" aria-selected="false" class="flex-1 py-2 text-center text-slate-500">Pedidos</button>
                <button type="button" data-notification-tab="coins" aria-selected="false" class="flex-1 py-2 text-center text-slate-500">FusaCoins</button>
              </div>
              <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 text-xs">
                @forelse(auth()->user()->notifications()->take(6)->get() as $notif)
                  @php
                    $notifUrl = $notif->data['url'] ?? route('notifications.index');
                    if (($notif->data['type'] ?? '') === 'merchant_sales_report' && !str_contains($notifUrl, 'notification_id=')) {
                      $notifUrl .= (str_contains($notifUrl, '?') ? '&' : '?') . 'notification_id=' . $notif->id;
                    }
                  @endphp
                  <a href="{{ $notifUrl }}" class="p-3 hover:bg-slate-50 transition-colors flex items-start gap-2.5 {{ $notif->read_at ? 'opacity-70' : 'bg-emerald-50/40' }}">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-[#10B981] flex items-center justify-center shrink-0 mt-0.5">
                      <span class="material-symbols-outlined text-sm">{{ $notif->data['icon'] ?? 'mail' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-bold text-slate-800 truncate">{{ $notif->data['title'] ?? 'Actualización de Pedido' }}</p>
                      <p class="text-slate-600 line-clamp-2 mt-0.5">{{ $notif->data['message'] ?? 'Tu estado ha cambiado en FusaShop.' }}</p>
                      <div class="flex items-center justify-between mt-1">
                        <span class="text-[10px] text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>
                        @if(($notif->data['type'] ?? '') === 'merchant_sales_report')
                          <span class="text-[10px] font-extrabold text-[#006c47]">Abrir Reporte →</span>
                        @endif
                      </div>
                    </div>
                  </a>
                @empty
                  <div class="py-10 text-center text-slate-400">
                    <span class="material-symbols-outlined text-3xl mb-1 text-slate-300">notifications_none</span>
                    <p>Sin notificaciones por ahora</p>
                  </div>
                @endforelse
              </div>
            </div>
          </div>

          <!-- Carrito Dinámico con Split Inteligente (Productos Físicos vs Reservas O2O) -->
          @if(auth()->user()->isConsumer())
            @php
              $cart = auth()->user()->cart()->with('items.product')->first();
              $cartCount = $cart ? $cart->items->sum('quantity') : 0;
              $physicalItems = $cart ? $cart->items->filter(fn($i) => !str_contains(strtolower($i->product->category ?? ''), 'turismo') && !str_contains(strtolower($i->product->category ?? ''), 'restaurante'))->sum('quantity') : 0;
              $o2oItems = $cartCount - $physicalItems;
            @endphp
            <a href="{{ route('consumer.cart') }}" class="relative p-2 rounded-full hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors group" title="Mi Carrito de Compras">
              <span class="material-symbols-outlined text-2xl group-hover:scale-105 transition-transform">shopping_cart</span>
              @if($cartCount > 0)
                <span class="absolute top-1 right-1 bg-[#ba1a1a] text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full border-2 border-white leading-none">
                  {{ $cartCount > 9 ? '9+' : $cartCount }}
                </span>
              @endif
            </a>
          @endif

          <!-- Módulo de Usuario con Saldo de FusaCoins -->
          @php
            $userCoins = \App\Models\LoyaltyPoint::where('user_id', auth()->id())->sum('points');
          @endphp
          <div id="user-menu" class="relative">
            <button id="user-menu-toggle" aria-expanded="false" aria-controls="user-dropdown" type="button" class="flex items-center gap-2 p-1.5 pl-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-full transition-all">
              @if(auth()->user()->avatarUrl())
                <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="w-7 h-7 rounded-full object-cover shrink-0 border border-emerald-300 bg-white">
              @else
                <div class="w-7 h-7 bg-gradient-to-tr from-[#10B981] to-[#047857] text-white rounded-full flex items-center justify-center font-black text-xs shrink-0">
                  {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
              @endif
              <div class="hidden lg:flex flex-col text-left leading-none pr-1">
                <span class="text-xs font-bold text-slate-800 truncate max-w-[100px]">{{ auth()->user()->name }}</span>
                <span class="text-[10px] font-extrabold text-[#F97316] flex items-center gap-0.5 mt-0.5">
                  <span class="material-symbols-outlined text-[11px]">monetization_on</span>
                  {{ number_format($userCoins, 0, ',', '.') }} pts
                </span>
              </div>
              <span class="material-symbols-outlined text-slate-500 text-sm pr-1">expand_more</span>
            </button>

            <!-- Dropdown Usuario -->
            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-slate-100 p-2 z-[70] text-xs">
              <div class="p-3 bg-slate-50 rounded-xl mb-2 border border-slate-100">
                <p class="font-extrabold text-slate-900 text-sm truncate">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                <div class="mt-2.5 pt-2 border-t border-slate-200 flex items-center justify-between">
                  <span class="text-slate-600 font-medium">Billetera FusaCoins:</span>
                  <span class="font-black text-[#F97316] bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200">
                    {{ number_format($userCoins, 0, ',', '.') }} FusaCoins
                  </span>
                </div>
              </div>

              <!-- Accesos rápidos según rol -->
              @if(auth()->user()->isConsumer())
                <a href="{{ route('account.profile') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold">
                  <span class="material-symbols-outlined text-base text-[#10B981]">person</span> Mi Cuenta & Billetera
                </a>
                <a href="{{ route('consumer.orders') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold">
                  <span class="material-symbols-outlined text-base text-[#10B981]">local_shipping</span> Mis Pedidos & Envíos
                </a>
                <a href="{{ route('consumer.addresses') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold">
                  <span class="material-symbols-outlined text-base text-slate-500">pin_drop</span> Libreta de Direcciones
                </a>
              @elseif(auth()->user()->isMerchant())
                <a href="{{ route('merchant.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl font-bold mb-1">
                  <span class="material-symbols-outlined text-base text-[#10B981]">dashboard</span> Merchant Center
                </a>
                <a href="{{ route('merchant.products') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold">
                  <span class="material-symbols-outlined text-base text-slate-500">inventory_2</span> Catálogo de Productos
                </a>
                <a href="{{ route('merchant.orders') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold">
                  <span class="material-symbols-outlined text-base text-slate-500">local_shipping</span> Gestión de Guías Envia
                </a>
              @elseif(auth()->user()->isAnalyst())
                <a href="{{ route('analyst.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/50 rounded-xl font-bold mb-1">
                  <span class="material-symbols-outlined text-base text-[#10B981]">admin_panel_settings</span> Operations Console
                </a>
                <a href="{{ route('analyst.integrations') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold">
                  <span class="material-symbols-outlined text-base text-slate-500">sync_alt</span> Auditoría Integraciones
                </a>
              @endif

              <div class="my-1 border-t border-slate-100"></div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-2.5 px-3 py-2 text-red-600 hover:bg-red-50 rounded-xl font-bold">
                  <span class="material-symbols-outlined text-base">logout</span> Cerrar Sesión
                </button>
              </form>
            </div>
          </div>

        @else
          <!-- No autenticado -->
          <a href="{{ route('login') }}" class="px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors">
            Ingresar
          </a>
          <a href="{{ route('register') }}" class="px-4 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-black rounded-xl shadow-sm transition-all">
            Registrarme
          </a>
        @endauth

      </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. MEGA MENÚ DE CATEGORÍAS (COMPRADORES)   -->
    <!-- ========================================== -->
    <div class="border-t border-slate-100 bg-slate-50/70 overflow-x-auto scrollbar-none">
      <div class="max-w-7xl mx-auto px-4 md:px-6 flex items-center gap-1 md:gap-2 py-1.5 text-xs font-bold whitespace-nowrap">
        
        <!-- Botón Inicio Principal -->
        <a href="{{ route('consumer.home') }}" class="px-3.5 py-1.5 rounded-xl {{ request()->routeIs('consumer.home') ? 'bg-[#10B981] text-white shadow-sm' : 'text-slate-700 hover:text-[#10B981] hover:bg-white' }} flex items-center gap-1.5 transition-all">
          <span class="material-symbols-outlined text-[17px]">home</span>
          <span>Inicio</span>
        </a>

        <!-- Todo el Catálogo -->
        <a href="{{ route('consumer.catalog') }}" class="px-3 py-1.5 rounded-xl {{ request()->routeIs('consumer.catalog') && !request('category') ? 'bg-slate-200 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-[#10B981] hover:bg-white' }} flex items-center gap-1.5 transition-colors">
          <span class="material-symbols-outlined text-[16px] text-[#10B981]">grid_view</span>
          <span>Todo el Catálogo</span>
        </a>

        <!-- 1. Retail & Tecnología (Estilo Alkosto) -->
        <a href="{{ route('consumer.catalog', ['category' => 'Electrónica']) }}" class="px-3 py-1.5 rounded-xl text-slate-700 hover:text-[#10B981] hover:bg-white flex items-center gap-1.5 transition-colors">
          <span class="material-symbols-outlined text-[16px] text-[#10B981]">devices</span>
          <span>Retail & Tecnología</span>
        </a>

        <!-- 2. Moda & Hogar (Estilo Mercado Libre) -->
        <a href="{{ route('consumer.catalog', ['category' => 'Ropa']) }}" class="px-3 py-1.5 rounded-xl text-slate-700 hover:text-[#10B981] hover:bg-white flex items-center gap-1.5 transition-colors">
          <span class="material-symbols-outlined text-[16px] text-[#10B981]">checkroom</span>
          <span>Moda & Hogar</span>
        </a>

        <!-- 3. Mercado Local & Viveros -->
        <a href="{{ route('consumer.catalog', ['category' => 'Alimentos']) }}" class="px-3 py-1.5 rounded-xl text-slate-700 hover:text-[#10B981] hover:bg-white flex items-center gap-1.5 transition-colors">
          <span class="material-symbols-outlined text-[16px] text-[#10B981]">potted_plant</span>
          <span>Mercado Local & Viveros</span>
        </a>

        <!-- 4. Turismo & Hotelería (O2O) -->
        <a href="{{ route('consumer.catalog', ['category' => 'Turismo']) }}" class="px-3 py-1.5 rounded-xl text-slate-700 hover:text-[#F97316] hover:bg-white flex items-center gap-1.5 transition-colors">
          <span class="material-symbols-outlined text-[16px] text-[#F97316]">holiday_village</span>
          <span>Turismo & Glamping (O2O)</span>
        </a>

        <!-- 5. Restaurantes & Gastronomía (O2O) -->
        <a href="{{ route('consumer.catalog', ['category' => 'Gastronomía']) }}" class="px-3 py-1.5 rounded-xl text-slate-700 hover:text-rose-600 hover:bg-white flex items-center gap-1.5 transition-colors">
          <span class="material-symbols-outlined text-[16px] text-rose-600">restaurant</span>
          <span>Restaurantes & Cafés</span>
        </a>

        <!-- 6. Mercado Mayorista (B2B) -->
        <a href="{{ route('consumer.catalog', ['category' => 'Mercado Mayorista (B2B)']) }}" class="px-3 py-1.5 rounded-xl bg-emerald-50 text-[#047857] hover:bg-emerald-100 border border-emerald-200 flex items-center gap-1.5 transition-colors ml-auto">
          <span class="material-symbols-outlined text-[16px]">inventory</span>
          <span>Mayoristas B2B</span>
        </a>

      </div>
    </div>
</header>
@endif

<!-- ========================================== -->
<!-- 4. MODAL / ASISTENTE FUSABOT IA            -->
<!-- ========================================== -->
<div id="fusabot-modal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-end p-4 sm:p-6" aria-hidden="true" style="display: none; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);">
  <div role="dialog" aria-modal="true" aria-label="FusaBot Asistente IA" class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden flex flex-col h-[min(520px,calc(100vh-2rem))] animate-in slide-in-from-bottom-6 duration-300">
    <!-- Header FusaBot -->
    <div class="p-4 bg-gradient-to-r from-slate-900 to-emerald-950 text-white flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#10B981] to-[#047857] flex items-center justify-center text-white shadow-md">
          <span class="material-symbols-outlined text-lg">smart_toy</span>
        </div>
        <div>
          <h4 class="font-extrabold text-sm flex items-center gap-1">
            <span>FusaBot Asistente IA</span>
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          </h4>
          <p class="text-[10px] text-slate-300">Sumapaz Concierge & Compras Inteligentes</p>
        </div>
      </div>
      <button id="fusabot-close" type="button" aria-label="Cerrar chat" class="text-slate-400 hover:text-white"><span class="material-symbols-outlined text-xl">close</span></button>
    </div>

    <!-- Mensajes FusaBot -->
    <div class="flex-1 p-4 overflow-y-auto space-y-3 text-xs bg-slate-50" id="fusabot-chat-box" aria-live="polite">
      <div class="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm border border-slate-100 max-w-[85%]">
        <p class="font-bold text-[#10B981] text-[11px] mb-1">¡Hola! Soy FusaBot 🌸</p>
        <p class="text-slate-700 leading-relaxed">
          Te ayudo a encontrar productos locales de Fusagasugá, cotizar envíos por Envia.com, reservar glampings o redimir tus <strong>FusaCoins</strong>. ¿Qué buscas hoy?
        </p>
      </div>
    </div>

    <!-- Sugerencias rápidas -->
    <div class="p-2.5 bg-white border-t border-slate-100 flex gap-1.5 overflow-x-auto text-[10px] whitespace-nowrap scrollbar-none">
      <button onclick="sendQuickPrompt('¿Cómo redimo mis FusaCoins?')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold">🪙 Redimir FusaCoins</button>
      <button onclick="sendQuickPrompt('Glampings recomendados en Chinauta')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold">🏕️ Glampings Chinauta</button>
      <button onclick="sendQuickPrompt('Tiempos de entrega con Envia.com')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold">📦 Envíos Envia.com</button>
    </div>

    <!-- Input FusaBot -->
      <form id="fusabot-form" class="p-3 bg-white border-t border-slate-100 flex items-center gap-2">
        <input type="text" id="fusabot-input" maxlength="150" autocomplete="off" placeholder="Pregunta por productos, ventas o tu perfil..." class="flex-1 bg-slate-100 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-[#10B981]">
        <button type="submit" aria-label="Enviar mensaje" class="p-2 bg-gradient-to-r from-[#10B981] to-[#047857] text-white rounded-xl hover:opacity-90"><span class="material-symbols-outlined text-sm">send</span></button>
      </form>
    </div>
  </div>
  <button id="fusabot-launcher" type="button" aria-label="Abrir FusaBot" aria-haspopup="dialog" aria-controls="fusabot-modal"
          class="fixed bottom-20 right-5 md:bottom-24 md:right-6 z-[80] w-14 h-14 rounded-full bg-gradient-to-br from-[#10B981] to-[#047857] text-white shadow-xl border-2 border-white flex items-center justify-center hover:scale-110 transition-transform animate-bounce"
          style="animation-duration: 1.8s">
    <span class="material-symbols-outlined text-3xl">smart_toy</span>
  </button>
</div>

<!-- ========================================== -->
<!-- 5. CONTENIDO PRINCIPAL (+ SIDEBAR ADMIN)   -->
<!-- ========================================== -->
@if($isAdmin)
<div style="display: flex; min-height: 100vh; width: 100%; align-items: flex-start;">
  <!-- Barra de Administración Vertical al Costado Izquierdo -->
  <x-admin-topbar />

  <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; min-height: 100vh;">
@endif

<main class="flex-1">
  @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-4">
      <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-2.5 text-xs font-bold shadow-sm">
        <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif
  @if(session('error'))
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-4">
      <div class="p-3.5 bg-red-50 border border-red-200 text-red-800 rounded-2xl flex items-center gap-2.5 text-xs font-bold shadow-sm">
        <span class="material-symbols-outlined text-red-600 text-lg">error</span>
        <span>{{ session('error') }}</span>
      </div>
    </div>
  @endif

  @yield('content')
</main>

<!-- ========================================== -->
<!-- 6. FOOTER CORPORATIVO OFICIAL              -->
<!-- ========================================== -->
<footer class="bg-slate-950 text-slate-400 mt-20 border-t border-slate-900 pb-20 md:pb-0 text-xs">
  <div class="max-w-7xl mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-5 gap-10">
    
    <!-- Columna 1: Marca & Misión -->
    <div class="md:col-span-2 space-y-4">
      <x-logo size="lg" :with-microcopy="false" />
      <p class="text-slate-400 leading-relaxed text-xs max-w-sm">
        El epicentro de compras, turismo y negocios del Sumapaz. Conectando a más de 140.000 habitantes de Fusagasugá, Arbeláez, Silvania, Pasca y Chinauta con pasarela segura Wompi, logística Envia.com y facturación electrónica Factus DIAN.
      </p>
      <div class="flex items-center gap-3 pt-2">
        <span class="px-2.5 py-1 bg-slate-900 rounded-lg text-[10px] font-bold text-slate-300 border border-slate-800 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs text-[#10B981]">verified</span> Wompi Verified
        </span>
        <span class="px-2.5 py-1 bg-slate-900 rounded-lg text-[10px] font-bold text-slate-300 border border-slate-800 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs text-[#10B981]">local_shipping</span> Envia.com Logística
        </span>
        <span class="px-2.5 py-1 bg-slate-900 rounded-lg text-[10px] font-bold text-slate-300 border border-slate-800 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs text-[#F97316]">receipt_long</span> Factus DIAN
        </span>
      </div>
    </div>

    <!-- Columna 2: Vitrinas -->
    <div>
      <h5 class="font-extrabold text-white text-xs uppercase tracking-wider mb-3">Vitrinas & O2O</h5>
      <ul class="space-y-2">
        <li><a href="{{ route('consumer.catalog') }}" class="hover:text-white transition-colors">Tecnología & Hogar</a></li>
        <li><a href="{{ route('consumer.catalog', ['category' => 'Alimentos']) }}" class="hover:text-white transition-colors">Viveros & Café de Origen</a></li>
        <li><a href="{{ route('consumer.catalog', ['category' => 'Turismo']) }}" class="hover:text-white transition-colors">Ruta Turística & Glamping</a></li>
        <li><a href="{{ route('consumer.catalog', ['category' => 'Gastronomía']) }}" class="hover:text-white transition-colors">Restaurantes del Sumapaz</a></li>
        <li><a href="{{ route('consumer.catalog', ['category' => 'Mercado Mayorista (B2B)']) }}" class="hover:text-white transition-colors">Mercado Mayorista B2B</a></li>
      </ul>
    </div>

    <!-- Columna 3: Para Vendedores -->
    <div>
      <h5 class="font-extrabold text-white text-xs uppercase tracking-wider mb-3">Comunidad Comercial</h5>
      <ul class="space-y-2">
        <li><a href="{{ route('register') }}" class="hover:text-white transition-colors">Vender en FusaShop</a></li>
        <li><a href="{{ route('merchant.dashboard') }}" class="hover:text-white transition-colors">Merchant Center</a></li>
        <li><a href="{{ route('consumer.directory') }}" class="hover:text-white transition-colors">Directorio de Tiendas</a></li>
        <li><a href="#" class="hover:text-white transition-colors">Tarifas & FusaCoins</a></li>
      </ul>
    </div>

    <!-- Columna 4: Soporte & Legal -->
    <div>
      <h5 class="font-extrabold text-white text-xs uppercase tracking-wider mb-3">Soporte & Legal</h5>
      <ul class="space-y-2">
        <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Política de Privacidad</a></li>
        <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Términos del Servicio</a></li>
        <li><a href="{{ route('pqrs.create') }}" class="hover:text-white transition-colors">PQRS & Garantías</a></li>
        <li><span class="text-slate-500">Fusagasugá, Cundinamarca, CO</span></li>
      </ul>
    </div>

  </div>

  <div class="border-t border-slate-900 py-6 text-center text-slate-600 text-[11px]">
    <p>© {{ date('Y') }} FusaShop Inc. Todos los derechos reservados. Marca Registrada en Fusagasugá, Colombia.</p>
  </div>
</footer>

@if($isAdmin)
  </div>
</div>
@endif

<!-- Bottom Nav (Mobile) -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-slate-200 flex items-center justify-around z-40 text-[10px] font-bold shadow-lg">
  <a href="{{ route('consumer.home') }}" class="flex flex-col items-center justify-center flex-1 h-full text-[#10B981]">
    <span class="material-symbols-outlined text-2xl">storefront</span>
    <span>Inicio</span>
  </a>
  <a href="{{ route('consumer.catalog') }}" class="flex flex-col items-center justify-center flex-1 h-full text-slate-500 hover:text-slate-900">
    <span class="material-symbols-outlined text-2xl">explore</span>
    <span>Catálogo</span>
  </a>
  @auth
    @if(auth()->user()->isConsumer())
      <a href="{{ route('consumer.cart') }}" class="flex flex-col items-center justify-center flex-1 h-full text-slate-500 hover:text-slate-900">
        <span class="material-symbols-outlined text-2xl">shopping_cart</span>
        <span>Carrito</span>
      </a>
      <a href="{{ route('account.profile') }}" class="flex flex-col items-center justify-center flex-1 h-full text-slate-500 hover:text-slate-900">
        <span class="material-symbols-outlined text-2xl">account_circle</span>
        <span>Perfil</span>
      </a>
    @elseif(auth()->user()->isMerchant())
      <a href="{{ route('merchant.dashboard') }}" class="flex flex-col items-center justify-center flex-1 h-full text-emerald-700">
        <span class="material-symbols-outlined text-2xl">dashboard</span>
        <span>Panel</span>
      </a>
    @elseif(auth()->user()->isAnalyst())
      <a href="{{ route('analyst.dashboard') }}" class="flex flex-col items-center justify-center flex-1 h-full text-emerald-700">
        <span class="material-symbols-outlined text-2xl">admin_panel_settings</span>
        <span>Admin</span>
      </a>
    @endif
  @else
    <a href="{{ route('login') }}" class="flex flex-col items-center justify-center flex-1 h-full text-slate-500">
      <span class="material-symbols-outlined text-2xl">login</span>
      <span>Entrar</span>
    </a>
  @endauth
</nav>

<!-- Scripts globales -->
<script>
const notificationMenu = document.getElementById('notification-menu');
const notificationToggle = document.getElementById('notification-menu-toggle');
const notificationDropdown = document.getElementById('notif-dropdown');
const userMenu = document.getElementById('user-menu');
const userToggle = document.getElementById('user-menu-toggle');
const userDropdown = document.getElementById('user-dropdown');

function setHeaderDropdowns(openMenu = null) {
  const notificationIsOpen = openMenu === 'notifications';
  const userIsOpen = openMenu === 'user';

  if (notificationDropdown && notificationToggle) {
    notificationDropdown.classList.toggle('hidden', !notificationIsOpen);
    notificationToggle.setAttribute('aria-expanded', String(notificationIsOpen));
  }

  if (userDropdown && userToggle) {
    userDropdown.classList.toggle('hidden', !userIsOpen);
    userToggle.setAttribute('aria-expanded', String(userIsOpen));
  }
}

if (notificationMenu && notificationToggle && notificationDropdown) {
  notificationToggle.addEventListener('click', () => {
    setHeaderDropdowns(notificationDropdown.classList.contains('hidden') ? 'notifications' : null);
  });
}

if (userMenu && userToggle && userDropdown) {
  userToggle.addEventListener('click', () => {
    setHeaderDropdowns(userDropdown.classList.contains('hidden') ? 'user' : null);
  });
}

document.querySelectorAll('[data-notification-tab]').forEach((tab) => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('[data-notification-tab]').forEach((otherTab) => {
      const isSelected = otherTab === tab;
      otherTab.setAttribute('aria-selected', String(isSelected));
      otherTab.classList.toggle('text-[#10B981]', isSelected);
      otherTab.classList.toggle('border-b-2', isSelected);
      otherTab.classList.toggle('border-[#10B981]', isSelected);
      otherTab.classList.toggle('text-slate-500', !isSelected);
    });
  });
});

document.addEventListener('click', (event) => {
  if ((!notificationMenu || !notificationMenu.contains(event.target))
      && (!userMenu || !userMenu.contains(event.target))) {
    setHeaderDropdowns();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    const focusTarget = notificationDropdown && !notificationDropdown.classList.contains('hidden')
      ? notificationToggle
      : userDropdown && !userDropdown.classList.contains('hidden')
        ? userToggle
        : null;
    setHeaderDropdowns();
    focusTarget?.focus();
  }
});

const locationSelector = document.getElementById('location-selector');
const locationToggle = document.getElementById('location-selector-toggle');
const locationMenu = document.getElementById('location-selector-menu');
const currentLocationText = document.getElementById('current-location-text');

function setLocationMenuOpen(isOpen) {
  if (!locationMenu || !locationToggle) return;
  locationMenu.style.display = isOpen ? 'block' : 'none';
  locationMenu.setAttribute('aria-hidden', String(!isOpen));
  locationToggle.setAttribute('aria-expanded', String(isOpen));
}

if (locationToggle && locationMenu && locationSelector) {
  locationToggle.addEventListener('click', () => {
    setLocationMenuOpen(locationMenu.style.display === 'none');
  });
  locationMenu.addEventListener('click', (event) => {
    const option = event.target.closest('[data-location]');
    if (!option) return;

    if (currentLocationText) currentLocationText.textContent = option.dataset.location;
    setLocationMenuOpen(false);
  });
  document.addEventListener('click', (event) => {
    if (!locationSelector.contains(event.target)) {
      setLocationMenuOpen(false);
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setLocationMenuOpen(false);
    }
  });
}

const fusaBotModal = document.getElementById('fusabot-modal');
const fusaBotLauncher = document.getElementById('fusabot-launcher');
const fusaBotClose = document.getElementById('fusabot-close');

function setFusaBotOpen(isOpen) {
  if (!fusaBotModal) return;
  fusaBotModal.style.display = isOpen ? 'flex' : 'none';
  fusaBotModal.setAttribute('aria-hidden', String(!isOpen));

  if (isOpen) {
    fusaBotClose?.focus();
  } else {
    fusaBotLauncher?.focus();
  }
}

if (fusaBotLauncher && fusaBotModal && fusaBotClose) {
  fusaBotLauncher.addEventListener('click', () => setFusaBotOpen(true));
  fusaBotClose.addEventListener('click', () => setFusaBotOpen(false));
  fusaBotModal.addEventListener('click', (event) => {
    if (event.target === fusaBotModal) {
      setFusaBotOpen(false);
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && fusaBotModal.style.display !== 'none') {
      setFusaBotOpen(false);
    }
  });
}

function startVoiceSearch() {
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    alert('Tu navegador no soporta búsqueda por voz directa. Por favor escribe tu búsqueda.');
    return;
  }
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const recognition = new SpeechRecognition();
  recognition.lang = 'es-CO';
  recognition.start();
  const input = document.getElementById('omnibar-input');
  input.placeholder = 'Escuchando en Fusagasugá...';
  recognition.onresult = function(event) {
    const text = event.results[0][0].transcript;
    input.value = text;
    input.closest('form').submit();
  };
  recognition.onerror = function() {
    input.placeholder = 'Buscar productos, restaurantes, glampings...';
  };
}

function openVisualSearchModal() {
  alert('Búsqueda visual activada: Selecciona o arrastra una foto para encontrar productos similares en comercios de Fusagasugá.');
}

function sendQuickPrompt(text) {
  const input = document.getElementById('fusabot-input');
  input.value = text;
  sendFusaBotMessage();
}

function appendFusaBotText(text, isUser = false) {
  const message = document.createElement('div');
  message.className = isUser
    ? 'bg-[#10B981] text-white p-3 rounded-2xl rounded-tr-none shadow-sm max-w-[85%] ml-auto text-xs'
    : 'bg-white p-3 rounded-2xl rounded-tl-none shadow-sm border border-slate-100 max-w-[85%] text-xs';
  message.textContent = text;
  document.getElementById('fusabot-chat-box').appendChild(message);
  return message;
}

function appendFusaBotResult(product) {
  const card = document.createElement('a');
  card.href = product.url;
  card.className = 'block max-w-[85%] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition-shadow';

  if (product.image) {
    const image = document.createElement('img');
    image.src = product.image;
    image.alt = product.name;
    image.loading = 'lazy';
    image.className = 'w-full h-32 object-cover bg-slate-100';
    card.appendChild(image);
  } else {
    const placeholder = document.createElement('div');
    placeholder.className = 'w-full h-32 flex flex-col items-center justify-center gap-1 bg-gradient-to-br from-emerald-50 to-green-100 text-[#047857]';
    const icon = document.createElement('span');
    const category = (product.category || '').toLocaleLowerCase('es');
    icon.className = 'material-symbols-outlined text-4xl';
    icon.textContent = /ropa|moda|vestuario|calzado/.test(category)
      ? 'checkroom'
      : /turismo|hotel|glamping|hospedaje/.test(category)
        ? 'hotel'
        : /servicio|restaurante|experiencia/.test(category)
          ? 'room_service'
          : 'inventory_2';
    const caption = document.createElement('span');
    caption.className = 'text-[10px] font-medium';
    caption.textContent = 'Imagen no disponible';
    placeholder.append(icon, caption);
    card.appendChild(placeholder);
  }

  const details = document.createElement('div');
  details.className = 'p-3';
  const name = document.createElement('p');
  name.className = 'font-bold text-slate-800 text-xs';
  name.textContent = product.name;
  details.appendChild(name);

  if (product.category) {
    const category = document.createElement('p');
    category.className = 'mt-1 text-[10px] text-slate-500';
    category.textContent = product.category;
    details.appendChild(category);
  }

  if (product.description) {
    const description = document.createElement('p');
    description.className = 'mt-1 text-[10px] text-slate-600';
    description.textContent = product.description;
    details.appendChild(description);
  }

  const price = document.createElement('p');
  price.className = 'mt-2 font-extrabold text-[#047857] text-xs';
  price.textContent = '$' + product.price + ' COP';
  details.appendChild(price);
  card.appendChild(details);
  document.getElementById('fusabot-chat-box').appendChild(card);
}

function appendFusaBotLinks(links) {
  if (!Array.isArray(links) || links.length === 0) return;

  const container = document.createElement('div');
  container.className = 'flex flex-wrap gap-2 max-w-[90%]';
  links.forEach((link) => {
    const anchor = document.createElement('a');
    anchor.href = link.url;
    anchor.textContent = link.label;
    anchor.className = 'inline-flex items-center rounded-full bg-emerald-50 px-3 py-1.5 text-[10px] font-bold text-[#047857] hover:bg-emerald-100';
    container.appendChild(anchor);
  });
  document.getElementById('fusabot-chat-box').appendChild(container);
}

async function sendFusaBotMessage() {
  const input = document.getElementById('fusabot-input');
  const text = input.value.trim();
  if (!text) return;

  const box = document.getElementById('fusabot-chat-box');
  appendFusaBotText(text, true);
  input.value = '';

  const loading = appendFusaBotText('Estoy buscando información para ayudarte…');
  box.scrollTop = box.scrollHeight;

  try {
    const response = await fetch(`{{ route('consumer.assistant.search') }}?q=${encodeURIComponent(text)}`, {
      headers: { Accept: 'application/json' }
    });
    if (!response.ok) {
      throw new Error(`No se pudo consultar el asistente (${response.status}).`);
    }

    const result = await response.json();
    loading.remove();
    appendFusaBotText(result.answer);
    (result.products || []).forEach(appendFusaBotResult);
    appendFusaBotLinks(result.links);
  } catch (error) {
    loading.textContent = 'No pude consultar el catálogo en este momento. Inténtalo de nuevo en unos segundos.';
    console.error('FusaBot request failed:', error);
  } finally {
    box.scrollTop = box.scrollHeight;
  }
}

const fusaBotFormEl = document.getElementById('fusabot-form');
if (fusaBotFormEl) {
  fusaBotFormEl.addEventListener('submit', (event) => {
    event.preventDefault();
    sendFusaBotMessage();
  });
}
</script>

@stack('scripts')
</body>
</html>
