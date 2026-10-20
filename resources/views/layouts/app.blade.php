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
    /* Pestañita verde deslizante que enmarca los botones de categorías */
    #nav-sliding-pill {
      position: absolute;
      border-radius: 0.65rem;
      border: 2px solid #10B981;
      background: rgba(16, 185, 129, 0.12);
      box-shadow: 0 1px 4px 0 rgba(16, 185, 129, 0.25);
      pointer-events: none;
      transition: transform 320ms cubic-bezier(0.25, 1, 0.5, 1),
                  width 320ms cubic-bezier(0.25, 1, 0.5, 1),
                  height 320ms cubic-bezier(0.25, 1, 0.5, 1),
                  opacity 200ms ease;
      will-change: transform, width, height;
      z-index: 1;
    }
    .top-nav-btn {
      position: relative;
      z-index: 2;
      transition: color 200ms ease, font-weight 200ms ease;
    }
    .top-nav-btn.is-active {
      font-weight: 800 !important;
      color: #064e3b !important;
    }
    .top-nav-btn.is-active .material-symbols-outlined {
      color: #047857 !important;
    }

    /* Animaciones de deslizamiento sincronizado del contenido */
    .content-slide-forward-exit {
      animation: contentSlideForwardExit 180ms cubic-bezier(0.4, 0, 1, 1) forwards !important;
    }
    .content-slide-forward-enter {
      animation: contentSlideForwardEnter 280ms cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
    }
    .content-slide-backward-exit {
      animation: contentSlideBackwardExit 180ms cubic-bezier(0.4, 0, 1, 1) forwards !important;
    }
    .content-slide-backward-enter {
      animation: contentSlideBackwardEnter 280ms cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
    }

    @keyframes contentSlideForwardExit {
      0% { opacity: 1; transform: translateX(0); }
      100% { opacity: 0; transform: translateX(-20px); }
    }
    @keyframes contentSlideForwardEnter {
      0% { opacity: 0; transform: translateX(20px); }
      100% { opacity: 1; transform: translateX(0); }
    }

    @keyframes contentSlideBackwardExit {
      0% { opacity: 1; transform: translateX(0); }
      100% { opacity: 0; transform: translateX(20px); }
    }
    @keyframes contentSlideBackwardEnter {
      0% { opacity: 0; transform: translateX(-20px); }
      100% { opacity: 1; transform: translateX(0); }
    }

  [x-cloak] { display: none !important; visibility: hidden !important; }
  body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
  h1, h2, h3, .font-heading { font-family: 'Plus Jakarta Sans', 'Manrope', sans-serif; }
  
  /* Brand colors */
  :root {
    --fusa-green: #10B981;
    --fusa-green-dark: #047857;
    --fusa-orange: #F97316;
  }

  /* Botón Fusabot con Ilustración Oficial */
  .fusabot-launcher-btn {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
  }
  .fusabot-launcher-btn:hover {
    transform: translateY(-2px) scale(1.08) !important;
  }
  .fusabot-launcher-btn:active {
    transform: translateY(0) scale(0.95) !important;
  }

  /* Ventana Flotante Fusabot por encima de todo el contenido */
  #fusabot-modal {
    z-index: 99999 !important;
  }

  /* Scrollbar personalizado para el dropdown de categorías */
  #categories-more-list::-webkit-scrollbar {
    width: 6px;
  }
  #categories-more-list::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 9999px;
  }
  #categories-more-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
  }
  #categories-more-list::-webkit-scrollbar-thumb:hover {
    background: #10B981;
  }
</style>
@stack('styles')
</head>
<body class="bg-[#f7fcf8] text-slate-800 min-h-screen flex flex-col antialiased" x-data="{ omniFilter: 'all' }">

@php
  $isAdmin = request()->is('admin*') || (auth()->check() && auth()->user()->isAnalyst() && !request()->routeIs('consumer.*') && !request()->is('catalogo*') && !request()->is('producto*') && !request()->is('carrito*') && !request()->is('checkout*'));
  $isMerchantPanel = request()->is('merchant*') || request()->routeIs('merchant.*') || request()->is('reporte-ventas-comercio*') || (auth()->check() && auth()->user()->isMerchant() && !request()->routeIs('consumer.*') && !request()->is('catalogo*') && !request()->is('producto*') && !request()->is('carrito*') && !request()->is('checkout*'));
  $isPanel = $isAdmin || $isMerchantPanel;
@endphp

