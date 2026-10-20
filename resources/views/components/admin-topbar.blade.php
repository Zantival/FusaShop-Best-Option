@php
  $adminUser = auth()->user();
  $pendingKycCount = \App\Models\CompanyProfile::where('kyc_status', 'pending')->count();
  $failedIntegrationsCount = \App\Models\IntegrationLog::where('status', 'failed')->whereNull('resolved_at')->where('created_at', '>=', now()->subDay())->count();
  $openPqrsCount = \App\Models\PQRS::whereIn('status', ['pending', 'in_review'])->count();
  $pendingBannersCount = \App\Models\BannerRequest::where('status', 'pending')->count();
  $unreadDbNotifs = $adminUser ? $adminUser->unreadNotifications()->count() : 0;
  $totalAlertsCount = $pendingKycCount + $failedIntegrationsCount + $openPqrsCount + $pendingBannersCount + $unreadDbNotifs;
@endphp

<aside id="panel-sidebar" data-panel-type="admin" aria-label="Barra lateral de administración"
       class="bg-white border-r border-slate-200 shadow-sm flex flex-col justify-between select-none"
       style="width: 270px; min-width: 270px; height: 100vh; position: sticky; top: 0; left: 0; z-index: 60; overflow-y: auto;">

  <!-- ====================================================================== -->
  <!-- PARTE SUPERIOR Y CENTRAL DE LA BARRA VERTICAL IZQUIERDA                -->
  <!-- ====================================================================== -->
  <div class="p-4 space-y-4">

    <!-- 1. LOGO INSTITUCIONAL -->
    <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
      <x-logo size="default" :href="route('analyst.dashboard')" :with-microcopy="false" />
    </div>

    <!-- 2. PÍLDORA DE PERFIL DEL ADMIN + BOTÓN DE EDITAR PERFIL -->
    <div id="user-menu" class="relative">
      <div class="flex items-center justify-between bg-emerald-50/90 hover:bg-emerald-100/80 border border-emerald-200 rounded-2xl p-2 transition-all gap-2 shadow-2xs">
        <button id="user-menu-toggle" aria-expanded="false" aria-controls="user-dropdown" type="button" class="flex items-center gap-2.5 text-left cursor-pointer min-w-0 flex-1">
          @if($adminUser->avatarUrl())
            <img src="{{ $adminUser->avatarUrl() }}" alt="{{ $adminUser->name }}" class="w-9 h-9 rounded-full object-cover shrink-0 border-2 border-[#10B981] shadow-2xs bg-white">
          @else
            <div class="w-9 h-9 bg-gradient-to-tr from-[#006c47] to-[#10B981] text-white rounded-full flex items-center justify-center font-black text-xs shrink-0 shadow-2xs">
              {{ strtoupper(substr($adminUser->name, 0, 1)) }}
            </div>
          @endif
          <div class="flex flex-col text-left leading-tight min-w-0 flex-1">
            <span class="text-xs font-extrabold text-slate-900 truncate">{{ $adminUser->name }}</span>
            <span class="text-[10px] font-bold text-[#006c47] truncate">{{ $adminUser->adminSubroleLabel() }}</span>
          </div>
          <span class="material-symbols-outlined text-slate-500 text-base shrink-0">expand_more</span>
        </button>

        <a href="{{ route('analyst.users.edit', $adminUser->id) }}" 
           class="w-7 h-7 rounded-full bg-white hover:bg-[#006c47] text-slate-700 hover:text-white flex items-center justify-center shadow-2xs transition-colors border border-emerald-200 shrink-0" 
           title="Editar mi perfil y foto de Administrador">
          <span class="material-symbols-outlined text-[14px]">edit</span>
        </a>
      </div>

      <!-- Menú desplegable de Píldora de Perfil (se abre verticalmente debajo) -->
      <div id="user-dropdown" class="hidden mt-2 w-full bg-white rounded-2xl shadow-lg border border-emerald-200 p-2 z-[80] text-xs">
        <div class="p-2.5 bg-slate-50 rounded-xl mb-1.5 border border-slate-100">
          <div class="flex items-center justify-between gap-1">
            <p class="font-extrabold text-slate-900 text-xs truncate">{{ $adminUser->name }}</p>
            <span class="px-1.5 py-0.5 text-[8px] font-black bg-emerald-100 text-[#006c47] rounded shrink-0">2FA</span>
          </div>
          <p class="text-[10px] text-slate-500 truncate">{{ $adminUser->email }}</p>
        </div>

        <a href="{{ route('analyst.users.edit', $adminUser->id) }}" class="flex items-center gap-2 px-2.5 py-2 text-emerald-950 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/60 rounded-xl font-bold mb-1 transition-colors">
          <span class="material-symbols-outlined text-base text-[#10B981]">photo_camera</span> Editar Perfil y Foto
        </a>
        <a href="{{ route('account.profile') }}" class="flex items-center gap-2 px-2.5 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
          <span class="material-symbols-outlined text-base text-slate-500">lock_reset</span> Cambiar Contraseña
        </a>
        <a href="{{ route('analyst.audit-logs') }}" class="flex items-center gap-2 px-2.5 py-2 text-slate-700 hover:bg-slate-100 rounded-xl font-semibold transition-colors">
          <span class="material-symbols-outlined text-base text-slate-500">policy</span> Bitácora de Auditoría
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

    <!-- 3. CAMPANA DE ALERTAS Y BOTÓN PQRS (BOTONES MÁS PEQUEÑOS Y ELEGANTES) -->
    <div id="notification-menu" class="space-y-1.5">
      <div class="grid grid-cols-2 gap-1.5">
        <!-- Botón Unificado de Alertas -->
        <button id="notification-menu-toggle" aria-expanded="false" aria-controls="notif-dropdown" type="button" 
                class="px-2 py-1.5 rounded-xl hover:bg-emerald-50 border border-slate-200 bg-white text-slate-700 hover:text-[#006c47] transition-all flex items-center justify-center gap-1 shadow-2xs text-[11px] font-extrabold cursor-pointer {{ request()->routeIs('analyst.notifications*') || request()->routeIs('notifications.*') ? 'border-emerald-400 bg-emerald-50 text-[#006c47]' : '' }}" 
                title="Notificaciones y Alertas del Sistema">
          <span class="material-symbols-outlined text-[15px] text-[#006c47] shrink-0">notifications</span>
          <span class="truncate">Alertas</span>
          @if($totalAlertsCount > 0)
            <span class="bg-[#F97316] text-white text-[9px] font-black px-1.5 py-0.5 rounded-full leading-none shrink-0">
              {{ $totalAlertsCount > 99 ? '99+' : $totalAlertsCount }}
            </span>
          @endif
        </button>

        <!-- Atajo directo PQRS -->
        <a href="{{ route('analyst.pqrs.index') }}" 
           class="px-2 py-1.5 rounded-xl border text-[11px] font-extrabold flex items-center justify-center gap-1 transition-all shadow-2xs {{ request()->routeIs('analyst.pqrs*') ? 'bg-[#006c47] text-white border-[#006c47]' : 'bg-white text-slate-700 border-slate-200 hover:border-[#10B981] hover:text-[#006c47]' }}"
           title="Atajo directo a PQRS y SLA">
          <span class="material-symbols-outlined text-[15px] shrink-0">mail</span>
          <span class="truncate">PQRS</span>
          @if($openPqrsCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black leading-none shrink-0 {{ request()->routeIs('analyst.pqrs*') ? 'bg-white text-[#006c47]' : 'bg-emerald-100 text-[#006c47]' }}">
              {{ $openPqrsCount }}
            </span>
          @endif
        </a>
      </div>

      <!-- Botón Fusabot Admin (Estilizado, Pequeño y Redondito) -->
      <button type="button" onclick="setFusaBotOpen(true)"
              class="w-full py-1.5 px-3 rounded-full text-white text-xs font-extrabold flex items-center justify-between shadow-[0_3px_12px_rgba(16,185,129,0.28)] hover:shadow-[0_4px_16px_rgba(16,185,129,0.38)] hover:scale-[1.01] active:scale-98 transition-all duration-200 cursor-pointer border border-emerald-400/40 bg-gradient-to-r from-[#006c47] via-[#10B981] to-[#047857] group"
              title="Abrir Asistente FusaBot IA">
        <span class="flex items-center gap-2 min-w-0">
          <span class="relative w-6 h-6 rounded-full bg-white p-0.5 shadow-xs overflow-hidden shrink-0 flex items-center justify-center ring-1 ring-white/60 group-hover:rotate-6 transition-transform">
            <img src="{{ asset('fusabot.png') }}" alt="FusaBot" class="w-full h-full object-cover">
          </span>
          <span class="text-white text-[11.5px] font-extrabold tracking-tight truncate">FusaBot Asistente</span>
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
            <span class="material-symbols-outlined text-sm text-[#10B981]">notifications_active</span> Alertas Operativas
          </h3>
          <a href="{{ route('analyst.notifications') }}" class="text-[10px] font-bold text-[#006c47] hover:underline">Ver todas</a>
        </div>

        <div class="p-2 bg-emerald-50/40 border-b border-slate-100 space-y-1">
          <a href="{{ route('analyst.users', ['kyc_status' => 'pending']) }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
            <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-amber-600">badge</span> KYC Pendientes</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $pendingKycCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500' }}">{{ $pendingKycCount }}</span>
          </a>
          <a href="{{ route('analyst.integrations', ['status' => 'failed']) }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
            <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-rose-600">webhook</span> Fallos API 24h</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $failedIntegrationsCount > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-500' }}">{{ $failedIntegrationsCount }}</span>
          </a>
          <a href="{{ route('analyst.pqrs.index') }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
            <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-emerald-600">mark_email_unread</span> PQRS Abiertas</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $openPqrsCount > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">{{ $openPqrsCount }}</span>
          </a>
          <a href="{{ route('analyst.banners') }}" class="p-1.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#10B981] flex items-center justify-between transition-all">
            <span class="font-bold text-slate-700 flex items-center gap-1 text-[11px]"><span class="material-symbols-outlined text-xs text-emerald-700">ad_units</span> Banners x Aprobar</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $pendingBannersCount > 0 ? 'bg-emerald-100 text-[#006c47]' : 'bg-slate-100 text-slate-500' }}">{{ $pendingBannersCount }}</span>
          </a>
        </div>

        <div class="max-h-56 overflow-y-auto divide-y divide-slate-100">
          @forelse($adminUser->unreadNotifications()->take(6)->get() as $notif)
            <a href="{{ route('notifications.open', $notif->id) }}"
               class="p-2.5 hover:bg-emerald-50/80 transition-all flex items-start gap-2 bg-emerald-50/30 group cursor-pointer block"
               title="Haz clic para abrir y ver el mensaje">
              <div class="w-6 h-6 rounded-full bg-emerald-100 text-[#006c47] flex items-center justify-center shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-xs">{{ $notif->data['icon'] ?? 'notifications' }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 group-hover:text-[#006c47] truncate text-[11px] transition-colors">{{ $notif->data['title'] ?? 'Alerta del Sistema' }}</p>
                <p class="text-slate-600 line-clamp-2 text-[10px] mt-0.5 leading-snug">{{ $notif->data['message'] ?? 'Evento administrativo registrado.' }}</p>
                <div class="flex items-center justify-between mt-1">
                  <span class="text-[9px] text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>
                  <span class="text-[10px] font-extrabold text-[#006c47] group-hover:underline flex items-center gap-0.5">
                    Ver mensaje <span class="material-symbols-outlined text-[11px]">arrow_forward</span>
                  </span>
                </div>
              </div>
            </a>
          @empty
            <div class="py-4 text-center text-slate-400">
              <p class="text-[11px]">Sin notificaciones pendientes por ver</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <!-- 4. BUSCADOR GLOBAL ADMIN -->
    <form action="{{ route('analyst.search') }}" method="GET" class="flex items-center bg-slate-100 focus-within:bg-white border border-slate-200 focus-within:border-[#10B981] rounded-xl px-3 py-2 transition-all">
      <span class="material-symbols-outlined text-slate-400 text-[17px]">search</span>
      <input type="text" name="q" value="{{ request('q') ?? request('search') }}" 
             placeholder="Buscar #pedido, usuario..." 
             class="bg-transparent border-none text-xs text-slate-800 placeholder-slate-400 focus:ring-0 outline-none pl-2 w-full">
    </form>

    <!-- 5. BOTÓN "⚡ ACCIONES" DESPLEGABLE -->
    <div>
      <button type="button" 
              onclick="document.getElementById('admin-quick-actions-menu').classList.toggle('hidden')"
              class="w-full px-3.5 py-2.5 rounded-xl bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-extrabold flex items-center justify-between transition-all shadow-xs cursor-pointer">
        <span class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[18px]">bolt</span>
          <span>Acciones Rápidas</span>
        </span>
        <span class="material-symbols-outlined text-[16px]">expand_more</span>
      </button>

      <div id="admin-quick-actions-menu" class="hidden mt-2 w-full bg-slate-50 rounded-2xl border border-slate-200 p-1.5 text-xs space-y-0.5">
        <div class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-400">Ejecución Inmediata</div>
        <a href="{{ route('analyst.users.create') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">person_add</span> Nuevo Usuario
        </a>
        <a href="{{ route('analyst.banners') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">add_photo_alternate</span> Subir / Aprobar Banner
        </a>
        <a href="{{ route('analyst.sales-report.print') }}" target="_blank" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">analytics</span> Reporte Causal de Ventas
        </a>
        <a href="{{ route('analyst.reviews-report') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">reviews</span> Voz del Cliente por Tema
        </a>
        <a href="{{ route('analyst.audit-logs') }}" class="flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold">
          <span class="material-symbols-outlined text-base text-[#10B981]">history_edu</span> Log de Auditoría Admin
        </a>
        <div class="my-1 border-t border-slate-200"></div>
        <form action="{{ route('analyst.run-ml') }}" method="POST">
          @csrf
          <button type="submit" class="w-full text-left flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-[#006c47] font-bold cursor-pointer">
            <span class="material-symbols-outlined text-base text-[#10B981]">psychology</span> Ejecutar Segmentación ML
          </button>
        </form>
        <form action="{{ route('analyst.integrations.retry-bulk') }}" method="POST">
          @csrf
          <button type="submit" class="w-full text-left flex items-center gap-2 px-2.5 py-2 rounded-xl hover:bg-amber-50 text-amber-800 font-bold cursor-pointer">
            <span class="material-symbols-outlined text-base text-amber-600">sync_problem</span> Reintentar Fallos API
          </button>
        </form>
      </div>
    </div>

    <div class="border-t border-slate-100 pt-2">
      <div class="px-2 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Módulos de Gestión</div>

      <!-- 6. NAVEGACIÓN VERTICAL DE MÓDULOS -->
      <nav class="flex flex-col space-y-1">
        <a href="{{ route('analyst.dashboard') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2.5 {{ request()->routeIs('analyst.dashboard') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="material-symbols-outlined text-[18px]">dashboard</span>
          <span>Dashboard</span>
        </a>
        <a href="{{ route('analyst.users') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('analyst.users*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">group</span>
            <span>Usuarios & KYC</span>
          </span>
          @if($pendingKycCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">{{ $pendingKycCount }}</span>
          @endif
        </a>
        <a href="{{ route('analyst.orders') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2.5 {{ request()->routeIs('analyst.orders*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="material-symbols-outlined text-[18px]">receipt_long</span>
          <span>Pedidos</span>
        </a>
        <a href="{{ route('analyst.pqrs.index') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('analyst.pqrs*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">mail</span>
            <span>PQRS & SLA</span>
          </span>
          @if($openPqrsCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-[#006c47]">{{ $openPqrsCount }}</span>
          @endif
        </a>
        <a href="{{ route('analyst.banners') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('analyst.banners*') || request()->routeIs('analyst.banner-requests*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">image</span>
            <span>Banners</span>
          </span>
          @if($pendingBannersCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-[#006c47]">{{ $pendingBannersCount }}</span>
          @endif
        </a>
        <a href="{{ route('analyst.integrations') }}" 
           class="px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center justify-between {{ request()->routeIs('analyst.integrations*') ? 'bg-emerald-100 text-[#006c47] font-extrabold shadow-2xs' : 'text-slate-600 hover:text-[#006c47] hover:bg-slate-100' }}">
          <span class="flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px]">hub</span>
            <span>Integraciones</span>
          </span>
          @if($failedIntegrationsCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">{{ $failedIntegrationsCount }}</span>
          @endif
        </a>
      </nav>
    </div>

  </div>

  <!-- ====================================================================== -->
  <!-- PIE DE LA BARRA VERTICAL IZQUIERDA: VER VITRINA                        -->
  <!-- ====================================================================== -->
  <div class="p-4 border-t border-slate-100 bg-slate-50/60 space-y-2">
    <a href="{{ route('consumer.home') }}" 
       class="w-full px-3.5 py-2.5 rounded-xl text-xs font-extrabold text-[#006c47] bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 flex items-center justify-center gap-2 transition-all shadow-2xs" 
       title="Ir a la vitrina pública de la tienda">
      <span class="material-symbols-outlined text-[18px] text-[#10B981]">storefront</span>
      <span>Ver Vitrina</span>
    </a>
  </div>

</aside>
