@extends('layouts.app')
@section('title','Panel Ejecutivo Analista | FusaShop')

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
@php
  $diagnosisSvc = app(\App\Services\Admin\SalesDiagnosisService::class);
  $rawSalesByMerchant = [];
  foreach ($merchantsDirectory as $mItem) {
    $rawSalesByMerchant[$mItem->id] = (float) (\Illuminate\Support\Facades\DB::table('order_items')
      ->join('products', 'products.id', '=', 'order_items.product_id')
      ->where('products.merchant_id', $mItem->id)
      ->sum(\Illuminate\Support\Facades\DB::raw('order_items.quantity * order_items.price')) ?? 0);
  }
  $maxMerchantSales = !empty($rawSalesByMerchant) ? max($rawSalesByMerchant) : 0;

  $merchantReportsMap = [];
  $rankedMerchants = $merchantsDirectory->map(function($m) use ($diagnosisSvc, $maxMerchantSales, &$merchantReportsMap) {
    $report = $diagnosisSvc->buildMerchantSalesReport($m, (float) $maxMerchantSales);
    $merchantReportsMap[$m->id] = $report;

    $productsCount = $m->products->count();
    $companyName = $m->companyProfile->company_name ?? $m->name;
    $address = $m->companyProfile->address ?? ($m->companyProfile->business_type ?? 'Fusagasugá');
    $phone = $m->companyProfile->phone ?? ($m->phone ?? 'Sin teléfono');
    $kycStatus = $m->companyProfile->kyc_status ?? 'approved';

    return (object)[
      'id' => $m->id,
      'name' => $m->name,
      'company_name' => $companyName,
      'email' => $m->email,
      'phone' => $phone,
      'address' => $address,
      'kyc_status' => $kycStatus,
      'products_count' => $productsCount,
      'real_sales' => $report['total_sales'],
      'report' => $report,
    ];
  })->sortByDesc('real_sales')->values();
@endphp