<!-- ========================================== -->
<!-- BARRA PRINCIPAL (MAIN NAVBAR PÚBLICA)      -->
<!-- ========================================== -->
@if(!$isPanel)
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
      <div class="flex-1 max-w-2xl hidden md:block relative" id="omnibar-wrapper">
        <form action="{{ route('consumer.catalog') }}" method="GET" id="omnibar-form" class="relative flex items-center">
          <div class="relative flex-1 flex items-center bg-slate-100 rounded-2xl border border-slate-200 focus-within:border-[#10B981] focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100 transition-all overflow-hidden shadow-inner">
            <span class="material-symbols-outlined text-slate-400 pl-3.5 text-xl">search</span>
            <input type="text" 
                   name="search" 
                   id="omnibar-input"
                   value="{{ request('search') }}"
                   autocomplete="off"
                   placeholder="Buscar productos, ropa, tecnología, restaurantes o viveros..." 
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

        <!-- Dropdown de Sugerencias Automáticas / Predictivo -->
        <div id="omnibar-autocomplete-dropdown" 
             class="hidden absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden z-[100] max-h-[420px] overflow-y-auto divide-y divide-slate-100 animate-in fade-in slide-in-from-top-2 duration-150">
        </div>
      </div>

      <!-- Acciones Derechas Comprador -->
      <div class="flex items-center gap-2 sm:gap-3">
        
        <!-- Botón Fusabot con Ilustración Oficial (al lado de la Campana) -->
        <button id="fusabot-launcher" type="button" aria-label="Abrir Asistente FusaBot" aria-haspopup="dialog" aria-controls="fusabot-modal"
                class="fusabot-launcher-btn group relative flex items-center justify-center p-0.5 rounded-full hover:scale-110 active:scale-95 transition-all duration-300 cursor-pointer select-none focus:outline-none"
                style="width: 44px; height: 44px; min-width: 44px; min-height: 44px; background: transparent; border: none; box-shadow: none;"
                title="Abrir Asistente FusaBot IA">
          <div class="relative w-full h-full rounded-full overflow-hidden shadow-md group-hover:shadow-xl transition-all duration-300 ring-2 ring-emerald-400/50 group-hover:ring-emerald-500 bg-white">
            <img src="{{ asset('fusabot.png') }}" alt="Fusabot IA" class="w-full h-full object-cover pointer-events-none select-none">
          </div>
          <span class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white animate-pulse"></span>
        </button>

        @auth
          <!-- Campanita de Notificaciones -->
          <div id="notification-menu" class="relative">
            <button id="notification-menu-toggle" aria-expanded="false" aria-controls="notif-dropdown" type="button" class="relative p-2 rounded-full hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors" title="Notificaciones">
              <span class="material-symbols-outlined text-2xl">notifications</span>
              @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
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
                @forelse(auth()->user()->unreadNotifications()->take(6)->get() as $notif)
                  <a href="{{ route('notifications.open', $notif->id) }}" class="p-3 hover:bg-emerald-50/70 transition-colors flex items-start gap-2.5 bg-emerald-50/40 group">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-[#10B981] flex items-center justify-center shrink-0 mt-0.5">
                      <span class="material-symbols-outlined text-sm">{{ $notif->data['icon'] ?? 'mail' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-bold text-slate-800 group-hover:text-[#006c47] truncate transition-colors">{{ $notif->data['title'] ?? 'Actualización de Pedido' }}</p>
                      <p class="text-slate-600 line-clamp-2 mt-0.5">{{ $notif->data['message'] ?? 'Tu estado ha cambiado en FusaShop.' }}</p>
                      <div class="flex items-center justify-between mt-1">
                        <span class="text-[10px] text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>
                        <span class="text-[10px] font-extrabold text-[#006c47] group-hover:underline">
                          {{ ($notif->data['type'] ?? '') === 'merchant_sales_report' ? 'Abrir Reporte →' : 'Ver mensaje →' }}
                        </span>
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
              $selectedIds = session('checkout_selected_items');
              if (is_array($selectedIds)) {
                $cartCount = $cart ? $cart->items->whereIn('id', $selectedIds)->sum('quantity') : 0;
              } else {
                $cartCount = $cart ? $cart->items->sum('quantity') : 0;
              }
              $physicalItems = $cart ? $cart->items->filter(fn($i) => !str_contains(strtolower($i->product->category ?? ''), 'turismo') && !str_contains(strtolower($i->product->category ?? ''), 'restaurante'))->sum('quantity') : 0;
              $o2oItems = ($cart ? $cart->items->sum('quantity') : 0) - $physicalItems;
            @endphp
            <a href="{{ route('consumer.cart') }}" class="relative p-2 rounded-full hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors group" title="Mi Carrito de Compras">
              <span class="material-symbols-outlined text-2xl group-hover:scale-105 transition-transform">shopping_cart</span>
              <span id="navbar-cart-badge" class="cart-badge absolute top-1 right-1 bg-[#ba1a1a] text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full border-2 border-white leading-none {{ $cartCount > 0 ? '' : 'hidden' }}">
                {{ $cartCount > 9 ? '9+' : $cartCount }}
              </span>
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
                <div class="flex items-center justify-between">
                  <div class="min-w-0 pr-2">
                    <p class="font-extrabold text-slate-900 text-sm truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                  </div>
                  <a href="{{ route('account.profile.edit') }}" class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-[#10B981] hover:border-emerald-300 transition-colors shrink-0 shadow-2xs" title="Editar Perfil">
                    <span class="material-symbols-outlined text-[15px]">edit</span>
                  </a>
                </div>
                <div class="mt-2.5 pt-2 border-t border-slate-200 flex items-center justify-between">
                  <span class="text-slate-600 font-medium">Billetera FusaCoins:</span>
                  <span class="font-black text-[#F97316] bg-orange-50 px-2 py-0.5 rounded-full border border-orange-200">
                    {{ number_format($userCoins, 0, ',', '.') }} FusaCoins
                  </span>
                </div>
              </div>

              <!-- Accesos rápidos según rol -->
              @if(auth()->user()->isConsumer())
                <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-emerald-800 bg-emerald-50/70 hover:bg-emerald-100 rounded-xl font-bold mb-0.5 transition-colors">
                  <span class="material-symbols-outlined text-base text-[#10B981]">manage_accounts</span> Editar Perfil
                </a>
                <a href="{{ route('account.profile') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
                  <span class="material-symbols-outlined text-base text-slate-500">account_balance_wallet</span> Mi Cuenta & Billetera
                </a>
                <a href="{{ route('consumer.orders') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
                  <span class="material-symbols-outlined text-base text-slate-500">local_shipping</span> Mis Pedidos & Envíos
                </a>
                <a href="{{ route('consumer.addresses') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
                  <span class="material-symbols-outlined text-base text-slate-500">pin_drop</span> Libreta de Direcciones
                </a>
              @elseif(auth()->user()->isMerchant())
                <a href="{{ route('merchant.profile') }}" class="flex items-center gap-2.5 px-3 py-2 text-emerald-800 bg-emerald-50/70 hover:bg-emerald-100 rounded-xl font-bold mb-0.5 transition-colors">
                  <span class="material-symbols-outlined text-base text-[#10B981]">store</span> Editar Perfil Comercial
                </a>
                <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
                  <span class="material-symbols-outlined text-base text-slate-500">manage_accounts</span> Editar Perfil Personal
                </a>
                <a href="{{ route('merchant.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-xl font-bold mb-1 transition-colors">
                  <span class="material-symbols-outlined text-base text-[#10B981]">dashboard</span> Merchant Center
                </a>
                <a href="{{ route('merchant.products') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
                  <span class="material-symbols-outlined text-base text-slate-500">inventory_2</span> Catálogo de Productos
                </a>
                <a href="{{ route('merchant.orders') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
                  <span class="material-symbols-outlined text-base text-slate-500">local_shipping</span> Gestión de Guías Envia
                </a>
              @elseif(auth()->user()->isAnalyst())
                <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-emerald-900 bg-emerald-50/70 hover:bg-emerald-100 rounded-xl font-bold mb-0.5 transition-colors">
                  <span class="material-symbols-outlined text-base text-[#10B981]">manage_accounts</span> Editar Perfil
                </a>
                <a href="{{ route('analyst.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/50 rounded-xl font-bold mb-1 transition-colors">
                  <span class="material-symbols-outlined text-base text-[#10B981]">admin_panel_settings</span> Operations Console
                </a>
                <a href="{{ route('analyst.integrations') }}" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
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
          <a href="{{ route('register') }}" class="px-4 py-1.5 text-xs font-bold text-white bg-[#10B981] hover:bg-[#047857] rounded-xl shadow-sm transition-all">
            Registrarme
          </a>
        @endauth

      </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. MEGA MENÚ DE CATEGORÍAS (COMPRADORES)   -->
    <!-- ========================================== -->
    <div id="top-nav-category-wrapper" class="border-t border-slate-100 bg-slate-50/70 overflow-x-auto md:overflow-visible scrollbar-none relative">
      <div id="top-nav-category-container" class="relative max-w-7xl mx-auto px-3 sm:px-4 md:px-6 flex items-center gap-1 py-1 text-[10.5px] font-semibold whitespace-nowrap">
        
        <!-- Pestañita verde animada que enmarca el botón activo y se desliza suavemente -->
        <div id="nav-sliding-pill" style="opacity: 0; transform: translate3d(0, 0, 0); top: 0; left: 0; width: 0; height: 0;"></div>
        
        <!-- Botón Inicio Principal -->
        <a href="{{ route('consumer.home') }}" data-nav-category="home" data-tab-index="0"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request()->routeIs('consumer.home') ? 'is-active font-bold' : 'text-slate-600 hover:text-[#10B981]' }} flex items-center gap-1 transition-all select-none">
          <span class="material-symbols-outlined text-[14px]">home</span>
          <span>Inicio</span>
        </a>

        <!-- Catálogo -->
        <a href="{{ route('consumer.catalog') }}" data-nav-category="catalog" data-tab-index="1"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request()->routeIs('consumer.catalog') && !request('category') ? 'is-active font-bold' : 'text-slate-600 hover:text-[#10B981]' }} flex items-center gap-1 transition-colors select-none">
          <span class="material-symbols-outlined text-[14px] text-[#10B981]">grid_view</span>
          <span>Catálogo</span>
        </a>

        <!-- Negocios Registrados -->
        <a href="{{ route('consumer.directory') }}" data-nav-category="directory" data-tab-index="2"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request()->routeIs('consumer.directory*') || request()->routeIs('consumer.merchant.profile') ? 'is-active font-bold' : 'text-slate-600 hover:text-[#10B981]' }} flex items-center gap-1 transition-colors select-none" title="Ver todos los negocios y empresas registradas">
          <span class="material-symbols-outlined text-[14px] text-[#10B981]">storefront</span>
          <span>Negocios</span>
        </a>

        <!-- 1. Tecnología -->
        <a href="{{ route('consumer.catalog', ['category' => 'Electrónica']) }}" data-nav-category="tecnologia" data-tab-index="3"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request('category') === 'Electrónica' ? 'is-active font-bold' : 'text-slate-600 hover:text-[#10B981]' }} flex items-center gap-1 transition-colors select-none">
          <span class="material-symbols-outlined text-[14px] text-[#10B981]">devices</span>
          <span>Tecnología</span>
        </a>

        <!-- 2. Moda & Calzado -->
        <a href="{{ route('consumer.catalog', ['category' => 'Ropa']) }}" data-nav-category="moda" data-tab-index="4"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request('category') === 'Ropa' ? 'is-active font-bold' : 'text-slate-600 hover:text-[#10B981]' }} flex items-center gap-1 transition-colors select-none">
          <span class="material-symbols-outlined text-[14px] text-[#10B981]">checkroom</span>
          <span>Moda & Calzado</span>
        </a>

        <!-- 3. Viveros & Agro -->
        <a href="{{ route('consumer.catalog', ['category' => 'Alimentos']) }}" data-nav-category="viveros" data-tab-index="5"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request('category') === 'Alimentos' ? 'is-active font-bold' : 'text-slate-600 hover:text-[#10B981]' }} flex items-center gap-1 transition-colors select-none">
          <span class="material-symbols-outlined text-[14px] text-[#10B981]">potted_plant</span>
          <span>Viveros & Agro</span>
        </a>

        <!-- 4. Turismo -->
        <a href="{{ route('consumer.catalog', ['category' => 'Turismo']) }}" data-nav-category="turismo" data-tab-index="6"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request('category') === 'Turismo' ? 'is-active font-bold' : 'text-slate-600 hover:text-[#F97316]' }} flex items-center gap-1 transition-colors select-none">
          <span class="material-symbols-outlined text-[14px] text-[#F97316]">holiday_village</span>
          <span>Turismo</span>
        </a>

        <!-- 5. Restaurantes -->
        <a href="{{ route('consumer.catalog', ['category' => 'Gastronomía']) }}" data-nav-category="restaurantes" data-tab-index="7"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request('category') === 'Gastronomía' ? 'is-active font-bold' : 'text-slate-600 hover:text-rose-600' }} flex items-center gap-1 transition-colors select-none">
          <span class="material-symbols-outlined text-[14px] text-rose-600">restaurant</span>
          <span>Restaurantes</span>
        </a>

        <!-- 6. Mayoristas B2B -->
        <a href="{{ route('consumer.catalog', ['category' => 'Mercado Mayorista (B2B)']) }}" data-nav-category="mayoristas" data-tab-index="8"
           class="top-nav-btn relative z-10 px-2.5 py-1 rounded-lg {{ request('category') === 'Mercado Mayorista (B2B)' ? 'is-active font-bold' : 'text-[#047857] hover:bg-emerald-50' }} flex items-center gap-1 transition-colors text-[10.5px] font-bold select-none">
          <span class="material-symbols-outlined text-[14px]">inventory</span>
          <span>Mayoristas B2B</span>
        </a>

        <!-- 7. Menú Desplegable "Ver más" -->
        <div id="categories-more-menu" class="relative inline-block ml-auto sm:ml-0">
          <button id="categories-more-toggle" type="button" aria-expanded="false" aria-controls="categories-more-dropdown"
                  class="px-2 py-0.5 rounded-lg text-slate-600 hover:text-[#10B981] hover:bg-white flex items-center gap-1 transition-colors cursor-pointer select-none font-bold"
                  title="Ver más categorías de la región">
            <span class="material-symbols-outlined text-[14px] text-[#10B981]">more_horiz</span>
            <span>Ver más</span>
            <span id="categories-more-arrow" class="material-symbols-outlined text-[13px] transition-transform duration-200">expand_more</span>
          </button>

          <!-- Dropdown Flotante Scrolleable de Categorías Adicionales (Sin duplicados) -->
          <div id="categories-more-dropdown" class="hidden absolute right-0 top-full mt-1.5 w-72 md:w-80 bg-white rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.18)] border border-slate-200 z-[9999] text-xs overflow-hidden flex flex-col">
            <!-- Encabezado fijo -->
            <div class="px-3.5 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between shrink-0">
              <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base text-[#10B981]">category</span> Más Categorías Sumapaz
              </span>
              <span class="text-[9px] bg-emerald-100 text-[#047857] px-2 py-0.5 rounded-full font-bold">14 Rubros</span>
            </div>

            <!-- Lista con scroll vertical independiente -->
            <div id="categories-more-list" class="p-2 overflow-y-auto max-h-[330px] space-y-1 divide-y divide-slate-50">
              <!-- Hogar, Muebles & Decoración -->
              <a href="{{ route('consumer.catalog', ['category' => 'Hogar']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">chair</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Hogar, Muebles & Confort</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Decoración, camas, salas y diseño</p>
                </div>
              </a>

              <!-- Deportes & Fitness -->
              <a href="{{ route('consumer.catalog', ['category' => 'Deportes']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">sports_soccer</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Deportes, Fitness & Ciclo</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Balones, indumentaria, pesas y rutas</p>
                </div>
              </a>

              <!-- Orquídeas & Plantas Exóticas -->
              <a href="{{ route('consumer.catalog', ['search' => 'Orquídeas']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">local_florist</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Orquídeas & Plantas Exóticas</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Jardines y flores insignia de Fusagasugá</p>
                </div>
              </a>

              <!-- Café Especial & Origen -->
              <a href="{{ route('consumer.catalog', ['search' => 'Café']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-amber-100/70 text-amber-800 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">coffee</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Café de Origen del Sumapaz</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Tostión artesanal, microlotes y grano</p>
                </div>
              </a>

              <!-- Artesanías & Souvenirs -->
              <a href="{{ route('consumer.catalog', ['search' => 'Artesanías']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">palette</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Artesanías & Souvenirs</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Mochilas, tallados y obras locales</p>
                </div>
              </a>

              <!-- Mascotas & Veterinaria -->
              <a href="{{ route('consumer.catalog', ['search' => 'Mascotas']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-yellow-50 text-amber-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">pets</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Mascotas & Veterinaria</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Alimentos, accesorios y cuidado animal</p>
                </div>
              </a>

              <!-- Salud, Belleza & Cuidado -->
              <a href="{{ route('consumer.catalog', ['search' => 'Belleza']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">spa</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Salud, Belleza & Cuidado Personal</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Cosmética natural, boticas y bienestar</p>
                </div>
              </a>

              <!-- Ferretería & Construcción -->
              <a href="{{ route('consumer.catalog', ['search' => 'Ferretería']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">home_repair_service</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Ferretería & Construcción</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Herramientas, pinturas e insumos</p>
                </div>
              </a>

              <!-- Automotriz, Motos & Repuestos -->
              <a href="{{ route('consumer.catalog', ['search' => 'Automotriz']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">two_wheeler</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Motos, Repuestos & Automotriz</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Cascos, llantas, aceites y accesorios</p>
                </div>
              </a>

              <!-- Juguetes & Bebés -->
              <a href="{{ route('consumer.catalog', ['search' => 'Juguetes']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">toys</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Juguetería, Bebés & Niños</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Juegos didácticos, ropa y regalos</p>
                </div>
              </a>

              <!-- Papelería & Oficina -->
              <a href="{{ route('consumer.catalog', ['search' => 'Papelería']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">menu_book</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Libros, Papelería & Oficina</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Escolares, libros y artículos de oficina</p>
                </div>
              </a>

              <!-- Fincas de Recreo & Pasadías -->
              <a href="{{ route('consumer.catalog', ['search' => 'Fincas']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-lime-50 text-lime-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">villa</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Fincas de Recreo & Pasadías</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Hoteles, gastronomía y turismo en Fusagasugá</p>
                </div>
              </a>

              <!-- Servicios Técnicos & Profesionales -->
              <a href="{{ route('consumer.catalog', ['search' => 'Servicios']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-[#10B981] font-semibold transition-colors group">
                <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                  <span class="material-symbols-outlined text-base">build_circle</span>
                </div>
                <div class="flex-1">
                  <p class="leading-tight">Servicios Técnicos Locales</p>
                  <p class="text-[9.5px] text-slate-400 font-normal">Mantenimiento, instalaciones y asesoría</p>
                </div>
              </a>
            </div>

            <!-- Footer fijo -->
            <div class="p-2 bg-slate-50 border-t border-slate-100 shrink-0">
              <a href="{{ route('consumer.directory') }}" class="flex items-center justify-between px-3 py-2 rounded-xl bg-white hover:bg-emerald-50 border border-slate-200/80 hover:border-emerald-300 text-slate-800 hover:text-[#047857] font-bold transition-all shadow-2xs">
                <span class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-base text-[#10B981]">storefront</span>
                  <span>Ver Todos los Negocios Registrados</span>
                </span>
                <span class="material-symbols-outlined text-xs">arrow_forward</span>
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
</header>
@endif



<!-- ========================================== -->
<!-- 5. CONTENIDO PRINCIPAL (+ SIDEBAR ADMIN)   -->
<!-- ========================================== -->
@if($isAdmin)
<div style="display: flex; min-height: 100vh; width: 100%; align-items: flex-start;">
  <!-- Barra de Administración Vertical al Costado Izquierdo -->
  <x-admin-topbar />

  <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; min-height: 100vh;">
@elseif($isMerchantPanel)
<div style="display: flex; min-height: 100vh; width: 100%; align-items: flex-start;">
  <!-- Barra de Comerciante Vertical al Costado Izquierdo (Mismo estilo que Analista) -->
  <x-merchant-topbar />

  <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; min-height: 100vh;">
@endif

<!-- Toast Notification Container (Floating Top-Right) -->
<div id="toast-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 md:px-0"></div>

<main id="app-main-content" class="flex-1">
  @if(session('success'))
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-4" data-flash-container>
      <div data-flash-message="{{ session('success') }}" data-flash-type="success" class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center justify-between gap-2.5 text-xs font-bold shadow-sm">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-emerald-600 text-lg">check_circle</span>
          <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.closest('[data-flash-container]').remove()" class="text-emerald-700/60 hover:text-emerald-900 transition-colors p-1">
          <span class="material-symbols-outlined text-sm">close</span>
        </button>
      </div>
    </div>
  @endif
  @if(session('error'))
    <div class="max-w-7xl mx-auto px-4 md:px-6 pt-4" data-flash-container>
      <div data-flash-message="{{ session('error') }}" data-flash-type="error" class="p-3.5 bg-red-50 border border-red-200 text-red-800 rounded-2xl flex items-center justify-between gap-2.5 text-xs font-bold shadow-sm">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-red-600 text-lg">error</span>
          <span>{{ session('error') }}</span>
        </div>
        <button type="button" onclick="this.closest('[data-flash-container]').remove()" class="text-red-700/60 hover:text-red-900 transition-colors p-1">
          <span class="material-symbols-outlined text-sm">close</span>
        </button>
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
      <h5 class="font-extrabold text-white text-xs uppercase tracking-wider mb-3">Hoteles & Turismo</h5>
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
        <li><a href="{{ route('register', ['role' => 'merchant']) }}" class="hover:text-white transition-colors">Vender en FusaShop</a></li>
        <li><a href="{{ route('merchant.dashboard') }}" class="hover:text-white transition-colors">Merchant Center</a></li>
        <li><a href="{{ route('consumer.directory') }}" class="hover:text-white transition-colors">Directorio de Tiendas</a></li>
        <li><a href="{{ route('fees') }}" class="hover:text-white transition-colors">Tarifas & FusaCoins</a></li>
      </ul>
    </div>

    <!-- Columna 4: Soporte & Legal -->
    <div>
      <h5 class="font-extrabold text-white text-xs uppercase tracking-wider mb-3">Soporte & Legal</h5>
      <ul class="space-y-2">
        <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Política de Privacidad</a></li>
        <li><a href="{{ route('terms') }}" class="hover:text-white transition-colors">Términos del Servicio</a></li>
        <li><a href="{{ route('pqrs.create') }}" class="hover:text-white transition-colors">PQRS & Garantías</a></li>
        <li><span class="text-slate-500">Fusagasugá, Cundinamarca, CO</span></li>
      </ul>
    </div>

  </div>

  <div class="border-t border-slate-900 py-6 text-center text-slate-600 text-[11px]">
    <p>© {{ date('Y') }} FusaShop Inc. Todos los derechos reservados. Marca Registrada en Fusagasugá, Colombia.</p>
  </div>
</footer>

@if($isPanel)
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
      <a href="{{ route('consumer.cart') }}" class="flex flex-col items-center justify-center flex-1 h-full text-slate-500 hover:text-slate-900 relative">
        <div class="relative inline-block">
          <span class="material-symbols-outlined text-2xl">shopping_cart</span>
          <span class="cart-badge absolute -top-1 -right-2 bg-[#ba1a1a] text-white text-[9px] font-black w-4 h-4 flex items-center justify-center rounded-full border-2 border-white leading-none {{ ($cartCount ?? 0) > 0 ? '' : 'hidden' }}">
            {{ ($cartCount ?? 0) > 9 ? '9+' : ($cartCount ?? 0) }}
          </span>
        </div>
        <span>Carrito</span>
      </a>
      <a href="{{ route('account.profile') }}" class="flex flex-col items-center justify-center flex-1 h-full text-slate-500 hover:text-slate-900">
        @if(auth()->user()->avatarUrl())
          <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="w-6 h-6 rounded-full object-cover shrink-0 border border-emerald-300">
        @else
          <span class="material-symbols-outlined text-2xl">account_circle</span>
        @endif
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

<!-- ========================================== -->
<!-- VENTANA FLOTANTE ASISTENTE FUSABOT IA      -->
<!-- (Ubicada al final del contenido, z-index 99999) -->
<!-- ========================================== -->
<div id="fusabot-modal" role="dialog" aria-modal="true" aria-label="FusaBot Asistente IA"
     style="display: none; z-index: 99999 !important;"
     class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 w-[calc(100vw-2rem)] sm:w-96 md:w-[410px] bg-white rounded-3xl shadow-[0_25px_70px_rgba(15,23,42,0.35)] border border-slate-200 overflow-hidden flex flex-col h-[min(540px,calc(100vh-6rem))] transition-all duration-300 animate-in slide-in-from-bottom-6 sm:slide-in-from-right-6"
     aria-hidden="true">
  <!-- Header FusaBot -->
  <div class="p-4 bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 text-white flex items-center justify-between select-none shrink-0 shadow-sm">
    <div class="flex items-center gap-2.5">
      <div class="w-10 h-10 rounded-full overflow-hidden shadow-md shrink-0 ring-2 ring-emerald-400/60 bg-white">
        <img src="{{ asset('fusabot.png') }}" alt="Fusabot IA" class="w-full h-full object-cover select-none">
      </div>
      <div>
        <h4 class="font-extrabold text-sm flex items-center gap-1.5 text-white">
          @if(auth()->check() && auth()->user()->isMerchant())
            <span>FusaBot Comerciantes</span>
          @elseif(auth()->check() && auth()->user()->isAnalyst())
            <span>FusaBot Asistente Interno</span>
          @else
            <span>FusaBot Asistente IA</span>
          @endif
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        </h4>
        <p class="text-[10px] text-slate-300">
          @if(auth()->check() && auth()->user()->isMerchant())
            Soporte de operaciones y ventas
          @elseif(auth()->check() && auth()->user()->isAnalyst())
            Gestión de plataforma y métricas
          @else
            Sumapaz Concierge & Compras
          @endif
        </p>
      </div>
    </div>
    <div class="flex items-center gap-1">
      <button id="fusabot-minimize" type="button" aria-label="Minimizar chat" title="Minimizar chat" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
        <span class="material-symbols-outlined text-lg">remove</span>
      </button>
      <button id="fusabot-close" type="button" aria-label="Cerrar chat" title="Cerrar" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
        <span class="material-symbols-outlined text-lg">close</span>
      </button>
    </div>
  </div>

  <!-- Mensajes FusaBot -->
  <div class="flex-1 p-4 overflow-y-auto space-y-3 text-xs bg-slate-50" id="fusabot-chat-box" aria-live="polite">
    <div class="bg-white p-3.5 rounded-2xl rounded-tl-none shadow-sm border border-slate-100 max-w-[90%] space-y-1.5">
      @if(auth()->check() && auth()->user()->isMerchant())
        <p class="font-bold text-[#10B981] text-[11px]">¡Hola {{ auth()->user()->name }}! 👋</p>
        <p class="text-slate-700 leading-relaxed">
          Soy tu <strong>Asistente FusaBot para Comerciantes</strong>. Puedo darte recomendaciones para mejorar las fotos y descripciones de tus productos para vender más, resolver dudas sobre despachos con Envia.com, facturación Factus o tus comisiones. ¿En qué te ayudo hoy?
        </p>
      @elseif(auth()->check() && auth()->user()->isAnalyst())
        <p class="font-bold text-[#10B981] text-[11px]">Panel Administrativo FusaBot 🛡️</p>
        <p class="text-slate-700 leading-relaxed">
          Hola {{ auth()->user()->name }}. Asistente interno listo para consultas de métricas de ventas, comercios con verificación KYC pendiente, incidentes de integración o PQRS activas.
        </p>
      @else
        <p class="font-bold text-[#10B981] text-[11px]">¡Hola! Soy FusaBot 🌸</p>
        <p class="text-slate-700 leading-relaxed">
          Te ayudo a encontrar productos locales de Fusagasugá, resolver dudas sobre métodos de pago con Wompi, envíos con Envia.com o cómo redimir tus <strong>FusaCoins</strong>. Si tienes alguna duda mayor, te conecto con un administrador en vivo.
        </p>
      @endif
    </div>
  </div>

  <!-- Sugerencias rápidas adaptadas por rol -->
  <div class="p-2.5 bg-white border-t border-slate-100 flex gap-1.5 overflow-x-auto text-[10px] whitespace-nowrap scrollbar-none shrink-0">
    @if(auth()->check() && auth()->user()->isMerchant())
      <button type="button" onclick="sendQuickPrompt('¿Cómo mejorar la presentación de mis productos para vender más?')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-full font-bold transition-colors">📈 Mejorar mis ventas</button>
      <button type="button" onclick="sendQuickPrompt('¿Cómo gestionar guías y despachos con Envia.com?')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">🚚 Envíos Envia.com</button>
      <button type="button" onclick="sendQuickPrompt('¿Cómo funciona la facturación electrónica con Factus?')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">🧾 Facturas Factus</button>
      <button type="button" onclick="sendQuickPrompt('¿Cómo funcionan las comisiones y retiros de dinero?')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">💰 Comisiones y retiros</button>
    @elseif(auth()->check() && auth()->user()->isAnalyst())
      <button type="button" onclick="sendQuickPrompt('Resumen de ventas y transacciones pagadas')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-full font-bold transition-colors">📊 Métricas y ventas</button>
      <button type="button" onclick="sendQuickPrompt('Comercios pendientes por verificar KYC')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">🏢 Comercios KYC</button>
      <button type="button" onclick="sendQuickPrompt('Estado de PQRS y solicitudes activas')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">📑 PQRS activas</button>
      <button type="button" onclick="sendQuickPrompt('Auditoría de logs de integración y fallos')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">🛡️ Logs de integración</button>
    @else
      <button type="button" onclick="sendQuickPrompt('¿Cómo redimo mis FusaCoins?')" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-full font-bold transition-colors">🪙 Redimir FusaCoins</button>
      <button type="button" onclick="sendQuickPrompt('Recomiéndame productos populares en Fusagasugá')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">🛍️ Recomendar productos</button>
      <button type="button" onclick="sendQuickPrompt('Tiempos y costos de entrega con Envia.com')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">🚚 Envíos Envia.com</button>
      <button type="button" onclick="sendQuickPrompt('¿Cómo cancelar un pedido o solicitar garantía?')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-full font-bold transition-colors">🔄 Cancelación y garantías</button>
      <button type="button" onclick="sendQuickPrompt('Quiero hablar con un asesor o administrador')" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-800 rounded-full font-bold transition-colors">💬 Hablar con administrador</button>
    @endif
  </div>

  <!-- Input FusaBot -->
  <form id="fusabot-form" class="p-3 bg-white border-t border-slate-100 flex items-center gap-2 shrink-0">
    <input type="text" id="fusabot-input" maxlength="150" autocomplete="off"
           placeholder="{{ auth()->check() && auth()->user()->isMerchant() ? 'Pregunta sobre fotos, envíos, comisiones...' : (auth()->check() && auth()->user()->isAnalyst() ? 'Consultar métricas, KYC, PQRS...' : 'Pregunta por productos, envíos, FusaCoins...') }}"
           class="flex-1 bg-slate-100 rounded-xl px-3.5 py-2.5 text-xs outline-none focus:ring-2 focus:ring-[#10B981] transition-all">
    <button type="submit" aria-label="Enviar mensaje" style="background: linear-gradient(135deg, #006c47, #10B981) !important; color: white !important;" class="p-2.5 text-white rounded-xl hover:opacity-90 active:scale-95 transition-all flex items-center justify-center">
      <span class="material-symbols-outlined text-sm text-white">send</span>
    </button>
  </form>
</div>

<!-- Scripts globales -->
<script>
const notificationMenu = document.getElementById('notification-menu');
const notificationToggle = document.getElementById('notification-menu-toggle');
const notificationDropdown = document.getElementById('notif-dropdown');
const userMenu = document.getElementById('user-menu');
const userToggle = document.getElementById('user-menu-toggle');
const userDropdown = document.getElementById('user-dropdown');
const categoriesMoreMenu = document.getElementById('categories-more-menu');
const categoriesMoreToggle = document.getElementById('categories-more-toggle');
const categoriesMoreDropdown = document.getElementById('categories-more-dropdown');
const categoriesMoreArrow = document.getElementById('categories-more-arrow');

function setHeaderDropdowns(openMenu = null) {
  const notificationIsOpen = openMenu === 'notifications';
  const userIsOpen = openMenu === 'user';
  const categoriesIsOpen = openMenu === 'categories';

  if (notificationDropdown && notificationToggle) {
    notificationDropdown.classList.toggle('hidden', !notificationIsOpen);
    notificationToggle.setAttribute('aria-expanded', String(notificationIsOpen));
  }

  if (userDropdown && userToggle) {
    userDropdown.classList.toggle('hidden', !userIsOpen);
    userToggle.setAttribute('aria-expanded', String(userIsOpen));
  }

  if (categoriesMoreDropdown && categoriesMoreToggle) {
    categoriesMoreDropdown.classList.toggle('hidden', !categoriesIsOpen);
    categoriesMoreToggle.setAttribute('aria-expanded', String(categoriesIsOpen));
    if (categoriesIsOpen) {
      categoriesMoreToggle.classList.add('bg-white', 'text-[#10B981]', 'shadow-xs', 'ring-1', 'ring-slate-200');
    } else {
      categoriesMoreToggle.classList.remove('bg-white', 'text-[#10B981]', 'shadow-xs', 'ring-1', 'ring-slate-200');
    }
    if (categoriesMoreArrow) {
      categoriesMoreArrow.style.transform = categoriesIsOpen ? 'rotate(180deg)' : 'rotate(0deg)';
    }
  }
}

if (notificationMenu && notificationToggle && notificationDropdown) {
  notificationToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    setHeaderDropdowns(notificationDropdown.classList.contains('hidden') ? 'notifications' : null);
  });
}

