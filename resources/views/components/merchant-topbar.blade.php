@php
  $merchantUser = auth()->user();
  $companyProfile = $merchantUser ? $merchantUser->companyProfile : null;
  $companyName = $companyProfile->company_name ?? $merchantUser->name ?? 'Mi Tienda';
  
  $pendingOrdersCount = $merchantUser ? \App\Models\Order::whereHas('items.product', function($q) use ($merchantUser) {
      $q->where('merchant_id', $merchantUser->id);
  })->whereIn('status', ['pending', 'processing'])->count() : 0;

  $lowStockCount = $merchantUser ? \App\Models\Product::where('merchant_id', $merchantUser->id)
      ->where('stock', '<=', 5)
      ->where('stock', '>', 0)
      ->count() : 0;

  $productsCount = $merchantUser ? \App\Models\Product::where('merchant_id', $merchantUser->id)->count() : 0;

  $pendingReviewsCount = $merchantUser ? \App\Models\Review::whereHas('product', function($q) use ($merchantUser) {
      $q->where('merchant_id', $merchantUser->id);
  })->whereNull('merchant_reply')->count() : 0;

  $unreadDirectMessages = $merchantUser ? \App\Models\Message::with('sender')
      ->where('receiver_id', $merchantUser->id)
      ->where('is_read', false)
      ->latest()
      ->get() : collect();

  $unreadSupportCount = $unreadDirectMessages->filter(fn($m) => $m->sender && $m->sender->isAnalyst())->count();
  $unreadMessagesCount = $unreadDirectMessages->reject(fn($m) => $m->sender && $m->sender->isAnalyst())->count();

  $unreadMerchantNotifsAll = $merchantUser ? $merchantUser->unreadNotifications()->get() : collect();
  $unreadMerchantNotifsList = $unreadMerchantNotifsAll->take(8);
  $notifiedSenderIds = $unreadMerchantNotifsList
      ->map(fn($n) => $n->data['sender_id'] ?? null)
      ->filter()
      ->unique()
      ->values()
      ->all();

  $extraUnreadMessages = $unreadDirectMessages
      ->reject(fn($m) => in_array($m->sender_id, $notifiedSenderIds))
      ->take(5);

  $unreadMerchantNotifs = $unreadMerchantNotifsAll
      ->reject(fn($n) => isset($n->data['sender_id']) || $n->type === \App\Notifications\NewMessageNotification::class)
      ->count();
  $totalAlertsCount = $pendingOrdersCount + $lowStockCount + $pendingReviewsCount + $unreadDirectMessages->count() + $unreadMerchantNotifs;
@endphp