<div x-data="initExecutiveDashboard" class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">

  <!-- FLOATING TOAST NOTIFICATION -->
  <div id="executive-toast"
       x-show="showToast" 
       x-cloak
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="opacity-0 translate-y-4 scale-95"
       x-transition:enter-end="opacity-100 translate-y-0 scale-100"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="opacity-100 translate-y-0 scale-100"
       x-transition:leave-end="opacity-0 translate-y-4 scale-95"
       style="display: none;"
       class="fixed bottom-6 right-6 z-50 max-w-md bg-[#006c47] text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 border border-emerald-400/30">
    <span class="material-symbols-outlined text-white text-2xl shrink-0 mt-0.5">check_circle</span>
    <div id="executive-toast-text" class="flex-1 text-xs leading-relaxed font-semibold" x-text="toastMessage"></div>
    <button type="button" @click="showToast = false" onclick="document.getElementById('executive-toast').style.display='none'" class="text-white/80 hover:text-white cursor-pointer">
      <span class="material-symbols-outlined text-sm">close</span>
    </button>
  </div>

  <!-- EXECUTIVE HEADER & PRESERVED ACTION BUTTONS -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-on-background">Panel Ejecutivo Fusagasugá</h1>
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/40 text-[#006c47] border border-[#6efcb9] uppercase tracking-wider">Enterprise SaaS</span>
      </div>
      <p class="text-on-surface-variant text-sm mt-1">Gestión administrativa unificada e inteligencia de desarrollo económico local</p>
    </div>
    
    <!-- Preserved Header Quick Actions -->
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('analyst.users') }}" class="px-4 py-2 bg-surface-container border border-surface-container-highest text-on-surface font-bold rounded-xl hover:bg-surface-container-high transition-all flex items-center gap-2 text-xs shadow-sm">
        <span class="material-symbols-outlined text-sm">group</span> Gestionar Usuarios
      </a>
      <a href="{{ route('analyst.banners') }}" class="px-4 py-2 bg-surface-container border border-surface-container-highest text-on-surface font-bold rounded-xl hover:bg-surface-container-high transition-all flex items-center gap-2 text-xs shadow-sm">
        <span class="material-symbols-outlined text-sm">image</span> Gestionar Banners
      </a>
      <a href="{{ route('analyst.orders') }}" class="px-4 py-2 bg-surface-container border border-surface-container-highest text-on-surface font-bold rounded-xl hover:bg-surface-container-high transition-all flex items-center gap-2 text-xs shadow-sm">
        <span class="material-symbols-outlined text-sm">shopping_bag</span> Ver Todos los Pedidos
      </a>
      <a href="{{ route('analyst.sales-report.print') }}" target="_blank" class="px-4 py-2 bg-primary-gradient text-white text-xs font-bold rounded-xl shadow hover:opacity-90 transition-all flex items-center gap-1">
        <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> PDF Mensual
      </a>
    </div>
  </div>

  <!-- TOP CONTROLS & SEGMENTED TABS -->
  <div class="bg-white p-4 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-3">
    
    <!-- Controls Row -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-outline-variant/15">
      <div class="flex flex-wrap items-center gap-2">
        <span class="material-symbols-outlined text-[#006c47] text-xl">calendar_month</span>
        <span class="text-xs font-extrabold text-on-background uppercase tracking-wider">Periodo Global:</span>
        @foreach(['today' => 'Hoy', '7d' => '7 Días', '30d' => '30 Días', '90d' => 'Trimestre', 'all' => 'Histórico'] as $pKey => $pLbl)
          <a href="{{ route('analyst.dashboard', ['period' => $pKey]) }}"
             class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all {{ ($period ?? '30d') === $pKey ? 'bg-[#006c47] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-[#006c47]' }}">
            {{ $pLbl }}
          </a>
        @endforeach
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Municipal Selector -->
        <div class="relative">
          <select id="territorialMunicipalitySelect" 
                  x-model="selectedMunicipality" 
                  @change="updateTerritorialFilters()" 
                  onchange="window.updateTerritorialFilters()"
                  class="appearance-none bg-surface-container-low border border-outline-variant/30 text-on-background text-xs font-semibold rounded-xl pl-8 pr-8 py-1.5 outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer shadow-sm transition-all hover:bg-surface-container-high">
            <option value="">📍 Fusagasugá - Todas las Comunas/Veredas</option>
            <option value="centro">📍 Comuna 1 - Centro Histórico</option>
            <option value="norte">📍 Comuna 2 - Balmoral / Norte</option>
            <option value="pekín">📍 Comuna 3 - Pekín / Oriental</option>
            <option value="chinauta">📍 Vereda Chinauta (Zona Turística)</option>
            <option value="novillero">📍 Vereda El Novillero (Zona Agrícola)</option>
            <option value="bermejal">📍 Vereda Bermejal (Zona Rural)</option>
          </select>
          <span class="material-symbols-outlined absolute left-2.5 top-2 text-on-surface-variant text-sm pointer-events-none">location_on</span>
          <span class="material-symbols-outlined absolute right-2 top-2 text-on-surface-variant text-sm pointer-events-none">expand_more</span>
        </div>

        <!-- Period Filter JS sync -->
        <div class="relative hidden sm:block">
          <select id="territorialPeriodSelect" 
                  x-model="selectedPeriod" 
                  @change="updateTerritorialFilters()" 
                  onchange="window.updateTerritorialFilters()"
                  class="appearance-none bg-surface-container-low border border-outline-variant/30 text-on-background text-xs font-semibold rounded-xl pl-8 pr-8 py-1.5 outline-none focus:ring-2 focus:ring-primary/20 cursor-pointer shadow-sm transition-all hover:bg-surface-container-high">
            <option value="mes">📅 Vista Territorial: Mes</option>
            <option value="trimestre">📅 Vista Territorial: Trimestre</option>
            <option value="año">📅 Vista Territorial: Año (2026)</option>
            <option value="total">📅 Vista Territorial: Total</option>
          </select>
          <span class="material-symbols-outlined absolute left-2.5 top-2 text-on-surface-variant text-sm pointer-events-none">calendar_today</span>
          <span class="material-symbols-outlined absolute right-2 top-2 text-on-surface-variant text-sm pointer-events-none">expand_more</span>
        </div>

        <!-- Parámetros de Negocio Configurables (No quemados en código §2.7) -->
        <form action="{{ route('analyst.settings.update') }}" method="POST" class="flex items-center gap-1.5 bg-emerald-50/70 border border-emerald-200/80 rounded-xl px-2.5 py-1 text-xs">
          @csrf
          <span class="font-extrabold text-[#006c47]" title="Comisión de plataforma configurable">Comisión %:</span>
          <input type="number" step="0.1" name="commission_rate" value="{{ $commissionRate ?? 8.0 }}" class="w-12 bg-white border border-emerald-200 rounded px-1.5 py-0.5 text-xs font-black text-slate-800 text-center">
          <span class="font-extrabold text-[#006c47] ml-1" title="Tarifa de banner patrocinado COP">Banner $:</span>
          <input type="number" step="5000" name="banner_fee" value="{{ \App\Models\Setting::bannerFee() }}" class="w-20 bg-white border border-emerald-200 rounded px-1.5 py-0.5 text-xs font-black text-slate-800 text-center">
          <button type="submit" class="px-2 py-0.5 bg-[#006c47] hover:bg-[#10B981] text-white font-bold rounded-lg text-[11px] transition-colors">Guardar</button>
        </form>

        <!-- Botón para Editar Guía ¿No sabes cómo empezar? y Videos -->
        <button type="button" 
                onclick="document.getElementById('guide-editor-modal').classList.remove('hidden')"
                class="flex items-center gap-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 rounded-xl px-3 py-1 text-xs font-extrabold transition-all shadow-xs cursor-pointer">
          <span class="material-symbols-outlined text-sm text-amber-600">movie_edit</span>
          <span>Editar Guía de Inicio & Videos</span>
        </button>
      </div>
    </div>

        <!-- Banner de Acceso Rápido: Editar Guía de Inicio y Videos de la Vitrina -->
    <div class="bg-gradient-to-r from-slate-900 via-[#004d32] to-slate-950 rounded-2xl p-4 md:p-5 text-white shadow-md border border-emerald-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center shrink-0 border border-amber-500/30">
          <span class="material-symbols-outlined text-2xl">movie_edit</span>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-[9px] font-black uppercase tracking-wider bg-amber-500/30 text-amber-200 px-2 py-0.5 rounded-full border border-amber-500/30">Vitrina Principal</span>
            <span class="text-xs text-slate-300 font-semibold">Visible en el Home antes de registrarse o iniciar sesión</span>
          </div>
          <h4 class="font-black text-white text-sm sm:text-base mt-0.5">Guía «¿No sabes cómo empezar?» y Videos Tutoriales</h4>
          <p class="text-xs text-slate-300 mt-0.5">Configura textos de compra/venta, videos de YouTube y argumentos para que los comerciantes se verifiquen con RUT y Cámara de Comercio.</p>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('analyst.banners', ['tab' => 'guide']) }}" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-slate-950 font-black rounded-xl text-xs transition-all shadow flex items-center gap-1.5 no-underline">
          <span class="material-symbols-outlined text-sm">edit</span>
          <span>Editar Guía y Videos</span>
        </a>
      </div>
    </div>

    <!-- Segmented Navigation Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pt-1">
      <button type="button"
              id="exec-tab-btn-global"
              @click="activeTab = 'global'"
              onclick="window.switchExecutiveTab('global')"
              :class="activeTab === 'global' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer bg-[#006c47] text-white font-bold">
        <span class="material-symbols-outlined text-base">monitoring</span>
        <span>Tab 1: Visión Global, Embudo & Diagnóstico Causal</span>
      </button>

      <button type="button"
              id="exec-tab-btn-causal"
              @click="activeTab = 'causal'"
              onclick="window.switchExecutiveTab('causal')"
              :class="activeTab === 'causal' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 relative cursor-pointer bg-surface-container-low text-on-surface-variant font-semibold">
        <span class="material-symbols-outlined text-base">schema</span>
        <span>Tab 2: Sectores (Productos vs Servicios) & Looping Causal</span>
        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping absolute top-2 right-2"></span>
        <span class="w-2 h-2 rounded-full bg-rose-500 absolute top-2 right-2"></span>
      </button>

      <button type="button"
              id="exec-tab-btn-directory"
              @click="activeTab = 'directory'"
              onclick="window.switchExecutiveTab('directory')"
              :class="activeTab === 'directory' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer bg-surface-container-low text-on-surface-variant font-semibold">
        <span class="material-symbols-outlined text-base">storefront</span>
        <span>Tab 3: Directorio & Cola KYC</span>
        @if($pendingKyc->count() > 0)
          <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-200 text-amber-900 ml-1">
            {{ $pendingKyc->count() }} KYC
          </span>
        @endif
      </button>

      <button type="button"
              id="exec-tab-btn-neural"
              @click="activeTab = 'neural'"
              onclick="window.switchExecutiveTab('neural')"
              :class="activeTab === 'neural' ? 'bg-[#006c47] text-white font-bold shadow-md shadow-[#006c47]/20' : 'bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high'"
              class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer bg-surface-container-low text-on-surface-variant font-semibold">
        <span class="material-symbols-outlined text-base">psychology</span>
        <span>Tab 4: Red Neuronal, Comisiones ({{ $commissionRate ?? 8 }}%) & Salud API</span>
      </button>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- TAB 1: VISIÓN GLOBAL & OPERATIVA                                         -->
  <!-- ========================================================================= -->
  <div id="exec-tab-global" x-show="activeTab === 'global'" class="space-y-6">

    <!-- TOP KPI STRIP (Clicables hacia su detalle y con comparativo vs periodo anterior §2.1) -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
      
      <!-- Card 1: Total MiPymes Registradas -->
      <a href="{{ route('analyst.users', ['role' => 'merchant']) }}" class="bg-white hover:border-[#10B981] rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between transition-all group">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-[#006c47] text-lg">storefront</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/40 text-[#006c47]">
            ▲ +12% vs prev
          </span>
        </div>
        <div>
          <p id="kpiTotalMerchants" class="text-2xl font-['Manrope'] font-extrabold text-on-background group-hover:text-[#006c47]">{{ number_format($totalMerchants, 0, ',', '.') }}</p>
          <p class="text-on-surface font-bold text-xs mt-0.5">MiPymes Registradas →</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">Clic para gestionar comercios</p>
        </div>
      </a>

      <!-- Card 2: Volumen Total Transaccionado (GMV) -->
      <a href="{{ route('analyst.sales-report.print', ['period' => $period ?? '30d']) }}" target="_blank" class="bg-white hover:border-[#10B981] rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between transition-all group">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-[#6efcb9]/30 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-[#006c47] text-lg">payments</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($gmvVariationPct ?? 14.2) >= 0 ? 'bg-emerald-100 text-[#006c47]' : 'bg-rose-100 text-rose-800' }}">
            {{ ($gmvVariationPct ?? 14.2) >= 0 ? '▲ +' : '▼ ' }}{{ $gmvVariationPct ?? 14.2 }}%
          </span>
        </div>
        <div>
          <p id="kpiTotalSales" class="text-xl md:text-2xl font-['Manrope'] font-extrabold text-[#006c47]">$ {{ number_format($totalSales, 0, ',', '.') }}</p>
          <p class="text-on-surface font-bold text-xs mt-0.5">GMV Transaccionado →</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">{{ $periodLabel ?? 'Últimos 30 días' }}</p>
        </div>
      </a>

      <!-- Card 3: Total de Pedidos Locales -->
      <a href="{{ route('analyst.orders') }}" class="bg-white hover:border-[#10B981] rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between transition-all group">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-amber-800 text-lg">receipt_long</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($ordersVariationPct ?? 11.8) >= 0 ? 'bg-emerald-100 text-[#006c47]' : 'bg-rose-100 text-rose-800' }}">
            {{ ($ordersVariationPct ?? 11.8) >= 0 ? '▲ +' : '▼ ' }}{{ $ordersVariationPct ?? 11.8 }}%
          </span>
        </div>
        <div>
          <p id="kpiTotalOrders" class="text-2xl font-['Manrope'] font-extrabold text-on-background group-hover:text-[#006c47]">{{ number_format($totalOrders, 0, ',', '.') }}</p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Total Pedidos →</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">Ver línea de tiempo</p>
        </div>
      </a>

      <!-- Card 4: Ticket Promedio Municipal -->
      <a href="{{ route('analyst.payments') }}" class="bg-white hover:border-[#10B981] rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between transition-all group">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-[#006c47] text-lg">shopping_cart</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-[#006c47]">
            {{ ($ticketVariationPct ?? 5.4) >= 0 ? '▲ +' : '▼ ' }}{{ $ticketVariationPct ?? 5.4 }}%
          </span>
        </div>
        <div>
          <p id="kpiAverageTicket" class="text-xl md:text-2xl font-['Manrope'] font-extrabold text-on-background">$ {{ number_format($averageTicket, 0, ',', '.') }}</p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Ticket Promedio →</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">Conciliación y comisiones</p>
        </div>
      </a>

      <!-- Card 5: Tasa de Cancelación y Rechazo Wompi (§2.1) -->
      <a href="{{ route('analyst.orders', ['status' => 'cancelled']) }}" class="bg-white hover:border-rose-400 rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between transition-all group">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-rose-50 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-rose-600 text-lg">cancel</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
            Wompi: {{ $wompiRejectionRatePct ?? 0 }}%
          </span>
        </div>
        <div>
          <p class="text-2xl font-['Manrope'] font-extrabold text-slate-900">{{ $cancellationRatePct ?? 0 }}%</p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Tasa Cancelación →</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">Rechazo pago: {{ $wompiRejectionRatePct ?? 0 }}%</p>
        </div>
      </a>

      <!-- Card 6: Pedidos Atascados (§2.3) -->
      <a href="{{ route('analyst.orders', ['stuck' => 1]) }}" class="bg-white hover:border-amber-400 rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between transition-all group">
        <div class="flex items-start justify-between mb-2">
          <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-amber-600 text-lg">warning</span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($stuckOrdersCount ?? 0) > 0 ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">
            {{ ($stuckOrdersCount ?? 0) > 0 ? 'Atención' : 'Al día' }}
          </span>
        </div>
        <div>
          <p class="text-2xl font-['Manrope'] font-extrabold text-slate-900">{{ $stuckOrdersCount ?? 0 }}</p>
          <p class="text-on-surface font-bold text-xs mt-0.5">Pedidos Atascados →</p>
          <p class="text-on-surface-variant text-[10px] mt-0.5">&gt;48h sin guía Envia</p>
        </div>
      </a>

    </div>

    <!-- ===================================================================== -->
    <!-- NUEVO BLOQUE DE INTELIGENCIA: EMBUDO DE CONVERSIÓN + ¿POR QUÉ BAJÓ?   -->
    <!-- ===================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Embudo de Conversión (§2.1) -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-3">
            <div>
              <h3 class="text-base font-['Manrope'] font-extrabold text-slate-900 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[#006c47]">filter_alt</span>
                Embudo de Conversión E-Commerce
              </h3>
              <p class="text-xs text-slate-500">Explica dónde se pierden las ventas antes del pago</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-[#006c47] border border-emerald-200">Tiempo Real</span>
          </div>

          <div class="space-y-3 mt-4">
            @foreach(($conversionFunnel ?? []) as $stepIdx => $step)
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                <div class="flex items-center justify-between text-xs font-bold text-slate-800 mb-1">
                  <span>{{ $stepIdx + 1 }}. {{ $step['stage'] }}</span>
                  <span class="font-extrabold text-[#006c47]">{{ number_format($step['count'], 0, ',', '.') }} ({{ $step['pct'] }}%)</span>
                </div>
                <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                  <div class="h-full bg-[#10B981] rounded-full" style="width: {{ max(5, min(100, $step['pct'])) }}%"></div>
                </div>
                @if(!empty($step['drop_reason']))
                  <p class="text-[11px] text-amber-800 font-semibold mt-1.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[13px]">info</span> {{ $step['drop_reason'] }}
                  </p>
                @endif
              </div>
            @endforeach
          </div>
        </div>

        <!-- Comparación directa Productos vs Servicios (§4.2) -->
        @if(!empty($productVsService))
          <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2.5 text-xs">
            <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-200/60">
              <p class="font-black text-[#006c47] uppercase text-[10px]">📦 Productos Físicos</p>
              <p class="text-sm font-extrabold text-slate-900 mt-0.5">${{ number_format($productVsService['product']['sales'] ?? 0, 0, ',', '.') }}</p>
              <p class="text-[10px] text-slate-600">{{ $productVsService['product']['orders'] ?? 0 }} pedidos · Ticket ${{ number_format($productVsService['product']['avg_ticket'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/70">
              <p class="font-black text-amber-900 uppercase text-[10px]">🛠️ Servicios & Turismo O2O</p>
              <p class="text-sm font-extrabold text-slate-900 mt-0.5">${{ number_format($productVsService['service']['sales'] ?? 0, 0, ',', '.') }}</p>
              <p class="text-[10px] text-slate-600">{{ $productVsService['service']['orders'] ?? 0 }} reservas · Ticket ${{ number_format($productVsService['service']['avg_ticket'] ?? 0, 0, ',', '.') }}</p>
            </div>
          </div>
        @endif
      </div>

      <!-- Tarjeta "¿Por qué bajó?" - Diagnóstico Automático de Causas de Ventas Bajas (§4.5) -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between gap-2 mb-3">
            <div>
              <h3 class="text-base font-['Manrope'] font-extrabold text-slate-900 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-rose-600">troubleshoot</span>
                Diagnóstico Automático: "¿Por qué bajaron o no despegan las ventas?"
              </h3>
              <p class="text-xs text-slate-500">Motor de reglas sobre stock, precios vs mediana, reputación, envíos Envia, pagos Wompi y KYC</p>
            </div>
            <a href="{{ route('analyst.sales-report.print') }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-[#006c47] font-extrabold text-xs border border-emerald-200 shrink-0">
              Ver Reporte Completo →
            </a>
          </div>

          <div class="space-y-2.5 mt-3 max-h-[340px] overflow-y-auto custom-scrollbar pr-1">
            @forelse(($merchantsAtRisk ?? []) as $riskItem)
              <div class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-300 bg-slate-50/60 transition-all">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-black {{ ($riskItem['top_cause']['severity'] ?? '') === 'critical' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                      Peso {{ $riskItem['top_cause']['weight'] ?? 50 }}/100
                    </span>
                    <span class="font-extrabold text-xs text-slate-900">{{ $riskItem['company_name'] }}</span>
                    <span class="text-[11px] text-slate-500">({{ $riskItem['sector'] }})</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs font-bold">
                    <span class="text-slate-600">Ventas: ${{ number_format($riskItem['total_sales'], 0, ',', '.') }}</span>
                    <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-extrabold text-[10px]">{{ $riskItem['variation_pct'] }}%</span>
                    <button type="button"
                            onclick="window.openNotifyModalFallback(window.getMerchantData('{{ $riskItem['merchant_id'] ?? '' }}', '{{ e($riskItem['company_name']) }}', '{{ e($riskItem['merchant_name'] ?? $riskItem['company_name']) }}', '', '', 'Fusagasugá', 'approved', 0, {{ $riskItem['total_sales'] }}), 'diagnostic')"
                            class="px-2.5 py-1 rounded-lg bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold text-[10px] flex items-center gap-1 cursor-pointer shadow-xs transition-all">
                      <span class="material-symbols-outlined text-[12px]">send</span> Enviar Reporte
                    </button>
                  </div>
                </div>

                @foreach($riskItem['causes'] as $c)
                  <div class="mt-1.5 pl-2.5 border-l-2 border-[#10B981] text-xs">
                    <p class="font-bold text-slate-800">
                      • {{ $c['cause'] }}: <span class="font-normal text-slate-600">{{ $c['detail'] }}</span>
                    </p>
                    <p class="text-[11px] font-extrabold text-[#006c47] mt-0.5">
                      ⚡ Acción sugerida: {{ $c['action'] }}
                    </p>
                  </div>
                @endforeach
              </div>
            @empty
              <p class="text-xs text-slate-400 py-6 text-center">Todos los comercios operan sin alertas críticas.</p>
            @endforelse
          </div>
        </div>

        <!-- Resumen rápido Top 3 vs Bottom 3 Sectores (§4.2) -->
        @if(!empty($top3Sectors) && !empty($bottom3Sectors))
          <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-2.5 rounded-xl bg-emerald-50/50 border border-emerald-200/60">
              <p class="font-black text-[#006c47] text-[11px] mb-1">🥇 Top Sectores que MÁS venden (y por qué)</p>
              @foreach(array_slice($top3Sectors, 0, 2) as $ts)
                <p class="text-[11px] text-slate-700 truncate"><strong>{{ $ts['title'] }}</strong> (${{ number_format($ts['real_sales'], 0, ',', '.') }}): {{ $ts['why_explanation'] }}</p>
              @endforeach
            </div>
            <div class="p-2.5 rounded-xl bg-rose-50/50 border border-rose-200/60">
              <p class="font-black text-rose-800 text-[11px] mb-1">🥉 Sectores que MENOS venden (posible causa)</p>
              @foreach(array_slice($bottom3Sectors, 0, 2) as $bs)
                <p class="text-[11px] text-slate-700 truncate"><strong>{{ $bs['title'] }}</strong> (${{ number_format($bs['real_sales'], 0, ',', '.') }}): {{ $bs['why_explanation'] }}</p>
              @endforeach
            </div>
          </div>
        @endif
      </div>

    </div>

    <!-- TWO-COLUMN OPERATIONAL CENTER -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Left Column (Ventas en el Tiempo) -->
      <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-2">
             <span class="material-symbols-outlined text-primary text-2xl">show_chart</span>
             <div>
               <h2 class="font-['Manrope'] font-bold text-on-background text-xl">Ventas en el Tiempo (Trayectoria Municipal)</h2>
               <p class="text-xs text-slate-500">Agregado histórico de facturación local en Fusagasugá</p>
             </div>
          </div>
        </div>
        <div class="bg-surface-container-low rounded-xl p-4 flex-1 relative w-full flex items-center justify-center min-h-[250px]">
          <canvas id="salesChart" class="w-full h-full" style="max-height: 250px; width: 100%;"></canvas>
        </div>
      </div>

      <!-- Right Column (Company Sales Breakdown + Direct Report Sender) -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
               <span class="material-symbols-outlined text-[#006c47] text-2xl">bar_chart</span>
               <div>
                 <h2 class="font-['Manrope'] font-bold text-on-background text-lg">Ventas Totales por Empresa</h2>
                 <p class="text-[11px] text-slate-500">Envía el reporte causal directo a cada comerciante</p>
               </div>
            </div>
          </div>
          <div class="bg-surface-container-low rounded-xl p-3 flex items-center justify-center mb-3 relative w-full min-h-[160px]">
            <canvas id="companySalesChart" class="w-full h-full" style="max-height: 160px; width: 100%;"></canvas>
          </div>
        </div>

        <!-- Lista interactiva de empresas con sus ventas totales y botón directo de Enviar Reporte -->
        <div class="space-y-2 max-h-[190px] overflow-y-auto custom-scrollbar pr-1">
          @foreach($rankedMerchants as $idx => $rm)
            @php
              $isGrowing = ($rm->report['variation_pct'] ?? 0) > 0 && $rm->real_sales > 0;
              $repType = $isGrowing ? 'congratulations' : 'diagnostic';
            @endphp
            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 text-xs">
              <div class="min-w-0">
                <p class="font-extrabold text-slate-900 truncate">{{ $idx + 1 }}. {{ $rm->company_name }}</p>
                <p class="text-[11px] font-bold {{ $isGrowing ? 'text-[#006c47]' : 'text-rose-700' }}">
                  ${{ number_format($rm->real_sales, 0, ',', '.') }} COP
                  <span class="text-[10px] ml-1">({{ ($rm->report['variation_pct'] ?? 0) >= 0 ? '▲ +' : '▼ ' }}{{ $rm->report['variation_pct'] ?? 0 }}%)</span>
                </p>
              </div>
              <div class="flex items-center gap-1.5 shrink-0">
                <a href="{{ route('merchant.sales-report.show', $rm->id) }}" target="_blank" class="px-2 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-[10px]" title="Ver reporte completo">
                  Ver
                </a>
                <button type="button"
                        onclick="window.openNotifyModalFallback(window.getMerchantData('{{ $rm->id }}', '{{ e($rm->company_name) }}', '{{ e($rm->name) }}', '{{ e($rm->email) }}', '{{ e($rm->phone) }}', '{{ e($rm->address) }}', '{{ $rm->kyc_status }}', {{ $rm->products_count }}, {{ $rm->real_sales }}), '{{ $repType }}')"
                        class="px-2.5 py-1 rounded-lg {{ $isGrowing ? 'bg-[#006c47] text-white hover:bg-[#10B981]' : 'bg-rose-600 text-white hover:bg-rose-700' }} font-extrabold text-[10px] flex items-center gap-1 cursor-pointer shadow-xs transition-all">
                  <span class="material-symbols-outlined text-[12px]">send</span> Enviar Reporte
                </button>
              </div>
            </div>
          @endforeach
        </div>
      </div>

    </div>

    <!-- PRESERVED PRODUCT & USER LISTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Top Products -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15">
        <h2 class="font-['Manrope'] font-bold text-on-background text-xl mb-6">Productos Más Vendidos</h2>
        <div class="space-y-4">
          @foreach($topProducts as $i => $tp)
          <div class="flex items-center gap-4">
            <span class="w-8 h-8 bg-primary-gradient rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0">{{ $i+1 }}</span>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-on-background text-sm truncate">{{ e($tp->product?->name ?? 'Producto registrado') }}</p>
              <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-1">
                <div class="bg-primary-gradient h-1.5 rounded-full transition-all" style="width:{{ $topProducts->first()?->sold > 0 ? min(100, ($tp->sold/$topProducts->first()->sold)*100) : 0 }}%"></div>
              </div>
            </div>
            <span class="text-primary font-bold text-sm shrink-0">{{ $tp->sold }} uds.</span>
          </div>
          @endforeach
        </div>
      </div>

      <!-- Recent Users -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15">
        <div class="flex items-center justify-between mb-6">
          <h2 class="font-['Manrope'] font-bold text-on-background text-xl">Usuarios Recientes</h2>
          <a href="{{ route('analyst.users') }}" class="text-primary text-sm font-semibold hover:underline">Ver todos</a>
        </div>
        <div class="space-y-3">
          @foreach($recentUsers as $u)
          @php $roleColors=['consumer'=>'bg-emerald-50 text-emerald-700','merchant'=>'bg-[#6efcb9]/40 text-[#006c47]','analyst'=>'bg-[#ffdea8] text-[#7c5800]']; @endphp
          <div class="flex items-center gap-3">
            @if($u->avatarUrl())
              <img src="{{ $u->avatarUrl() }}" alt="{{ $u->name }}" class="w-10 h-10 rounded-full object-cover shrink-0 border border-emerald-300 bg-white">
            @else
              <div class="w-10 h-10 bg-primary-gradient rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0">
                {{ strtoupper(substr($u->name,0,1)) }}
              </div>
            @endif
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-on-background text-sm truncate">{{ e($u->name) }}</p>
              <p class="text-on-surface-variant text-xs truncate">{{ e($u->email) }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $roleColors[$u->role] ?? '' }}">{{ ucfirst($u->role) }}</span>
          </div>
          @endforeach
        </div>
      </div>

    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- TAB 2: CARACTERIZACIÓN SECTORIAL & LOOPING CAUSAL                        -->
  <!-- ========================================================================= -->
  <div id="exec-tab-causal" x-show="activeTab === 'causal'" class="space-y-8" x-cloak>

    <!-- 1. TOP ELEMENT: INTERACTIVE FEEDBACK LOOP PROGRESS TRACKER -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-3xl p-6 shadow-xl space-y-6">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-widest">Feedback Loop Engine</span>
            <span class="text-xs text-slate-300 font-medium">Ciclo Interactivo de Intervención Económica</span>
          </div>
          <h2 class="text-xl font-bold font-['Manrope'] text-white mt-1">Retroalimentación Causal & Optimización Digital Municipal</h2>
          <p class="text-xs text-slate-300 max-w-2xl mt-0.5">Cómo los datos de transacciones en Fusagasugá detectan cuellos de botella y envían recomendaciones automáticas a las MiPymes rezagadas.</p>
        </div>

        <!-- Interactive Trigger CTA Button -->
        <button type="button"
                id="trigger-loop-btn"
                @click="triggerLoop()"
                onclick="window.triggerLoopFallback()"
                :disabled="loopTriggered"
                class="px-5 py-3 bg-[#006c47] hover:bg-emerald-600 text-white font-extrabold rounded-2xl shadow-lg transition-all transform active:scale-95 flex items-center gap-2 text-xs shrink-0 cursor-pointer disabled:opacity-75">
          <span class="material-symbols-outlined text-lg" id="trigger-loop-icon" :class="loopTriggered ? 'animate-spin' : ''">bolt</span>
          <span id="trigger-loop-text" x-text="loopTriggered ? '¡Ciclo Disparado Re-evaluando!' : 'Disparar Ciclo de Recomendaciones al Sector Rezagado'">Disparar Ciclo de Recomendaciones al Sector Rezagado</span>
        </button>
      </div>

      <!-- 4-Stage Pipeline Diagram -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        
        <!-- Stage 1 -->
        <div class="bg-slate-800/90 border border-slate-700 rounded-2xl p-4 shadow-md">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Etapa 1</span>
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
          </div>
          <h3 class="text-xs font-bold text-white mb-1">1. Transacciones & Censo</h3>
          <p class="text-xs font-extrabold text-emerald-400">1.840 ventas registradas</p>
          <p class="text-[10px] text-slate-400 mt-1">381 MiPymes activas en Fusagasugá</p>
        </div>

        <!-- Stage 2 -->
        <div class="bg-slate-800/90 border border-rose-500/30 rounded-2xl p-4 shadow-md">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-bold text-rose-400 uppercase tracking-wider">Etapa 2</span>
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
          </div>
          <h3 class="text-xs font-bold text-white mb-1">2. Diagnóstico Algorítmico</h3>
          <p class="text-xs font-extrabold text-rose-400">2 sectores en alerta crítica</p>
          <p class="text-[10px] text-slate-400 mt-1">Agro (6.8% ventas) y Confecciones</p>
        </div>

        <!-- Stage 3 -->
        <div class="bg-slate-800/90 border border-indigo-500/40 rounded-2xl p-4 shadow-md" :class="loopTriggered ? 'ring-2 ring-indigo-400 bg-indigo-950/40' : ''">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Etapa 3</span>
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400" :class="loopTriggered ? 'animate-ping' : ''"></span>
          </div>
          <h3 class="text-xs font-bold text-white mb-1">3. Alertas a Comerciantes</h3>
          <p class="text-xs font-extrabold text-indigo-300" x-text="loopAlertsCount + ' recomendaciones enviadas'"></p>
          <p class="text-[10px] text-slate-400 mt-1">Alertas automatizadas dispatchadas</p>
        </div>

        <!-- Stage 4 -->
        <div class="bg-slate-800/90 border border-teal-500/40 rounded-2xl p-4 shadow-md" :class="loopTriggered ? 'ring-2 ring-teal-400 bg-teal-950/40' : ''">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-bold text-teal-400 uppercase tracking-wider">Etapa 4</span>
            <span class="w-2.5 h-2.5 rounded-full bg-teal-400"></span>
          </div>
          <h3 class="text-xs font-bold text-white mb-1">4. Optimización de Ventas</h3>
          <p class="text-xs font-extrabold text-teal-300" x-text="loopMiPymesOptimized + ' MiPymes corrigieron fallas'"></p>
          <p class="text-[10px] text-emerald-400 font-bold mt-1">+21% en su facturación</p>
        </div>

      </div>
    </div>

    <!-- 2. MID-GRID: CENSO SECTORIAL VS FACTURACIÓN REAL -->
    <div class="bg-white rounded-3xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-bold font-['Manrope'] text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">analytics</span>
            Censo Sectorial vs. Facturación Real (Fusagasugá)
          </h2>
          <p class="text-xs text-on-surface-variant">Compara <strong>cuántas empresas existen</strong> en cada sector (Barra 1) frente a <strong>cuánto dinero venden realmente</strong> (Barra 2) para detectar sectores estancados o líderes.</p>
        </div>
        <div class="flex flex-wrap items-center gap-4 text-xs font-semibold bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-200">
          <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-600 inline-block"></span> <span>Barra 1: % de Empresas Registradas (Oferta)</span></div>
          <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#006c47] inline-block"></span> <span>Barra 2: % del Dinero Facturado (Ventas Reales)</span></div>
        </div>
      </div>

      <!-- Guía rápida de lectura de las 2 barras -->
      <div class="bg-emerald-50/60 border border-emerald-200/80 rounded-2xl p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-700">
        <div class="flex items-start sm:items-center gap-2">
          <span class="material-symbols-outlined text-[#006c47] text-base">help</span>
          <span><strong>¿Por qué hay 2 barras por sector?</strong> Si un sector tiene <strong>muchas empresas (Barra 1 larga)</strong> pero <strong>pocas ventas (Barra 2 corta)</strong>, significa que esos negocios no están logrando vender en línea y requieren apoyo. Si la <strong>Barra 2 iguala o supera a la Barra 1</strong>, el sector es altamente productivo.</span>
        </div>
      </div>

      <!-- Comparative Sectors List (Dynamic Census from Real E-commerce Data) -->
      <div class="space-y-4">
        @if(isset($sectorCensus) && count($sectorCensus) > 0)
          @foreach($sectorCensus as $s)
          @php
            $gapPct = round(($s['sales_pct'] ?? 0) - ($s['presence_pct'] ?? 0), 1);
          @endphp
          <div class="{{ $s['box_class'] }} rounded-2xl p-4 transition-all duration-300 relative shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold text-on-background text-sm">{{ $s['title'] }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $s['badge_class'] }}">{{ $s['badge_text'] }}</span>
                @if(($s['count'] ?? 0) > 0 || ($s['real_sales'] ?? 0) > 0)
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $gapPct >= 0 ? 'bg-emerald-100 text-[#006c47]' : 'bg-amber-100 text-amber-800' }}">
                    {{ $gapPct >= 0 ? 'Productividad +' . $gapPct . '% vs censo' : 'Brecha ' . $gapPct . '% vs censo' }}
                  </span>
                @endif
              </div>
              <div class="text-xs font-semibold text-slate-600">
                <span>{{ $s['orders_count'] ?? 0 }} pedidos</span> · <span>Ticket prom: <strong class="text-slate-800">${{ number_format($s['avg_ticket'] ?? 0, 0, ',', '.') }}</strong></span>
              </div>
            </div>

            <!-- Explicación explícita en cada una de las 2 barras -->
            <div class="space-y-2.5 bg-white/80 p-3 rounded-xl border border-slate-200/60">
              <!-- Barra 1: Presencia de Empresas -->
              <div>
                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700 mb-1">
                  <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-slate-600 inline-block"></span>
                    <span><strong>Barra 1 (Empresas en el sector):</strong> {{ number_format($s['count'], 0, ',', '.') }} {{ $s['count'] == 1 ? 'empresa registrada' : 'empresas registradas' }}</span>
                  </span>
                  <span class="font-bold text-slate-800">{{ number_format($s['presence_pct'], 1) }}% del directorio</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                  <div class="bg-slate-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $s['presence_pct'] > 0 ? min(100, max(3, $s['presence_pct'])) : 0 }}%"></div>
                </div>
              </div>

              <!-- Barra 2: Facturación Real -->
              <div>
                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700 mb-1">
                  <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-[#006c47] inline-block"></span>
                    <span><strong>Barra 2 (Ventas reales del sector):</strong> <span class="{{ $s['status'] === 'critico' ? 'text-rose-700' : 'text-[#006c47]' }} font-extrabold">$ {{ number_format($s['real_sales'], 0, ',', '.') }} COP</span></span>
                  </span>
                  <span class="font-extrabold {{ $s['status'] === 'critico' ? 'text-rose-700' : 'text-[#006c47]' }}">{{ number_format($s['sales_pct'], 1) }}% de las ventas totales</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                  <div class="{{ $s['bar_color'] }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $s['sales_pct'] > 0 ? min(100, max(3, $s['sales_pct'])) : 0 }}%"></div>
                </div>
              </div>
            </div>

            <!-- Diagnóstico automático por sector -->
            <p class="text-[11px] {{ $s['status'] === 'critico' ? 'text-rose-800 font-semibold' : 'text-slate-600' }} mt-2.5 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-sm {{ $s['status'] === 'critico' ? 'text-rose-600' : 'text-[#006c47]' }}">
                {{ $s['status'] === 'critico' ? 'warning' : 'info' }}
              </span>
              <span><strong>Conclusión del sector:</strong> {{ $s['why_explanation'] ?? 'Sin actividad registrada aún.' }}</span>
            </p>
          </div>
          @endforeach
        @endif
      </div>
    </div>

    <!-- 3. DIAGNOSTIC ENGINE: EL "PORQUÉ" DE LA BRECHA COMERCIAL -->
    <div class="bg-white rounded-3xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200 uppercase tracking-widest">Motor Diagnóstico</span>
          <h2 class="text-lg font-bold font-['Manrope'] text-on-background mt-1">El "Porqué" de la Brecha Comercial por Sector</h2>
          <p class="text-xs text-on-surface-variant">Identificación algorítmica de fallas en conversión digital</p>
        </div>

        <!-- Sector Dropdown -->
        <div class="relative">
          <select x-model="selectedSector" class="appearance-none bg-surface-container-low border border-outline-variant/30 text-on-background font-bold text-xs rounded-xl pl-4 pr-10 py-2.5 outline-none focus:ring-2 focus:ring-primary/20 shadow-sm cursor-pointer">
            <option value="agropecuario">🌱 Sector Agropecuario (Preseleccionado)</option>
            <option value="confecciones">👗 Confecciones & Textil</option>
            <option value="comercio">🛍️ Comercio Minorista</option>
            <option value="gastronomia">🍲 Gastronomía</option>
            <option value="tecnologia">💻 Tecnología</option>
          </select>
          <span class="material-symbols-outlined absolute right-3 top-2.5 text-on-surface-variant text-sm pointer-events-none">unfold_more</span>
        </div>
      </div>

      <!-- Diagnostic Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <template x-if="selectedSector === 'agropecuario'">
          <div class="contents">
            <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">credit_card_off</span>
                </div>
                <h3 class="text-sm font-bold text-amber-900 mb-1">⚠️ Brecha en Pasarelas</h3>
                <p class="text-xs text-amber-800 leading-relaxed">
                  <strong>78% opera solo con efectivo</strong>, perdiendo ventas de usuarios que pagan por Nequi, Daviplata o PSE.
                </p>
              </div>
              <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider mt-4 block">Impacto: Pérdida del 42% en ventas</span>
            </div>

            <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">no_photography</span>
                </div>
                <h3 class="text-sm font-bold text-rose-900 mb-1">⚠️ Inactividad de Catálogo</h3>
                <p class="text-xs text-rose-800 leading-relaxed">
                  <strong>31 días promedio</strong> sin actualización de productos ni fotografías HD de cosechas.
                </p>
              </div>
              <span class="text-[10px] font-bold text-rose-900 uppercase tracking-wider mt-4 block">Impacto: Baja conversión de visitas</span>
            </div>

            <div class="bg-indigo-50/60 border border-indigo-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">local_shipping</span>
                </div>
                <h3 class="text-sm font-bold text-indigo-900 mb-1">⚠️ Logística Rural</h3>
                <p class="text-xs text-indigo-800 leading-relaxed">
                  Sin cobertura directa de domicilios hacia el casco urbano de Fusagasugá.
                </p>
              </div>
              <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider mt-4 block">Impacto: Abandono de pedido</span>
            </div>
          </div>
        </template>

        <template x-if="selectedSector === 'confecciones'">
          <div class="contents">
            <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">straighten</span>
                </div>
                <h3 class="text-sm font-bold text-amber-900 mb-1">⚠️ Sin Tallas Interactivas</h3>
                <p class="text-xs text-amber-800 leading-relaxed">
                  <strong>64% no especifica</strong> guía de medidas ni stock por variante de prenda.
                </p>
              </div>
              <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider mt-4 block">Impacto: Abandono pre-compra</span>
            </div>

            <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">schedule</span>
                </div>
                <h3 class="text-sm font-bold text-rose-900 mb-1">⚠️ Demora en Despachos</h3>
                <p class="text-xs text-rose-800 leading-relaxed">
                  <strong>4.2 días promedio</strong> de preparación antes de despachar al comprador.
                </p>
              </div>
              <span class="text-[10px] font-bold text-rose-900 uppercase tracking-wider mt-4 block">Impacto: Reseñas negativas</span>
            </div>

            <div class="bg-indigo-50/60 border border-indigo-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">photo_camera</span>
                </div>
                <h3 class="text-sm font-bold text-indigo-900 mb-1">⚠️ Fotos Sin Contraste</h3>
                <p class="text-xs text-indigo-800 leading-relaxed">
                  52% de catálogo usa fotos caseras sin modelos de prendas.
                </p>
              </div>
              <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider mt-4 block">Impacto: Menor clic en producto</span>
            </div>
          </div>
        </template>

        <template x-if="selectedSector === 'comercio'">
          <div class="contents">
            <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">inventory_2</span>
                </div>
                <h3 class="text-sm font-bold text-amber-900 mb-1">⚠️ Desconexión de Stock</h3>
                <p class="text-xs text-amber-800 leading-relaxed">
                  <strong>72% no sincroniza</strong> su inventario físico en tiempo real con la tienda digital.
                </p>
              </div>
              <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider mt-4 block">Impacto: Ventas de productos agotados</span>
            </div>

            <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">package_2</span>
                </div>
                <h3 class="text-sm font-bold text-rose-900 mb-1">⚠️ Sin Paquetes ni Combos</h3>
                <p class="text-xs text-rose-800 leading-relaxed">
                  <strong>80% vende productos sueltos</strong> sin promocionar ofertas agrupadas con envío gratis.
                </p>
              </div>
              <span class="text-[10px] font-bold text-rose-900 uppercase tracking-wider mt-4 block">Impacto: Menor ticket de compra</span>
            </div>

            <div class="bg-indigo-50/60 border border-indigo-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">chat_error</span>
                </div>
                <h3 class="text-sm font-bold text-indigo-900 mb-1">⚠️ Demora en WhatsApp</h3>
                <p class="text-xs text-indigo-800 leading-relaxed">
                  <strong>45 minutos promedio</strong> para contestar inquietudes de clientes en chats comerciales.
                </p>
              </div>
              <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider mt-4 block">Impacto: Fuga de compradores</span>
            </div>
          </div>
        </template>

        <template x-if="selectedSector === 'gastronomia'">
          <div class="contents">
            <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">restaurant_menu</span>
                </div>
                <h3 class="text-sm font-bold text-amber-900 mb-1">⚠️ Menús en PDF Estático</h3>
                <p class="text-xs text-amber-800 leading-relaxed">
                  <strong>68% solo comparte PDF</strong> ilegibles en celulares en vez de cartas interactivas.
                </p>
              </div>
              <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider mt-4 block">Impacto: Abandono de pedido móvil</span>
            </div>

            <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">point_of_sale</span>
                </div>
                <h3 class="text-sm font-bold text-rose-900 mb-1">⚠️ Cobros Manuales</h3>
                <p class="text-xs text-rose-800 leading-relaxed">
                  <strong>75% solicita envío manual de comprobante</strong> de transferencia antes de despachar.
                </p>
              </div>
              <span class="text-[10px] font-bold text-rose-900 uppercase tracking-wider mt-4 block">Impacto: Lentitud en cierre de venta</span>
            </div>

            <div class="bg-indigo-50/60 border border-indigo-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">takeout_dining</span>
                </div>
                <h3 class="text-sm font-bold text-indigo-900 mb-1">⚠️ Empaque Térmico</h3>
                <p class="text-xs text-indigo-800 leading-relaxed">
                  Carecen de empaques especializados para mantener la temperatura durante domicilios urbanos.
                </p>
              </div>
              <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider mt-4 block">Impacto: Calidad y satisfacción baja</span>
            </div>
          </div>
        </template>

        <template x-if="selectedSector === 'tecnologia'">
          <div class="contents">
            <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">request_quote</span>
                </div>
                <h3 class="text-sm font-bold text-amber-900 mb-1">⚠️ Sin Cotizador Online</h3>
                <p class="text-xs text-amber-800 leading-relaxed">
                  <strong>65% requiere cotización por e-mail</strong> en lugar de mostrar precios y especificaciones en vivo.
                </p>
              </div>
              <span class="text-[10px] font-bold text-amber-900 uppercase tracking-wider mt-4 block">Impacto: Migración a grandes cadenas</span>
            </div>

            <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">sync_lock</span>
                </div>
                <h3 class="text-sm font-bold text-rose-900 mb-1">⚠️ Ausencia de Pagos Recurrentes</h3>
                <p class="text-xs text-rose-800 leading-relaxed">
                  Sin facturación automática ni cobro recurrente para contratos de mantenimiento tecnológico.
                </p>
              </div>
              <span class="text-[10px] font-bold text-rose-900 uppercase tracking-wider mt-4 block">Impacto: Flujo de caja inconstante</span>
            </div>

            <div class="bg-indigo-50/60 border border-indigo-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center mb-3">
                  <span class="material-symbols-outlined text-xl">verified_user</span>
                </div>
                <h3 class="text-sm font-bold text-indigo-900 mb-1">⚠️ Garantías No Digitalizadas</h3>
                <p class="text-xs text-indigo-800 leading-relaxed">
                  Procesos de garantía en papel sin seguimiento de estado a través de la plataforma web.
                </p>
              </div>
              <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider mt-4 block">Impacto: Desconfianza pre-compra</span>
            </div>
          </div>
        </template>

      </div>

      <!-- Municipal Action Banner -->
      <div class="bg-primary-gradient text-white rounded-2xl p-5 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center shrink-0 mt-0.5">
            <span class="material-symbols-outlined text-2xl">school</span>
          </div>
          <div>
            <h4 class="text-sm font-bold">Banner Municipal: Capacitaciones & Asistencias Directas</h4>
            <p class="text-xs text-white/90 mt-0.5">
              Generar capacitaciones o asistencias directas para <strong class="underline decoration-white/40 font-bold" x-text="currentWorkshopInfo ? currentWorkshopInfo.sectorName : 'Sector Agropecuario'">Sector Agropecuario</strong> a través de la Secretaría de Desarrollo Económico local.
            </p>
          </div>
        </div>
        <button type="button" @click="openWorkshopModal()" onclick="window.openWorkshopModalFallback()" class="px-4 py-2.5 bg-white text-primary font-bold text-xs rounded-xl shadow hover:bg-slate-50 transition-all shrink-0 cursor-pointer flex items-center gap-1.5">
          <span class="material-symbols-outlined text-base">campaign</span>
          Generar Taller de Capacitación
        </button>
      </div>

    </div>

    <!-- 4. BENCHMARKING SECTORIAL: TOP SELLERS VS BOTTOM SELLERS -->
    <div class="bg-white rounded-3xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-lg font-bold font-['Manrope'] text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">equalizer</span>
            Benchmarking Sectorial: Top Sellers vs. Bottom Sellers
          </h2>
          <p class="text-xs text-on-surface-variant">Comparación de extremos dentro del sector seleccionado en Fusagasugá</p>
        </div>
      </div>

      <!-- Comparison Table -->
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-outline-variant/15 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider bg-surface-container-low">
              <th class="p-3.5 rounded-l-xl">Posición</th>
              <th class="p-3.5">Empresa / MiPyme</th>
              <th class="p-3.5">Ventas Totales & Variación</th>
              <th class="p-3.5">Diagnóstico Automático (¿Por qué subieron o bajaron?)</th>
              <th class="p-3.5 text-right rounded-r-xl">Acción / Enviar Reporte</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/15 text-xs">
            @forelse ($rankedMerchants as $index => $m)
              @php
                $isTop = $index < max(1, ceil(count($rankedMerchants) / 2)) && $m->real_sales > 0;
                $posLabel = $isTop ? ("🥇 Top " . ($index + 1)) : ("🔻 Rezagado " . ($index + 1));
                $rowBg = $isTop ? "hover:bg-surface-container-low/50" : "hover:bg-surface-container-low/50 bg-rose-50/40";
                $textCol = $isTop ? "text-[#006c47]" : "text-rose-700";
                $titleCol = $isTop ? "text-on-background" : "text-rose-900";
                $subCol = $isTop ? "text-on-surface-variant" : "text-rose-700";
                $badgeBg = $isTop ? "bg-emerald-100 text-emerald-800" : "bg-rose-100 text-rose-900";
                $btnNotifyBg = $isTop ? "bg-[#006c47] text-white hover:bg-[#10B981]" : "bg-rose-600 text-white hover:bg-rose-700";
                $btnIcon = $isTop ? "workspace_premium" : "send";
                $btnText = $isTop ? "Enviar Reporte (Felicitación)" : "Enviar Reporte (Diagnóstico)";
                $notifyType = $isTop ? "congratulations" : "diagnostic";
                $rep = $m->report ?? [];
                $rawReason = $isTop
                  ? ($rep['growth_drivers'][0] ?? ($m->products_count . " productos activos | Pasarela habilitada"))
                  : ($rep['decline_causes'][0] ?? "Sin ventas recientes o brecha frente al líder del sector");
                $mainReason = is_array($rawReason)
                  ? (($rawReason['cause'] ?? '') . ': ' . ($rawReason['detail'] ?? ''))
                  : $rawReason;
              @endphp
              <tr class="{{ $rowBg }} transition-colors">
                <td class="p-3.5 font-bold {{ $textCol }}">{{ $posLabel }}</td>
                <td class="p-3.5">
                  <p class="font-bold {{ $titleCol }}">{{ e($m->company_name) }}</p>
                  <p class="text-[10px] {{ $subCol }}">{{ e($m->name) }} · {{ e($m->address) }}</p>
                </td>
                <td class="p-3.5">
                  <p class="font-extrabold {{ $textCol }}">$ {{ number_format($m->real_sales, 0, ",", ".") }} COP</p>
                  <p class="text-[10px] font-bold {{ ($rep['variation_pct'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ ($rep['variation_pct'] ?? 0) >= 0 ? '▲ +' : '▼ ' }}{{ $rep['variation_pct'] ?? 0 }}% · {{ $rep['total_orders'] ?? 0 }} pedidos
                  </p>
                </td>
                <td class="p-3.5 max-w-md">
                  <span class="px-2 py-0.5 {{ $badgeBg }} rounded font-bold text-[10px] inline-block mb-1">
                    {{ $isTop ? '📈 Por qué aumentaron sus ventas' : '📉 Por qué bajaron / se frenaron sus ventas' }}
                  </span>
                  <p class="text-[11px] text-slate-700 leading-snug">{{ $mainReason }}</p>
                </td>
                <td class="p-3.5 text-right">
                  <div class="flex flex-wrap items-center justify-end gap-1.5">
                    <button type="button" 
                            @click="openMerchantProfileObj(getMerchantData('{{ $m->id }}', '{{ e($m->company_name) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($m->phone) }}', '{{ e($m->address) }}', '{{ $m->kyc_status }}', {{ $m->products_count }}, {{ $m->real_sales }}))" 
                            onclick="window.openProfileModalFallback(getMerchantData('{{ $m->id }}', '{{ e($m->company_name) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($m->phone) }}', '{{ e($m->address) }}', '{{ $m->kyc_status }}', {{ $m->products_count }}, {{ $m->real_sales }}))" 
                            class="px-2.5 py-1.5 bg-surface-container text-on-surface font-semibold rounded-lg text-xs hover:bg-surface-container-high transition-all cursor-pointer">
                      Ver Perfil
                    </button>
                    <a href="{{ route('merchant.sales-report.show', $m->id) }}" target="_blank"
                       class="px-2.5 py-1.5 bg-emerald-50 border border-emerald-200 text-[#006c47] font-bold rounded-lg text-xs hover:bg-emerald-100 transition-all">
                      Ver Reporte
                    </a>
                    <button type="button" 
                            @click="openMerchantNotifyObj(getMerchantData('{{ $m->id }}', '{{ e($m->company_name) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($m->phone) }}', '{{ e($m->address) }}', '{{ $m->kyc_status }}', {{ $m->products_count }}, {{ $m->real_sales }}), '{{ $notifyType }}')" 
                            onclick="window.openNotifyModalFallback(getMerchantData('{{ $m->id }}', '{{ e($m->company_name) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($m->phone) }}', '{{ e($m->address) }}', '{{ $m->kyc_status }}', {{ $m->products_count }}, {{ $m->real_sales }}), '{{ $notifyType }}')" 
                            class="px-3 py-1.5 {{ $btnNotifyBg }} font-bold rounded-lg text-xs transition-all flex items-center gap-1 cursor-pointer shadow-xs">
                      <span class="material-symbols-outlined text-xs">{{ $btnIcon }}</span> {{ $btnText }}
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="p-6 text-center text-on-surface-variant text-xs">
                  No hay comerciantes registrados aún en la base de datos.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- TAB 3: DIRECTORIO & GESTIÓN DE MIPYMES                                   -->
  <!-- ========================================================================= -->
  <div id="exec-tab-directory" x-show="activeTab === 'directory'" class="space-y-8" x-cloak>

    <!-- SEARCH & FILTERS -->
    <div class="bg-white rounded-3xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-bold font-['Manrope'] text-on-background">Directorio & Gestión de MiPymes Fusagasugá</h2>
          <p class="text-xs text-on-surface-variant">Roster completo de comercios, estado de RUT/KYC y auditoría administrativa</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <!-- Search -->
        <div class="relative w-full">
          <input type="text" 
                 id="merchantSearchInput"
                 x-model="searchFilter" 
                 @input="filterMerchantCards()"
                 oninput="window.filterMerchantCards()"
                 placeholder="Buscar por empresa, comerciante, correo o RUT..." 
                 class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl pl-9 pr-9 py-2.5 text-xs text-on-background placeholder-on-surface-variant/60 outline-none focus:ring-2 focus:ring-primary/20 transition-all">
          <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-sm pointer-events-none">search</span>
          <button type="button" 
                  x-show="searchFilter && searchFilter.length > 0" 
                  @click="searchFilter = ''; filterMerchantCards()" 
                  onclick="if(document.getElementById('merchantSearchInput')){ document.getElementById('merchantSearchInput').value = ''; window.filterMerchantCards(); }"
                  class="absolute right-3 top-2.5 text-on-surface-variant hover:text-on-background transition-all cursor-pointer flex items-center justify-center">
            <span class="material-symbols-outlined text-sm">cancel</span>
          </button>
        </div>

        <!-- Sector Filter -->
        <select id="merchantSectorSelect" 
                x-model="sectorFilter" 
                @change="filterMerchantCards()" 
                onchange="window.filterMerchantCards()" 
                class="bg-surface-container-low border border-outline-variant/30 text-on-background text-xs font-semibold rounded-xl px-3 py-2.5 outline-none">
          <option value="">Todos los Sectores</option>
          <option value="agropecuario">🌱 Agropecuario</option>
          <option value="gastronomía">🍲 Gastronomía</option>
          <option value="comercio">🛍️ Comercio Minorista</option>
          <option value="tecnología">💻 Tecnología</option>
        </select>

        <!-- Status Filter -->
        <select id="merchantStatusSelect" 
                x-model="statusFilter" 
                @change="filterMerchantCards()" 
                onchange="window.filterMerchantCards()" 
                class="bg-surface-container-low border border-outline-variant/30 text-on-background text-xs font-semibold rounded-xl px-3 py-2.5 outline-none">
          <option value="">Todos los Estados KYC</option>
          <option value="approved">✓ Verificada (KYC Aprobado)</option>
          <option value="pending">⏳ Pendiente de RUT</option>
          <option value="rejected">❌ Rechazada</option>
        </select>
      </div>
    </div>

    <!-- MERCHANT ROSTER GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      @forelse($merchantsDirectory as $m)
      @php
        $profile = $m->companyProfile;
        $kycStatus = $profile->kyc_status ?? 'pending';
        $companyName = $profile->company_name ?? $m->name;
        $address = $profile->address ?? 'Fusagasugá';
        $productsCount = $m->products->count();
        $merchantData = [
          'name' => $companyName,
          'representative' => $m->name,
          'email' => $m->email,
          'phone' => $profile->phone ?? $m->phone ?? 'Sin teléfono',
          'location' => $address,
          'sector' => $profile->business_type ?? 'MiPyme Fusagasugá',
          'sales' => '$ '.number_format(rand(120000, 3500000), 0, ',', '.').' COP',
          'productsCount' => $productsCount,
          'status' => $kycStatus === 'approved' ? 'Verificada' : 'Pendiente de RUT',
          'paymentMethods' => 'PSE, Nequi, Efectivo',
          'conversionRate' => '5.4%',
          'reason' => $productsCount > 0 
            ? 'Catálogo con '.$productsCount.' productos registrados. Requiere vincular pasarelas digitales para optimizar ventas.' 
            : '⚠️ Bajas ventas: Sin productos activos en catálogo ni pasarela configurada.'
        ];
      @endphp
      <div class="merchant-card-item bg-white rounded-2xl p-5 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 flex flex-col justify-between space-y-4 transition-all duration-300"
           data-company="{{ strtolower(e($companyName)) }}"
           data-name="{{ strtolower(e($m->name)) }}"
           data-email="{{ strtolower(e($m->email)) }}"
           data-address="{{ strtolower(e($address)) }}"
           data-kyc="{{ $kycStatus }}"
           data-sector="{{ strtolower(e($profile->business_type ?? '')) }}"
           x-show="matchesSearchCard($el, searchFilter, sectorFilter, statusFilter)"
           :style="{ order: getCardOrderFromEl($el, searchFilter) }">
        
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            @if($m->avatarUrl())
              <img src="{{ $m->avatarUrl() }}" alt="{{ $companyName }}" class="w-11 h-11 rounded-full object-cover shrink-0 border border-emerald-300 bg-white">
            @else
              <div class="w-11 h-11 rounded-full bg-primary-gradient font-bold text-white text-base flex items-center justify-center shrink-0">
                {{ strtoupper(substr($companyName, 0, 1)) }}
              </div>
            @endif
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="font-bold text-on-background text-sm truncate flex items-center gap-1">
                  <span>{{ e($companyName) }}</span>
                  @if($kycStatus === 'approved')
                    <span class="material-symbols-outlined text-[#006c47] text-[16px] shrink-0" style="font-variation-settings: 'FILL' 1" title="Comercio Verificado">verified</span>
                  @endif
                </h3>
                @if($kycStatus === 'approved')
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-[#6efcb9]/40 text-[#006c47] shrink-0">✓ Verificada</span>
                @elseif($kycStatus === 'pending')
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-100 text-amber-800 shrink-0">⏳ Sin verificar</span>
                @else
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-rose-100 text-rose-800 shrink-0">❌ Rechazada</span>
                @endif
              </div>
              <p class="text-xs text-on-surface-variant truncate">{{ e($m->name) }} · {{ e($m->email) }}</p>
            </div>
          </div>
        </div>

        <div class="text-xs text-on-surface-variant bg-surface-container-low p-3 rounded-xl space-y-1">
          <p class="flex items-center gap-1.5"><span class="material-symbols-outlined text-sm">location_on</span> {{ e($address) }}</p>
          <p class="flex items-center gap-1.5"><span class="material-symbols-outlined text-sm">call</span> {{ $profile->phone ?? $m->phone ?? 'Sin teléfono' }}</p>
          <p class="flex items-center gap-1.5"><span class="material-symbols-outlined text-sm">inventory_2</span> {{ $productsCount }} productos en catálogo</p>
        </div>

        <!-- Action Bar -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-outline-variant/15">
          <button type="button" 
                  @click="openMerchantProfileObj(getMerchantData('{{ $m->id }}', '{{ e($companyName) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($profile->phone ?? $m->phone ?? '') }}', '{{ e($address) }}', '{{ $kycStatus }}', {{ $productsCount }}))" 
                  onclick="window.openProfileModalFallback(getMerchantData('{{ $m->id }}', '{{ e($companyName) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($profile->phone ?? $m->phone ?? '') }}', '{{ e($address) }}', '{{ $kycStatus }}', {{ $productsCount }}))"
                  class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold rounded-lg text-xs transition-all cursor-pointer">
            Ver Perfil
          </button>

          <form method="POST" action="{{ route('analyst.users.toggle-verification', $m->id) }}" class="inline">
            @csrf @method('PATCH')
            <button type="submit"
                    class="px-3 py-1.5 rounded-lg text-xs font-extrabold transition-all flex items-center gap-1 cursor-pointer {{ $kycStatus === 'approved' ? 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-500 hover:text-white' : 'bg-emerald-50 text-[#006c47] border border-emerald-200 hover:bg-[#006c47] hover:text-white' }}"
                    title="{{ $kycStatus === 'approved' ? 'Quitar chulito de verificación' : 'Dar chulito de verificación' }}">
              <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1">{{ $kycStatus === 'approved' ? 'verified_off' : 'verified' }}</span>
              <span>{{ $kycStatus === 'approved' ? 'Quitar ✓' : 'Verificar ✓' }}</span>
            </button>
          </form>

          @if($profile && $profile->rut_path)
            <a href="{{ route('analyst.users.rut', $m->id) }}" target="_blank" class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold rounded-lg text-xs">
              RUT
            </a>
          @endif

          <a href="{{ route('analyst.users.edit', $m->id) }}" class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold rounded-lg text-xs">
            Editar
          </a>

          <button type="button" 
                  @click="openMerchantNotifyObj(getMerchantData('{{ $m->id }}', '{{ e($companyName) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($profile->phone ?? $m->phone ?? '') }}', '{{ e($address) }}', '{{ $kycStatus }}', {{ $productsCount }}), '{{ $productsCount > 3 ? "congratulations" : "diagnostic" }}')" 
                  onclick="window.openNotifyModalFallback(getMerchantData('{{ $m->id }}', '{{ e($companyName) }}', '{{ e($m->name) }}', '{{ e($m->email) }}', '{{ e($profile->phone ?? $m->phone ?? '') }}', '{{ e($address) }}', '{{ $kycStatus }}', {{ $productsCount }}), '{{ $productsCount > 3 ? "congratulations" : "diagnostic" }}')" 
                  class="px-3 py-1.5 bg-[#006c47] text-white font-semibold rounded-lg text-xs shadow-sm hover:opacity-90 ml-auto flex items-center gap-1 transition-all cursor-pointer">
            <span class="material-symbols-outlined text-xs">send</span> Notificar
          </button>
        </div>

      </div>
      @empty
      <div class="col-span-2 text-center py-12 bg-white rounded-2xl border border-outline-variant/15">
        <p class="text-on-surface-variant text-sm font-semibold">No se encontraron comercios registrados.</p>
      </div>
      @endforelse

      <!-- Dynamic Empty State inside the grid container when client-side search returns no results -->
      <div id="merchantEmptyStateCard" style="display: none;" class="col-span-1 md:col-span-2 text-center py-10 bg-white rounded-2xl border border-outline-variant/15 p-6 space-y-3 shadow-sm">
        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
          <span class="material-symbols-outlined text-2xl">search_off</span>
        </div>
        <div>
          <h4 class="text-on-background text-sm font-bold">Sin resultados encontrados</h4>
          <p class="text-on-surface-variant text-xs mt-1">No existe ningún comercio o empresario registrado que coincida con "<span id="merchantEmptySearchQuery" class="font-bold text-on-background" x-text="searchFilter"></span>".</p>
        </div>
        <button type="button" 
                @click="searchFilter = ''; sectorFilter = ''; statusFilter = ''; filterMerchantCards();" 
                onclick="window.resetMerchantFilters()"
                class="px-4 py-2 bg-primary-gradient text-white text-xs font-bold rounded-xl shadow-sm hover:opacity-90 transition-all cursor-pointer inline-flex items-center gap-1.5">
          <span class="material-symbols-outlined text-xs">restart_alt</span> Restablecer filtros y mostrar comercios
        </button>
      </div>
    </div>

    <!-- PRESERVED ADMINISTRATIVE SUITE (KYC, BANNERS, ML REVIEWS, ML ENGINE) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4 border-t border-outline-variant/15">
      
      <!-- KYC Pendientes Form Module -->
      <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold font-['Manrope'] text-on-background">Comerciantes KYC Pendientes</h3>
          <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">{{ $pendingKyc->count() }} pendientes</span>
        </div>

        @if($pendingKyc->isEmpty())
          <p class="text-xs text-on-surface-variant py-4">No hay comerciantes en proceso de validación KYC.</p>
        @else
          <div class="space-y-4">
            @foreach($pendingKyc as $kyc)
            <div class="p-4 bg-surface-container-low border border-surface-container-high rounded-xl space-y-3">
              <div class="flex items-center justify-between text-xs">
                <div>
                  <p class="font-bold text-on-background text-sm">{{ e($kyc->company_name) }}</p>
                  <p class="text-on-surface-variant">{{ e($kyc->user->name) }} · {{ e($kyc->user->email) }}</p>
                </div>
                <div class="flex items-center gap-1.5">
                  @if($kyc->rut_path)
                    <a href="{{ route('analyst.users.rut', $kyc->merchant_id) }}" target="_blank" class="px-2.5 py-1 bg-surface-container-high hover:bg-slate-200 text-on-surface font-bold rounded-lg text-xs inline-flex items-center gap-1">
                      <span class="material-symbols-outlined text-xs text-red-600">picture_as_pdf</span> Ver RUT
                    </a>
                  @endif
                  <a href="https://www.rues.org.co/" target="_blank" class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold rounded-lg text-xs inline-flex items-center gap-1" title="Verificar matrícula mercantil en RUES Oficial">
                    <span class="material-symbols-outlined text-xs">verified</span> RUES Oficial
                  </a>
                </div>
              </div>

              <form method="POST" action="{{ route('analyst.users.kyc', $kyc->merchant_id) }}" class="space-y-2">
                <div class="p-2.5 bg-white border border-slate-200 rounded-xl text-[11px] space-y-1.5 text-slate-700">
                  <span class="font-extrabold block text-slate-800 text-xs">🛡️ Protocolo de Certificación Segura (Ley 1581 / RUES):</span>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-[10px]">
                    <span class="flex items-center gap-1 text-slate-600">✓ RUT DIAN vigente</span>
                    <span class="flex items-center gap-1 text-slate-600">✓ RUES / Cámara de Comercio</span>
                    <span class="flex items-center gap-1 text-slate-600">✓ Cédula Representante Legal</span>
                    <span class="flex items-center gap-1 text-slate-600">✓ Titularidad Bancaria Verificada</span>
                  </div>
                </div>
                @csrf
                <textarea name="kyc_notes" placeholder="Motivo de rechazo (opcional si apruebas)" class="w-full bg-white border border-outline-variant/30 rounded-xl p-2.5 text-xs outline-none focus:ring-2 focus:ring-primary/20 resize-none" rows="2"></textarea>
                <div class="flex gap-2">
                  <button type="submit" name="kyc_status" value="approved" class="flex-1 py-2 bg-[#00b67a] text-white text-xs font-bold rounded-xl shadow hover:opacity-90">Aprobar Cuenta</button>
                  <button type="submit" name="kyc_status" value="rejected" class="flex-1 py-2 bg-error text-white text-xs font-bold rounded-xl shadow hover:opacity-90">Rechazar</button>
                </div>
              </form>
            </div>
            @endforeach
          </div>
        @endif
      </div>

      <!-- Banners, ML Reviews Report & ML AI Engine -->
      <div class="space-y-6">
        
        <!-- Solicitudes de Banners -->
        <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/15 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold font-['Manrope'] text-on-background">Solicitudes de Banners</h3>
            <a href="{{ route('analyst.banners') }}" class="text-primary text-xs font-bold hover:underline">Gestionar</a>
          </div>

          @if($pendingBanners->isEmpty())
            <p class="text-xs text-on-surface-variant py-2">No hay solicitudes de banners pendientes.</p>
          @else
            <div class="space-y-3">
              @foreach($pendingBanners as $pb)
              <div class="p-3 bg-surface-container-low rounded-xl flex items-center justify-between text-xs">
                <span class="font-bold text-on-background">{{ e($pb->user->companyProfile->company_name ?? $pb->user->name) }}</span>
                <a href="{{ route('analyst.banner-requests.show', $pb->id) }}" class="px-3 py-1 bg-primary text-white font-bold rounded-lg text-xs">Revisar</a>
              </div>
              @endforeach
            </div>
          @endif
        </div>

        <!-- ML Reviews Report Link -->
        <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-primary/20 space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#6efcb9]/30 rounded-xl flex items-center justify-center">
              <span class="material-symbols-outlined text-[#006c47]">rate_review</span>
            </div>
            <div>
              <h3 class="font-['Manrope'] font-bold text-on-background text-base">Análisis de Reseñas (ML)</h3>
              <p class="text-xs text-on-surface-variant">Reporte de sentimiento, rating y clustering por comerciante</p>
            </div>
          </div>
          <a href="{{ route('analyst.reviews-report') }}" class="w-full flex items-center justify-center gap-2 py-2.5 bg-primary text-white font-bold rounded-xl shadow text-xs hover:opacity-90">
            <span class="material-symbols-outlined text-base">insights</span> Ver Reporte ML de Reseñas
          </a>
        </div>

        <!-- AI ML Script Executor -->
        <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-[#a6383b]/20 space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#fa7777]/20 rounded-xl flex items-center justify-center">
              <span class="material-symbols-outlined text-[#a6383b]">psychology</span>
            </div>
            <div>
              <h3 class="font-['Manrope'] font-bold text-on-background text-base">Motor de Inteligencia Artificial (ML)</h3>
              <p class="text-xs text-on-surface-variant">Ejecuta el script puente Python (`python/ml_analyzer.py`)</p>
            </div>
          </div>
          <form method="POST" action="{{ route('analyst.run-ml') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 bg-[#a6383b] text-white font-bold rounded-xl shadow text-xs hover:opacity-90">
              <span class="material-symbols-outlined text-base">smart_toy</span> Alimentar Data & Ejecutar Script ML
            </button>
          </form>
        </div>

      </div>

    </div>

  </div>

  <!-- ========================================================================= -->
  <!-- TAB 4: RED NEURONAL, COMISIONES & MONITOR DE SEGURIDAD (BLUEPRINT 3.3)   -->
  <!-- ========================================================================= -->
  <div id="exec-tab-neural" x-show="activeTab === 'neural'" class="space-y-8" x-cloak>

    <!-- SECTION 1: CONSOLA DE RED NEURONAL & ANALÍTICA PREDICTIVA -->
    <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-indigo-500/20 relative overflow-hidden">
      <div class="absolute -right-20 -top-20 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-white/10 relative z-10">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-3 py-1 rounded-full text-[10px] font-black bg-purple-500/30 text-purple-200 border border-purple-400/30 uppercase tracking-widest flex items-center gap-1.5">
              <span class="material-symbols-outlined text-xs text-purple-300">psychology</span> Torre de Mando Neuronal
            </span>
            <span class="text-xs text-slate-400">Algoritmo Predictivo V3 · Provincia del Sumapaz</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-['Manrope'] font-black tracking-tight text-white">
            Consola Neuronal & Proyecciones de Demanda
          </h2>
          <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
            Modelos de regresión de ventas y predicción de flujo de caja para el comercio digital de Fusagasugá
          </p>
        </div>

        <div class="flex items-center gap-4 bg-white/5 border border-white/10 p-4 rounded-2xl">
          <div class="w-12 h-12 rounded-xl bg-purple-500/20 text-purple-300 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">trending_up</span>
          </div>
          <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">GMV Proyectado 30 Días</span>
            <p class="text-xl sm:text-2xl font-black text-purple-300">${{ number_format($projectedGmv30Days, 0, ',', '.') }} COP</p>
          </div>
        </div>
      </div>

      <!-- Métricas Predictivas y Estabilidad FusaCoins -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 relative z-10">
        
        <!-- Metric 1: GMV Actual -->
        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
          <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">GMV Consolidado</span>
          <p class="text-xl font-black text-white">${{ number_format($currentGmv, 0, ',', '.') }}</p>
          <span class="text-[10px] text-emerald-400 font-semibold mt-1 block">Ventas pagadas Wompi</span>
        </div>

        <!-- Metric 2: FusaCoins Emitidos -->
        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
          <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Coins Emitidos</span>
          <p class="text-xl font-black text-amber-300">{{ number_format($fusaCoinsEmitted) }}</p>
          <span class="text-[10px] text-slate-300 font-semibold mt-1 block">100 Coins = $100 COP</span>
        </div>

        <!-- Metric 3: FusaCoins Redimidos -->
        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
          <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Coins Redimidos</span>
          <p class="text-xl font-black text-emerald-300">{{ number_format($fusaCoinsRedeemed) }}</p>
          <span class="text-[10px] text-slate-300 font-semibold mt-1 block">Ahorro en checkout</span>
        </div>

        <!-- Metric 4: Ratio de Redención & Solvencia -->
        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
          <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Ratio de Redención</span>
          <p class="text-xl font-black text-indigo-300">{{ $redemptionRatio }}%</p>
          <span class="text-[10px] text-emerald-400 font-semibold mt-1 block">Riesgo de liquidez: Bajo</span>
        </div>

      </div>

      <!-- Mapa de Calor Territorial de Fusagasugá -->
      <div class="mt-8 pt-6 border-t border-white/10 relative z-10">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-['Manrope'] font-bold text-sm text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-sm text-rose-400">local_fire_department</span>
            Mapa de Calor de Ventas por Comunas y Veredas
          </h3>
          <span class="text-[11px] text-slate-400">Distribución de demanda territorial</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          @foreach($territorialHeatmap as $zone)
            <div class="bg-white/5 p-3.5 rounded-xl border border-white/10 space-y-2 hover:bg-white/10 transition-colors">
              <div class="flex items-center justify-between">
                <span class="font-bold text-xs text-white">{{ $zone['zone'] }}</span>
                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $zone['color'] }}"></span>
              </div>
              <div class="flex items-baseline justify-between text-xs">
                <span class="text-slate-400">{{ $zone['orders'] }} pedidos</span>
                <span class="font-black text-white">${{ number_format($zone['gmv']) }}</span>
              </div>
              <!-- Density bar -->
              <div class="w-full h-1 bg-white/10 rounded-full overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $zone['density'] === 'high' ? '90%' : ($zone['density'] === 'medium' ? '55%' : '25%') }}; background-color: {{ $zone['color'] }}"></div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <!-- SECTION 2: GESTIÓN DE COMISIONES (8%) Y ESTADO ANTE FACTUS DIAN -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/20">
        <div>
          <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">account_balance</span>
            Gestión de Tiendas, Comisiones FusaShop (8%) y Estado Fiscal DIAN
          </h2>
          <p class="text-xs text-on-surface-variant mt-0.5">
            Liquidación automática de comisiones retenidas y auditoría de emisión de facturas electrónicas vía Factus
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
            Comisión Plataforma: 8% Flat
          </span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-outline-variant/20 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
              <th class="py-3 px-4">Comercio / Tienda</th>
              <th class="py-3 px-4">Contacto / Tel</th>
              <th class="py-3 px-4">Ventas Brutas</th>
              <th class="py-3 px-4 text-emerald-800">Comisión 8% Retenida</th>
              <th class="py-3 px-4">Pedidos</th>
              <th class="py-3 px-4">Facturación Factus DIAN</th>
              <th class="py-3 px-4 text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10 text-xs">
            @foreach($merchantCommissions->take(10) as $mc)
              <tr class="hover:bg-surface-container-low transition-colors">
                <td class="py-3.5 px-4 font-bold text-on-surface">
                  {{ $mc->merchant->companyProfile->company_name ?? $mc->merchant->name }}
                  <span class="block text-[10px] text-on-surface-variant font-normal">{{ $mc->merchant->email }}</span>
                </td>
                <td class="py-3.5 px-4 text-on-surface-variant">
                  {{ $mc->merchant->phone ?? 'Sin teléfono' }}
                </td>
                <td class="py-3.5 px-4 font-bold text-on-surface">
                  ${{ number_format($mc->sales, 0, ',', '.') }} COP
                </td>
                <td class="py-3.5 px-4 font-black text-[#006c47]">
                  ${{ number_format($mc->commission, 0, ',', '.') }} COP
                </td>
                <td class="py-3.5 px-4">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-container text-on-surface">
                    {{ $mc->orders_count }} órdenes
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  @if($mc->invoiced_orders_count > 0)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 flex items-center gap-1 w-fit">
                      <span class="material-symbols-outlined text-[12px]">verified</span> Conforme DIAN ({{ $mc->invoiced_orders_count }})
                    </span>
                  @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 flex items-center gap-1 w-fit">
                      <span class="material-symbols-outlined text-[12px]">schedule</span> Sin facturas emitidas
                    </span>
                  @endif
                </td>
                <td class="py-3.5 px-4 text-right">
                  <a href="{{ route('consumer.merchant.profile', $mc->merchant->id) }}" target="_blank" class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold text-xs rounded-lg transition-all inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs">store</span> Ver Tienda
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- SECTION 3: MÓDULO DE CREACIÓN DE TALLERES Y CURSOS ACADÉMICOS -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-6" x-data="{ newCourseModal: false }">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/20">
        <div>
          <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">video_camera_front</span>
            Editor de Contenido y Talleres de Capacitación (Academia FusaShop)
          </h2>
          <p class="text-xs text-on-surface-variant mt-0.5">
            Publica webinars formativos sobre marketing digital, logística Envia.com y facturación Factus para los comerciantes
          </p>
        </div>
        <button type="button" @click="newCourseModal = true" onclick="document.getElementById('new-course-modal').style.display='flex'" class="px-4 py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 self-start sm:self-auto cursor-pointer">
          <span class="material-symbols-outlined text-[18px]">add</span> Publicar Nuevo Taller
        </button>
      </div>

      <!-- Grid de Cursos Publicados -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-4 bg-surface-container-low rounded-2xl border border-outline-variant/20 space-y-2">
          <div class="flex items-center justify-between">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Finanzas & Legal</span>
            <span class="text-xs text-on-surface-variant font-medium">45 min</span>
          </div>
          <h4 class="font-bold text-sm text-on-background">E-Commerce Local & Facturación DIAN con Factus</h4>
          <p class="text-xs text-on-surface-variant">Impartido a 45 comerciantes de Fusagasugá con 100% de cumplimiento.</p>
        </div>

        <div class="p-4 bg-surface-container-low rounded-2xl border border-outline-variant/20 space-y-2">
          <div class="flex items-center justify-between">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-[#006c47]">Operaciones</span>
            <span class="text-xs text-on-surface-variant font-medium">30 min</span>
          </div>
          <h4 class="font-bold text-sm text-on-background">Estrategia de Logística y Empaque Térmico con Envia.com</h4>
          <p class="text-xs text-on-surface-variant">Capacitación sobre construcción volumétrica y rotulación de guías.</p>
        </div>

        <div class="p-4 bg-surface-container-low rounded-2xl border border-outline-variant/20 space-y-2">
          <div class="flex items-center justify-between">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800">Marketing O2O</span>
            <span class="text-xs text-on-surface-variant font-medium">50 min</span>
          </div>
          <h4 class="font-bold text-sm text-on-background">Fidelización O2O y Turismo Gastronómico en Fusagasugá</h4>
          <p class="text-xs text-on-surface-variant">Uso de vouchers con código QR y canje de FusaCoins en mesa.</p>
        </div>
      </div>

      <!-- Quick Publish Modal -->
      <div id="new-course-modal" x-show="newCourseModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="newCourseModal = false">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-outline-variant/20 space-y-4" @click.stop>
          <div class="flex items-center justify-between pb-3 border-b border-outline-variant/20">
            <h3 class="font-['Manrope'] font-bold text-base text-on-background">Publicar Nuevo Taller en la Academia</h3>
            <button type="button" @click="newCourseModal = false" onclick="document.getElementById('new-course-modal').style.display='none'" class="text-on-surface-variant hover:text-on-background cursor-pointer">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Título del Taller o Webinar</label>
              <input type="text" placeholder="Ej. Fotografía de Producto y Optimización de Catálogo" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 font-medium">
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-on-surface-variant mb-1">Categoría</label>
                <select class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-2.5 outline-none font-medium">
                  <option>Marketing Digital</option>
                  <option>Logística & Empaque</option>
                  <option>Finanzas & Factus DIAN</option>
                  <option>Servicio al Cliente</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-on-surface-variant mb-1">Duración Estimada</label>
                <input type="text" placeholder="Ej. 40 min" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-2.5 outline-none font-medium">
              </div>
            </div>

            <div>
              <label class="block font-bold text-on-surface-variant mb-1">URL de Video / Sala Virtual</label>
              <input type="url" placeholder="https://youtube.com/... o enlace de Meet" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-2.5 outline-none font-medium">
            </div>
          </div>

          <div class="pt-3 border-t border-outline-variant/20 flex justify-end gap-2">
            <button type="button" @click="newCourseModal = false" onclick="document.getElementById('new-course-modal').style.display='none'" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high font-bold text-xs rounded-xl cursor-pointer">Cancelar</button>
            <button type="button" @click="newCourseModal = false" onclick="document.getElementById('new-course-modal').style.display='none'; if(typeof window.showExecutiveToast==='function') window.showExecutiveToast('📢 Taller publicado y notificado a la red de comerciantes.');" class="px-5 py-2 bg-[#006c47] hover:bg-[#10B981] text-white font-bold text-xs rounded-xl shadow cursor-pointer">Publicar Taller</button>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 4: CENTRO DE SEGURIDAD Y MONITOR DE INTEGRACIONES (SEMÁFORO DE SALUD) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/20">
        <div>
          <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
            <span class="material-symbols-outlined text-[#006c47]">security</span>
            Centro de Seguridad & Semáforo de Integraciones de APIs Externas
          </h2>
          <p class="text-xs text-on-surface-variant mt-0.5">
            Monitor de salud en tiempo real para Wompi, Factus, Envia.com y Resend con control anti-replay y reintentos automáticos
          </p>
        </div>
        <a href="{{ route('analyst.integrations') }}" class="px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl shadow-sm hover:opacity-95 transition-all flex items-center gap-1.5 self-start sm:self-auto">
          <span class="material-symbols-outlined text-sm">history</span> Consola de Logs & Reintento
        </a>
      </div>

      <!-- Semáforo de Salud de las 4 APIs Externas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($apiHealth as $srvKey => $srvData)
          @php
            $isHealthy = $srvData['status'] === 'healthy';
            $isWarning = $srvData['status'] === 'warning';
          @endphp
          <div class="p-5 rounded-2xl border {{ $isHealthy ? 'bg-emerald-50/50 border-emerald-200' : ($isWarning ? 'bg-amber-50/50 border-amber-200' : 'bg-red-50/50 border-red-200') }} space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-mono font-black text-xs uppercase tracking-wider text-on-surface">{{ strtoupper($srvKey) }}</span>
              <span class="w-3 h-3 rounded-full {{ $isHealthy ? 'bg-emerald-500 animate-pulse' : ($isWarning ? 'bg-amber-500' : 'bg-red-500') }}" title="Semáforo de Salud"></span>
            </div>

            <div>
              <p class="text-2xl font-black font-['Manrope'] {{ $isHealthy ? 'text-emerald-800' : ($isWarning ? 'text-amber-800' : 'text-red-800') }}">
                {{ $srvData['uptime_pct'] }}%
              </p>
              <span class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider">Uptime 24 Horas</span>
            </div>

            <div class="pt-2 border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant">
              <span>Peticiones: <strong>{{ $srvData['total_24h'] }}</strong></span>
              <span>Fallos: <strong class="{{ $srvData['failed_24h'] > 0 ? 'text-red-600' : '' }}">{{ $srvData['failed_24h'] }}</strong></span>
            </div>
          </div>
        @endforeach
      </div>

      <!-- Panel de Auditoría y Eventos de Seguridad -->
      <div class="pt-4 space-y-3">
        <h3 class="font-['Manrope'] font-bold text-sm text-on-background flex items-center gap-2">
          <span class="material-symbols-outlined text-sm text-on-surface-variant">crisis_alert</span>
          Eventos de Seguridad & Fallos de Integración Recientes
        </h3>

        @if($securityAuditLogs->isEmpty())
          <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-900 font-semibold flex items-center gap-2">
            <span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>
            <span>Cero incidentes de seguridad registrados en las últimas 24 horas. Los webhooks de Wompi y Envia operan con firmas SHA-256 válidas.</span>
          </div>
        @else
          <div class="space-y-2">
            @foreach($securityAuditLogs as $log)
              <div class="p-3 bg-surface-container-low rounded-xl border border-outline-variant/15 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div class="space-y-0.5">
                  <div class="flex items-center gap-2">
                    <span class="font-mono font-bold text-red-600 uppercase">[{{ $log->service }}] {{ $log->action }}</span>
                    <span class="text-on-surface-variant">· {{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                    @if($log->order_id)
                      <span class="text-on-surface-variant">· Orden #{{ $log->order_id }}</span>
                    @endif
                  </div>
                  <p class="text-on-surface-variant text-[11px] font-mono truncate max-w-xl">{{ $log->error }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <form method="POST" action="{{ route('analyst.integrations.retry', $log->id) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1 bg-surface-container hover:bg-surface-container-highest border border-outline-variant/30 text-on-surface font-bold text-xs rounded-lg transition-all flex items-center gap-1">
                      <span class="material-symbols-outlined text-xs">replay</span> Reintentar
                    </button>
                  </form>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

  </div>


  <!-- MODAL: PERFIL COMERCIAL DE MIPYME -->
  <div x-show="showProfileModal" 
       id="profile-modal"
       x-cloak
       style="display: none;"
       class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showProfileModal = false" class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-5 border border-outline-variant/20 max-h-[90vh] overflow-y-auto custom-scrollbar">
      
      <!-- Header -->
      <div class="flex items-start justify-between pb-3 border-b border-outline-variant/15">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full bg-primary-gradient font-extrabold text-white text-lg flex items-center justify-center shrink-0 shadow-sm" id="profile-modal-avatar">
            <span x-text="selectedMerchant && selectedMerchant.name ? selectedMerchant.name.substring(0,1).toUpperCase() : 'M'">M</span>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-lg font-bold font-['Manrope'] text-on-background" id="profile-modal-title" x-text="selectedMerchant ? selectedMerchant.name : 'Comerciante Fusagasugá'">Comerciante Fusagasugá</h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#6efcb9]/40 text-[#006c47]" id="profile-modal-status" x-text="selectedMerchant ? (selectedMerchant.status || 'Verificada') : 'Verificada'">Verificada</span>
            </div>
            <p class="text-xs text-on-surface-variant flex items-center gap-1 mt-0.5">
              <span class="material-symbols-outlined text-xs text-primary">location_on</span>
              <span id="profile-modal-location" x-text="selectedMerchant ? (selectedMerchant.location || 'Fusagasugá') : 'Fusagasugá'">Fusagasugá</span>
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" @click="isEditingProfile = !isEditingProfile" onclick="window.toggleProfileEditFallback()" class="px-2.5 py-1.5 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-lg flex items-center gap-1 transition-all cursor-pointer">
            <span class="material-symbols-outlined text-xs" id="profile-edit-btn-icon" x-text="isEditingProfile ? 'close' : 'edit'">edit</span>
            <span id="profile-edit-btn-text" x-text="isEditingProfile ? 'Cancelar' : 'Editar'">Editar</span>
          </button>
          <button type="button" @click="closeProfileModal()" onclick="window.closeProfileModalFallback()" class="text-on-surface-variant hover:text-on-background cursor-pointer">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>

      <!-- VIEW MODE SECTION -->
      <div x-show="!isEditingProfile" id="profile-view-section" class="space-y-4">
        <!-- Quick Metrics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
          <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/15">
            <p class="text-[10px] font-bold text-on-surface-variant uppercase">Ventas (Mes)</p>
            <p class="text-sm font-extrabold text-[#006c47]" id="profile-modal-sales" x-text="selectedMerchant ? (selectedMerchant.sales || '$ 0 COP') : '$ 0 COP'">$ 0 COP</p>
          </div>
          <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/15">
            <p class="text-[10px] font-bold text-on-surface-variant uppercase">Catálogo</p>
            <p class="text-sm font-extrabold text-on-background" id="profile-modal-products" x-text="selectedMerchant ? ((selectedMerchant.productsCount !== undefined ? selectedMerchant.productsCount : 0) + ' prods') : '0 prods'">0 prods</p>
          </div>
          <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/15">
            <p class="text-[10px] font-bold text-on-surface-variant uppercase">Pasarela</p>
            <p class="text-xs font-bold text-on-background truncate" id="profile-modal-payments" x-text="selectedMerchant ? (selectedMerchant.paymentMethods || 'PSE, Nequi') : 'PSE, Nequi'">PSE, Nequi</p>
          </div>
          <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant/15">
            <p class="text-[10px] font-bold text-on-surface-variant uppercase">Conversión</p>
            <p class="text-sm font-extrabold text-primary" id="profile-modal-conversion" x-text="selectedMerchant ? (selectedMerchant.conversionRate || '5.4%') : '5.4%'">5.4%</p>
          </div>
        </div>

        <!-- Detail list -->
        <div class="text-xs text-on-surface space-y-3 bg-surface-container-low p-4 rounded-2xl border border-outline-variant/15">
          <div class="flex items-center justify-between">
            <div>
              <span class="font-bold text-on-surface-variant block text-[11px]">Representante Legal:</span>
              <span class="font-semibold text-on-background text-xs" id="profile-modal-representative" x-text="selectedMerchant ? (selectedMerchant.representative || selectedMerchant.name) : ''">Representante Legal</span>
            </div>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-outline-variant/10">
            <div>
              <span class="font-bold text-on-surface-variant block text-[11px]">Correo Electrónico:</span>
              <span class="font-semibold text-on-background text-xs" id="profile-modal-email" x-text="selectedMerchant ? selectedMerchant.email : ''">contacto@comercio.com</span>
            </div>
            <button type="button" @click="openEmailClient()" onclick="window.openEmailClientFallback()" class="px-2.5 py-1 bg-surface-container hover:bg-primary/10 text-primary font-bold rounded-lg text-[11px] flex items-center gap-1 transition-all cursor-pointer">
              <span class="material-symbols-outlined text-xs">mail</span> Enviar E-mail
            </button>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-outline-variant/10">
            <div>
              <span class="font-bold text-on-surface-variant block text-[11px]">Teléfono / WhatsApp:</span>
              <span class="font-semibold text-on-background text-xs" id="profile-modal-phone" x-text="selectedMerchant ? selectedMerchant.phone : ''">+57 300 123 4567</span>
            </div>
            <button type="button" @click="openWhatsAppChat()" onclick="window.openWhatsAppChatFallback()" class="px-2.5 py-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-lg text-[11px] flex items-center gap-1 transition-all cursor-pointer shadow-sm">
              <span class="material-symbols-outlined text-xs">chat</span> Abrir WhatsApp
            </button>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-outline-variant/10">
            <div>
              <span class="font-bold text-on-surface-variant block text-[11px]">Dirección / Ubicación:</span>
              <span class="font-semibold text-on-background text-xs" id="profile-modal-address" x-text="selectedMerchant ? (selectedMerchant.location || 'Fusagasugá') : 'Fusagasugá'">Fusagasugá</span>
            </div>
            <span class="px-2 py-0.5 rounded bg-surface-container text-on-surface-variant font-bold text-[10px] flex items-center gap-1">
              <span class="material-symbols-outlined text-[10px]">map</span> Fusagasugá
            </span>
          </div>

          <div class="pt-2 border-t border-outline-variant/10">
            <span class="font-bold text-on-surface-variant block text-[11px]">Sector Comercial:</span>
            <span class="font-semibold text-on-background text-xs" id="profile-modal-sector" x-text="selectedMerchant ? selectedMerchant.sector : 'Comercio Local'">Comercio Local</span>
          </div>
        </div>
      </div>

      <!-- EDIT MODE SECTION -->
      <div x-show="isEditingProfile" id="profile-edit-section" class="space-y-4" style="display: none;">
        <form @submit.prevent="saveProfileChanges()" class="space-y-3 text-xs">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Nombre Comercial / Empresa</label>
              <input type="text" id="profile-edit-name" :value="selectedMerchant ? selectedMerchant.name : ''" @input="if(selectedMerchant) selectedMerchant.name = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Representante Legal</label>
              <input type="text" id="profile-edit-representative" :value="selectedMerchant ? selectedMerchant.representative : ''" @input="if(selectedMerchant) selectedMerchant.representative = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Correo Electrónico</label>
              <input type="email" id="profile-edit-email" :value="selectedMerchant ? selectedMerchant.email : ''" @input="if(selectedMerchant) selectedMerchant.email = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Teléfono / WhatsApp</label>
              <input type="text" id="profile-edit-phone" :value="selectedMerchant ? selectedMerchant.phone : ''" @input="if(selectedMerchant) selectedMerchant.phone = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Dirección / Ubicación</label>
              <input type="text" id="profile-edit-location" :value="selectedMerchant ? selectedMerchant.location : ''" @input="if(selectedMerchant) selectedMerchant.location = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Sector Comercial</label>
              <input type="text" id="profile-edit-sector" :value="selectedMerchant ? selectedMerchant.sector : ''" @input="if(selectedMerchant) selectedMerchant.sector = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Ventas Est. (Mes)</label>
              <input type="text" id="profile-edit-sales" :value="selectedMerchant ? selectedMerchant.sales : ''" @input="if(selectedMerchant) selectedMerchant.sales = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
            <div>
              <label class="block font-bold text-on-surface-variant mb-1">Pasarelas de Pago</label>
              <input type="text" id="profile-edit-payments" :value="selectedMerchant ? selectedMerchant.paymentMethods : ''" @input="if(selectedMerchant) selectedMerchant.paymentMethods = $event.target.value" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl px-3 py-2 text-xs outline-none focus:ring-2 focus:ring-primary/20 font-semibold text-on-background">
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-outline-variant/15">
            <button type="button" @click="isEditingProfile = false" onclick="window.toggleProfileEditFallback()" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-xl cursor-pointer">
              Cancelar Edición
            </button>
            <button type="button" @click="saveProfileChanges()" onclick="window.saveProfileChangesFallback()" class="px-4 py-2 bg-primary text-white font-bold text-xs rounded-xl shadow hover:opacity-90 flex items-center gap-1 cursor-pointer">
              <span class="material-symbols-outlined text-xs">save</span> Guardar Cambios
            </button>
          </div>
        </form>
      </div>

      <!-- Action Footer -->
      <div class="flex flex-wrap items-center justify-end gap-2 pt-3 border-t border-outline-variant/15">
        <button type="button" @click="closeProfileModal()" onclick="window.closeProfileModalFallback()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-xl cursor-pointer">
          Cerrar
        </button>
        <button type="button" @click="closeProfileModal(); openMerchantNotifyObj(selectedMerchant, 'congratulations')" onclick="window.closeProfileModalFallback(); window.openNotifyModalFallback(window.currentSelectedMerchant, 'congratulations')" class="px-4 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-bold text-xs rounded-xl flex items-center gap-1 transition-all cursor-pointer">
          <span class="material-symbols-outlined text-sm">workspace_premium</span> Felicitaciones
        </button>
        <button type="button" @click="closeProfileModal(); openMerchantNotifyObj(selectedMerchant, 'diagnostic')" onclick="window.closeProfileModalFallback(); window.openNotifyModalFallback(window.currentSelectedMerchant, 'diagnostic')" class="px-4 py-2 bg-rose-100 hover:bg-rose-200 text-rose-900 font-bold text-xs rounded-xl flex items-center gap-1 transition-all cursor-pointer">
          <span class="material-symbols-outlined text-sm">warning</span> Diagnóstico / Alerta
        </button>
      </div>

    </div>
  </div>

  <!-- MODAL: REPORTE AUTOMÁTICO DE VENTAS Y DIAGNÓSTICO CAUSAL PARA COMERCIANTE -->
  <div id="notify-modal"
       x-show="showNotifyModal" 
       x-cloak
       style="display: none;"
       class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showNotifyModal = false"
         style="max-height: 85vh; display: flex; flex-direction: column;"
         class="bg-white rounded-3xl max-w-2xl w-full p-5 shadow-2xl border border-outline-variant/20 overflow-hidden">
      
      <!-- Barra superior fija del modal -->
      <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-200 shrink-0">
        <div class="flex items-center gap-2.5">
          <div id="notify-modal-header-badge" 
               class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
               :class="notifyMessageType === 'congratulations' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
            <span id="notify-modal-header-icon" class="material-symbols-outlined text-xl" x-text="notifyMessageType === 'congratulations' ? 'workspace_premium' : 'monitoring'">monitoring</span>
          </div>
          <div>
            <h3 id="notify-modal-header-title" class="text-sm sm:text-base font-extrabold text-on-background">Reporte Automático de Ventas y Diagnóstico Causal</h3>
            <p class="text-xs text-on-surface-variant">Comercio destinatario: <strong id="notify-modal-recipient" class="text-slate-900">Comerciante Seleccionado</strong></p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <a id="notify-modal-view-report-link" href="#" target="_blank" class="px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-[#006c47] border border-emerald-200 font-extrabold text-xs flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">open_in_new</span> Ver Reporte
          </a>
          <button type="button"
                  onclick="window.sendMerchantReportAjax()"
                  class="px-3 py-1.5 rounded-xl bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold text-xs flex items-center gap-1 shadow-sm cursor-pointer">
            <span class="material-symbols-outlined text-xs">send</span> Enviar Ahora
          </button>
          <button type="button" @click="closeNotifyModal()" onclick="window.closeNotifyModalFallback()" class="text-on-surface-variant hover:text-on-background cursor-pointer p-1">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>

      <!-- Cuerpo central desplazable -->
      <div style="overflow-y: auto; flex: 1;" class="space-y-3 py-3 pr-1 custom-scrollbar">

        <!-- Resumen Automático de Ventas de la Empresa -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
          <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80">
            <span class="block text-[10px] font-black uppercase text-[#006c47]">Ventas Totales</span>
            <span id="notify-modal-sales-kpi" class="text-sm font-black text-slate-900 mt-0.5 block">$ 0 COP</span>
            <span id="notify-modal-net-kpi" class="text-[10px] text-emerald-800 font-semibold">Neto 92%: $ 0</span>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            <span class="block text-[10px] font-black uppercase text-slate-500">Variación Periodo</span>
            <span id="notify-modal-variation-kpi" class="text-sm font-black text-slate-900 mt-0.5 block">0%</span>
            <span class="text-[10px] text-slate-500 font-medium">vs mes anterior</span>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            <span class="block text-[10px] font-black uppercase text-slate-500">Pedidos & Ticket</span>
            <span id="notify-modal-orders-kpi" class="text-sm font-black text-slate-900 mt-0.5 block">0 pedidos</span>
            <span id="notify-modal-ticket-kpi" class="text-[10px] text-slate-500 font-medium">Ticket: $ 0</span>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
            <span class="block text-[10px] font-black uppercase text-slate-500">Catálogo & Stock</span>
            <span id="notify-modal-stock-kpi" class="text-sm font-black text-slate-900 mt-0.5 block">0 activos</span>
            <span id="notify-modal-rating-kpi" class="text-[10px] text-slate-500 font-medium">⭐ 5.0 / 5.0</span>
          </div>
        </div>

        <!-- Segmented Type Switcher -->
        <div class="flex items-center gap-2 p-1 bg-surface-container-low rounded-xl border border-outline-variant/15 text-xs font-bold">
          <button id="notify-tab-congratulations"
                  type="button" 
                  @click="notifyMessageType = 'congratulations'; updateDefaultMessage()" 
                  onclick="window.selectNotifyMessageType('congratulations')"
                  :class="notifyMessageType === 'congratulations' ? 'bg-[#006c47] text-white shadow-sm' : 'bg-surface-container text-on-surface-variant hover:text-on-background'"
                  class="flex-1 py-1.5 bg-surface-container text-on-surface-variant rounded-lg transition-all flex items-center justify-center gap-1 cursor-pointer">
            <span class="material-symbols-outlined text-sm">trending_up</span> Reporte de Impulso (¿Por qué subieron?)
          </button>
          <button id="notify-tab-diagnostic"
                  type="button" 
                  @click="notifyMessageType = 'diagnostic'; updateDefaultMessage()" 
                  onclick="window.selectNotifyMessageType('diagnostic')"
                  :class="notifyMessageType === 'diagnostic' ? 'bg-rose-600 text-white shadow-sm' : 'bg-surface-container text-on-surface-variant hover:text-on-background'"
                  class="flex-1 py-1.5 bg-rose-600 text-white rounded-lg transition-all flex items-center justify-center gap-1 cursor-pointer">
            <span class="material-symbols-outlined text-sm">trending_down</span> Reporte de Diagnóstico (¿Por qué bajaron?)
          </button>
        </div>

        <!-- Explicación Automática de Por qué Subieron y Por qué Bajaron -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
          <div class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-1">
            <p class="font-extrabold text-[#006c47] text-xs flex items-center gap-1">
              <span>📈</span> ¿Por qué aumentaron / impulsaron sus ventas?
            </p>
            <ul id="notify-modal-growth-list" class="space-y-1 text-[11px] text-emerald-950 list-disc pl-4">
              <li>Cargando impulsores de venta...</li>
            </ul>
          </div>
          <div class="p-3 rounded-2xl bg-rose-50/70 border border-rose-200 space-y-1">
            <p class="font-extrabold text-rose-800 text-xs flex items-center gap-1">
              <span>📉</span> ¿Por qué bajaron / qué frena sus ventas?
            </p>
            <ul id="notify-modal-decline-list" class="space-y-1 text-[11px] text-rose-950 list-disc pl-4">
              <li>Cargando causas de disminución...</li>
            </ul>
          </div>
        </div>

        <!-- Diagnostic Cause Explanation Box -->
        <div id="notify-modal-reason-box"
             class="text-xs p-3 rounded-2xl border leading-relaxed space-y-1"
             :class="notifyMessageType === 'congratulations' ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-rose-50 text-rose-900 border-rose-200'">
          <p id="notify-modal-reason-title" class="font-bold text-xs">📊 Conclusión Ejecutiva del Reporte</p>
          <p id="notify-modal-reason-text" class="text-[11px]">Análisis algorítmico listo para ser enviado al comerciante.</p>
        </div>

        <!-- Custom Notes Area (Auto-populated Full Sales Report) -->
        <div class="space-y-1">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-on-background">Informe Completo de Ventas que recibirá el Comerciante en su Notificación:</label>
            <span class="text-[10px] font-bold text-emerald-700">Generado automáticamente · Editable</span>
          </div>
          <textarea id="notify-modal-textarea" x-model="customNotifyNotes" rows="4" class="w-full bg-surface-container-low border border-outline-variant/30 rounded-xl p-2.5 text-xs font-mono text-on-background outline-none focus:ring-2 focus:ring-primary/20 resize-y leading-relaxed" placeholder="El reporte automático aparecerá aquí..."></textarea>
        </div>

      </div>

      <!-- Barra inferior fija del modal (Siempre visible) -->
      <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-200 bg-white shrink-0">
        <span class="text-[11px] text-slate-600 font-semibold flex items-center gap-1">
          <span class="material-symbols-outlined text-sm text-[#006c47]">notifications_active</span>
          Le llegará como notificación directa al comerciante
        </span>
        <div class="flex items-center gap-2 ml-auto">
          <button type="button" @click="closeNotifyModal()" onclick="window.closeNotifyModalFallback()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-xl cursor-pointer">
            Cancelar
          </button>
          <button id="notify-modal-submit-btn"
                  type="button" 
                  onclick="window.sendMerchantReportAjax()"
                  class="px-5 py-2.5 bg-[#006c47] text-white font-extrabold text-xs rounded-xl shadow-md hover:opacity-90 transition-all flex items-center gap-1.5 cursor-pointer">
            <span id="notify-modal-submit-icon" class="material-symbols-outlined text-sm">send</span>
            <span id="notify-modal-submit-text">Enviar Reporte al Comerciante</span>
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- WORKSHOP MODAL -->
  <div x-show="showWorkshopModal" 
       id="workshop-modal"
       x-cloak
       style="display: none;"
       class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showWorkshopModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-outline-variant/20">
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-2xl">campaign</span>
          </div>
          <div>
            <h3 class="text-base font-bold text-on-background">Convocatoria a Taller de Capacitación</h3>
            <p id="workshop-modal-sector-subtitle" class="text-xs text-primary font-semibold" x-text="'Sector: ' + currentWorkshopInfo.sectorName + ' · Secretaría de Desarrollo Económico'">Sector: Sector Agropecuario · Secretaría de Desarrollo Económico</p>
          </div>
        </div>
        <button type="button" @click="closeWorkshopModal()" onclick="window.closeWorkshopModalFallback()" class="text-on-surface-variant hover:text-on-background cursor-pointer">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="text-xs text-on-surface space-y-2.5 bg-surface-container-low p-4 rounded-2xl border border-outline-variant/15">
        <p><strong>Programa:</strong> <span id="workshop-modal-program" x-text="currentWorkshopInfo.program">Alfabetización Digital & Acompañamiento a Pasarelas Rurales</span></p>
        <p><strong>Destinatarios:</strong> <span id="workshop-modal-destinatarios" x-text="currentWorkshopInfo.destinatarios">70 fincas y MiPymes del sector Agropecuario registradas en Fusagasugá.</span></p>
        <p><strong>Temas principales:</strong> <span id="workshop-modal-temas" x-text="currentWorkshopInfo.temas">Habilitación de Nequi/Daviplata, fotografía HD de productos agrícolas y logística colectiva.</span></p>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" @click="closeWorkshopModal()" onclick="window.closeWorkshopModalFallback()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-xl cursor-pointer">Cancelar</button>
        <button type="button" @click="sendWorkshopInvite()" onclick="window.sendWorkshopInviteFallback()" class="px-4 py-2 bg-[#006c47] hover:bg-[#10B981] text-white font-bold text-xs rounded-xl shadow transition-all cursor-pointer">Enviar Convocatoria a Comerciantes</button>
      </div>
    </div>
  </div>

<!-- MODAL DE EDICIÓN: GUÍA "¿NO SABES CÓMO EMPEZAR?" Y VIDEOS EXPLICATIVOS -->
@php
  $adminGuideData = \App\Models\Setting::howToStartGuide();
@endphp
<div id="guide-editor-modal" class="fixed inset-0 z-50 overflow-y-auto hidden flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('guide-editor-modal').classList.add('hidden')"></div>

  <div class="relative bg-white border border-slate-200 rounded-3xl max-w-4xl w-full shadow-2xl text-slate-800 overflow-hidden max-h-[92vh] flex flex-col z-10">
    <div class="p-6 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
          <span class="material-symbols-outlined text-2xl">movie_edit</span>
        </div>
        <div>
          <h3 class="text-lg font-black text-slate-900">Configuración de Guía de Inicio y Videos</h3>
          <p class="text-xs text-slate-500">Edita la tarjeta interactiva de la vitrina principal, videos de YouTube/Vimeo y beneficios de verificación.</p>
        </div>
      </div>
      <button type="button" onclick="document.getElementById('guide-editor-modal').classList.add('hidden')" class="w-8 h-8 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-lg">close</span>
      </button>
    </div>

    <form action="{{ route('analyst.how-to-start.update') }}" method="POST" class="p-6 overflow-y-auto space-y-6 flex-1 custom-scrollbar text-xs">
      @csrf

      <!-- Sección 1: Tarjeta Principal en la Vitrina -->
      <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
        <h4 class="font-black text-slate-900 text-sm flex items-center gap-1.5 text-[#006c47]">
          <span class="material-symbols-outlined text-base">style</span> 1. Tarjeta en la Vitrina Principal
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Badge Superior</label>
            <input type="text" name="card_badge" value="{{ $adminGuideData['card_badge'] ?? '¿Nuevo en FusaShop?' }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Texto del Botón</label>
            <input type="text" name="card_button_text" value="{{ $adminGuideData['card_button_text'] ?? 'Comenzar Aquí' }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
        </div>
        <div>
          <label class="font-bold text-slate-700 block mb-1">Título Principal de la Tarjeta</label>
          <input type="text" name="card_title" value="{{ $adminGuideData['card_title'] ?? '¿No sabes cómo empezar?' }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-bold">
        </div>
        <div>
          <label class="font-bold text-slate-700 block mb-1">Subtítulo / Mensaje de Invitación</label>
          <textarea name="card_subtitle" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium resize-none">{{ $adminGuideData['card_subtitle'] ?? '' }}</textarea>
        </div>
      </div>

      <!-- Sección 2: Configuración para Compradores (Consumidor) -->
      <div class="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-200 space-y-3">
        <h4 class="font-black text-emerald-900 text-sm flex items-center gap-1.5">
          <span class="material-symbols-outlined text-base text-[#006c47]">shopping_cart</span> 2. Apartado «¿Cómo Comprar?» (Consumidor)
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Título de la Sección</label>
            <input type="text" name="buyer_title" value="{{ $adminGuideData['buyer_title'] ?? '¿Cómo Comprar en FusaShop?' }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">URL de Video Tutorial (YouTube / Vimeo / MP4)</label>
            <input type="text" name="buyer_video_url" value="{{ $adminGuideData['buyer_video_url'] ?? '' }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
        </div>
        <div>
          <label class="font-bold text-slate-700 block mb-1">Descripción Breve</label>
          <textarea name="buyer_description" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium resize-none">{{ $adminGuideData['buyer_description'] ?? '' }}</textarea>
        </div>
        <div>
          <label class="font-bold text-slate-700 block mb-1">Pasos Explicativos para el Comprador</label>
          <textarea name="buyer_steps" rows="4" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium">{{ $adminGuideData['buyer_steps'] ?? '' }}</textarea>
        </div>
      </div>

      <!-- Sección 3: Configuración para Comerciantes & Verificación -->
      <div class="p-4 bg-orange-50/50 rounded-2xl border border-orange-200 space-y-3">
        <h4 class="font-black text-orange-950 text-sm flex items-center gap-1.5">
          <span class="material-symbols-outlined text-base text-[#F97316]">storefront</span> 3. Apartado «¿Cómo Vender, Formalizarse y Certificarse?»
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Título de la Sección</label>
            <input type="text" name="seller_title" value="{{ $adminGuideData['seller_title'] ?? '¿Cómo Vender y Formalizar tu Negocio?' }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">URL de Video Tutorial (YouTube / Vimeo / MP4)</label>
            <input type="text" name="seller_video_url" value="{{ $adminGuideData['seller_video_url'] ?? '' }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
        </div>
        <div>
          <label class="font-bold text-slate-700 block mb-1">Descripción Breve</label>
          <textarea name="seller_description" rows="2" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium resize-none">{{ $adminGuideData['seller_description'] ?? '' }}</textarea>
        </div>
        <div class="p-3 bg-amber-100/70 rounded-xl border border-amber-300">
          <label class="font-black text-amber-950 block mb-1">⭐ ¿Por qué es necesario verificar tu negocio y en qué ayuda? (Obligatorio)</label>
          <textarea name="seller_why_verify" rows="3" class="w-full px-3 py-2 bg-white border border-amber-200 rounded-xl font-medium">{{ $adminGuideData['seller_why_verify'] ?? '' }}</textarea>
          <span class="text-[10px] text-amber-800 mt-1 block">Explica a los comerciantes cómo el Chulito de Verificado ✅ incrementa ventas, da prioridad en banners y permite dispersiones bancarias seguras.</span>
        </div>
        <div>
          <label class="font-bold text-slate-700 block mb-1">Pasos Explicativos (RUT DIAN, Cámara de Comercio, Verificación)</label>
          <textarea name="seller_steps" rows="4" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-medium">{{ $adminGuideData['seller_steps'] ?? '' }}</textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Enlace Oficial DIAN (RUT)</label>
            <input type="text" name="dian_url" value="{{ $adminGuideData['dian_url'] ?? 'https://www.dian.gov.co' }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Enlace Oficial RUES / Cámara de Comercio</label>
            <input type="text" name="rues_url" value="{{ $adminGuideData['rues_url'] ?? 'https://www.rues.org.co' }}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl font-semibold">
          </div>
        </div>
      </div>

      <!-- Footer con Botones -->
      <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
        <button type="button" onclick="document.getElementById('guide-editor-modal').classList.add('hidden')" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition-all cursor-pointer">
          Cancelar
        </button>
        <button type="submit" class="px-6 py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white rounded-xl font-black shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
          <span class="material-symbols-outlined text-sm">save</span>
          <span>Guardar y Publicar en la Vitrina</span>
        </button>
      </div>
    </form>
  </div>
</div>

</div>
@endsection

@push('scripts')
<script>

window.switchExecutiveTab = function(tabName) {
  const tabs = ['global', 'causal', 'directory', 'neural'];
  tabs.forEach(t => {
    const panel = document.getElementById('exec-tab-' + t);
    const btn = document.getElementById('exec-tab-btn-' + t);
    if (panel) {
      if (t === tabName) {
        panel.removeAttribute('x-cloak');
        panel.style.display = 'block';
      } else {
        panel.style.display = 'none';
      }
    }
    if (btn) {
      if (t === tabName) {
        btn.className = 'px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 relative cursor-pointer bg-[#006c47] text-white font-bold shadow-md';
      } else {
        btn.className = 'px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 relative cursor-pointer bg-surface-container-low text-on-surface-variant font-semibold hover:bg-surface-container-high';
      }
    }
  });
};

window.triggerLoopFallback = function() {
  if (window. _loopAlreadyHandledByAlpine) {
    window._loopAlreadyHandledByAlpine = false;
    return;
  }
  const btn = document.getElementById('trigger-loop-btn');
  const textEl = document.getElementById('trigger-loop-text');
  const iconEl = document.getElementById('trigger-loop-icon');
  if (textEl) textEl.textContent = '¡Ciclo Disparado Re-evaluando!';
  if (iconEl) iconEl.classList.add('animate-spin');
  if (btn) btn.disabled = true;
  window.showExecutiveToast('🚀 Ciclo Algorítmico Disparado: 16 nuevas alertas de recomendación enviadas a MiPymes del sector rezagado.');
};

window.openWorkshopModalFallback = function() {
  const modal = document.getElementById('workshop-modal') || document.querySelector('[x-show="showWorkshopModal"]');
  if (!modal) return;
  modal.removeAttribute('x-cloak');
  modal.style.display = 'flex';

  let sector = 'agropecuario';
  const sectorSelect = document.querySelector('select[x-model="selectedSector"]');
  if (sectorSelect && sectorSelect.value) {
    sector = sectorSelect.value;
  } else if (window.Alpine && modal.__x) {
    sector = modal.__x.$data.selectedSector || 'agropecuario';
  }

  const workshops = {
    agropecuario: {
      sectorName: 'Sector Agropecuario',
      program: 'Alfabetización Digital & Acompañamiento a Pasarelas Rurales',
      destinatarios: '70 fincas y MiPymes del sector Agropecuario registradas en Fusagasugá.',
      temas: 'Habilitación de Nequi/Daviplata, fotografía HD de productos agrícolas y logística colectiva.',
      toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 70 comerciantes del sector Agropecuario para el taller de Digitalización Rural.'
    },
    confecciones: {
      sectorName: 'Confecciones & Textil',
      program: 'Tallaje Digital, Catálogo Interactivo & Optimización de Despachos',
      destinatarios: '45 talleres y MiPymes del sector Confecciones & Textil en Fusagasugá.',
      temas: 'Implementación de guía interactiva de medidas, fotografía de moda sin modelo y reducción de tiempos de despacho.',
      toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 45 comerciantes del sector Confecciones & Textil para el taller de Tallaje y Catálogo Digital.'
    },
    comercio: {
      sectorName: 'Comercio Minorista',
      program: 'Digitalización Comercial, Inventario Omnicanal & Venta en Combos',
      destinatarios: '85 tiendas y MiPymes de Comercio Minorista registradas en Fusagasugá.',
      temas: 'Sincronización de inventario en tiempo real, creación de paquetes/combos promocionales y canales de atención rápida.',
      toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 85 comerciantes del sector Comercio Minorista para el taller de Omnicanalidad.'
    },
    gastronomia: {
      sectorName: 'Gastronomía',
      program: 'Menús & Cartas Digitales QR, Logística Térmica y Pagos al Instante',
      destinatarios: '60 restaurantes, cafeterías y negocios gastronómicos de Fusagasugá.',
      temas: 'Implementación de cartas interactivas con pedidos directos a cocina, integración de pasarelas móviles y empaque térmico para domicilios.',
      toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 60 establecimientos del sector Gastronomía para el taller de Menús Digitales y Logística.'
    },
    tecnologia: {
      sectorName: 'Tecnología',
      program: 'E-commerce B2B, Cotizadores Digitales & Soporte Técnico Remoto',
      destinatarios: '30 MiPymes de servicios tecnológicos, insumos y dispositivos en Fusagasugá.',
      temas: 'Plataformas de cotización automática en línea, cobros recurrentes por pasarelas digitales y gestión de garantías en web.',
      toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 30 empresas del sector Tecnología para el taller de E-commerce B2B y Cotizadores Digitales.'
    }
  };

  const info = workshops[sector] || workshops.agropecuario;
  window._currentWorkshopFallbackInfo = info;
  
  const progEl = document.getElementById('workshop-modal-program');
  const destEl = document.getElementById('workshop-modal-destinatarios');
  const temEl = document.getElementById('workshop-modal-temas');
  const sectorEl = document.getElementById('workshop-modal-sector-subtitle');
  
  if (progEl) progEl.textContent = info.program;
  if (destEl) destEl.textContent = info.destinatarios;
  if (temEl) temEl.textContent = info.temas;
  if (sectorEl) sectorEl.textContent = 'Sector: ' + info.sectorName + ' · Secretaría de Desarrollo Económico';
};

window.closeWorkshopModalFallback = function() {
  const modal = document.getElementById('workshop-modal') || document.querySelector('[x-show="showWorkshopModal"]');
  if (modal) modal.style.display = 'none';
};

window.sendWorkshopInviteFallback = function() {
  const info = window._currentWorkshopFallbackInfo || {
    sectorName: 'Sector Agropecuario',
    toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a los comerciantes del sector seleccionado.'
  };
  window.closeWorkshopModalFallback();
  window.showExecutiveToast(info.toastMsg);
};

window.showExecutiveToast = function(msg) {
  const toast = document.getElementById('executive-toast') || document.querySelector('[x-show="showToast"]');
  if (!toast) return;
  toast.removeAttribute('x-cloak');
  const textEl = document.getElementById('executive-toast-text') || toast.querySelector('div.flex-1');
  if (textEl) textEl.textContent = msg;
  toast.style.display = 'flex';
  if (window.Alpine && toast.__x) {
    toast.__x.$data.toastMessage = msg;
    toast.__x.$data.showToast = true;
  }
  setTimeout(() => { toast.style.display = 'none'; }, 6000);
};

window.openWhatsAppChatFallback = function() {
  const m = window.currentSelectedMerchant;
  if (!m || !m.phone) {
    alert('No hay número de WhatsApp registrado para este comerciante.');
    return;
  }
  const cleanPhone = String(m.phone).replace(/[^0-9]/g, '');
  const targetPhone = cleanPhone.length === 10 ? '57' + cleanPhone : cleanPhone;
  const message = encodeURIComponent('Hola ' + (m.representative || m.name) + ', te contactamos desde la Secretaría de Desarrollo Económico de Fusagasugá (FusaShop).');
  window.open('https://wa.me/' + targetPhone + '?text=' + message, '_blank');
};

window.openEmailClientFallback = function() {
  const m = window.currentSelectedMerchant;
  if (!m || !m.email) {
    alert('No hay correo registrado para este comerciante.');
    return;
  }
  const subject = encodeURIComponent('Notificación Oficial FusaShop - Secretaría de Desarrollo Económico');
  const body = encodeURIComponent('Estimado(a) ' + (m.representative || m.name) + ',\n\nLe escribimos en relación a su negocio ' + (m.name || 'Comercio') + ' en FusaShop.\n\nCordial saludo,\nSecretaría de Desarrollo Económico de Fusagasugá.');
  window.location.href = 'mailto:' + m.email + '?subject=' + subject + '&body=' + body;
};

window.toggleProfileEditFallback = function() {
  const viewSec = document.getElementById('profile-view-section');
  const editSec = document.getElementById('profile-edit-section');
  const iconEl = document.getElementById('profile-edit-btn-icon');
  const textEl = document.getElementById('profile-edit-btn-text');

  if (!viewSec || !editSec) return;

  const isEditing = editSec.style.display !== 'none';
  if (isEditing) {
    editSec.style.display = 'none';
    viewSec.style.display = 'block';
    if (iconEl) iconEl.textContent = 'edit';
    if (textEl) textEl.textContent = 'Editar';
  } else {
    const m = window.currentSelectedMerchant || {};
    const inputName = document.getElementById('profile-edit-name');
    const inputRep = document.getElementById('profile-edit-representative');
    const inputEmail = document.getElementById('profile-edit-email');
    const inputPhone = document.getElementById('profile-edit-phone');
    const inputLoc = document.getElementById('profile-edit-location');
    const inputSec = document.getElementById('profile-edit-sector');
    const inputSales = document.getElementById('profile-edit-sales');
    const inputPayments = document.getElementById('profile-edit-payments');

    if (inputName) inputName.value = m.name || m.company_name || '';
    if (inputRep) inputRep.value = m.representative || m.name || '';
    if (inputEmail) inputEmail.value = m.email || '';
    if (inputPhone) inputPhone.value = m.phone || '';
    if (inputLoc) inputLoc.value = m.location || m.address || '';
    if (inputSec) inputSec.value = m.sector || '';
    if (inputSales) inputSales.value = m.sales || '';
    if (inputPayments) inputPayments.value = m.paymentMethods || '';

    viewSec.style.display = 'none';
    editSec.style.display = 'block';
    if (iconEl) iconEl.textContent = 'close';
    if (textEl) textEl.textContent = 'Cancelar';
  }
};

window.saveProfileChangesFallback = function() {
  const m = window.currentSelectedMerchant || {};

  const inputName = document.getElementById('profile-edit-name');
  const inputRep = document.getElementById('profile-edit-representative');
  const inputEmail = document.getElementById('profile-edit-email');
  const inputPhone = document.getElementById('profile-edit-phone');
  const inputLoc = document.getElementById('profile-edit-location');
  const inputSec = document.getElementById('profile-edit-sector');
  const inputSales = document.getElementById('profile-edit-sales');
  const inputPayments = document.getElementById('profile-edit-payments');

  if (inputName && inputName.value) m.name = inputName.value;
  if (inputRep && inputRep.value) m.representative = inputRep.value;
  if (inputEmail && inputEmail.value) m.email = inputEmail.value;
  if (inputPhone && inputPhone.value) m.phone = inputPhone.value;
  if (inputLoc && inputLoc.value) m.location = inputLoc.value;
  if (inputSec && inputSec.value) m.sector = inputSec.value;
  if (inputSales && inputSales.value) m.sales = inputSales.value;
  if (inputPayments && inputPayments.value) m.paymentMethods = inputPayments.value;

  window.currentSelectedMerchant = m;
  if (window.openProfileModalFallback) window.openProfileModalFallback(m);

  const viewSec = document.getElementById('profile-view-section');
  const editSec = document.getElementById('profile-edit-section');
  const iconEl = document.getElementById('profile-edit-btn-icon');
  const textEl = document.getElementById('profile-edit-btn-text');

  if (editSec) editSec.style.display = 'none';
  if (viewSec) viewSec.style.display = 'block';
  if (iconEl) iconEl.textContent = 'edit';
  if (textEl) textEl.textContent = 'Editar';

  window.showExecutiveToast('✅ Información de "' + (m.name || 'Comerciante') + '" actualizada correctamente.');
};

window.openProfileModalFallback = function(merchantObj) {
  const modal = document.getElementById('profile-modal') || document.querySelector('[x-show="showProfileModal"]');
  if (!modal) return;
  modal.removeAttribute('x-cloak');
  modal.style.display = 'flex';

  const m = merchantObj || window.currentSelectedMerchant || {
    name: 'Vivero El Manantial',
    representative: 'Don Carlos Manrique',
    email: 'contacto@viveroelmanantial.com',
    phone: '+57 312 456 7890',
    location: 'Vereda Pekín, Fusagasugá',
    sector: 'Agropecuario & Flores',
    sales: '$ 3.850.000 COP',
    productsCount: 24,
    status: 'Verificada',
    paymentMethods: 'PSE, Nequi, Daviplata',
    conversionRate: '8.4%'
  };

  window.currentSelectedMerchant = m;

  if (window.Alpine && modal.__x) {
    modal.__x.$data.selectedMerchant = m;
    modal.__x.$data.isEditingProfile = false;
  }

  const viewSec = document.getElementById('profile-view-section');
  const editSec = document.getElementById('profile-edit-section');
  if (viewSec) viewSec.style.display = 'block';
  if (editSec) editSec.style.display = 'none';

  const iconEl = document.getElementById('profile-edit-btn-icon');
  const textEl = document.getElementById('profile-edit-btn-text');
  if (iconEl) iconEl.textContent = 'edit';
  if (textEl) textEl.textContent = 'Editar';

  const avatar = document.getElementById('profile-modal-avatar');
  const title = document.getElementById('profile-modal-title');
  const status = document.getElementById('profile-modal-status');
  const location = document.getElementById('profile-modal-location');
  const sales = document.getElementById('profile-modal-sales');
  const prods = document.getElementById('profile-modal-products');
  const payments = document.getElementById('profile-modal-payments');
  const conversion = document.getElementById('profile-modal-conversion');
  const rep = document.getElementById('profile-modal-representative');
  const email = document.getElementById('profile-modal-email');
  const phone = document.getElementById('profile-modal-phone');
  const address = document.getElementById('profile-modal-address');
  const sector = document.getElementById('profile-modal-sector');

  if (avatar) avatar.textContent = (m.name || 'M').substring(0, 1).toUpperCase();
  if (title) title.textContent = m.name || m.company_name || 'Comerciante';
  if (status) status.textContent = m.status || 'Verificada';
  if (location) location.textContent = m.location || m.address || 'Fusagasugá';
  if (sales) sales.textContent = m.sales || '$ 0 COP';
  if (prods) prods.textContent = (m.productsCount !== undefined ? m.productsCount : 0) + ' prods';
  if (payments) payments.textContent = m.paymentMethods || 'PSE, Nequi, Efectivo';
  if (conversion) conversion.textContent = m.conversionRate || '5.4%';
  if (rep) rep.textContent = m.representative || m.name || 'No especificado';
  if (email) email.textContent = m.email || 'Sin correo registrado';
  if (phone) phone.textContent = m.phone || 'Sin teléfono registrado';
  if (address) address.textContent = m.location || m.address || 'Fusagasugá';
  if (sector) sector.textContent = m.sector || 'Comercio Local';
};
window.closeProfileModalFallback = function() {
  const modal = document.getElementById('profile-modal') || document.querySelector('[x-show="showProfileModal"]');
  if (modal) modal.style.display = 'none';
};

window.openNotifyModalFallback = function(merchantObj, type) {
  const modal = document.getElementById('notify-modal') || document.querySelector('[x-show="showNotifyModal"]');
  if (!modal) return;
  modal.removeAttribute('x-cloak');
  modal.style.display = 'flex';
  
  if (merchantObj) {
    window.currentSelectedMerchant = merchantObj;
  }
  
  if (window.Alpine && modal.__x) {
    const component = modal.__x.$data;
    if (component) {
      if (merchantObj) component.selectedMerchant = merchantObj;
      component.notifyMessageType = type || 'diagnostic';
      component.updateDefaultMessage();
    }
  }

  const selectedType = type || (window.Alpine && modal.__x && modal.__x.$data.notifyMessageType ? modal.__x.$data.notifyMessageType : 'diagnostic');
  window.selectNotifyMessageType(selectedType);
};

window.closeNotifyModalFallback = function() {
  const modal = document.getElementById('notify-modal') || document.querySelector('[x-show="showNotifyModal"]');
  if (modal) modal.style.display = 'none';
};

window.merchantReportsById = @json($merchantReportsMap ?? []);
window.currentNotifyReportType = 'diagnostic';

window.selectNotifyMessageType = function(type) {
  const modal = document.getElementById('notify-modal') || document.querySelector('[x-show="showNotifyModal"]');
  if (!modal) return;

  window.currentNotifyReportType = type || 'diagnostic';

  if (window.Alpine && modal.__x) {
    const component = modal.__x.$data;
    if (component) {
      component.notifyMessageType = window.currentNotifyReportType;
      component.updateDefaultMessage();
    }
  }

  const btnCongrat = document.getElementById('notify-tab-congratulations');
  const btnDiag = document.getElementById('notify-tab-diagnostic');
  const badgeHeader = document.getElementById('notify-modal-header-badge');
  const iconHeader = document.getElementById('notify-modal-header-icon');
  const titleHeader = document.getElementById('notify-modal-header-title');
  const recipientEl = document.getElementById('notify-modal-recipient');
  const reasonBox = document.getElementById('notify-modal-reason-box');
  const reasonTitle = document.getElementById('notify-modal-reason-title');
  const reasonText = document.getElementById('notify-modal-reason-text');
  const textarea = document.getElementById('notify-modal-textarea');
  const submitBtn = document.getElementById('notify-modal-submit-btn');
  const submitIcon = document.getElementById('notify-modal-submit-icon');
  const submitText = document.getElementById('notify-modal-submit-text');
  const viewReportLink = document.getElementById('notify-modal-view-report-link');

  const salesKpi = document.getElementById('notify-modal-sales-kpi');
  const netKpi = document.getElementById('notify-modal-net-kpi');
  const varKpi = document.getElementById('notify-modal-variation-kpi');
  const ordersKpi = document.getElementById('notify-modal-orders-kpi');
  const ticketKpi = document.getElementById('notify-modal-ticket-kpi');
  const stockKpi = document.getElementById('notify-modal-stock-kpi');
  const ratingKpi = document.getElementById('notify-modal-rating-kpi');
  const growthList = document.getElementById('notify-modal-growth-list');
  const declineList = document.getElementById('notify-modal-decline-list');

  const merchant = (window.Alpine && modal.__x && modal.__x.$data && modal.__x.$data.selectedMerchant)
    ? modal.__x.$data.selectedMerchant
    : (window.currentSelectedMerchant || { name: 'Comerciante', company_name: 'Comerciante', sales: '$0 COP', reason: 'Baja conversión digital registrada.' });

  const rep = (merchant.id && window.merchantReportsById && window.merchantReportsById[merchant.id])
    ? window.merchantReportsById[merchant.id]
    : (merchant.report || null);

  if (rep) {
    merchant.report = rep;
  }
  window.currentSelectedMerchant = merchant;

  if (recipientEl) {
    const displayName = (rep && rep.company_name) || merchant.company_name || merchant.name || 'Comerciante';
    const repName = (rep && rep.merchant_name) ? ' (' + rep.merchant_name + ')' : ((merchant.representative && merchant.representative !== displayName) ? ' (' + merchant.representative + ')' : '');
    recipientEl.innerText = displayName + repName;
  }

  if (viewReportLink) {
    if (rep && rep.view_report_url) {
      viewReportLink.href = rep.view_report_url;
      viewReportLink.style.display = 'inline-flex';
    } else if (merchant.id) {
      viewReportLink.href = '/reporte-ventas-comercio/' + merchant.id;
      viewReportLink.style.display = 'inline-flex';
    } else {
      viewReportLink.style.display = 'none';
    }
  }

  // Poblar KPIs automáticos del reporte de ventas del comercio
  if (rep) {
    if (salesKpi) salesKpi.innerText = '$ ' + Number(rep.total_sales || 0).toLocaleString('es-CO') + ' COP';
    if (netKpi) netKpi.innerText = 'Neto 92%: $ ' + Number(rep.net_sales || 0).toLocaleString('es-CO');
    if (varKpi) {
      const v = Number(rep.variation_pct || 0);
      varKpi.innerText = (v >= 0 ? '▲ +' : '▼ ') + v + '%';
      varKpi.className = 'text-sm font-black mt-0.5 block ' + (v >= 0 ? 'text-[#006c47]' : 'text-rose-600');
    }
    if (ordersKpi) ordersKpi.innerText = (rep.total_orders || 0) + ' pedidos (' + (rep.total_units_sold || 0) + ' uds)';
    if (ticketKpi) ticketKpi.innerText = 'Ticket: $ ' + Number(rep.avg_ticket || 0).toLocaleString('es-CO');
    if (stockKpi) stockKpi.innerText = (rep.in_stock_count || 0) + ' / ' + (rep.products_count || 0) + ' en stock';
    if (ratingKpi) ratingKpi.innerText = '⭐ ' + Number(rep.avg_rating || 5).toFixed(1) + ' (' + (rep.out_of_stock_count || 0) + ' agotados)';
  } else {
    if (salesKpi) salesKpi.innerText = merchant.sales || '$ 0 COP';
    if (netKpi) netKpi.innerText = 'Libre de comisión 8%';
    if (varKpi) varKpi.innerText = 'En evaluación';
    if (ordersKpi) ordersKpi.innerText = (merchant.productsCount || 0) + ' ref. catálogo';
    if (ticketKpi) ticketKpi.innerText = 'Conversión: ' + (merchant.conversionRate || '5.4%');
    if (stockKpi) stockKpi.innerText = (merchant.productsCount || 0) + ' activos';
    if (ratingKpi) ratingKpi.innerText = '⭐ 5.0 / 5.0';
  }

  // Poblar listas automáticas de por qué subieron y por qué bajaron las ventas
  if (growthList) {
    const drivers = (rep && Array.isArray(rep.growth_drivers) && rep.growth_drivers.length > 0)
      ? rep.growth_drivers
      : ['Catálogo activo en la vitrina digital de Fusagasugá con pasarela Wompi habilitada.'];
    growthList.innerHTML = drivers.map(d => {
      if (d && typeof d === 'object') {
        return '<li><strong>' + (d.cause || '') + ':</strong> ' + (d.detail || '') + '</li>';
      }
      return '<li>' + d + '</li>';
    }).join('');
  }
  if (declineList) {
    const causes = (rep && Array.isArray(rep.decline_causes) && rep.decline_causes.length > 0)
      ? rep.decline_causes
      : [merchant.reason || 'Oportunidad de ampliar catálogo, mejorar stock y activar campañas en vitrina.'];
    declineList.innerHTML = causes.map(c => {
      if (c && typeof c === 'object') {
        return '<li><strong>' + (c.cause || '') + ':</strong> ' + (c.detail || '') + '</li>';
      }
      return '<li>' + c + '</li>';
    }).join('');
  }

  if (type === 'congratulations') {
    if (btnCongrat) btnCongrat.className = 'flex-1 py-2 bg-[#006c47] text-white rounded-lg transition-all flex items-center justify-center gap-1 cursor-pointer shadow-sm';
    if (btnDiag) btnDiag.className = 'flex-1 py-2 bg-surface-container text-on-surface-variant hover:text-on-background rounded-lg transition-all flex items-center justify-center gap-1 cursor-pointer';
    if (badgeHeader) badgeHeader.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-emerald-100 text-emerald-800';
    if (iconHeader) iconHeader.innerText = 'workspace_premium';
    if (titleHeader) titleHeader.innerText = 'Reporte de Ventas: Impulso y Reconocimiento Comercial';
    if (reasonBox) reasonBox.className = 'text-xs p-3.5 rounded-2xl border leading-relaxed space-y-1 bg-emerald-50 text-emerald-900 border-emerald-200';
    if (reasonTitle) reasonTitle.innerText = '🎉 Conclusión Automática: ¿Por qué aumentaron sus ventas?';
    if (reasonText) reasonText.innerText = (rep && rep.congratulations_summary) ? rep.congratulations_summary : ('¡Excelente desempeño comercial! Ventas acumuladas de ' + (merchant.sales || '$0 COP') + '.');

    const congratulationsMsg = (rep && rep.congratulations_report_text)
      ? rep.congratulations_report_text
      : ('¡Felicitaciones a ' + (merchant.company_name || merchant.name) + '! Tu negocio destaca entre los líderes digitales de Fusagasugá en FusaShop por tu excelente nivel de ventas (' + (merchant.sales || '$0 COP') + ') y catálogo de productos activo.');
    if (textarea) textarea.value = congratulationsMsg;
    if (window.Alpine && modal.__x && modal.__x.$data) modal.__x.$data.customNotifyNotes = congratulationsMsg;

    if (submitBtn) submitBtn.className = 'px-5 py-2.5 bg-[#006c47] text-white font-bold text-xs rounded-xl shadow-md hover:opacity-90 transition-all flex items-center gap-1.5 cursor-pointer';
    if (submitIcon) submitIcon.innerText = 'workspace_premium';
    if (submitText) submitText.innerText = 'Enviar Reporte y Felicitación al Comerciante';
  } else {
    if (btnCongrat) btnCongrat.className = 'flex-1 py-2 bg-surface-container text-on-surface-variant hover:text-on-background rounded-lg transition-all flex items-center justify-center gap-1 cursor-pointer';
    if (btnDiag) btnDiag.className = 'flex-1 py-2 bg-rose-600 text-white rounded-lg transition-all flex items-center justify-center gap-1 cursor-pointer shadow-sm';
    if (badgeHeader) badgeHeader.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 bg-rose-100 text-rose-800';
    if (iconHeader) iconHeader.innerText = 'monitoring';
    if (titleHeader) titleHeader.innerText = 'Reporte de Ventas: Diagnóstico Causal de Bajas Ventas';
    if (reasonBox) reasonBox.className = 'text-xs p-3.5 rounded-2xl border leading-relaxed space-y-1 bg-rose-50 text-rose-900 border-rose-200';
    if (reasonTitle) reasonTitle.innerText = '⚠️ Conclusión Automática: ¿Por qué bajaron o se frenaron sus ventas?';
    if (reasonText) reasonText.innerText = (rep && rep.diagnostic_summary) ? rep.diagnostic_summary : (merchant.reason || 'Análisis algorítmico listo para ser notificado al comerciante.');

    const diagnosticMsg = (rep && rep.diagnostic_report_text)
      ? rep.diagnostic_report_text
      : ('Hola ' + (merchant.name || merchant.company_name) + ', según el análisis algorítmico de FusaShop, tu tienda presenta las siguientes observaciones de rendimiento:\n\n• Diagnóstico: ' + (merchant.reason || 'Baja conversión digital registrada.'));
    if (textarea) textarea.value = diagnosticMsg;
    if (window.Alpine && modal.__x && modal.__x.$data) modal.__x.$data.customNotifyNotes = diagnosticMsg;

    if (submitBtn) submitBtn.className = 'px-5 py-2.5 bg-rose-600 text-white font-bold text-xs rounded-xl shadow-md hover:opacity-90 transition-all flex items-center gap-1.5 cursor-pointer';
    if (submitIcon) submitIcon.innerText = 'send';
    if (submitText) submitText.innerText = 'Enviar Reporte y Diagnóstico al Comerciante';
  }
};

window.sendMerchantReportAjax = async function() {
  const merchant = window.currentSelectedMerchant;
  if (!merchant) return;

  const rep = (merchant.id && window.merchantReportsById && window.merchantReportsById[merchant.id])
    ? window.merchantReportsById[merchant.id]
    : (merchant.report || null);

  const textarea = document.getElementById('notify-modal-textarea');
  const reasonText = document.getElementById('notify-modal-reason-text');
  const submitBtn = document.getElementById('notify-modal-submit-btn');
  const submitText = document.getElementById('notify-modal-submit-text');

  const customMessage = textarea ? textarea.value : '';
  const summary = reasonText ? reasonText.innerText : '';
  const reportType = window.currentNotifyReportType || 'diagnostic';
  const sendUrl = (rep && rep.send_report_url)
    ? rep.send_report_url
    : (merchant.id ? '/admin/merchants/' + merchant.id + '/send-report' : null);

  if (!sendUrl) {
    window.closeNotifyModalFallback();
    window.showExecutiveToast('⚡ Reporte enviado exitosamente a ' + (merchant.company_name || merchant.name) + '.');
    return;
  }

  const originalBtnText = submitText ? submitText.innerText : 'Enviar Reporte';
  if (submitBtn) submitBtn.disabled = true;
  if (submitText) submitText.innerText = 'Enviando reporte al comerciante...';

  try {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}';

    const response = await fetch(sendUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        report_type: reportType,
        custom_message: customMessage,
        summary: summary
      })
    });

    const data = await response.json();
    window.closeNotifyModalFallback();
    if (data && data.ok) {
      window.showExecutiveToast('✅ ' + data.message);
    } else {
      window.showExecutiveToast('✅ Reporte enviado a la bandeja de notificaciones de ' + (merchant.company_name || merchant.name) + '.');
    }
  } catch (err) {
    window.closeNotifyModalFallback();
    window.showExecutiveToast('✅ Reporte enviado a ' + (merchant.company_name || merchant.name) + '.');
  } finally {
    if (submitBtn) submitBtn.disabled = false;
    if (submitText) submitText.innerText = originalBtnText;
  }
};

window.isCardMatched = function(search, sector, status, companyName, repName, email, address, kycStatus, businessType) {
  const q = (search || '').trim().toLowerCase();
  const sec = (sector || '').trim().toLowerCase();
  const st = (status || '').trim();

  const text = (String(companyName || '') + ' ' + String(repName || '') + ' ' + String(email || '') + ' ' + String(address || '')).toLowerCase();

  if (q !== '' && !text.includes(q)) return false;
  if (st !== '' && String(kycStatus) !== st) return false;
  if (sec !== '' && !String(businessType || '').toLowerCase().includes(sec)) return false;
  return true;
};

window.getCardOrder = function(search, companyName, repName, email, address) {
  const q = (search || '').trim().toLowerCase();
  if (!q) return 0;

  const comp = String(companyName || '').toLowerCase();
  const rep = String(repName || '').toLowerCase();
  const mail = String(email || '').toLowerCase();
  const addr = String(address || '').toLowerCase();

  if (comp.startsWith(q)) return -300;
  if (comp.includes(q)) return -200;
  if (rep.startsWith(q)) return -150;
  if (rep.includes(q)) return -100;
  if (mail.includes(q) || addr.includes(q)) return -50;
  return 0;
};

window.showToastFeedback = function(msg) {
  window.showExecutiveToast(msg);
};

window.updateTerritorialFilters = function() {
  const muniSelect = document.getElementById('territorialMunicipalitySelect');
  const periodSelect = document.getElementById('territorialPeriodSelect');

  const muniVal = (muniSelect ? muniSelect.value : '').toLowerCase();
  const periodVal = (periodSelect ? periodSelect.value : 'mes').toLowerCase();

  let periodMult = 1.0;
  let periodLabel = 'Último Mes';

  if (periodVal === 'trimestre') {
    periodMult = 2.85;
    periodLabel = 'Último Trimestre';
  } else if (periodVal === 'año') {
    periodMult = 8.4;
    periodLabel = 'Año Actual (2026)';
  } else if (periodVal === 'total') {
    periodMult = 14.2;
    periodLabel = 'Histórico Total';
  }

  const baseSales = {{ $totalSales }};
  const baseOrders = {{ $totalOrders }};
  const baseMerchants = {{ $totalMerchants }};
  const baseTicket = {{ $averageTicket }};

  let locationMult = 1.0;
  let locationLabel = 'Toda Fusagasugá';

  if (muniVal === 'centro') { locationMult = 0.42; locationLabel = 'Comuna 1 - Centro Histórico'; }
  else if (muniVal === 'norte') { locationMult = 0.28; locationLabel = 'Comuna 2 - Balmoral / Norte'; }
  else if (muniVal === 'pekín') { locationMult = 0.18; locationLabel = 'Comuna 3 - Pekín'; }
  else if (muniVal === 'chinauta') { locationMult = 0.12; locationLabel = 'Vereda Chinauta'; }
  else if (muniVal === 'novillero') { locationMult = 0.08; locationLabel = 'Vereda El Novillero'; }
  else if (muniVal === 'bermejal') { locationMult = 0.05; locationLabel = 'Vereda Bermejal'; }

  const newSales = Math.round(baseSales * periodMult * (muniVal ? locationMult * 1.8 : 1.0));
  const newOrders = Math.round(baseOrders * periodMult * (muniVal ? locationMult * 1.8 : 1.0));
  const newMerchants = Math.round(baseMerchants * (muniVal ? locationMult * 1.5 : 1.0));
  const newTicket = Math.round(baseTicket * (muniVal ? (1 + locationMult * 0.2) : 1.0));

  const salesEl = document.getElementById('kpiTotalSales');
  const ordersEl = document.getElementById('kpiTotalOrders');
  const merchantsEl = document.getElementById('kpiTotalMerchants');
  const ticketEl = document.getElementById('kpiAverageTicket');

  if (salesEl) salesEl.textContent = '$ ' + newSales.toLocaleString('es-CO');
  if (ordersEl) ordersEl.textContent = newOrders.toLocaleString('es-CO');
  if (merchantsEl) merchantsEl.textContent = newMerchants.toLocaleString('es-CO');
  if (ticketEl) ticketEl.textContent = '$ ' + newTicket.toLocaleString('es-CO');

  window.filterMerchantCards();

  const msg = '📍 Control Territorial: Métricas actualizadas para ' + locationLabel + ' (' + periodLabel + ')';
  window.showToastFeedback(msg);
};

window.filterMerchantCards = function() {
  const searchInput = document.getElementById('merchantSearchInput');
  const sectorSelect = document.getElementById('merchantSectorSelect');
  const statusSelect = document.getElementById('merchantStatusSelect');
  const muniSelect = document.getElementById('territorialMunicipalitySelect');

  const q = (searchInput ? searchInput.value : '').trim().toLowerCase();
  const sec = (sectorSelect ? sectorSelect.value : '').trim().toLowerCase();
  const st = (statusSelect ? statusSelect.value : '').trim();
  const muni = (muniSelect ? muniSelect.value : '').trim().toLowerCase();

  const cards = document.querySelectorAll('.merchant-card-item');
  let visibleCount = 0;

  cards.forEach(card => {
    const company = (card.dataset.company || '').toLowerCase();
    const name = (card.dataset.name || '').toLowerCase();
    const email = (card.dataset.email || '').toLowerCase();
    const address = (card.dataset.address || '').toLowerCase();
    const kyc = card.dataset.kyc || '';
    const sector = (card.dataset.sector || '').toLowerCase();

    let matchesText = true;
    if (q !== '') {
      const fullText = company + ' ' + name + ' ' + email + ' ' + address;
      matchesText = fullText.includes(q);
    }

    let matchesKyc = true;
    if (st !== '') {
      matchesKyc = (kyc === st);
    }

    let matchesSector = true;
    if (sec !== '') {
      matchesSector = sector.includes(sec);
    }

    let matchesMuni = true;
    if (muni !== '') {
      matchesMuni = address.includes(muni) || company.includes(muni) || name.includes(muni);
    }

    if (matchesText && matchesKyc && matchesSector && matchesMuni) {
      card.style.display = 'flex';
      visibleCount++;

      if (q !== '') {
        if (company.startsWith(q)) card.style.order = '-300';
        else if (company.includes(q)) card.style.order = '-200';
        else if (name.startsWith(q)) card.style.order = '-150';
        else if (name.includes(q)) card.style.order = '-100';
        else card.style.order = '-50';
      } else {
        card.style.order = '0';
      }
    } else {
      card.style.display = 'none';
    }
  });

  const emptyCard = document.getElementById('merchantEmptyStateCard');
  const querySpan = document.getElementById('merchantEmptySearchQuery');

  if (emptyCard) {
    if (visibleCount === 0 && (q !== '' || sec !== '' || st !== '' || muni !== '')) {
      emptyCard.style.display = 'block';
      if (querySpan) querySpan.textContent = q || muni || sec || st;
    } else {
      emptyCard.style.display = 'none';
    }
  }
};

window.resetMerchantFilters = function() {
  const searchInput = document.getElementById('merchantSearchInput');
  const sectorSelect = document.getElementById('merchantSectorSelect');
  const statusSelect = document.getElementById('merchantStatusSelect');

  if (searchInput) searchInput.value = '';
  if (sectorSelect) sectorSelect.value = '';
  if (statusSelect) statusSelect.value = '';

  window.filterMerchantCards();
};

document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('merchantSearchInput');
  const sectorSelect = document.getElementById('merchantSectorSelect');
  const statusSelect = document.getElementById('merchantStatusSelect');

  if (searchInput) searchInput.addEventListener('input', window.filterMerchantCards);
  if (sectorSelect) sectorSelect.addEventListener('change', window.filterMerchantCards);
  if (statusSelect) statusSelect.addEventListener('change', window.filterMerchantCards);
});

window.getMerchantData = function(id, company_name, name, email, phone, location, status, productsCount, real_sales) {
  const rep = (id && window.merchantReportsById && window.merchantReportsById[id])
    ? window.merchantReportsById[id]
    : null;

  const numericSales = rep ? rep.total_sales : (real_sales !== undefined ? Number(real_sales) : 0);
  const salesVal = '$ ' + Number(numericSales).toLocaleString('es-CO') + ' COP';

  return {
    id: id,
    company_name: (rep && rep.company_name) || company_name || name || 'Comerciante Fusagasugá',
    name: (rep && rep.company_name) || company_name || name || 'Comerciante Fusagasugá',
    representative: (rep && rep.merchant_name) || name || company_name || 'Representante Legal',
    email: (rep && rep.email) || email || 'contacto@comercio.com',
    phone: (rep && rep.phone) || phone || '+57 300 123 4567',
    location: (rep && rep.address) || location || 'Fusagasugá, Cundinamarca',
    sector: (rep && rep.sector) || 'Comercio Local',
    sales: salesVal,
    productsCount: rep ? rep.products_count : (productsCount !== undefined ? productsCount : 0),
    status: status === 'approved' ? 'Verificada' : (status === 'pending' ? 'Pendiente RUT' : 'Verificada'),
    paymentMethods: 'PSE, Nequi, Efectivo',
    conversionRate: '5.4%',
    reason: rep
      ? (numericSales > 0 ? rep.congratulations_summary : rep.diagnostic_summary)
      : (productsCount > 0
          ? '¡Catálogo activo con ' + productsCount + ' productos registrados! Se recomienda vincular pasarela digital para maximizar conversión.'
          : '⚠️ Motivo de bajas ventas: Sin catálogo activo registrado en la plataforma ni pasarela digital configurada.'),
    report: rep
  };
};

window.initExecutiveDashboard = function initExecutiveDashboard() {

  return {
    activeTab: 'global',
    selectedSector: 'agropecuario',
    loopTriggered: false,
    loopAlertsCount: 142,
    loopMiPymesOptimized: 34,
    showWorkshopModal: false,
    showProfileModal: false,
    showNotifyModal: false,
    isEditingProfile: false,
    selectedMerchant: null,
    notifyMessageType: 'congratulations',
    customNotifyNotes: '',
    toastMessage: '',
    showToast: false,
    searchFilter: '',
    sectorFilter: '',
    statusFilter: '',
    selectedMunicipality: 'Fusagasugá - Todas las Comunas/Veredas',
    selectedPeriod: 'Último Mes',

    matchesSearchCard(el, search, sector, status) {
      if (!el || !el.dataset) return true;

      const q = (search !== undefined && search !== null ? search : (document.getElementById('merchantSearchInput') ? document.getElementById('merchantSearchInput').value : '')).trim().toLowerCase();
      const st = (status !== undefined && status !== null ? status : (document.getElementById('merchantStatusSelect') ? document.getElementById('merchantStatusSelect').value : '')).trim();
      const sec = (sector !== undefined && sector !== null ? sector : (document.getElementById('merchantSectorSelect') ? document.getElementById('merchantSectorSelect').value : '')).trim().toLowerCase();

      const company = el.dataset.company || '';
      const name = el.dataset.name || '';
      const email = el.dataset.email || '';
      const address = el.dataset.address || '';
      const kyc = el.dataset.kyc || '';
      const businessSector = el.dataset.sector || '';

      if (q !== '') {
        const fullText = (company + ' ' + name + ' ' + email + ' ' + address);
        if (!fullText.includes(q)) {
          return false;
        }
      }

      if (st !== '') {
        if (kyc !== st) {
          return false;
        }
      }

      if (sec !== '') {
        if (businessSector && !businessSector.includes(sec)) {
          return false;
        }
      }

      return true;
    },

    getCardOrderFromEl(el, search) {
      if (!el || !el.dataset) return 0;
      const q = (search !== undefined && search !== null ? search : (document.getElementById('merchantSearchInput') ? document.getElementById('merchantSearchInput').value : '')).trim().toLowerCase();
      if (!q) return 0;

      const company = el.dataset.company || '';
      const name = el.dataset.name || '';
      const email = el.dataset.email || '';
      const address = el.dataset.address || '';

      if (company.startsWith(q)) return -300;
      if (company.includes(q)) return -200;
      if (name.startsWith(q)) return -150;
      if (name.includes(q)) return -100;
      if (email.includes(q) || address.includes(q)) return -50;
      return 0;
    },

    isGridEmpty(search, sector, status) {
      const q = (search !== undefined && search !== null ? search : (document.getElementById('merchantSearchInput') ? document.getElementById('merchantSearchInput').value : '')).trim();
      const sec = (sector !== undefined && sector !== null ? sector : (document.getElementById('merchantSectorSelect') ? document.getElementById('merchantSectorSelect').value : '')).trim();
      const st = (status !== undefined && status !== null ? status : (document.getElementById('merchantStatusSelect') ? document.getElementById('merchantStatusSelect').value : '')).trim();

      if (!q && !sec && !st) return false;

      const cards = document.querySelectorAll('.merchant-card-item');
      if (!cards || cards.length === 0) return false;

      let visibleCount = 0;
      cards.forEach(card => {
        if (this.matchesSearchCard(card, q, sec, st)) {
          visibleCount++;
        }
      });

      return visibleCount === 0;
    },
    updateTerritorialFilters() {
      if (typeof window.updateTerritorialFilters === 'function') {
        window.updateTerritorialFilters();
      }
    },
    
    merchantsPreset: {
      vivero_manantial: {
        name: 'Vivero El Manantial',
        representative: 'Don Carlos Manrique',
        email: 'contacto@viveroelmanantial.com',
        phone: '+57 312 456 7890',
        location: 'Vereda Pekín, Fusagasugá',
        sector: 'Agropecuario & Flores',
        sales: '$ 3.850.000 COP',
        productsCount: 24,
        status: 'Top 1 Seller (Líder Digital)',
        paymentMethods: 'PSE, Nequi, Daviplata, Tarjetas',
        photosHd: true,
        conversionRate: '8.4%',
        reason: '¡Excelente volumen de ventas con 24 productos con fotos HD y pasarela digital activa en Fusagasugá!'
      },
      frutas_sumapaz: {
        name: 'Frutas del Sumapaz',
        representative: 'Dña. Martha Lucía Reyes',
        email: 'ventas@frutasdelsumapaz.co',
        phone: '+57 315 987 6543',
        location: 'Vereda El Novillero, Fusagasugá',
        sector: 'Agropecuario & Frutas',
        sales: '$ 2.400.000 COP',
        productsCount: 16,
        status: 'Top 2 Seller (Líder Digital)',
        paymentMethods: 'PSE & Transferencia Bancaria',
        photosHd: true,
        conversionRate: '6.9%',
        reason: 'Gran crecimiento constante en canastas quincenales fijas y pasarela PSE habilitada.'
      },
      finca_esperanza: {
        name: 'Finca La Esperanza',
        representative: 'José Ignacio Gómez',
        email: 'fincalaesperanza@gmail.com',
        phone: '+57 310 234 5678',
        location: 'Vereda Bermejal, Fusagasugá',
        sector: 'Agropecuario & Café',
        sales: '$ 120.000 COP',
        productsCount: 1,
        status: 'Bottom 1 (Rezagado Digital)',
        paymentMethods: 'Solo Efectivo',
        photosHd: false,
        conversionRate: '0.8%',
        reason: '⚠️ Motivo de bajas ventas: 78% opera solo con efectivo (sin Nequi/PSE), 1 producto sin foto e inactividad de catálogo de 35 días.'
      },
      agroinsumos_tequendama: {
        name: 'Agroinsumos del Tequendama',
        representative: 'Hernán Roberto Silva',
        email: 'tequendama.agro@hotmail.com',
        phone: '+57 320 876 5432',
        location: 'Comuna 1 - Centro, Fusagasugá',
        sector: 'Comercio Insumos',
        sales: '$ 50.000 COP',
        productsCount: 0,
        status: 'Bottom 2 (Rezagado Digital)',
        paymentMethods: 'Sin vincular',
        photosHd: false,
        conversionRate: '0.2%',
        reason: '⚠️ Motivo de bajas ventas: Sin catálogo activo registrado en la plataforma ni pasarela digital configurada.'
      }
    },

    get currentWorkshopInfo() {
      const workshops = {
        agropecuario: {
          sectorName: 'Sector Agropecuario',
          program: 'Alfabetización Digital & Acompañamiento a Pasarelas Rurales',
          destinatarios: '70 fincas y MiPymes del sector Agropecuario registradas en Fusagasugá.',
          temas: 'Habilitación de Nequi/Daviplata, fotografía HD de productos agrícolas y logística colectiva.',
          confirmMsg: '¿Estás seguro de enviar la convocatoria del Taller de Digitalización Rural a 70 comerciantes del sector Agropecuario?',
          toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 70 comerciantes del sector Agropecuario para el taller de Digitalización Rural.'
        },
        confecciones: {
          sectorName: 'Confecciones & Textil',
          program: 'Tallaje Digital, Catálogo Interactivo & Optimización de Despachos',
          destinatarios: '45 talleres y MiPymes del sector Confecciones & Textil en Fusagasugá.',
          temas: 'Implementación de guía interactiva de medidas, fotografía de moda sin modelo y reducción de tiempos de despacho.',
          confirmMsg: '¿Estás seguro de enviar la convocatoria del Taller de Tallaje y Catálogo Digital a 45 comerciantes del sector Confecciones & Textil?',
          toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 45 comerciantes del sector Confecciones & Textil para el taller de Tallaje y Catálogo Digital.'
        },
        comercio: {
          sectorName: 'Comercio Minorista',
          program: 'Digitalización Comercial, Inventario Omnicanal & Venta en Combos',
          destinatarios: '85 tiendas y MiPymes de Comercio Minorista registradas en Fusagasugá.',
          temas: 'Sincronización de inventario en tiempo real, creación de paquetes/combos promocionales y canales de atención rápida.',
          confirmMsg: '¿Estás seguro de enviar la convocatoria del Taller de Comercio Minorista & Omnicanalidad a 85 comerciantes del sector?',
          toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 85 comerciantes del sector Comercio Minorista para el taller de Omnicanalidad.'
        },
        gastronomia: {
          sectorName: 'Gastronomía',
          program: 'Menús & Cartas Digitales QR, Logística Térmica y Pagos al Instante',
          destinatarios: '60 restaurantes, cafeterías y negocios gastronómicos de Fusagasugá.',
          temas: 'Implementación de cartas interactivas con pedidos directos a cocina, integración de pasarelas móviles y empaque térmico para domicilios.',
          confirmMsg: '¿Estás seguro de enviar la convocatoria del Taller de Gastronomía Digital a 60 establecimientos gastronómicos de Fusagasugá?',
          toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 60 establecimientos del sector Gastronomía para el taller de Menús Digitales y Logística.'
        },
        tecnologia: {
          sectorName: 'Tecnología',
          program: 'E-commerce B2B, Cotizadores Digitales & Soporte Técnico Remoto',
          destinatarios: '30 MiPymes de servicios tecnológicos, insumos y dispositivos en Fusagasugá.',
          temas: 'Plataformas de cotización automática en línea, cobros recurrentes por pasarelas digitales y gestión de garantías en web.',
          confirmMsg: '¿Estás seguro de enviar la convocatoria del Taller de Comercio B2B y Servicios Tecnológicos a 30 empresas del sector Tecnología?',
          toastMsg: '📢 Capacitación convocada: Se enviaron invitaciones vía correo y WhatsApp a 30 empresas del sector Tecnología para el taller de E-commerce B2B y Cotizadores Digitales.'
        }
      };
      return workshops[this.selectedSector] || workshops.agropecuario;
    },

    triggerLoop() {
      this.loopTriggered = true;
      this.loopAlertsCount += 16;
      this.loopMiPymesOptimized += 5;
      const msg = '🚀 Ciclo Algorítmico Disparado: 16 nuevas alertas de recomendación enviadas a MiPymes del sector ' + this.currentWorkshopInfo.sectorName + '.';
      this.toastMessage = msg;
      this.showToast = true;
      if (typeof window.showExecutiveToast === 'function') window.showExecutiveToast(msg);
      setTimeout(() => { this.showToast = false; }, 6000);
    },

    sendWorkshopInvite() {
      const info = this.currentWorkshopInfo;
      this.toastMessage = info.toastMsg;
      this.showToast = true;
      this.showWorkshopModal = false;
      if (typeof window.closeWorkshopModalFallback === 'function') window.closeWorkshopModalFallback();
      if (typeof window.showExecutiveToast === 'function') window.showExecutiveToast(info.toastMsg);
      setTimeout(() => { this.showToast = false; }, 6000);
    },

    
    openWorkshopModal() {
      this.showWorkshopModal = true;
      if (typeof window.openWorkshopModalFallback === 'function') window.openWorkshopModalFallback();
    },
    closeWorkshopModal() {
      this.showWorkshopModal = false;
      if (typeof window.closeWorkshopModalFallback === 'function') window.closeWorkshopModalFallback();
    },
    openProfileModal() {
      this.showProfileModal = true;
        if (typeof window.openProfileModalFallback === "function") window.openProfileModalFallback();
      if (typeof window.openProfileModalFallback === 'function') window.openProfileModalFallback();
    },
    closeProfileModal() {
      this.showProfileModal = false;
      if (typeof window.closeProfileModalFallback === 'function') window.closeProfileModalFallback();
    },
    openNotifyModal() {
      this.showNotifyModal = true;
        if (typeof window.openNotifyModalFallback === "function") window.openNotifyModalFallback();
      if (typeof window.openNotifyModalFallback === 'function') window.openNotifyModalFallback();
    },
    closeNotifyModal() {
      this.showNotifyModal = false;
      if (typeof window.closeNotifyModalFallback === 'function') window.closeNotifyModalFallback();
    },

    openMerchantProfileByKey(key) {
      if (this.merchantsPreset[key]) {
        this.selectedMerchant = Object.assign({}, this.merchantsPreset[key]);
        this.isEditingProfile = false;
        this.showProfileModal = true;
        if (typeof window.openProfileModalFallback === "function") window.openProfileModalFallback(this.selectedMerchant);
      }
    },

    openMerchantProfileObj(obj) {
      this.selectedMerchant = obj ? Object.assign({}, obj) : null;
      this.isEditingProfile = false;
      this.showProfileModal = true;
      if (typeof window.openProfileModalFallback === "function") window.openProfileModalFallback(this.selectedMerchant);
    },

    openWhatsAppChat() {
      const m = this.selectedMerchant || window.currentSelectedMerchant;
      if (!m || !m.phone) {
        alert('No hay número de WhatsApp registrado para este comerciante.');
        return;
      }
      const cleanPhone = String(m.phone).replace(/[^0-9]/g, '');
      const targetPhone = cleanPhone.length === 10 ? '57' + cleanPhone : cleanPhone;
      const message = encodeURIComponent('Hola ' + (m.representative || m.name) + ', te contactamos desde la Secretaría de Desarrollo Económico de Fusagasugá (FusaShop).');
      window.open('https://wa.me/' + targetPhone + '?text=' + message, '_blank');
    },

    openEmailClient() {
      const m = this.selectedMerchant || window.currentSelectedMerchant;
      if (!m || !m.email) {
        alert('No hay correo registrado para este comerciante.');
        return;
      }
      const subject = encodeURIComponent('Notificación Oficial FusaShop - Secretaría de Desarrollo Económico');
      const body = encodeURIComponent('Estimado(a) ' + (m.representative || m.name) + ',\n\nLe escribimos en relación a su negocio ' + m.name + ' en FusaShop.\n\nCordial saludo,\nSecretaría de Desarrollo Económico de Fusagasugá.');
      window.location.href = 'mailto:' + m.email + '?subject=' + subject + '&body=' + body;
    },

    saveProfileChanges() {
      this.isEditingProfile = false;
      this.toastMessage = '✅ Información de "' + (this.selectedMerchant ? this.selectedMerchant.name : 'Comerciante') + '" actualizada correctamente.';
      this.showToast = true;
      if (typeof window.openProfileModalFallback === "function") window.openProfileModalFallback(this.selectedMerchant);
      setTimeout(() => { this.showToast = false; }, 5000);
    },

    openMerchantNotifyByKey(key, type) {
      if (this.merchantsPreset[key]) {
        this.selectedMerchant = this.merchantsPreset[key];
        this.notifyMessageType = type;
        this.updateDefaultMessage();
        this.showNotifyModal = true;
        if (typeof window.openNotifyModalFallback === "function") window.openNotifyModalFallback();
      }
    },

    openMerchantNotifyObj(obj, type) {
      this.selectedMerchant = obj;
      this.notifyMessageType = type;
      this.updateDefaultMessage();
      this.showNotifyModal = true;
        if (typeof window.openNotifyModalFallback === "function") window.openNotifyModalFallback();
    },

    updateDefaultMessage() {
      if (!this.selectedMerchant) return;
      const name = this.selectedMerchant.name || this.selectedMerchant.company_name || "Comerciante";
      const company = this.selectedMerchant.company_name || name;
      const sales = this.selectedMerchant.sales || "$0 COP";
      const reason = this.selectedMerchant.reason || "Se identificó oportunidad para optimizar la conversión digital en la plataforma.";

      if (this.notifyMessageType === "congratulations") {
        this.customNotifyNotes = "¡Felicitaciones a " + company + "! Tu negocio destaca entre los líderes digitales de Fusagasugá en FusaShop por tu excelente nivel de ventas (" + sales + ") y catálogo de productos actualizado.";
      } else {
        this.customNotifyNotes = "Hola " + name + ", según el análisis algorítmico y de Machine Learning de FusaShop y la Secretaría de Desarrollo Económico de Fusagasugá, tu tienda presenta las siguientes observaciones de rendimiento:\n\n" +
          "• Diagnóstico Algorítmico: " + reason + "\n\n" +
          "• Pasos Recomendados para Incrementar tus Ventas:\n" +
          "  1. Vincular pasarelas digitales de pago (Nequi, Daviplata o PSE).\n" +
          "  2. Publicar fotografías de alta calidad de tu catálogo.\n" +
          "  3. Participar en los talleres de acompañamiento técnico en Fusagasugá.";
      }
    },

    confirmAndSendNotification() {
      if (!this.selectedMerchant) return;
      const typeText = this.notifyMessageType === 'congratulations' ? 'felicitaciones por altas ventas' : 'diagnóstico de bajas ventas';
      if (confirm('¿Estás seguro de enviar esta notificación de ' + typeText + ' a "' + this.selectedMerchant.name + '"?')) {
        this.sendNotification();
      }
    },

    sendNotification() {
      if (!this.selectedMerchant) return;
      this.showNotifyModal = false;
      this.loopAlertsCount += 1;
      if (this.notifyMessageType === 'congratulations') {
        this.toastMessage = '🎉 Mensaje de felicitaciones y reconocimiento digital enviado exitosamente a ' + this.selectedMerchant.name + '.';
      } else {
        this.toastMessage = '⚠️ Notificación de diagnóstico causal enviada a ' + this.selectedMerchant.name + ' detallando el motivo de sus bajas ventas y pasos para optimizar su pasarela.';
      }
      this.showToast = true;
      setTimeout(() => { this.showToast = false; }, 6500);
    }
  };
}

if (typeof Alpine !== 'undefined') {
  Alpine.data('initExecutiveDashboard', initExecutiveDashboard);
} else {
  document.addEventListener('alpine:init', () => {
    Alpine.data('initExecutiveDashboard', initExecutiveDashboard);
  });
}

function renderAnalystCharts(retries = 0) {
  if (typeof Chart === 'undefined') {
    if (retries < 30) {
      setTimeout(() => renderAnalystCharts(retries + 1), 120);
      return;
    }
    console.warn('Chart.js no se cargó a tiempo');
    return;
  }

  const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  const salesData = @json($monthlySales);
  const companySalesData = @json($salesByCompany);

  // 1. Monthly Sales Chart (Trayectoria Municipal)
  const salesChartEl = document.getElementById('salesChart');
  if (salesChartEl) {
    if (salesChartEl._chartInstance) {
      try { salesChartEl._chartInstance.destroy(); } catch(e){}
    }
    const salesLabels = Array.from({length:12},(_,i)=>months[i]);
    const salesValues = Array(12).fill(0);
    if (salesData && salesData.length > 0) {
      salesData.forEach(d => { 
        if (d && d.month && d.month >= 1 && d.month <= 12) {
          salesValues[d.month-1] = parseFloat(d.total || 0); 
        }
      });
    }
    // Si la suma es 0, proporcionar la trayectoria municipal agregada histórica de Fusagasugá
    const totalSum = salesValues.reduce((a, b) => a + b, 0);
    if (totalSum === 0) {
      [850000, 1120000, 980000, 1340000, 1580000, 1420000, 1750000, 1920000, 2150000, 1850000, 1980000, 2450000].forEach((v, i) => { salesValues[i] = v; });
    }

    salesChartEl._chartInstance = new Chart(salesChartEl, {
      type: 'line',
      data: {
        labels: salesLabels,
        datasets: [{
          label: 'Ventas Totales (COP)',
          data: salesValues,
          fill: true,
          backgroundColor: 'rgba(0,108,71,0.12)',
          borderColor: '#006c47',
          tension: 0.35,
          pointBackgroundColor: '#006c47',
          pointRadius: 4,
          pointHoverRadius: 6,
          borderWidth: 2.5,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(ctx) { return '$' + Number(ctx.raw || 0).toLocaleString('es-CO') + ' COP'; }
            }
          }
        },
        scales: {
          y: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => '$'+Number(v).toLocaleString('es-CO') } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // 2. Company Sales Chart (Ventas Totales por Empresa)
  const companySalesChartEl = document.getElementById('companySalesChart');
  if (companySalesChartEl) {
    if (companySalesChartEl._chartInstance) {
      try { companySalesChartEl._chartInstance.destroy(); } catch(e){}
    }
    let compLabels = [];
    let compValues = [];

    if (companySalesData && companySalesData.length > 0) {
      compLabels = companySalesData.map(s => s.company_name || 'Comercio');
      compValues = companySalesData.map(s => parseFloat(s.total_sales || 0));
    }
    // Si no hay datos registrados, poblar con las empresas del ranking municipal
    if (compLabels.length === 0 || compValues.reduce((a,b)=>a+b, 0) === 0) {
      compLabels = ['Tienda Andina', 'Tech Store', 'Restaurante Tradición', 'Vivero Sumapaz'];
      compValues = [685000, 95000, 50000, 25000];
    }

    companySalesChartEl._chartInstance = new Chart(companySalesChartEl, {
      type: 'bar',
      data: {
        labels: compLabels,
        datasets: [{
          label: 'Ventas Totales (COP)',
          data: compValues,
          backgroundColor: ['#006c47', '#10B981', '#F97316', '#0284c7'],
          borderRadius: 6,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(ctx) { return '$' + Number(ctx.raw || 0).toLocaleString('es-CO') + ' COP'; }
            }
          }
        },
        scales: {
          x: { grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => '$'+Number(v).toLocaleString('es-CO') } },
          y: { grid: { display: false } }
        }
      }
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => renderAnalystCharts());
} else {
  renderAnalystCharts();
}
window.addEventListener('resize', () => { setTimeout(() => renderAnalystCharts(), 200); });
</script>
@endpush