if (userMenu && userToggle && userDropdown) {
  userToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    setHeaderDropdowns(userDropdown.classList.contains('hidden') ? 'user' : null);
  });
}

if (categoriesMoreMenu && categoriesMoreToggle && categoriesMoreDropdown) {
  categoriesMoreToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    setHeaderDropdowns(categoriesMoreDropdown.classList.contains('hidden') ? 'categories' : null);
  });
  categoriesMoreDropdown.addEventListener('click', () => {
    setHeaderDropdowns();
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
      && (!userMenu || !userMenu.contains(event.target))
      && (!categoriesMoreMenu || !categoriesMoreMenu.contains(event.target))) {
    setHeaderDropdowns();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    const focusTarget = notificationDropdown && !notificationDropdown.classList.contains('hidden')
      ? notificationToggle
      : userDropdown && !userDropdown.classList.contains('hidden')
        ? userToggle
        : categoriesMoreDropdown && !categoriesMoreDropdown.classList.contains('hidden')
          ? categoriesMoreToggle
          : null;
    setHeaderDropdowns();
    focusTarget?.focus();
  }
});

window.setFusaBotOpen = function(isOpen) {
  const modal = document.getElementById('fusabot-modal');
  if (!modal) return;
  modal.style.display = isOpen ? 'flex' : 'none';
  modal.setAttribute('aria-hidden', String(!isOpen));

  if (isOpen) {
    setTimeout(() => {
      document.getElementById('fusabot-input')?.focus();
    }, 60);
  }
};
function setFusaBotOpen(isOpen) {
  window.setFusaBotOpen(isOpen);
}

