@extends('layouts.app')
@section('title', 'Panel Ejecutivo Comercial | ' . (auth()->user()->companyProfile->company_name ?? auth()->user()->name) . ' | FusaShop')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  [x-cloak] { display: none !important; }
  .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
  .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.02); border-radius: 9999px; }
  .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 9999px; }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.3); }
</style>
@endpush

@section('content')
<div x-data="{ 
       activeTab: 'global', 
       selectedPeriod: '30d', 
       courseModalOpen: false, 
       activeCourse: null,
       productSearch: '',
       trackingModalOpen: false,
       trackingLoading: false,
       trackingData: null,
       async openTracking(url) {
         this.trackingModalOpen = true;
         this.trackingLoading = true;
         this.trackingData = null;
         try {
           const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
           this.trackingData = await res.json();
         } catch (e) {
           this.trackingData = { message: 'No se pudo conectar con el servicio de rastreo de Envia.com.' };
         } finally {
           this.trackingLoading = false;
         }
       }
     }" 
     class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">

  <!-- ========================================================================= -->
  <!-- 1. HEADER EJECUTIVO DEL COMERCIANTE (IDÉNTICO AL PANEL DEL ANALISTA)      -->
  <!-- ========================================================================= -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20">
    <div class="flex items-center gap-4">
      <div class="w-14 h-14 rounded-2xl bg-emerald-50 border-2 border-emerald-200 shrink-0 flex items-center justify-center shadow-inner overflow-hidden">
        @if(auth()->user()->companyProfile && auth()->user()->companyProfile->logo_path)
          <img src="{{ Storage::url(auth()->user()->companyProfile->logo_path) }}" alt="{{ auth()->user()->companyProfile->company_name }}" class="w-full h-full object-cover">
        @else
          <span class="material-symbols-outlined text-3xl text-[#006c47]">storefront</span>
        @endif
      </div>
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-on-background flex items-center gap-1.5">
            <span>{{ auth()->user()->companyProfile->company_name ?? auth()->user()->name }}</span>
            @if(auth()->user()->companyProfile?->kyc_status === 'approved')
              <span class="material-symbols-outlined text-[#006c47] text-2xl shrink-0" style="font-variation-settings: 'FILL' 1" title="Comercio Verificado">verified</span>
            @endif
          </h1>
          @if(auth()->user()->companyProfile?->kyc_status === 'approved')
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/40 text-[#006c47] border border-[#6efcb9] uppercase tracking-wider inline-flex items-center gap-1">
              <span class="material-symbols-outlined text-[12px]" style="font-variation-settings: 'FILL' 1">verified</span>
              MiPyme Oficial Verificada KYC
            </span>
          @else
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider">
              Sin insignia de verificación
            </span>
          @endif
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
            Enterprise Comerciante
          </span>
        </div>
        <p class="text-on-surface-variant text-xs md:text-sm mt-1">
          Gestión comercial unificada, inventario en tiempo real, logística Envia.com y analítica predictiva del Sumapaz
        </p>
      </div>
    </div>
    
    <!-- Botones de Acción Rápida en el Header -->
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('merchant.products.create') }}" class="px-4 py-2 bg-[#006c47] hover:bg-[#10B981] text-white font-bold rounded-xl text-xs transition-all flex items-center gap-2 shadow-sm">
        <span class="material-symbols-outlined text-sm">add_circle</span> Nuevo Producto
      </a>
      <a href="{{ route('merchant.inventory') }}" class="px-4 py-2 bg-surface-container border border-surface-container-highest text-on-surface font-bold rounded-xl hover:bg-surface-container-high transition-all flex items-center gap-2 text-xs shadow-sm">
        <span class="material-symbols-outlined text-sm">warehouse</span> Ajustar Stock
      </a>
      <a href="{{ route('merchant.sales-report.show', auth()->id()) }}" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-[#006c47] border border-emerald-200 font-bold rounded-xl transition-all flex items-center gap-2 text-xs shadow-sm">
        <span class="material-symbols-outlined text-sm">monitoring</span> Mi Reporte Causal
      </a>
      <a href="{{ route('consumer.merchant.profile', auth()->id()) }}" target="_blank" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition-all flex items-center gap-2 text-xs shadow-sm">
        <span class="material-symbols-outlined text-sm">visibility</span> Ver Mi Vitrina
      </a>
    </div>
  </div>

  <!-- Alerta de Notificación de Reporte Causal Recibido (Solo si no ha sido visto) -->
  @php
    $latestSalesReportNotif = auth()->user()->unreadNotifications()->get()
      ->first(fn($n) => ($n->data['type'] ?? '') === 'merchant_sales_report');
  @endphp
  @if($latestSalesReportNotif)
    <a href="{{ route('notifications.open', $latestSalesReportNotif->id) }}" class="bg-emerald-50/90 hover:bg-emerald-100/80 text-slate-900 border border-emerald-300 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm transition-all group block">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#006c47] text-white flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-lg">insights</span>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="font-bold text-xs text-[#006c47] group-hover:underline">{{ $latestSalesReportNotif->data['title'] ?? '📊 Diagnóstico de Ventas Actualizado por el Analista' }}</h3>
            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-emerald-200 text-emerald-900 uppercase">Nuevo</span>
          </div>
          <p class="text-[11px] text-slate-700 mt-0.5">{{ $latestSalesReportNotif->data['message'] ?? 'Se ha generado un nuevo reporte con métricas de ventas y recomendaciones para tu tienda.' }}</p>
        </div>
      </div>
      <span class="px-3.5 py-1.5 bg-[#006c47] group-hover:bg-[#10B981] text-white font-extrabold text-xs rounded-xl shrink-0 shadow-sm transition-all flex items-center gap-1.5 self-start sm:self-auto">
        <span class="material-symbols-outlined text-xs">open_in_new</span> Abrir Reporte
      </span>
    </a>
  @endif

  <!-- Alerta de Stock Crítico -->
  @if($outOfStock > 0 || $lowStock > 0)
    <div class="bg-rose-50 text-rose-900 border border-rose-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-xl">warning</span>
        </div>
        <div>
          <h3 class="font-bold text-xs text-rose-800">Alerta de Control de Inventario</h3>
          <p class="text-[11px] text-rose-700 mt-0.5">
            Tienes <strong>{{ $outOfStock }}</strong> producto(s) totalmente agotado(s) y <strong>{{ $lowStock }}</strong> con stock crítico (menor a 5 unidades).
          </p>
        </div>
      </div>
      <button type="button" @click="activeTab = 'catalog'" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shrink-0 shadow-sm transition-all flex items-center gap-1 self-start sm:self-auto">
        <span class="material-symbols-outlined text-xs">tune</span> Resolver en Inventario
      </button>
    </div>
  @endif

  <!-- ========================================================================= -->
  <!-- 2. BARRA DE CONTROL DE PERIODOS Y PESTAÑAS SEGMENTADAS (ESTILO ANALISTA) -->
  <!-- ========================================================================= -->
  <div class="bg-white p-4 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-3">
    
    <!-- Fila de Filtros y Periodo Global -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-outline-variant/15">
      <div class="flex flex-wrap items-center gap-2">
        <span class="material-symbols-outlined text-[#006c47] text-xl">calendar_month</span>
        <span class="text-xs font-extrabold text-on-background uppercase tracking-wider">Periodo Comercial:</span>
        @foreach(['today' => 'Hoy', '7d' => '7 Días', '30d' => '30 Días', '90d' => 'Trimestre', 'all' => 'Histórico'] as $pKey => $pLbl)
          <button type="button"
                  @click="selectedPeriod = '{{ $pKey }}'"
                  :class="selectedPeriod === '{{ $pKey }}' ? 'bg-[#006c47] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-[#006c47]'"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer">
            {{ $pLbl }}
          </button>
        @endforeach
      </div>

      <div class="flex flex-wrap items-center gap-3 text-xs">
        <div class="flex items-center gap-1.5 bg-emerald-50/80 border border-emerald-200/80 rounded-xl px-3 py-1 text-slate-700">
          <span class="font-extrabold text-[#006c47]">Comisión FusaShop:</span>
          <span class="font-black text-slate-900">8.0%</span>
        </div>
        <div class="flex items-center gap-1.5 bg-emerald-50/80 border border-emerald-200/80 rounded-xl px-3 py-1 text-slate-700">
          <span class="font-extrabold text-[#006c47]">Liquidación Neta:</span>
          <span class="font-black text-[#006c47]">92.0%</span>
        </div>
        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1 text-slate-700">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="font-bold text-slate-800">Vitrina Abierta al Público</span>
        </div>
      </div>
    </div>

    <!-- Pestañas Segmentadas (Estilo Analista con Iconos, Estados y Badges) -->
    <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pt-1">
      <!-- Tab 1: Visión Global -->
      <button type="button"
              @click="activeTab = 'global'"
              :class="activeTab === 'global' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer">
        <span class="material-symbols-outlined text-base">monitoring</span>
        <span>Tab 1: Visión General, Ventas & Diagnóstico IA</span>
      </button>

      <!-- Tab 2: Catálogo & Stock -->
      <button type="button"
              @click="activeTab = 'catalog'"
              :class="activeTab === 'catalog' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer relative">
        <span class="material-symbols-outlined text-base">inventory_2</span>
        <span>Tab 2: Catálogo de Productos & Control de Stock</span>
        @if(($outOfStock + $lowStock) > 0)
          <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white leading-none">
            {{ $outOfStock + $lowStock }}
          </span>
        @endif
      </button>

      <!-- Tab 3: Pedidos & Envia.com -->
      <button type="button"
              @click="activeTab = 'orders'"
              :class="activeTab === 'orders' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer relative">
        <span class="material-symbols-outlined text-base">local_shipping</span>
        <span>Tab 3: Pedidos & Despachos Envia.com</span>
        @if($pendingOrders > 0)
          <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white leading-none">
            {{ $pendingOrders }}
          </span>
        @endif
      </button>

      <!-- Tab 4: Finanzas & Banners -->
      <button type="button"
              @click="activeTab = 'finances'"
              :class="activeTab === 'finances' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer">
        <span class="material-symbols-outlined text-base">payments</span>
        <span>Tab 4: Finanzas, Balance & Pautas Publicitarias</span>
      </button>

      <!-- Tab 5: Reseñas & Rating -->
      <button type="button"
              @click="activeTab = 'reviews'"
              :class="activeTab === 'reviews' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer">
        <span class="material-symbols-outlined text-base">reviews</span>
        <span>Tab 5: Reseñas & Reputación</span>
        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 ml-1">
          ★ {{ $avgRating }}
        </span>
      </button>

      <!-- Tab 6: Academia -->
      <button type="button"
              @click="activeTab = 'academy'"
              :class="activeTab === 'academy' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer">
        <span class="material-symbols-outlined text-base">school</span>
        <span>Tab 6: Academia de Formación</span>
      </button>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- TAB 1: VISIÓN GENERAL, VENTAS & DIAGNÓSTICO IA                            -->
  <!-- ========================================================================= -->
  <div x-show="activeTab === 'global'" class="space-y-6">

    <!-- KPI STRIP (4 TARJETAS CON BADGES COMPARATIVOS IDÉNTICAS AL ANALISTA) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      
      <!-- KPI 1: Ventas del Mes -->
      <div class="bg-white rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-[#006c47] text-lg">payments</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/40 text-[#006c47]">
            ▲ +18.4% vs mes ant.
          </span>
        </div>
        <div>
          <p class="text-2xl font-['Manrope'] font-extrabold text-on-background">
            ${{ number_format($monthlySales, 0, ',', '.') }}
          </p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Ventas del Mes (Bruto)</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">Total acumulado: ${{ number_format($totalSales, 0, ',', '.') }} COP</p>
        </div>
      </div>

      <!-- KPI 2: Pedidos Pendientes de Despacho -->
      <div class="bg-white rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-amber-700 text-lg">pending_actions</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $pendingOrders > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-[#006c47]' }}">
            {{ $pendingOrders > 0 ? '● ' . $pendingOrders . ' Por despachar' : '✓ Al día' }}
          </span>
        </div>
        <div>
          <p class="text-2xl font-['Manrope'] font-extrabold {{ $pendingOrders > 0 ? 'text-amber-600' : 'text-on-background' }}">
            {{ $pendingOrders }}
          </p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Despachos Pendientes</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">Total órdenes procesadas: {{ $totalOrdersCount }}</p>
        </div>
      </div>

      <!-- KPI 3: Tasa de Cumplimiento & Reputación -->
      <div class="bg-white rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-700 text-lg">verified</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
            ★ {{ $avgRating }} / 5.0
          </span>
        </div>
        <div>
          <p class="text-2xl font-['Manrope'] font-extrabold text-on-background">{{ $fulfillmentRate }}%</p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Efectividad de Entrega</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">{{ $totalReviews }} reseñas verificadas de clientes</p>
        </div>
      </div>

      <!-- KPI 4: Saldo Listo a Liquidar -->
      <div class="bg-white rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-purple-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-purple-700 text-lg">account_balance</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
            Neto 92%
          </span>
        </div>
        <div>
          <p class="text-2xl font-['Manrope'] font-extrabold text-purple-900">
            ${{ number_format($readyToWithdraw, 0, ',', '.') }}
          </p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Saldo Listo a Liquidar</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">Deducida comisión de plataforma (8%)</p>
        </div>
      </div>

    </div>

    <!-- GRÁFICOS INTERACTIVOS CHART.JS (ESTILO ANALISTA EJECUTIVO) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Gráfico de Tendencia de Ventas (2 columnas) -->
      <div class="lg:col-span-2 bg-white p-5 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/15">
          <div>
            <h3 class="text-base font-['Manrope'] font-extrabold text-on-background flex items-center gap-2">
              <span class="material-symbols-outlined text-[#006c47]">trending_up</span> Curva de Ventas GMV Semanal
            </h3>
            <p class="text-xs text-on-surface-variant">Facturación diaria consolidada de tus productos en Fusagasugá</p>
          </div>
          <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-50 text-[#006c47] border border-emerald-200">
            Últimos 7 días
          </span>
        </div>
        <div class="h-64 relative">
          <canvas id="merchantSalesChart"></canvas>
        </div>
      </div>

      <!-- Gráfico de Distribución por Categoría (1 columna) -->
      <div class="bg-white p-5 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/15">
          <div>
            <h3 class="text-base font-['Manrope'] font-extrabold text-on-background flex items-center gap-2">
              <span class="material-symbols-outlined text-[#10B981]">pie_chart</span> Catálogo por Categorías
            </h3>
            <p class="text-xs text-on-surface-variant">Distribución de tu oferta comercial</p>
          </div>
          <span class="text-xs font-bold text-slate-500">{{ $totalProducts }} total</span>
        </div>
        <div class="h-64 relative flex items-center justify-center">
          <canvas id="merchantCategoryChart"></canvas>
        </div>
      </div>
    </div>

    <!-- DIAGNÓSTICO CAUSAL Y MOTOR NEURONAL FUSABOT -->
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 text-white p-6 rounded-2xl shadow-xl relative overflow-hidden border border-emerald-500/30">
      <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
        <div class="space-y-2 max-w-3xl">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/30 text-emerald-200 border border-emerald-400/30 uppercase tracking-widest flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">auto_awesome</span> Motor Neuronal FusaBot & Diagnóstico Causal
            </span>
            <span class="text-xs text-emerald-300">Análisis continuo de tu comercio</span>
          </div>

          <h3 class="text-lg sm:text-xl font-['Manrope'] font-extrabold text-white">
            Sugerencias de Optimización para Aumentar Ventas
          </h3>

          <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
            {{ $aiDiagnostics['price_recommendation'] }}
          </p>

          @if($aiDiagnostics['stock_warning'])
            <div class="p-3 bg-rose-500/20 border border-rose-500/40 rounded-xl text-xs text-rose-200 flex items-center gap-2 font-medium">
              <span class="material-symbols-outlined text-sm shrink-0">info</span>
              <span>{{ $aiDiagnostics['stock_warning'] }}</span>
            </div>
          @endif
        </div>

        <div class="shrink-0 flex flex-col sm:flex-row lg:flex-col gap-2">
          <button type="button" @click="activeTab = 'catalog'" class="px-4 py-2.5 bg-white text-slate-900 hover:bg-slate-100 font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">tune</span> Ajustar Precios y Stock
          </button>
          <a href="{{ route('merchant.sales-report.show', auth()->id()) }}" class="px-4 py-2.5 bg-emerald-800/80 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl border border-emerald-400/30 transition-all flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">insights</span> Abrir Diagnóstico Detallado
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- TAB 2: CATÁLOGO DE PRODUCTOS & CONTROL DE STOCK                           -->
  <!-- ========================================================================= -->
  <div x-show="activeTab === 'catalog'" class="space-y-6" style="display: none;">
    <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
      
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/15">
        <div>
          <h2 class="text-xl font-['Manrope'] font-extrabold text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">inventory_2</span> Catálogo Completo & Control de Inventario
          </h2>
          <p class="text-xs text-on-surface-variant mt-0.5">
            Ajusta stock en vivo, actualiza precios y administra la visibilidad de tus productos
          </p>
        </div>

        <div class="flex items-center gap-2">
          <input type="text" 
                 x-model="productSearch"
                 placeholder="Filtrar por nombre o categoría..." 
                 class="px-3 py-2 bg-slate-50 border border-slate-200 text-xs rounded-xl focus:outline-none focus:ring-2 focus:ring-[#10B981] w-48 sm:w-64">
          <a href="{{ route('merchant.products.create') }}" class="px-4 py-2 bg-[#006c47] hover:bg-[#10B981] text-white font-bold text-xs rounded-xl shadow-sm flex items-center gap-1.5 transition-all shrink-0">
            <span class="material-symbols-outlined text-sm">add_circle</span> Crear Producto
          </a>
        </div>
      </div>

      <!-- Tabla de Productos -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-outline-variant/20 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
              <th class="py-3 px-4">Producto</th>
              <th class="py-3 px-4">Categoría</th>
              <th class="py-3 px-4">Precio COP</th>
              <th class="py-3 px-4">Estado Stock</th>
              <th class="py-3 px-4">Ajustar Unidades</th>
              <th class="py-3 px-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10 text-xs">
            @forelse($products as $prod)
              <tr class="hover:bg-slate-50 transition-colors"
                  x-show="!productSearch || '{{ strtolower($prod->name . ' ' . $prod->category) }}'.includes(productSearch.toLowerCase())">
                
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                      @if($prod->image_url)
                        <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="w-full h-full object-cover">
                      @else
                        <div class="w-full h-full flex items-center justify-center text-slate-400">
                          <span class="material-symbols-outlined text-base">image</span>
                        </div>
                      @endif
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 line-clamp-1">{{ $prod->name }}</p>
                      <span class="text-[10px] text-slate-500 font-mono">SKU: {{ $prod->id }}</span>
                    </div>
                  </div>
                </td>

                <td class="py-3.5 px-4 font-semibold text-slate-700">
                  <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[10px] font-bold">
                    {{ ucfirst($prod->category ?? 'General') }}
                  </span>
                </td>

                <td class="py-3.5 px-4 font-black text-slate-900">
                  ${{ number_format($prod->price, 0, ',', '.') }}
                </td>

                <td class="py-3.5 px-4">
                  @if($prod->stock == 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 uppercase tracking-wider">Agotado</span>
                  @elseif($prod->stock <= 5)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 uppercase tracking-wider">Crítico ({{ $prod->stock }})</span>
                  @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-[#006c47] uppercase tracking-wider">En Stock ({{ $prod->stock }})</span>
                  @endif
                </td>

                <td class="py-3.5 px-4">
                  <form method="POST" action="{{ route('merchant.inventory.update') }}" class="flex items-center gap-1.5">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $prod->id }}">
                    <input type="number" name="stock" value="{{ $prod->stock }}" min="0" class="w-16 px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-bold text-center focus:ring-1 focus:ring-[#10B981]">
                    <button type="submit" class="px-2.5 py-1 bg-emerald-50 hover:bg-[#006c47] text-[#006c47] hover:text-white rounded-lg text-[10px] font-bold transition-all border border-emerald-200" title="Guardar stock">
                      OK
                    </button>
                  </form>
                </td>

                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <a href="{{ route('merchant.products.edit', $prod->id) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-[#006c47] hover:bg-slate-100 transition-colors" title="Editar producto">
                      <span class="material-symbols-outlined text-[16px]">edit</span>
                    </a>
                    <a href="{{ route('consumer.product', $prod->id) }}" target="_blank" class="p-1.5 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-slate-100 transition-colors" title="Ver en tienda">
                      <span class="material-symbols-outlined text-[16px]">visibility</span>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-10 text-slate-500 text-xs">
                  Aún no has registrado productos en tu vitrina comercial.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- TAB 3: PEDIDOS & LOGÍSTICA ENVIA.COM                                      -->
  <!-- ========================================================================= -->
  <div x-show="activeTab === 'orders'" class="space-y-6" style="display: none;">
    <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
      
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/15">
        <div>
          <h2 class="text-xl font-['Manrope'] font-extrabold text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">local_shipping</span> Logística y Despacho Automatizado Envia.com
          </h2>
          <p class="text-xs text-on-surface-variant mt-0.5">
            Generación oficial de guías, impresión de etiquetas térmicas y trazabilidad nacional en tiempo real
          </p>
        </div>

        <a href="{{ route('merchant.orders') }}" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30 flex items-center gap-1.5 transition-all">
          <span class="material-symbols-outlined text-sm">list_alt</span> Ver Todos los Pedidos
        </a>
      </div>

      @if($recentOrders->isEmpty())
        <div class="text-center py-12 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
          <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">receipt_long</span>
          <p class="text-sm font-bold text-slate-700">No tienes pedidos pendientes de despacho</p>
          <p class="text-xs text-slate-400 mt-1">Los nuevos pedidos pagados por clientes aparecerán aquí con botón de despacho inmediato.</p>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-outline-variant/20 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                <th class="py-3 px-4">Orden</th>
                <th class="py-3 px-4">Cliente / Contacto</th>
                <th class="py-3 px-4">Productos / Cant.</th>
                <th class="py-3 px-4">Total</th>
                <th class="py-3 px-4">Estado Pedido</th>
                <th class="py-3 px-4">Guía Envia.com</th>
                <th class="py-3 px-4 text-right">Acción Logística</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/10 text-xs">
              @foreach($recentOrders as $order)
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                    #{{ $order->id }}
                    <span class="block text-[10px] text-slate-400 font-normal">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                  </td>

                  <td class="py-3.5 px-4">
                    <p class="font-bold text-slate-900">{{ $order->user->name ?? 'Cliente' }}</p>
                    <span class="text-[11px] text-slate-500">{{ $order->user->phone ?? 'Sin teléfono' }}</span>
                  </td>

                  <td class="py-3.5 px-4">
                    <div class="space-y-0.5 max-w-[200px]">
                      @foreach($order->items as $item)
                        <div class="truncate text-[11px] text-slate-700">
                          <span class="font-bold">x{{ $item->quantity }}</span> {{ $item->product->name ?? 'Producto' }}
                        </div>
                      @endforeach
                    </div>
                  </td>

                  <td class="py-3.5 px-4 font-black text-slate-900">
                    ${{ number_format($order->total, 0, ',', '.') }} COP
                  </td>

                  <td class="py-3.5 px-4">
                    @php
                      $badgeColors = [
                        'pending' => 'bg-amber-100 text-amber-800',
                        'processing' => 'bg-blue-100 text-blue-800',
                        'shipped' => 'bg-indigo-100 text-indigo-800',
                        'delivered' => 'bg-emerald-100 text-emerald-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                      ];
                    @endphp
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $badgeColors[$order->status] ?? 'bg-slate-100 text-slate-700' }}">
                      {{ $order->stageLabel() }}
                    </span>
                  </td>

                  <td class="py-3.5 px-4 font-mono text-[11px]">
                    @if($order->tracking_number)
                      <div class="space-y-0.5">
                        <span class="font-bold text-[#006c47] block">{{ $order->tracking_number }}</span>
                        <span class="text-[10px] text-slate-400 uppercase">{{ $order->shipping_carrier ?? 'Envia' }}</span>
                      </div>
                    @else
                      <span class="text-slate-400 italic text-[11px]">Sin guía</span>
                    @endif
                  </td>

                  <td class="py-3.5 px-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      @if($order->status === 'cancelled')
                        <span class="text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-lg" title="{{ $order->notes }}">
                          Pedido cancelado
                        </span>
                      @elseif($order->tracking_number)
                        <button type="button"
                                @click="openTracking('{{ route('merchant.orders.tracking', $order->id) }}')"
                                class="px-3 py-1.5 bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1 cursor-pointer"
                                title="Consultar rastreo en vivo con Envia.com">
                          <span class="material-symbols-outlined text-[15px]">travel_explore</span>
                          <span>Ver Rastreo</span>
                        </button>
                      @elseif($order->payment_status === 'paid')
                        <span class="text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-lg">Generando en Envia...</span>
                      @endif

                      @if($order->label_url)
                        <a href="{{ $order->label_url }}" target="_blank" rel="noopener" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-all flex items-center gap-1" title="Imprimir Etiqueta Térmica">
                          <span class="material-symbols-outlined text-[15px] text-[#006c47]">print</span>
                          <span>Etiqueta</span>
                        </a>
                      @endif

                      @if($order->tracking_url)
                        <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-100 transition-colors" title="Abrir en Portal Envia.com">
                          <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                        </a>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- TAB 4: FINANZAS, BALANCE & PAUTAS PUBLICITARIAS                           -->
  <!-- ========================================================================= -->
  <div x-show="activeTab === 'finances'" class="space-y-6" style="display: none;">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- Card Balance Bruto -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Facturado</span>
        <p class="text-3xl font-['Manrope'] font-black text-slate-900">${{ number_format($totalSales, 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500">Volumen total de transacciones completadas</p>
      </div>

      <!-- Card Comisión FusaShop -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Comisión Plataforma (8%)</span>
        <p class="text-3xl font-['Manrope'] font-black text-amber-600">${{ number_format(round($totalSales * 0.08), 0, ',', '.') }}</p>
        <p class="text-xs text-slate-500">Tarifa fija por procesamiento e infraestructura local</p>
      </div>

      <!-- Card Saldo Neto Liquidable -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-2">
        <span class="text-xs font-bold uppercase tracking-wider text-[#006c47]">Saldo Neto Liquidable (92%)</span>
        <p class="text-3xl font-['Manrope'] font-black text-[#006c47]">${{ number_format($netIncome, 0, ',', '.') }}</p>
        <a href="{{ route('merchant.finances') }}" class="inline-block mt-1 text-xs font-bold text-[#006c47] hover:underline">Solicitar Transferencia Bancaria →</a>
      </div>

    </div>

    <!-- Pauta de Banners -->
    <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-1">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-2xl text-[#F97316]">campaign</span>
          <h3 class="text-lg font-['Manrope'] font-extrabold text-slate-900">Pauta Publicitaria en Banners Principales</h3>
        </div>
        <p class="text-xs text-slate-600">
          Destaca tus productos en el carrusel de la página de inicio ante miles de compradores de Fusagasugá y el Sumapaz.
        </p>
      </div>
      <a href="{{ route('merchant.banner.request') }}" class="px-5 py-2.5 bg-[#F97316] hover:bg-orange-600 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-2 shrink-0">
        <span class="material-symbols-outlined text-sm">add_photo_alternate</span> Solicitar Banner Comercial
      </a>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- TAB 5: RESEÑAS & SATISFACCIÓN                                             -->
  <!-- ========================================================================= -->
  <div x-show="activeTab === 'reviews'" class="space-y-6" style="display: none;">
    <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-outline-variant/15">
        <div>
          <h2 class="text-xl font-['Manrope'] font-extrabold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500">reviews</span> Calificaciones de tus Compradores
          </h2>
          <p class="text-xs text-slate-500">Reputación y comentarios de clientes que compraron en tu vitrina</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800">
          ★ Promedio {{ $avgRating }} / 5.0
        </span>
      </div>

      <div class="space-y-3">
        @forelse($recentReviews as $rev)
          <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-slate-900">{{ $rev->user->name ?? 'Comprador verificado' }}</span>
                <span class="text-amber-500 font-bold text-xs">★ {{ $rev->rating }}</span>
              </div>
              <span class="text-[10px] text-slate-400">{{ $rev->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-xs text-slate-700 italic">"{{ $rev->comment }}"</p>
            <p class="text-[10px] text-slate-400">Producto: <strong>{{ $rev->product->name ?? 'Artículo' }}</strong></p>
          </div>
        @empty
          <div class="text-center py-8 text-slate-400 text-xs">
            Aún no tienes reseñas de compradores. Tus valoraciones aparecerán aquí.
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- TAB 6: ACADEMIA DE CAPACITACIÓN                                           -->
  <!-- ========================================================================= -->
  <div x-show="activeTab === 'academy'" class="space-y-6" style="display: none;">
    <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-outline-variant/15">
        <div>
          <h2 class="text-xl font-['Manrope'] font-extrabold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">school</span> Academia FusaShop para Comerciantes
          </h2>
          <p class="text-xs text-slate-500">Talleres interactivos sobre facturación, empaque Envia.com y ventas locales</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-[#006c47]">3 Módulos Disponibles</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($academyCourses as $course)
          <div class="bg-slate-50 rounded-2xl overflow-hidden border border-slate-200/80 flex flex-col justify-between group hover:border-[#10B981] transition-all">
            <div>
              <div class="relative h-36 overflow-hidden bg-slate-900">
                <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 opacity-90">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[9px] font-black uppercase bg-white/95 text-slate-900">
                  {{ $course['category'] }}
                </span>
                <span class="absolute bottom-2 right-2 text-white text-[10px] font-bold">
                  {{ $course['duration'] }}
                </span>
              </div>
              <div class="p-4 space-y-1">
                <h4 class="font-bold text-xs text-slate-900 line-clamp-2">{{ $course['title'] }}</h4>
                <p class="text-[11px] text-slate-500">{{ $course['instructor'] }}</p>
              </div>
            </div>
            <div class="p-4 pt-0">
              <button type="button" 
                      @click="activeCourse = {{ json_encode($course) }}; courseModalOpen = true;"
                      class="w-full py-2 bg-emerald-50 hover:bg-[#006c47] text-[#006c47] hover:text-white font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 border border-emerald-200">
                <span class="material-symbols-outlined text-sm">play_circle</span> Ver Taller
              </button>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- MODAL REPRODUCTOR DE TALLERES ACADEMIA -->
  <div x-show="courseModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" @click.self="courseModalOpen = false">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden" @click.stop>
      <div class="p-4 flex items-center justify-between border-b border-slate-100">
        <div>
          <span class="text-[10px] font-black uppercase tracking-wider text-[#006c47]" x-text="activeCourse ? activeCourse.category : ''"></span>
          <h3 class="font-bold text-sm text-slate-900" x-text="activeCourse ? activeCourse.title : ''"></h3>
        </div>
        <button type="button" @click="courseModalOpen = false" class="p-1 rounded-full hover:bg-slate-100 text-slate-500">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="aspect-video bg-black flex items-center justify-center text-white space-y-2 p-6 text-center">
        <div>
          <span class="material-symbols-outlined text-5xl text-emerald-400 animate-pulse">smart_display</span>
          <p class="font-bold text-base mt-2" x-text="activeCourse ? activeCourse.title : ''"></p>
          <p class="text-xs text-slate-300 mt-1 max-w-sm">Capacitación oficial para el fortalecimiento del comercio local en Fusagasugá.</p>
        </div>
      </div>

      <div class="p-4 bg-slate-50 flex items-center justify-between">
        <span class="text-xs text-slate-500" x-text="activeCourse ? 'Instructor: ' + activeCourse.instructor : ''"></span>
        <button type="button" @click="courseModalOpen = false" class="px-4 py-1.5 bg-[#006c47] text-white font-bold text-xs rounded-xl hover:bg-emerald-700 transition-all">
          Cerrar
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL DE RASTREO OFICIAL ENVIA.COM -->
  <div x-show="trackingModalOpen" 
       x-cloak 
       style="display: none;" 
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" 
       @click.self="trackingModalOpen = false">
    
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200" @click.stop>
      
      <!-- Modal Header -->
      <div class="p-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center border border-emerald-500/30">
            <span class="material-symbols-outlined text-xl">local_shipping</span>
          </div>
          <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400">Rastreo Oficial Envia.com API</span>
            <h3 class="text-base font-extrabold font-['Manrope']">Estado del Envío en Tiempo Real</h3>
          </div>
        </div>
        <button type="button" @click="trackingModalOpen = false" class="p-1 rounded-full text-white/70 hover:text-white hover:bg-white/10 transition-colors">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-6">
        
        <!-- Loading State -->
        <template x-if="trackingLoading">
          <div class="py-12 text-center space-y-3">
            <span class="material-symbols-outlined text-4xl text-[#006c47] animate-spin">progress_activity</span>
            <p class="text-xs font-bold text-slate-600">Consultando los servidores de Envia.com...</p>
          </div>
        </template>

        <!-- Tracking Content -->
        <template x-if="!trackingLoading && trackingData">
          <div class="space-y-6">

            <!-- Fila de Detalles: Guía y Transportadora -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 text-xs">
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase">Transportadora</span>
                <p class="font-black text-slate-900" x-text="trackingData.carrier || 'Envia.com'"></p>
              </div>
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase">Número de Guía</span>
                <p class="font-mono font-black text-[#006c47]" x-text="trackingData.tracking_number || 'En asignación'"></p>
              </div>
              <div class="col-span-2 sm:col-span-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase">Estado Actual</span>
                <p class="font-extrabold text-blue-700" x-text="trackingData.status_label || trackingData.status || 'En tránsito'"></p>
              </div>
            </div>

            <!-- STEPPER VISUAL (5 ETAPAS) -->
            <div class="pt-2">
              <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-4">Línea de Progreso del Envío</h4>
              
              <div class="relative flex items-center justify-between">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-200 z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-[#10B981] transition-all duration-500 z-0"
                     :style="'width: ' + (((trackingData.step || 3) - 1) * 25) + '%'"></div>

                <!-- Paso 1 -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all bg-[#006c47] text-white shadow-sm ring-4 ring-white">
                    <span class="material-symbols-outlined text-[15px]">credit_score</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1 text-slate-800">Pagado</span>
                </div>

                <!-- Paso 2 -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 2 ? 'bg-[#006c47] text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">receipt_long</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 2 ? 'text-slate-800' : 'text-slate-400'">Guía</span>
                </div>

                <!-- Paso 3 -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 3 ? 'bg-[#006c47] text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">local_shipping</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 3 ? 'text-slate-800' : 'text-slate-400'">Tránsito</span>
                </div>

                <!-- Paso 4 -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 4 ? 'bg-[#006c47] text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">two_wheeler</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 4 ? 'text-slate-800' : 'text-slate-400'">Reparto</span>
                </div>

                <!-- Paso 5 -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 5 ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">done_all</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 5 ? 'text-emerald-700 font-black' : 'text-slate-400'">Entregado</span>
                </div>
              </div>
            </div>

            <!-- Historial de Novedades -->
            <div class="space-y-3">
              <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Historial de Novedades del Transportador</h4>

              <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-1">
                <template x-for="(ev, idx) in (trackingData.events || [])" :key="idx">
                  <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-start justify-between gap-3 text-xs">
                    <div class="space-y-0.5">
                      <p class="font-bold text-slate-900" x-text="ev.status"></p>
                      <p class="text-[11px] text-slate-500" x-show="ev.description" x-text="ev.description"></p>
                      <p class="text-[10px] text-emerald-700 font-semibold" x-show="ev.location" x-text="'📍 ' + ev.location"></p>
                    </div>
                    <div class="text-right shrink-0 text-[10px] text-slate-400 font-medium">
                      <span class="block font-bold text-slate-600" x-text="ev.date"></span>
                      <span x-text="ev.time"></span>
                    </div>
                  </div>
                </template>
                <div x-show="!trackingData.events || trackingData.events.length === 0" class="text-center py-4 text-xs text-slate-400">
                  Sin eventos registrados en la transportadora por el momento.
                </div>
              </div>
            </div>

            <!-- Botón Externo de Envia -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs" x-show="trackingData.url">
              <span class="text-slate-500">¿Quieres ver la trazabilidad directa?</span>
              <a :href="trackingData.url" target="_blank" rel="noopener" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl flex items-center gap-1.5 transition-all">
                <span>Abrir en Envia.com</span>
                <span class="material-symbols-outlined text-sm">open_in_new</span>
              </a>
            </div>

          </div>
        </template>

      </div>

      <!-- Modal Footer -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
        <button type="button" @click="trackingModalOpen = false" class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition-all">
          Cerrar
        </button>
      </div>

    </div>
  </div>

</div>

<!-- SCRIPT DE INICIALIZACIÓN DE GRÁFICOS CHART.JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const salesLabels = {!! json_encode(array_column($dailySales, 'label')) !!};
  const salesValues = {!! json_encode(array_column($dailySales, 'sales')) !!};

  // 1. Gráfico de Tendencia de Ventas (Línea con Gradiente)
  const salesCanvas = document.getElementById('merchantSalesChart');
  if (salesCanvas) {
    const ctx = salesCanvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(0, 108, 71, 0.35)');
    gradient.addColorStop(1, 'rgba(0, 108, 71, 0.0)');

    new Chart(salesCanvas, {
      type: 'line',
      data: {
        labels: salesLabels,
        datasets: [{
          label: 'Ventas Diarias (COP)',
          data: salesValues,
          fill: true,
          backgroundColor: gradient,
          borderColor: '#006c47',
          borderWidth: 2.5,
          pointBackgroundColor: '#10B981',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.38
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return '$' + new Intl.NumberFormat('es-CO').format(context.parsed.y) + ' COP';
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 10, family: 'Inter' }, color: '#64748b' }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: {
              font: { size: 10, family: 'Inter' },
              color: '#64748b',
              callback: function(val) {
                if (val >= 1000000) return '$' + (val / 1000000).toFixed(1) + 'M';
                if (val >= 1000) return '$' + (val / 1000).toFixed(0) + 'k';
                return '$' + val;
              }
            }
          }
        }
      }
    });
  }

  // 2. Gráfico Doughnut de Categorías
  const catData = {!! json_encode($categoryDistribution) !!};
  const catLabels = Object.keys(catData);
  const catCounts = Object.values(catData);

  const catCanvas = document.getElementById('merchantCategoryChart');
  if (catCanvas && catLabels.length > 0) {
    new Chart(catCanvas, {
      type: 'doughnut',
      data: {
        labels: catLabels,
        datasets: [{
          data: catCounts,
          backgroundColor: [
            '#006c47',
            '#10B981',
            '#34d399',
            '#F97316',
            '#fb923c',
            '#38bdf8'
          ],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { font: { size: 10, family: 'Inter' }, boxWidth: 12, padding: 8 }
          }
        }
      }
    });
  }
});
</script>
@endsection