<aside id="panel-sidebar" data-panel-type="merchant" aria-label="Barra lateral del comerciante"
       class="bg-white border-r border-slate-200 shadow-sm flex flex-col justify-between select-none"
       style="width: 270px; min-width: 270px; height: 100vh; position: sticky; top: 0; left: 0; z-index: 60; overflow-y: auto;">

  <!-- ====================================================================== -->
  <!-- PARTE SUPERIOR Y CENTRAL DE LA BARRA VERTICAL IZQUIERDA                -->
  <!-- ====================================================================== -->
  <div class="p-4 space-y-4">

    <!-- 1. LOGO INSTITUCIONAL -->
    <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
      <x-logo size="default" :href="route('merchant.dashboard')" :with-microcopy="false" />
    </div>

    <!-- 2. PÍLDORA DE PERFIL DEL COMERCIANTE + BOTÓN DE EDITAR PERFIL -->
    <div id="user-menu" class="relative">
      <div class="flex items-center justify-between bg-emerald-50/90 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl p-2 transition-all gap-2 shadow-2xs">
        <button id="user-menu-toggle" aria-expanded="false" aria-controls="user-dropdown" type="button" class="flex items-center gap-2.5 text-left cursor-pointer min-w-0 flex-1">
          @if($companyProfile && $companyProfile->logoUrl())
            <img src="{{ $companyProfile->logoUrl() }}" alt="{{ $companyName }}" class="w-9 h-9 rounded-full object-cover shrink-0 border-2 border-[#10B981] shadow-2xs bg-white">
          @elseif($merchantUser && $merchantUser->avatarUrl())
            <img src="{{ $merchantUser->avatarUrl() }}" alt="{{ $companyName }}" class="w-9 h-9 rounded-full object-cover shrink-0 border-2 border-[#10B981] shadow-2xs bg-white">
          @else
            <div class="w-9 h-9 bg-gradient-to-tr from-[#006c47] to-[#10B981] text-white rounded-full flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
              {{ strtoupper(substr($companyName, 0, 1)) }}
            </div>
          @endif
          <div class="flex flex-col text-left leading-tight min-w-0 flex-1">
            <span class="text-xs font-extrabold text-slate-900 truncate flex items-center gap-1">
              <span class="truncate">{{ $companyName }}</span>
              @if($companyProfile && $companyProfile->kyc_status === 'approved')
                <span class="material-symbols-outlined text-[#006c47] text-[14px] shrink-0" style="font-variation-settings: 'FILL' 1" title="Comercio Verificado">verified</span>
              @endif
            </span>
            <span class="text-[10px] font-bold text-[#006c47] truncate">
              @if($companyProfile && $companyProfile->kyc_status === 'approved')
                Comercio Verificado ✓
              @else
                Comerciante Sumapaz
              @endif
            </span>
          </div>
          <span class="material-symbols-outlined text-slate-500 text-base shrink-0">expand_more</span>
        </button>

        <a href="{{ route('merchant.profile') }}" 
           class="w-7 h-7 rounded-full bg-white hover:bg-[#006c47] text-slate-700 hover:text-white flex items-center justify-center shadow-2xs transition-colors border border-emerald-200 shrink-0" 
           title="Editar mi perfil comercial">
          <span class="material-symbols-outlined text-[14px]">edit</span>
        </a>
      </div>

      <!-- Menú desplegable de Píldora -->
      <div id="user-dropdown" class="hidden mt-2 w-full bg-white rounded-2xl shadow-lg border border-emerald-200 p-2 z-[80] text-xs">
        <div class="p-2.5 bg-slate-50 rounded-xl mb-1.5 border border-slate-100">
          <div class="flex items-center justify-between gap-1">
            <p class="font-extrabold text-slate-900 text-xs truncate">{{ $companyName }}</p>
            <span class="px-1.5 py-0.5 text-[8px] font-black bg-emerald-100 text-[#006c47] rounded shrink-0">KYC</span>
          </div>
          <p class="text-[10px] text-slate-500 truncate">{{ $merchantUser ? $merchantUser->email : '' }}</p>
        </div>

        <a href="{{ route('merchant.profile') }}" class="flex items-center gap-2 px-2.5 py-2 text-emerald-950 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/60 rounded-xl font-bold mb-1 transition-colors">
          <span class="material-symbols-outlined text-base text-[#10B981]">store</span> Editar Perfil Comercial
        </a>
        <a href="{{ route('merchant.store.edit') }}" class="flex items-center gap-2 px-2.5 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
          <span class="material-symbols-outlined text-base text-slate-500">storefront</span> Configurar Vitrina
        </a>
        <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-2 px-2.5 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
          <span class="material-symbols-outlined text-base text-slate-500">manage_accounts</span> Editar Perfil Personal
        </a>

        <div class="my-1 border-t border-slate-100"></div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="w-full text-left flex items-center gap-2 px-2.5 py-2 text-red-600 hover:bg-red-50 rounded-xl font-bold transition-colors">
            <span class="material-symbols-outlined text-base">logout</span> Cerrar Sesión
          </button>
        </form>
      </div>
    </div>

    <!-- 3. CAMPANA DE NOTIFICACIONES Y ATAJO PEDIDOS -->
    <div id="notification-menu" class="space-y-2">
      <div class="grid grid-cols-2 gap-2">
        <!-- Botón Unificado de Notificaciones -->
        <button id="notification-menu-toggle" aria-expanded="false" aria-controls="notif-dropdown" type="button" 
                class="px-3 py-2 rounded-xl hover:bg-emerald-50 border border-slate-200 bg-white text-slate-700 hover:text-[#006c47] transition-all flex items-center justify-center gap-1.5 shadow-2xs text-xs font-extrabold cursor-pointer {{ request()->routeIs('notifications.*') ? 'border-emerald-400 bg-emerald-50 text-[#006c47]' : '' }}" 
                title="Notificaciones y Alertas del Negocio">
          <span class="material-symbols-outlined text-[18px] text-[#006c47]">notifications</span>
          <span>Alertas</span>
          @if($totalAlertsCount > 0)
            <span class="bg-[#F97316] text-white text-[10px] font-black px-1.5 py-0.5 rounded-full leading-none">
              {{ $totalAlertsCount > 99 ? '99+' : $totalAlertsCount }}
            </span>
          @endif
        </button>

        <!-- Atajo directo Pedidos -->
        <a href="{{ route('merchant.orders') }}" 
           class="px-3 py-2 rounded-xl border text-xs font-extrabold flex items-center justify-center gap-1.5 transition-all shadow-2xs {{ request()->routeIs('merchant.orders*') ? 'bg-[#006c47] text-white border-[#006c47]' : 'bg-white text-slate-700 border-slate-200 hover:border-[#10B981] hover:text-[#006c47]' }}"
           title="Gestión de Pedidos & Guías">
          <span class="material-symbols-outlined text-[17px]">local_shipping</span>
          <span>Pedidos</span>
          @if($pendingOrdersCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black leading-none {{ request()->routeIs('merchant.orders*') ? 'bg-white text-[#006c47]' : 'bg-emerald-100 text-[#006c47]' }}">
              {{ $pendingOrdersCount }}
            </span>
          @endif
        </a>
      </div>

      <!-- Botón Fusabot Negocios (Estilizado, Pequeño y Redondito) -->
      <button type="button" onclick="setFusaBotOpen(true)"
              class="w-full py-1.5 px-3 rounded-full text-white text-xs font-extrabold flex items-center justify-between shadow-[0_3px_12px_rgba(16,185,129,0.28)] hover:shadow-[0_4px_16px_rgba(16,185,129,0.38)] hover:scale-[1.01] active:scale-98 transition-all duration-200 cursor-pointer border border-emerald-400/40 bg-gradient-to-r from-[#006c47] via-[#10B981] to-[#047857] group"
              title="Abrir Asistente FusaBot IA">
        <span class="flex items-center gap-2 min-w-0">
          <span class="relative w-6 h-6 rounded-full bg-white p-0.5 shadow-xs overflow-hidden shrink-0 flex items-center justify-center ring-1 ring-white/60 group-hover:rotate-6 transition-transform">
            <img src="{{ asset('fusabot.png') }}" alt="FusaBot" class="w-full h-full object-cover">
          </span>
          <span class="text-white text-[11.5px] font-extrabold tracking-tight truncate">FusaBot Negocios</span>
        </span>
        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase text-white tracking-wider border border-white/30 bg-white/20 backdrop-blur-xs flex items-center gap-1 shrink-0">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-200 animate-pulse"></span>
          <span>IA</span>
        </span>
      </button>

      <!-- Panel Desplegable de Notificaciones Agrupadas -->
      <div id="notif-dropdown" class="hidden w-full bg-white rounded-2xl shadow-lg border border-emerald-200 overflow-hidden text-xs">
        <div class="p-2.5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
          <h3 class="font-extrabold text-slate-800 text-[11px] flex items-center gap-1">
            <span class="material-symbols-outlined text-sm text-[#10B981]">notifications_active</span> Novedades del Negocio
          </h3>
          <a href="{{ route('notifications.index') }}" class="text-[10px] font-bold text-[#006c47] hover:underline">Ver todas</a>
        </div>

        <div class="p-2 bg-emerald-50/40 border-b border-slate-100 space-y-1">
          <a href="{{ route('merchant.orders') }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
            <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-amber-600">hourglass_top</span> Pedidos por Despachar</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $pendingOrdersCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500' }}">{{ $pendingOrdersCount }}</span>
          </a>
          <a href="{{ route('merchant.inventory') }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
             <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-rose-600">production_quantity_limits</span> Alertas Bajo Stock</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $lowStockCount > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-500' }}">{{ $lowStockCount }}</span>
          </a>
          <a href="{{ route('merchant.reviews') }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
            <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-emerald-600">star</span> Reseñas de Clientes</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $pendingReviewsCount > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">{{ $pendingReviewsCount }}</span>
          </a>
          <a href="{{ route('merchant.messages') }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
            <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-blue-600">forum</span> Mensajes de Clientes</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $unreadMessagesCount > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-500' }}">{{ $unreadMessagesCount }}</span>
          </a>
        </div>

        <div class="max-h-56 overflow-y-auto divide-y divide-slate-100">
          @if($merchantUser)
            @foreach($extraUnreadMessages as $msg)
              @php
                $isFromAnalyst = $msg->sender && $msg->sender->isAnalyst();
                $msgTargetUrl = $isFromAnalyst
                    ? route('merchant.support.contact', ['analyst' => $msg->sender_id])
                    : route('merchant.messages', $msg->sender_id);
              @endphp
              <a href="{{ $msgTargetUrl }}"
                 class="p-2.5 hover:bg-emerald-50/80 transition-all flex items-start gap-2 bg-emerald-50/30 group cursor-pointer block"
                 title="Haz clic para abrir el mensaje de {{ $msg->sender->name ?? ($isFromAnalyst ? 'Analista' : 'Cliente') }}">
                <div class="w-6 h-6 rounded-full {{ $isFromAnalyst ? 'bg-emerald-100 text-[#006c47]' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center shrink-0 mt-0.5">
                  <span class="material-symbols-outlined text-xs">{{ $isFromAnalyst ? 'support_agent' : 'chat_bubble' }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-slate-800 group-hover:text-[#006c47] truncate text-[11px] transition-colors">
                    {{ $isFromAnalyst ? 'Soporte Analista: ' : 'Nuevo mensaje de ' }}{{ $msg->sender->name ?? 'Cliente' }}
                  </p>
                  <p class="text-slate-600 line-clamp-2 text-[10px] mt-0.5 leading-snug">{{ $msg->content }}</p>
                  <div class="flex items-center justify-between mt-1">
                    <span class="text-[9px] text-slate-400">{{ $msg->created_at->diffForHumans() }}</span>
                    <span class="text-[10px] font-extrabold text-[#006c47] group-hover:underline flex items-center gap-0.5">
                      Ver mensaje <span class="material-symbols-outlined text-[11px]">arrow_forward</span>
                    </span>
                  </div>
                </div>
              </a>
            @endforeach

            @forelse($unreadMerchantNotifsList as $notif)
              <a href="{{ route('notifications.open', $notif->id) }}"
                 class="p-2.5 hover:bg-emerald-50/80 transition-all flex items-start gap-2 bg-emerald-50/30 group cursor-pointer block"
                 title="Haz clic para abrir y ver el mensaje completo">
                <div class="w-6 h-6 rounded-full bg-emerald-100 text-[#006c47] flex items-center justify-center shrink-0 mt-0.5">
                  <span class="material-symbols-outlined text-xs">{{ $notif->data['icon'] ?? 'notifications' }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-slate-800 group-hover:text-[#006c47] truncate text-[11px] transition-colors">
                    {{ $notif->data['title'] ?? 'Aviso Comercial' }}
                  </p>
                  <p class="text-slate-600 line-clamp-2 text-[10px] mt-0.5 leading-snug">
                    {{ $notif->data['message'] ?? 'Actividad registrada en tu tienda.' }}
                  </p>
                  <div class="flex items-center justify-between mt-1">
                    <span class="text-[9px] text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>
                    <span class="text-[10px] font-extrabold text-[#006c47] group-hover:underline flex items-center gap-0.5">
                      Ver mensaje <span class="material-symbols-outlined text-[11px]">arrow_forward</span>
                    </span>
                  </div>
                </div>
              </a>
            @empty
              @if($extraUnreadMessages->isEmpty())
                <div class="py-4 text-center text-slate-400">
                  <p class="text-[11px]">Sin notificaciones pendientes por ver</p>
                </div>
              @endif
            @endforelse
          @endif
        </div>
      </div>
    </div>

    <!-- 4. BUSCADOR GLOBAL DEL COMERCIO -->
    <form action="{{ route('merchant.products') }}" method="GET" class="flex items-center bg-slate-100 focus-within:bg-white border border-slate-200 focus-within:border-[#10B981] rounded-xl px-3 py-2 transition-all">
      <span class="material-symbols-outlined text-slate-400 text-[17px]">search</span>
      <input type="text" name="search" value="{{ request('search') }}" 
             placeholder="Buscar en mis productos..." 
             class="bg-transparent border-none text-xs text-slate-800 placeholder-slate-400 focus:ring-0 outline-none pl-2 w-full">
    </form>

    <!-- 5. BOTÓN "⚡ ACCIONES RÁPIDAS" -->
    <div>
      <button type="button" 
              onclick="document.getElementById('merchant-quick-actions-menu').classList.toggle('hidden')"
              class="w-full px-3.5 py-2.5 rounded-xl bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-extrabold flex items-center justify-between transition-all shadow-xs cursor-pointer">
        <span class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">bolt</span>
          <span>Acciones Rápidas</span>
        </span>
        <span class="material-symbols-outlined text-[16px]">expand_more</span>
      </button>

      <div id="merchant-quick-actions-menu" class="hidden mt-2 w-full bg-slate-50 rounded-2xl border border-slate-200 p-1.5 text-xs space-y-0.5">
        <div class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-400">Operaciones Rápidas</div>
        <a href="{{ route('merchant.products.create') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">add_circle</span> Nuevo Producto
        </a>
        <a href="{{ route('merchant.inventory') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">warehouse</span> Ajustar Stock Rápido
        </a>
        @if($merchantUser)
          <a href="{{ route('merchant.reports') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
            <span class="material-symbols-outlined text-base text-[#10B981]">assessment</span> Reportes y Descargas
          </a>
        @endif
        <a href="{{ route('merchant.banner.request') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">campaign</span> Pautar Mi Banner
        </a>
        <a href="{{ route('merchant.support.contact') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">support_agent</span> Contactar Soporte
        </a>
      </div>
    </div>

    <div class="border-t border-slate-100 pt-2">
      <div class="px-2 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Módulos Comerciales</div>

      <!-- 6. NAVEGACIÓN VERTICAL DE MÓDULOS -->
      <nav class="flex flex-col space-y-1">
        <!-- Dashboard -->
        <a href="{{ route('merchant.dashboard') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2.5 {{ request()->routeIs('merchant.dashboard') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="material-symbols-outlined text-[18px]">dashboard</span>
          <span>Dashboard</span>
        </a>

        <!-- Productos -->
        <a href="{{ route('merchant.products') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('merchant.products*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
            <span>Productos</span>
          </span>
          @if($productsCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-700">{{ $productsCount }}</span>
          @endif
        </a>

        <!-- Inventario -->
        <a href="{{ route('merchant.inventory') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('merchant.inventory*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">warehouse</span>
            <span>Inventario / Stock</span>
          </span>
          @if($lowStockCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800" title="Productos con bajo stock">{{ $lowStockCount }}</span>
          @endif
        </a>

        <!-- Pedidos -->
        <a href="{{ route('merchant.orders') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('merchant.orders*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">local_shipping</span>
            <span>Pedidos & Envíos</span>
          </span>
          @if($pendingOrdersCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">{{ $pendingOrdersCount }}</span>
          @endif
        </a>

        <!-- Finanzas -->
        <a href="{{ route('merchant.finances') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2.5 {{ request()->routeIs('merchant.finances*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="material-symbols-outlined text-[18px]">payments</span>
          <span>Finanzas & Ventas</span>
        </a>

        <!-- Reportes de Ventas & Descargas -->
        <a href="{{ route('merchant.reports') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('merchant.reports*') || request()->routeIs('merchant.sales-report*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">assessment</span>
            <span>Reportes de Ventas</span>
          </span>
          <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-50 text-[#006c47] border border-emerald-200">Admin & CSV</span>
        </a>

        <!-- Reseñas -->
        <a href="{{ route('merchant.reviews') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('merchant.reviews*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">reviews</span>
            <span>Reseñas de Clientes</span>
          </span>
          @if($pendingReviewsCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-[#006c47]">{{ $pendingReviewsCount }}</span>
          @endif
        </a>

        <!-- Mensajes / Conversaciones con Clientes -->
        <a href="{{ route('merchant.messages') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('merchant.messages*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">forum</span>
            <span>Mensajes con Clientes</span>
          </span>
          @if($unreadMessagesCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white animate-pulse" title="Mensajes de clientes sin leer">{{ $unreadMessagesCount }}</span>
          @endif
        </a>

        <!-- Perfil Comercial -->
        <a href="{{ route('merchant.profile') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2.5 {{ request()->routeIs('merchant.profile*') || request()->routeIs('merchant.store.edit') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="material-symbols-outlined text-[18px]">store</span>
          <span>Perfil Comercial</span>
        </a>

        <!-- Pautas / Banners -->
        <a href="{{ route('merchant.banner.request') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2.5 {{ request()->routeIs('merchant.banner.request*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="material-symbols-outlined text-[18px]">campaign</span>
          <span>Pautas Publicitarias</span>
        </a>

        <!-- Soporte Técnico con el Analista -->
        <a href="{{ route('merchant.support.contact') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('merchant.support.contact*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">support_agent</span>
            <span>Soporte Técnico</span>
          </span>
          @if($unreadSupportCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-600 text-white animate-pulse" title="Mensajes del analista sin leer">{{ $unreadSupportCount }}</span>
          @endif
        </a>
      </nav>
    </div>

  </div>

  <!-- ====================================================================== -->
  <!-- PIE DE LA BARRA VERTICAL IZQUIERDA: VER VITRINA                        -->
  <!-- ====================================================================== -->
  <div class="p-4 border-t border-slate-100 bg-slate-50/60 space-y-2">
    @if($merchantUser)
      <a href="{{ route('consumer.merchant.profile', $merchantUser->id) }}" target="_blank"
         class="w-full px-3.5 py-2.5 rounded-xl text-xs font-extrabold text-[#006c47] bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 flex items-center justify-center gap-2 transition-all shadow-2xs" 
         title="Ver vitrina de mi tienda como la ven los compradores">
        <span class="material-symbols-outlined text-[18px] text-[#10B981]">visibility</span>
        <span>Ver Mi Vitrina</span>
      </a>
    @endif
    <a href="{{ route('consumer.home') }}" 
       class="w-full px-3.5 py-2 rounded-xl text-[11px] font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 flex items-center justify-center gap-1.5 transition-all" 
       title="Ir a la tienda general de compradores">
      <span class="material-symbols-outlined text-[16px] text-slate-400">shopping_bag</span>
      <span>Tienda General</span>
    </a>
  </div>

</aside>