document.addEventListener('click', (event) => {
  const launcher = event.target.closest('#fusabot-launcher, .fusabot-launcher-btn, [data-fusabot-open]');
  if (launcher) {
    event.preventDefault();
    setFusaBotOpen(true);
    return;
  }
  const closeBtn = event.target.closest('#fusabot-close, #fusabot-minimize');
  if (closeBtn) {
    event.preventDefault();
    setFusaBotOpen(false);
  }
});

document.addEventListener('keydown', (event) => {
  const modal = document.getElementById('fusabot-modal');
  if (event.key === 'Escape' && modal && modal.style.display !== 'none') {
    setFusaBotOpen(false);
  }
});

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
  if (input) {
    input.value = text;
    sendFusaBotMessage();
  }
}

function appendFusaBotText(text, isUser = false) {
  const message = document.createElement('div');
  message.className = isUser
    ? 'bg-[#10B981] text-white p-3 rounded-2xl rounded-tr-none shadow-sm max-w-[85%] ml-auto text-xs font-medium leading-relaxed'
    : 'bg-white p-3.5 rounded-2xl rounded-tl-none shadow-sm border border-slate-100 max-w-[90%] text-xs leading-relaxed text-slate-800 space-y-1.5';

  if (isUser) {
    message.textContent = text;
  } else {
    // Escape de HTML seguro y formato markdown amigable (negrita, cursiva, viñetas, saltos)
    const safeHtml = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>')
      .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
      .replace(/\n/g, '<br>');
    message.innerHTML = safeHtml;
  }

  document.getElementById('fusabot-chat-box').appendChild(message);
  return message;
}

function appendFusaBotResult(product) {
  const card = document.createElement('a');
  card.href = product.url;
  card.className = 'block max-w-[90%] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition-all group';

  if (product.image) {
    const image = document.createElement('img');
    image.src = product.image;
    image.alt = product.name;
    image.loading = 'lazy';
    image.className = 'w-full h-32 object-cover bg-slate-100 group-hover:scale-105 transition-transform duration-300';
    card.appendChild(image);
  } else {
    const placeholder = document.createElement('div');
    placeholder.className = 'w-full h-28 flex flex-col items-center justify-center gap-1 bg-gradient-to-br from-emerald-50 to-green-100 text-[#047857]';
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
    caption.textContent = 'FusaShop';
    placeholder.append(icon, caption);
    card.appendChild(placeholder);
  }

  const details = document.createElement('div');
  details.className = 'p-3';
  const name = document.createElement('p');
  name.className = 'font-bold text-slate-800 text-xs group-hover:text-primary transition-colors';
  name.textContent = product.name;
  details.appendChild(name);

  if (product.category) {
    const category = document.createElement('p');
    category.className = 'mt-0.5 text-[10px] text-slate-500 font-medium';
    category.textContent = product.category;
    details.appendChild(category);
  }

  if (product.description) {
    const description = document.createElement('p');
    description.className = 'mt-1 text-[11px] text-slate-600 line-clamp-2';
    description.textContent = product.description;
    details.appendChild(description);
  }

  const price = document.createElement('p');
  price.className = 'mt-2 font-black text-[#047857] text-xs';
  price.textContent = '$' + product.price + ' COP';
  details.appendChild(price);
  card.appendChild(details);
  document.getElementById('fusabot-chat-box').appendChild(card);
}

function appendFusaBotLinks(links) {
  if (!Array.isArray(links) || links.length === 0) return;

  const container = document.createElement('div');
  container.className = 'flex flex-wrap gap-2 max-w-[95%] pt-1';
  links.forEach((link) => {
    if (link.prompt) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = link.label;
      btn.className = 'inline-flex items-center gap-1 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200/80 px-3 py-1.5 text-[11px] font-bold text-slate-700 transition-colors cursor-pointer';
      btn.onclick = () => sendQuickPrompt(link.prompt);
      container.appendChild(btn);
    } else {
      const anchor = document.createElement('a');
      anchor.href = link.url;
      anchor.textContent = link.label;
      if (link.primary) {
        anchor.className = 'inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 px-3.5 py-2 text-xs font-black text-white shadow-md hover:shadow-lg transition-all active:scale-95';
      } else {
        anchor.className = 'inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200/60 px-3 py-1.5 text-[11px] font-bold text-[#047857] hover:bg-emerald-100 transition-colors';
      }
      container.appendChild(anchor);
    }
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

  const loading = appendFusaBotText('Estoy buscando la mejor respuesta para ti…');
  box.scrollTop = box.scrollHeight;

  try {
    const response = await fetch(`{{ route('consumer.assistant.search') }}?q=${encodeURIComponent(text)}`, {
      headers: { Accept: 'application/json' }
    });
    if (!response.ok) {
      throw new Error(`Error en el asistente (${response.status}).`);
    }

    const result = await response.json();
    loading.remove();
    appendFusaBotText(result.answer);
    (result.products || []).forEach(appendFusaBotResult);
    appendFusaBotLinks(result.links);
  } catch (error) {
    loading.textContent = 'No pude procesar tu consulta en este momento. Inténtalo de nuevo en unos segundos.';
    console.error('FusaBot request failed:', error);
  } finally {
    box.scrollTop = box.scrollHeight;
  }
}

document.addEventListener('submit', (event) => {
  if (event.target && event.target.id === 'fusabot-form') {
    event.preventDefault();
    sendFusaBotMessage();
  }
});

// ==========================================
// BUSCADOR AUTOMÁTICO EN TIEMPO REAL (SIN RECARGA)
// ==========================================
(function() {
  const omniInput = document.getElementById('omnibar-input');
  const omniDropdown = document.getElementById('omnibar-autocomplete-dropdown');
  const omniWrapper = document.getElementById('omnibar-wrapper');
  const catalogContainer = document.getElementById('catalog-products-container');
  let debounceTimer = null;
  let activeAbortCtrl = null;

  function hideDropdown() {
    if (omniDropdown) {
      omniDropdown.classList.add('hidden');
      omniDropdown.innerHTML = '';
    }
  }

  // 1. Sugerencias rápidas automáticas en el Omnibar (Dropdown flotante)
  if (omniInput && omniDropdown) {
    omniInput.addEventListener('input', function() {
      const term = this.value.trim();
      clearTimeout(debounceTimer);

      if (term.length < 2) {
        hideDropdown();
        return;
      }

      debounceTimer = setTimeout(async () => {
        if (activeAbortCtrl) activeAbortCtrl.abort();
        activeAbortCtrl = new AbortController();

        try {
          omniDropdown.innerHTML = `
            <div class="p-3 text-center text-xs text-slate-400 flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-base animate-spin text-[#10B981]">sync</span>
              <span>Buscando en FusaShop...</span>
            </div>
          `;
          omniDropdown.classList.remove('hidden');

          const response = await fetch(`{{ route('consumer.assistant.search') }}?q=${encodeURIComponent(term)}`, {
            headers: { Accept: 'application/json' },
            signal: activeAbortCtrl.signal
          });

          if (!response.ok) throw new Error('Error al buscar');
          const data = await response.json();

          if (!data.products || data.products.length === 0) {
            omniDropdown.innerHTML = `
              <div class="p-4 text-center text-xs text-slate-500">
                <span class="material-symbols-outlined text-2xl text-slate-300 block mb-1">search_off</span>
                <p>No encontramos productos para "<span class="font-bold text-slate-700">${escapeHtml(term)}</span>"</p>
                <a href="{{ route('consumer.catalog') }}?search=${encodeURIComponent(term)}" class="inline-block mt-2 font-bold text-[#10B981] hover:underline">Ver en el catálogo completo →</a>
              </div>
            `;
            return;
          }

          let html = `
            <div class="px-3 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-500">
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm text-[#10B981]">auto_awesome</span> Sugerencias automáticas
              </span>
              <span class="text-[10px] text-slate-400 font-semibold">${data.products.length} encontrados</span>
            </div>
            <div class="divide-y divide-slate-100">
          `;

          data.products.forEach(prod => {
            const imgHtml = prod.image 
              ? `<img src="${prod.image}" alt="${escapeHtml(prod.name)}" class="w-11 h-11 rounded-xl object-cover shrink-0 bg-slate-100 border border-slate-200">`
              : `<div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#10B981] flex items-center justify-center shrink-0 border border-emerald-100"><span class="material-symbols-outlined text-lg">inventory_2</span></div>`;

            html += `
              <a href="${prod.url}" class="p-2.5 hover:bg-emerald-50/50 transition-colors flex items-center gap-3 group">
                ${imgHtml}
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-slate-800 group-hover:text-[#10B981] transition-colors truncate">${escapeHtml(prod.name)}</p>
                  <p class="text-[10px] text-slate-400 uppercase font-semibold tracking-wider">${escapeHtml(prod.category || 'General')}</p>
                </div>
                <div class="text-right shrink-0">
                  <span class="text-xs font-extrabold text-[#047857] block">$${prod.price}</span>
                  <span class="text-[9px] text-slate-400 font-semibold">COP</span>
                </div>
              </a>
            `;
          });

          html += `
            </div>
            <div class="p-2.5 bg-slate-50 border-t border-slate-100 text-center">
              <a href="{{ route('consumer.catalog') }}?search=${encodeURIComponent(term)}" class="text-xs font-extrabold text-[#10B981] hover:text-emerald-700 flex items-center justify-center gap-1">
                <span>Ver todos los resultados en el catálogo</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
              </a>
            </div>
          `;

          omniDropdown.innerHTML = html;
        } catch (err) {
          if (err.name !== 'AbortError') {
            console.error('Omnibar search error:', err);
            hideDropdown();
          }
        }
      }, 250);
    });

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', (e) => {
      if (omniWrapper && !omniWrapper.contains(e.target)) {
        hideDropdown();
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') hideDropdown();
    });
  }

  // 2. Si estamos en la página del catálogo (/shop/catalog), filtrar el contenido en vivo sin recargar
  if (catalogContainer) {
    const catalogSearchInput = document.querySelector('aside input[name="search"]');
    const inputsToWatch = [catalogSearchInput, omniInput].filter(Boolean);
    let catalogTimer = null;
    let catalogAbort = null;

    async function updateCatalogProducts(searchTerm) {
      if (catalogAbort) catalogAbort.abort();
      catalogAbort = new AbortController();

      catalogContainer.style.opacity = '0.4';
      catalogContainer.style.pointerEvents = 'none';

      try {
        const url = new URL('{{ route('consumer.catalog') }}', window.location.origin);
        if (searchTerm) url.searchParams.set('search', searchTerm);
        url.searchParams.set('partial', '1');

        // Mantener otros filtros si existen
        const catSelect = document.querySelector('aside select[name="category"]');
        if (catSelect && catSelect.value) url.searchParams.set('category', catSelect.value);

        const response = await fetch(url.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          signal: catalogAbort.signal
        });

        if (!response.ok) throw new Error('Error al cargar productos');
        const html = await response.text();

        catalogContainer.innerHTML = html;
        window.history.replaceState({}, '', searchTerm ? `{{ route('consumer.catalog') }}?search=${encodeURIComponent(searchTerm)}` : `{{ route('consumer.catalog') }}`);
      } catch (err) {
        if (err.name !== 'AbortError') {
          console.error('Catalog live search error:', err);
        }
      } finally {
        catalogContainer.style.opacity = '1';
        catalogContainer.style.pointerEvents = 'auto';
      }
    }

    inputsToWatch.forEach(inputEl => {
      inputEl.addEventListener('input', function() {
        const val = this.value.trim();
        // Sincronizar inputs si ambos existen
        inputsToWatch.forEach(other => {
          if (other !== inputEl) other.value = this.value;
        });

        clearTimeout(catalogTimer);
        catalogTimer = setTimeout(() => {
          updateCatalogProducts(val);
        }, 300);
      });
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // --- SISTEMA GLOBAL DE NOTIFICACIONES TOAST & BADGE DEL CARRITO ---
  window.updateCartBadge = function(count) {
    const num = parseInt(count, 10) || 0;
    document.querySelectorAll('.cart-badge').forEach(badge => {
      badge.textContent = num > 9 ? '9+' : num;
      if (num > 0) {
        badge.classList.remove('hidden');
        badge.classList.add('animate-bounce');
        setTimeout(() => badge.classList.remove('animate-bounce'), 1200);
      } else {
        badge.classList.add('hidden');
      }
    });
  };

  window.showToast = function(message, type = 'success', actionText = null, actionUrl = null) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'fixed top-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 md:px-0';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `pointer-events-auto transform transition-all duration-300 ease-out translate-y-3 opacity-0 flex items-center justify-between gap-3 p-3.5 rounded-2xl shadow-2xl border backdrop-blur-md text-xs font-bold ${
      type === 'success' 
        ? 'bg-slate-900/95 text-white border-emerald-500/30 shadow-emerald-950/20' 
        : 'bg-red-950/95 text-white border-red-500/30 shadow-red-950/20'
    }`;

    const iconName = type === 'success' ? 'check_circle' : 'error';
    const iconColor = type === 'success' ? 'text-emerald-400' : 'text-red-400';

    let actionHtml = '';
    if (actionText && actionUrl) {
      actionHtml = `<a href="${actionUrl}" class="px-3 py-1.5 bg-[#10B981] hover:bg-emerald-600 text-white rounded-xl text-[11px] font-black transition-all shrink-0 no-underline shadow-sm flex items-center gap-1">${actionText} <span class="material-symbols-outlined text-[13px]">arrow_forward</span></a>`;
    }

    toast.innerHTML = `
      <div class="flex items-center gap-2.5 min-w-0">
        <span class="material-symbols-outlined ${iconColor} text-xl shrink-0">${iconName}</span>
        <span class="truncate leading-snug">${message}</span>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        ${actionHtml}
        <button type="button" class="text-white/60 hover:text-white transition-colors p-1" onclick="this.closest('.pointer-events-auto').remove()">
          <span class="material-symbols-outlined text-sm">close</span>
        </button>
      </div>
    `;

    container.appendChild(toast);

    requestAnimationFrame(() => {
      toast.classList.remove('translate-y-3', 'opacity-0');
      toast.classList.add('translate-y-0', 'opacity-100');
    });

    setTimeout(() => {
      toast.classList.remove('translate-y-0', 'opacity-100');
      toast.classList.add('-translate-y-2', 'opacity-0');
      setTimeout(() => toast.remove(), 350);
    }, 4000);
  };

  // Delegación de formularios de agregado rápido al carrito (.add-to-cart-quick-form)
  document.addEventListener('submit', async function(e) {
    const form = e.target.closest('.add-to-cart-quick-form');
    if (!form) return;
    e.preventDefault();

    const btn = form.querySelector('button[type="submit"]');
    const originalContent = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">sync</span><span>Agregando...</span>';
    }

    try {
      const resp = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
      });

      if (resp.status === 401) {
        window.location.href = '{{ route("login") }}';
        return;
      }

      const data = await resp.json();
      if (data.success) {
        window.updateCartBadge(data.cart_count);
        window.showToast(data.message || '¡Producto agregado al carrito con éxito!', 'success', 'Ver Carrito', '{{ route("consumer.cart") }}');
        if (btn) {
          btn.innerHTML = '<span class="material-symbols-outlined text-base text-white">check</span><span>¡Listo!</span>';
          setTimeout(() => { if (btn) btn.innerHTML = originalContent; }, 2000);
        }
      } else {
        window.showToast(data.message || 'No se pudo agregar el producto', 'error');
        if (btn) btn.innerHTML = originalContent;
      }
    } catch (err) {
      console.error('Quick add to cart error:', err);
      form.submit();
    } finally {
      if (btn) btn.disabled = false;
    }
  });
})();

// =========================================================================
// 7. MOTOR DE ACTUALIZACIÓN DINÁMICA SIN RECARGA DE PÁGINA (SPA / AJAX)
//    - Preserva la posición del scroll de la barra lateral (#panel-sidebar)
//    - Actualiza únicamente <main id="app-main-content"> al navegar o pulsar botones
//    - Preserva el scroll vertical al ejecutar acciones o filtros en la misma vista
// =========================================================================
(function() {
  const SIDEBAR_SCROLL_KEY = 'fusashop_sidebar_scroll';
  let navAbortController = null;

  // 1. Persistir y restaurar el scroll de la barra lateral (#panel-sidebar)
  function initSidebarScrollPersistence() {
    const sidebar = document.getElementById('panel-sidebar');
    if (!sidebar) return;

    const savedScroll = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
    if (savedScroll !== null) {
      sidebar.scrollTop = parseInt(savedScroll, 10) || 0;
    }

    sidebar.addEventListener('scroll', () => {
      sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(sidebar.scrollTop));
    }, { passive: true });
  }

  initSidebarScrollPersistence();

  // Barra superior de progreso sutil durante transiciones dinámicas
  function showProgressBar() {
    let bar = document.getElementById('dynamic-nav-progress');
    if (!bar) {
      bar = document.createElement('div');
      bar.id = 'dynamic-nav-progress';
      bar.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0%;background:linear-gradient(90deg,#006c47,#10B981,#F97316);z-index:99999;transition:width 0.25s ease,opacity 0.2s ease;pointer-events:none;';
      document.body.appendChild(bar);
    }
    bar.style.opacity = '1';
    bar.style.width = '65%';
    return bar;
  }

  function hideProgressBar() {
    const bar = document.getElementById('dynamic-nav-progress');
    if (!bar) return;
    bar.style.width = '100%';
    setTimeout(() => {
      bar.style.opacity = '0';
      setTimeout(() => { bar.style.width = '0%'; }, 200);
    }, 150);
  }

  // Re-ejecutar etiquetas <script> dentro del contenedor actualizado
  function executeInlineScripts(container) {
    if (!container) return;
    const scripts = container.querySelectorAll('script');
    scripts.forEach((oldScript) => {
      const newScript = document.createElement('script');
      Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
      newScript.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  // Actualizar estado activo de los botones en el menú lateral sin recargarlo
  function updateSidebarActiveState(targetUrl) {
    const sidebar = document.getElementById('panel-sidebar');
    if (!sidebar) return;

    let parsedTarget;
    try {
      parsedTarget = new URL(targetUrl, window.location.origin);
    } catch (e) {
      return;
    }

    const activeClasses = ['bg-emerald-100', 'text-[#006c47]', 'font-extrabold', 'shadow-2xs'];
    const inactiveClasses = ['text-slate-600', 'hover:text-[#006c47]', 'hover:bg-slate-100'];

    sidebar.querySelectorAll('nav a[href]').forEach((link) => {
      try {
        const linkUrl = new URL(link.href, window.location.origin);
        const isExact = linkUrl.pathname === parsedTarget.pathname;
        const isSubroute = linkUrl.pathname !== '/merchant/dashboard'
          && linkUrl.pathname !== '/admin/dashboard'
          && parsedTarget.pathname.startsWith(linkUrl.pathname + '/');
        const isBannerDetail = linkUrl.pathname === '/admin/banners' && parsedTarget.pathname.startsWith('/admin/banner-requests');

        if (isExact || isSubroute || isBannerDetail) {
          link.classList.remove(...inactiveClasses);
          link.classList.add(...activeClasses);
        } else {
          link.classList.remove(...activeClasses);
          link.classList.add(...inactiveClasses);
        }
      } catch (e) {}
    });
  }

  // Intercambiar contenido de #app-main-content a partir del HTML recibido
  function swapMainContent(htmlText, finalUrl, options = {}) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlText, 'text/html');
    const newMain = doc.getElementById('app-main-content');
    const currentMain = document.getElementById('app-main-content');

    // Si la respuesta no tiene #app-main-content o cambia entre panel y tienda pública, hacer navegación normal
    const currentSidebar = document.getElementById('panel-sidebar');
    const newSidebar = doc.getElementById('panel-sidebar');
    const currentPanelType = currentSidebar ? currentSidebar.getAttribute('data-panel-type') : 'public';
    const newPanelType = newSidebar ? newSidebar.getAttribute('data-panel-type') : 'public';

    if (!newMain || !currentMain || currentPanelType !== newPanelType) {
      window.location.href = finalUrl;
      return false;
    }

    // Actualizar título del documento
    if (doc.title) {
      document.title = doc.title;
    }

    // Actualizar token CSRF si viene en el nuevo documento
    const newCsrf = doc.querySelector('meta[name="csrf-token"]')?.content;
    const currentCsrf = document.querySelector('meta[name="csrf-token"]');
    if (newCsrf && currentCsrf) {
      currentCsrf.setAttribute('content', newCsrf);
    }

    // Reemplazar el contenido principal
    currentMain.innerHTML = newMain.innerHTML;

    // Actualizar @stack('scripts') si existe
    const newScriptsStack = doc.getElementById('dynamic-page-scripts');
    const currentScriptsStack = document.getElementById('dynamic-page-scripts');
    if (newScriptsStack && currentScriptsStack) {
      currentScriptsStack.innerHTML = newScriptsStack.innerHTML;
      executeInlineScripts(currentScriptsStack);
    }

    // Ejecutar scripts inline del nuevo contenido y reactivar componentes Alpine.js
    executeInlineScripts(currentMain);
    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
      try {
        window.Alpine.initTree(currentMain);
      } catch (err) {
        console.warn('Alpine initTree warning:', err);
      }
    }

    // Si hay mensajes flash en la respuesta y fue una acción in-place, mostrar Toast flotante
    if (options.showFlashToast) {
      const flashEl = currentMain.querySelector('[data-flash-message]');
      if (flashEl && window.showToast) {
        const msg = flashEl.getAttribute('data-flash-message');
        const type = flashEl.getAttribute('data-flash-type') || 'success';
        if (msg) window.showToast(msg, type);
      }
    }

    // Actualizar estado activo del sidebar y cerrar menús desplegables abiertos
    updateSidebarActiveState(finalUrl);
    if (typeof setHeaderDropdowns === 'function') {
      setHeaderDropdowns(null);
    }

    return true;
  }

  // Verificar si un enlace <a> es elegible para carga dinámica sin recargar toda la página
  function shouldInterceptLink(anchor, event) {
    if (!anchor || !anchor.href) return false;
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (anchor.hasAttribute('download') || anchor.hasAttribute('data-no-ajax')) return false;
    if (anchor.target && anchor.target !== '_self') return false;

    const rawHref = anchor.getAttribute('href') || '';
    if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) {
      return false;
    }

    let url;
    try {
      url = new URL(anchor.href, window.location.origin);
    } catch (e) {
      return false;
    }

    if (url.origin !== window.location.origin) return false;

    // Excluir rutas de descarga, impresión, facturas PDF/CSV, autenticación externa
    const excludedPatterns = [
      /\.(pdf|csv|xlsx|xls|zip|png|jpg|jpeg|webp)$/i,
      /\/export/i,
      /\/download/i,
      /\/print/i,
      /\/invoice/i,
      /\/shipping-label/i,
      /\/auth\/google/i,
      /\/logout/i
    ];
    if (excludedPatterns.some((rx) => rx.test(url.pathname))) {
      return false;
    }

    // Si solo cambia el hash en la misma página, dejar comportamiento nativo de ancla
    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
      return false;
    }

    return true;
  }

  
  // =========================================================================
  // CONTROLADOR DE PESTAÑITA VERDE DESLIZANTE & TRANSICIÓN DE CONTENIDO
  // =========================================================================
  function moveSlidingPillTo(targetBtn) {
    const pill = document.getElementById("nav-sliding-pill");
    const container = document.getElementById("top-nav-category-container");
    if (!pill || !container || !targetBtn) return;

    const left = targetBtn.offsetLeft;
    const top = targetBtn.offsetTop;
    const width = targetBtn.offsetWidth;
    const height = targetBtn.offsetHeight;

    pill.style.transform = `translate3d(${left}px, ${top}px, 0)`;
    pill.style.width = `${width}px`;
    pill.style.height = `${height}px`;
    pill.style.opacity = "1";

    // Auto-scroll horizontal suave si se sale de la vista en móvil
    const wrapper = document.getElementById("top-nav-category-wrapper");
    if (wrapper) {
      const btnLeft = targetBtn.offsetLeft;
      const btnRight = btnLeft + targetBtn.offsetWidth;
      const scrollLeft = wrapper.scrollLeft;
      const clientWidth = wrapper.clientWidth;
      if (btnLeft < scrollLeft || btnRight > scrollLeft + clientWidth) {
        targetBtn.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "center" });
      }
    }
  }

  function updateTopNavActiveState(targetUrl) {
    let parsedUrl;
    try {
      parsedUrl = new URL(targetUrl, window.location.origin);
    } catch(e) {
      return;
    }

    const pathname = parsedUrl.pathname;
    const cat = parsedUrl.searchParams.get("category");

    let activeKey = null;
    if (pathname === "/" || pathname === "") {
      activeKey = "home";
    } else if (pathname.startsWith("/directorio") || pathname.startsWith("/comercio")) {
      activeKey = "directory";
    } else if (cat === "Electrónica") {
      activeKey = "tecnologia";
    } else if (cat === "Ropa") {
      activeKey = "moda";
    } else if (cat === "Alimentos") {
      activeKey = "viveros";
    } else if (cat === "Turismo") {
      activeKey = "turismo";
    } else if (cat === "Gastronomía") {
      activeKey = "restaurantes";
    } else if (cat && cat.includes("Mayorista")) {
      activeKey = "mayoristas";
    } else if (pathname.startsWith("/shop/catalog") || pathname.startsWith("/catalogo")) {
      activeKey = "catalog";
    }

    const buttons = document.querySelectorAll(".top-nav-btn");
    let matchedBtn = null;

    buttons.forEach((btn) => {
      const btnKey = btn.getAttribute("data-nav-category");
      if (btnKey === activeKey) {
        btn.classList.add("is-active");
        matchedBtn = btn;
      } else {
        btn.classList.remove("is-active");
      }
    });

    if (matchedBtn) {
      moveSlidingPillTo(matchedBtn);
    } else {
      const pill = document.getElementById("nav-sliding-pill");
      if (pill) pill.style.opacity = "0";
    }
  }

  function initTopNavSlidingPill() {
    const activeBtn = document.querySelector(".top-nav-btn.is-active");
    if (activeBtn) {
      moveSlidingPillTo(activeBtn);
    } else {
      updateTopNavActiveState(window.location.href);
    }
  }

  window.addEventListener("load", initTopNavSlidingPill);
  window.addEventListener("resize", () => {
    const activeBtn = document.querySelector(".top-nav-btn.is-active");
    if (activeBtn) moveSlidingPillTo(activeBtn);
  });


  // Navegación dinámica para enlaces <a>
  async function navigateDynamically(targetHref, pushState = true, preserveScroll = false, direction = null) {
    const currentMain = document.getElementById("app-main-content");
    if (currentMain && direction) {
      currentMain.classList.remove("content-slide-forward-enter", "content-slide-forward-exit", "content-slide-backward-enter", "content-slide-backward-exit");
      currentMain.classList.add(direction === "forward" ? "content-slide-forward-exit" : "content-slide-backward-exit");
    }
    if (navAbortController) {
      navAbortController.abort();
    }
    navAbortController = new AbortController();

    const savedScrollY = window.scrollY;
    const sidebar = document.getElementById('panel-sidebar');
    const savedSidebarScroll = sidebar ? sidebar.scrollTop : 0;

    showProgressBar();

    try {
      // Animar deslizamiento de salida del contenido si hay dirección
      const currentMain = document.getElementById("app-main-content");
      if (currentMain && direction) {
        currentMain.classList.remove("content-slide-forward-enter", "content-slide-forward-exit", "content-slide-backward-enter", "content-slide-backward-exit");
        currentMain.classList.add(direction === "forward" ? "content-slide-forward-exit" : "content-slide-backward-exit");
      }

      const response = await fetch(targetHref, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-Dynamic-Nav': '1',
          'Accept': 'text/html, application/xhtml+xml'
        },
        signal: navAbortController.signal
      });

      const contentType = response.headers.get('content-type') || '';
      if (!response.ok || !contentType.includes('text/html')) {
        window.location.href = targetHref;
        return;
      }

      const htmlText = await response.text();
      const finalUrl = response.url || targetHref;
      const swapped = swapMainContent(htmlText, finalUrl, { showFlashToast: false });
      if (!swapped) return;

      if (currentMain && direction) {
        currentMain.classList.remove("content-slide-forward-exit", "content-slide-backward-exit");
        currentMain.classList.add(direction === "forward" ? "content-slide-forward-enter" : "content-slide-backward-enter");
        setTimeout(() => {
          currentMain.classList.remove("content-slide-forward-enter", "content-slide-backward-enter");
        }, 320);
      }

      updateTopNavActiveState(finalUrl);

      // Restaurar el scroll exacto de la barra lateral
      if (sidebar) {
        sidebar.scrollTop = savedSidebarScroll;
      }

      if (pushState) {
        window.history.pushState({ dynamicNav: true }, '', finalUrl);
      }

      if (preserveScroll) {
        window.scrollTo({ top: savedScrollY, behavior: 'instant' });
      } else {
        window.scrollTo({ top: 0, behavior: 'instant' });
      }
    } catch (err) {
      if (err.name !== 'AbortError') {
        window.location.href = targetHref;
      }
    } finally {
      hideProgressBar();
    }
  }

  // Interceptar clics en enlaces <a>
  document.addEventListener('click', (event) => {
    const anchor = event.target.closest('a[href]');
    if (!shouldInterceptLink(anchor, event)) return;

    const url = new URL(anchor.href, window.location.origin);
    const isSamePageFilter = url.pathname === window.location.pathname || anchor.hasAttribute('data-preserve-scroll');

    let direction = null;
    const clickedNavBtn = anchor.closest(".top-nav-btn");
    if (clickedNavBtn) {
      const currentActiveBtn = document.querySelector(".top-nav-btn.is-active");
      const oldIndex = currentActiveBtn ? parseInt(currentActiveBtn.getAttribute("data-tab-index") || "0", 10) : 0;
      const newIndex = parseInt(clickedNavBtn.getAttribute("data-tab-index") || "0", 10);
      direction = newIndex >= oldIndex ? "forward" : "backward";

      // Deslizar inmediatamente la pestañita verde al hacer clic (respuesta instantánea)
      document.querySelectorAll(".top-nav-btn").forEach(b => b.classList.remove("is-active"));
      clickedNavBtn.classList.add("is-active");
      moveSlidingPillTo(clickedNavBtn);
    }

    event.preventDefault();
    navigateDynamically(url.toString(), true, isSamePageFilter, direction);
  });

  // Manejar botones Atrás / Adelante del navegador
  window.addEventListener('popstate', () => {
    navigateDynamically(window.location.href, false, false);
  });

  // Interceptar envío de formularios (<form>) para actualizar solo el contenido sin saltar al inicio
  document.addEventListener('submit', async (event) => {
    if (event.defaultPrevented) return;

    const form = event.target;
    if (!form || form.tagName !== 'FORM') return;

    // Excluir formularios que ya tienen manejador propio o requieren redirección completa
    if (
      form.classList.contains('add-to-cart-quick-form') ||
      form.id === 'fusabot-form' ||
      form.id === 'omnibar-form' ||
      form.hasAttribute('data-no-ajax') ||
      (form.target && form.target !== '_self')
    ) {
      return;
    }

    const rawAction = form.getAttribute('action') || window.location.href;
    let actionUrl;
    try {
      actionUrl = new URL(rawAction, window.location.origin);
    } catch (e) {
      return;
    }

    if (actionUrl.origin !== window.location.origin) return;

    // Excluir login, registro, logout, checkout Wompi y descargas/reportes
    const excludedFormPaths = [
      /\/login$/i,
      /\/register$/i,
      /\/logout$/i,
      /\/password/i,
      /\/two-factor/i,
      /\/checkout/i,
      /\/export/i,
      /\/download/i,
      /\/print/i,
      /\/invoice/i
    ];
    if (excludedFormPaths.some((rx) => rx.test(actionUrl.pathname))) {
      return;
    }

    event.preventDefault();

    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
    const originalBtnHtml = submitter ? submitter.innerHTML : '';
    const savedScrollY = window.scrollY;
    const prevPathname = window.location.pathname;
    const sidebar = document.getElementById('panel-sidebar');
    const savedSidebarScroll = sidebar ? sidebar.scrollTop : 0;

    if (submitter) {
      submitter.disabled = true;
      submitter.style.opacity = '0.75';
    }

    showProgressBar();

    try {
      let fetchUrl = actionUrl.toString();
      const formData = new FormData(form);
      if (submitter && submitter.name) {
        formData.append(submitter.name, submitter.value || '');
      }

      const fetchOptions = {
        method: method === 'GET' ? 'GET' : 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-Dynamic-Nav': '1',
          'Accept': 'text/html, application/xhtml+xml',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
      };

      if (method === 'GET') {
        const params = new URLSearchParams();
        for (const [k, v] of formData.entries()) {
          if (typeof v === 'string') params.append(k, v);
        }
        actionUrl.search = params.toString();
        fetchUrl = actionUrl.toString();
      } else {
        fetchOptions.body = formData;
      }

      const response = await fetch(fetchUrl, fetchOptions);
      const contentType = response.headers.get('content-type') || '';

      if (!contentType.includes('text/html')) {
        window.location.href = response.url || fetchUrl;
        return;
      }

      const htmlText = await response.text();
      const finalUrl = response.url || fetchUrl;
      const swapped = swapMainContent(htmlText, finalUrl, { showFlashToast: true });
      if (!swapped) return;

      if (sidebar) {
        sidebar.scrollTop = savedSidebarScroll;
      }

      window.history.pushState({ dynamicNav: true }, '', finalUrl);

      let parsedFinal;
      try {
        parsedFinal = new URL(finalUrl, window.location.origin);
      } catch (e) {
        parsedFinal = { pathname: prevPathname };
      }

      // Si nos mantenemos en la misma página tras enviar el botón/formulario, preservar exactamente el scroll
      if (parsedFinal.pathname === prevPathname) {
        window.scrollTo({ top: savedScrollY, behavior: 'instant' });
      } else {
        window.scrollTo({ top: 0, behavior: 'instant' });
      }
    } catch (err) {
      console.error('Dynamic form error:', err);
      form.setAttribute('data-no-ajax', '1');
      form.submit();
    } finally {
      hideProgressBar();
      if (submitter && document.body.contains(submitter)) {
        submitter.disabled = false;
        submitter.style.opacity = '1';
        if (originalBtnHtml) submitter.innerHTML = originalBtnHtml;
      }
    }
  });
})();
</script>

<div id="dynamic-page-scripts">
@stack('scripts')
</div>
</body>
</html>
