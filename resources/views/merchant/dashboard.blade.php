@extends('layouts.app')
@section('title', 'Merchant Center | Tablero Operativo')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ courseModalOpen: false, activeCourse: null }">

  <!-- Header & Store Status Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-surface-container-lowest p-6 rounded-3xl shadow-card border border-outline-variant/20">
    <div class="flex items-center gap-4">
      <div class="w-16 h-16 rounded-2xl bg-primary/10 border-2 border-primary/20 shrink-0 flex items-center justify-center shadow-inner overflow-hidden">
        @if(auth()->user()->companyProfile && auth()->user()->companyProfile->logo_path)
          <img src="{{ Storage::url(auth()->user()->companyProfile->logo_path) }}" alt="{{ auth()->user()->companyProfile->company_name }}" class="w-full h-full object-cover">
        @else
          <span class="material-symbols-outlined text-3xl text-primary">storefront</span>
        @endif
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-2xl sm:text-3xl font-['Manrope'] font-black text-on-background">
            {{ auth()->user()->companyProfile->company_name ?? auth()->user()->name }}
          </h1>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/30 text-[#006c47] border border-[#6efcb9] uppercase tracking-wider">
            Comerciante Verificado KYC
          </span>
        </div>
        <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">
          Panel de control comercial, inventario en tiempo real y logística automatizada Envia.com
        </p>
      </div>
    </div>

    <!-- Quick Operations Actions -->
    <div class="flex flex-wrap items-center gap-2.5">
      <a href="{{ route('merchant.sales-report.show', auth()->id()) }}" class="px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-[#006c47] font-extrabold text-xs rounded-xl border border-emerald-200 flex items-center gap-1.5 transition-all">
        <span class="material-symbols-outlined text-[18px]">monitoring</span> Mi Reporte de Ventas
      </a>
      <a href="{{ route('consumer.merchant.profile', auth()->id()) }}" target="_blank" class="px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30 flex items-center gap-1.5 transition-all">
        <span class="material-symbols-outlined text-[18px]">visibility</span> Ver Vitrina Pública
      </a>
      <a href="{{ route('merchant.inventory') }}" class="px-4 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30 flex items-center gap-1.5 transition-all">
        <span class="material-symbols-outlined text-[18px]">warehouse</span> Stock Rápido
      </a>
      <a href="{{ route('merchant.products.create') }}" class="px-5 py-2.5 bg-primary hover:opacity-95 text-white font-bold text-xs rounded-xl shadow-md shadow-primary/20 flex items-center gap-1.5 transition-all">
        <span class="material-symbols-outlined text-[18px]">add_circle</span> Nuevo Producto
      </a>
    </div>
  </div>

  @php
    $latestSalesReportNotif = auth()->user()->notifications
      ->first(fn($n) => ($n->data['type'] ?? '') === 'merchant_sales_report');
  @endphp
  @if($latestSalesReportNotif)
    <div class="bg-emerald-50/90 text-slate-900 border border-emerald-300 p-5 rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#006c47] text-white flex items-center justify-center shrink-0 mt-0.5">
          <span class="material-symbols-outlined text-xl">monitoring</span>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="font-black text-sm text-[#006c47]">{{ $latestSalesReportNotif->data['title'] ?? '📊 Reporte de Ventas y Diagnóstico Causal Recibido' }}</h3>
            @if(is_null($latestSalesReportNotif->read_at))
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-200 text-emerald-900 uppercase">Nuevo</span>
            @endif
          </div>
          <p class="text-xs text-slate-700 mt-1">{{ $latestSalesReportNotif->data['message'] ?? 'La Administración de FusaShop te ha enviado el reporte detallado de tus ventas y diagnóstico causal.' }}</p>
        </div>
      </div>
      <a href="{{ route('merchant.sales-report.show', ['user' => auth()->id(), 'notification_id' => $latestSalesReportNotif->id]) }}" class="px-4 py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white font-extrabold text-xs rounded-xl shrink-0 shadow transition-all flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm">open_in_new</span> Abrir Reporte Completo
      </a>
    </div>
  @endif

  <!-- Flash Inventory Alert (if out of stock) -->
  @if($outOfStock > 0)
    <div class="bg-red-50 text-red-900 border border-red-200 p-5 rounded-3xl flex items-center justify-between gap-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">warning</span>
        </div>
        <div>
          <h3 class="font-bold text-sm">Alerta de Stock Crítico</h3>
          <p class="text-xs text-red-700 mt-0.5">Tienes <strong>{{ $outOfStock }}</strong> producto(s) totalmente agotado(s) y {{ $lowStock }} con stock menor a 5 unidades. Repón inventario para no perder ventas locales.</p>
        </div>
      </div>
      <a href="{{ route('merchant.inventory') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shrink-0 shadow transition-all">
        Ajustar Stock
      </a>
    </div>
  @endif

  <!-- ======================================================== -->
  <!-- 1. BARRA DE INDICADORES CLAVE (KPIS GERENCIALES)         -->
  <!-- ======================================================== -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
    
    <!-- KPI 1: Ventas Brutas del Mes -->
    <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-3xl shadow-card border border-outline-variant/20 flex flex-col justify-between">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Ventas del Mes</span>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
          <span class="material-symbols-outlined text-[20px]">payments</span>
        </div>
      </div>
      <div>
        <p class="text-2xl sm:text-3xl font-['Manrope'] font-black text-on-background">
          ${{ number_format($monthlySales, 0, ',', '.') }}
        </p>
        <div class="flex items-center justify-between mt-1 text-[11px] text-on-surface-variant">
          <span>Total acumulado:</span>
          <span class="font-bold text-on-surface">${{ number_format($totalSales, 0, ',', '.') }} COP</span>
        </div>
      </div>
    </div>

    <!-- KPI 2: Pedidos Pendientes de Despacho -->
    <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-3xl shadow-card border border-outline-variant/20 flex flex-col justify-between">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Despachos Pendientes</span>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
          <span class="material-symbols-outlined text-[20px]">pending_actions</span>
        </div>
      </div>
      <div>
        <p class="text-2xl sm:text-3xl font-['Manrope'] font-black {{ $pendingOrders > 0 ? 'text-amber-600' : 'text-on-background' }}">
          {{ $pendingOrders }}
        </p>
        <div class="flex items-center justify-between mt-1 text-[11px] text-on-surface-variant">
          <span>Órdenes procesadas:</span>
          <span class="font-bold text-on-surface">{{ $totalOrdersCount }}</span>
        </div>
      </div>
    </div>

    <!-- KPI 3: Tasa de Cumplimiento & Reputación -->
    <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-3xl shadow-card border border-outline-variant/20 flex flex-col justify-between">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Cumplimiento / Rating</span>
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
          <span class="material-symbols-outlined text-[20px]">verified</span>
        </div>
      </div>
      <div>
        <div class="flex items-baseline gap-2">
          <p class="text-2xl sm:text-3xl font-['Manrope'] font-black text-on-background">{{ $fulfillmentRate }}%</p>
          <span class="text-xs font-black text-amber-500 flex items-center gap-0.5">
            ★ {{ $avgRating }} <span class="text-[10px] text-on-surface-variant font-normal">({{ $totalReviews }})</span>
          </span>
        </div>
        <p class="text-[11px] text-on-surface-variant mt-1">Entregas efectivas sin devolución</p>
      </div>
    </div>

    <!-- KPI 4: Ingresos Netos y Saldo Retirable -->
    <div class="bg-surface-container-lowest p-5 sm:p-6 rounded-3xl shadow-card border border-outline-variant/20 flex flex-col justify-between">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Saldo Listo a Liquidar</span>
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
          <span class="material-symbols-outlined text-[20px]">account_balance</span>
        </div>
      </div>
      <div>
        <p class="text-2xl sm:text-3xl font-['Manrope'] font-black text-purple-900">
          ${{ number_format($readyToWithdraw, 0, ',', '.') }}
        </p>
        <div class="flex items-center justify-between mt-1 text-[11px] text-on-surface-variant">
          <span>Neto (92% post comisión):</span>
          <a href="{{ route('merchant.finances') }}" class="font-bold text-primary hover:underline">Ver balance</a>
        </div>
      </div>
    </div>

  </div>

  <!-- ======================================================== -->
  <!-- 2. SECCIÓN "DIAGNÓSTICO & FEEDBACK IA"                   -->
  <!-- ======================================================== -->
  <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 rounded-3xl shadow-xl relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
      <div class="space-y-3 max-w-3xl">
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 uppercase tracking-widest flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">auto_awesome</span> Motor Neuronal FusaBot & Analítica
          </span>
          <span class="text-xs text-indigo-300">Diagnóstico en tiempo real</span>
        </div>

        <h2 class="text-xl sm:text-2xl font-['Manrope'] font-black tracking-tight text-white">
          Sugerencias de Optimización para tu Negocio
        </h2>

        <p class="text-xs sm:text-sm text-indigo-100/90 leading-relaxed">
          {{ $aiDiagnostics['price_recommendation'] }}
        </p>

        @if($aiDiagnostics['stock_warning'])
          <div class="p-3 bg-rose-500/20 border border-rose-500/30 rounded-xl text-xs text-rose-200 flex items-center gap-2 font-medium">
            <span class="material-symbols-outlined text-sm shrink-0">info</span>
            <span>{{ $aiDiagnostics['stock_warning'] }}</span>
          </div>
        @endif
      </div>

      <div class="shrink-0 flex flex-col sm:flex-row lg:flex-col gap-2.5">
        <a href="{{ route('merchant.inventory') }}" class="px-5 py-3 bg-white text-slate-900 hover:bg-slate-100 font-bold text-xs rounded-xl shadow-lg transition-all flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-sm">tune</span> Ajustar Precios y Stock
        </a>
        <a href="{{ route('merchant.finances') }}" class="px-5 py-3 bg-indigo-800/60 hover:bg-indigo-800 text-white font-bold text-xs rounded-xl border border-indigo-400/20 transition-all flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-sm">insights</span> Ver Métricas Detalladas
        </a>
      </div>
    </div>
  </div>

  <!-- ======================================================== -->
  <!-- 3. MÓDULO DE ÓRDENES Y LOGÍSTICA (ENVIA.COM 1-CLIC)      -->
  <!-- ======================================================== -->
  <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-card border border-outline-variant/20 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/20">
      <div>
        <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
          <span class="material-symbols-outlined text-blue-600">local_shipping</span> Logística y Despacho Automatizado Envia.com
        </h2>
        <p class="text-xs text-on-surface-variant mt-0.5">
          Generación en 1-clic de guías de despacho oficiales, impresión térmica y actualización automática de estado
        </p>
      </div>

      <a href="{{ route('merchant.orders') }}" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-bold text-xs rounded-xl border border-outline-variant/30 flex items-center gap-1.5 transition-all self-start sm:self-auto">
        <span class="material-symbols-outlined text-sm">list_alt</span> Ver Todos los Pedidos
      </a>
    </div>

    @if($recentOrders->isEmpty())
      <div class="text-center py-12 bg-surface-container-low rounded-2xl border-2 border-dashed border-outline-variant/30">
        <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2">receipt_long</span>
        <p class="text-sm font-bold text-on-surface">No tienes pedidos pendientes</p>
        <p class="text-xs text-on-surface-variant mt-1">Los nuevos pedidos aprobados por Wompi aparecerán aquí con botón de despacho inmediato.</p>
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
              <tr class="hover:bg-surface-container-low transition-colors">
                <!-- Orden ID & Fecha -->
                <td class="py-4 px-4 font-mono font-bold text-on-surface">
                  #{{ $order->id }}
                  <span class="block text-[10px] text-on-surface-variant font-normal">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </td>

                <!-- Cliente -->
                <td class="py-4 px-4">
                  <p class="font-bold text-on-surface">{{ $order->user->name ?? 'Cliente' }}</p>
                  <span class="text-[11px] text-on-surface-variant">{{ $order->user->phone ?? 'Sin teléfono' }}</span>
                </td>

                <!-- Productos -->
                <td class="py-4 px-4">
                  <div class="space-y-1 max-w-[200px]">
                    @foreach($order->items as $item)
                      <div class="truncate text-[11px]">
                        <span class="font-bold text-on-surface">x{{ $item->quantity }}</span> {{ $item->product->name ?? 'Producto' }}
                      </div>
                    @endforeach
                  </div>
                </td>

                <!-- Total -->
                <td class="py-4 px-4 font-black text-on-background">
                  ${{ number_format($order->total, 0, ',', '.') }} COP
                </td>

                <!-- Estado del Pedido -->
                <td class="py-4 px-4">
                  @php
                    $badgeColors = [
                      'pending' => 'bg-amber-100 text-amber-800',
                      'processing' => 'bg-blue-100 text-blue-800',
                      'shipped' => 'bg-indigo-100 text-indigo-800',
                      'delivered' => 'bg-emerald-100 text-emerald-800',
                      'cancelled' => 'bg-red-100 text-red-800',
                    ];
                  @endphp
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $badgeColors[$order->status] ?? 'bg-surface-container text-on-surface' }}">
                    {{ $order->stageLabel() }}
                  </span>
                </td>

                <!-- Guía Envia.com -->
                <td class="py-4 px-4 font-mono text-[11px]">
                  @if($order->tracking_number)
                    <div class="space-y-1">
                      <span class="font-bold text-primary block">{{ $order->tracking_number }}</span>
                      <span class="text-[10px] text-on-surface-variant uppercase">{{ $order->shipping_carrier ?? 'Envia' }}</span>
                    </div>
                  @else
                    <span class="text-on-surface-variant/70 italic text-[11px]">Sin guía asignada</span>
                  @endif
                </td>

                <!-- Acciones Logísticas en 1-clic -->
                <td class="py-4 px-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    @if(!$order->tracking_number && $order->payment_status === 'paid')
                      <!-- Botón 1-Clic Generar Guía Envia.com -->
                      <form method="POST" action="{{ route('merchant.orders.generate-label', $order->id) }}">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5" title="Generar Guía Oficial con Envia.com">
                          <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                          <span>1-Clic Guía</span>
                        </button>
                      </form>
                    @endif

                    @if($order->label_url)
                      <!-- Impresión de Etiqueta Térmica -->
                      <a href="{{ $order->label_url }}" target="_blank" class="px-3 py-2 bg-surface-container hover:bg-surface-container-high border border-outline-variant/30 text-on-surface font-bold text-xs rounded-xl transition-all flex items-center gap-1" title="Imprimir Etiqueta Térmica">
                        <span class="material-symbols-outlined text-[16px] text-primary">print</span>
                        <span>Etiqueta</span>
                      </a>
                    @endif

                    <!-- Selector Rápido de Estado -->
                    <form method="POST" action="{{ route('merchant.orders.update', $order->id) }}" class="inline-block">
                      @csrf
                      @method('PATCH')
                      @if($order->status === 'processing' && $order->tracking_number)
                        <input type="hidden" name="status" value="shipped">
                        <button type="submit" class="p-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl font-bold text-xs transition-all" title="Marcar como Enviado a Transportadora">
                          <span class="material-symbols-outlined text-sm">send</span>
                        </button>
                      @elseif($order->status === 'shipped')
                        <input type="hidden" name="status" value="delivered">
                        <button type="submit" class="p-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-xl font-bold text-xs transition-all" title="Marcar como Entregado al Cliente">
                          <span class="material-symbols-outlined text-sm">check_circle</span>
                        </button>
                      @endif
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <!-- ======================================================== -->
  <!-- 4. ACADEMIA DE CAPACITACIÓN (TALLERES DEL ADMIN)         -->
  <!-- ======================================================== -->
  <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-card border border-outline-variant/20 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/20">
      <div>
        <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">school</span> Academia FusaShop para Comerciantes
        </h2>
        <p class="text-xs text-on-surface-variant mt-0.5">
          Capacitación oficial promovida por el Administrador y la Red de Desarrollo Económico del Sumapaz
        </p>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20 self-start sm:self-auto">
        3 Talleres Activos
      </span>
    </div>

    <!-- Grid de Cursos -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      @foreach($academyCourses as $course)
        <div class="bg-surface-container-low rounded-2xl overflow-hidden border border-outline-variant/20 flex flex-col justify-between group hover:border-primary/40 transition-all">
          <div>
            <div class="relative h-40 overflow-hidden bg-slate-900">
              <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 opacity-90">
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
              
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-white/90 text-slate-900 backdrop-blur-sm">
                {{ $course['category'] }}
              </span>

              <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-white text-xs">
                <span class="flex items-center gap-1 font-medium">
                  <span class="material-symbols-outlined text-xs">schedule</span> {{ $course['duration'] }}
                </span>
                @if($course['completed'])
                  <span class="px-2 py-0.5 rounded-md bg-emerald-500/80 text-white font-bold text-[10px] flex items-center gap-1">
                    <span class="material-symbols-outlined text-[12px]">check</span> Completado
                  </span>
                @else
                  <span class="text-[11px] text-amber-300 font-bold">{{ $course['progress'] }}% completado</span>
                @endif
              </div>
            </div>

            <div class="p-5 space-y-2">
              <h3 class="font-['Manrope'] font-bold text-sm text-on-background line-clamp-2">
                {{ $course['title'] }}
              </h3>
              <p class="text-xs text-on-surface-variant flex items-center gap-1">
                <span class="material-symbols-outlined text-xs text-primary">person</span>
                {{ $course['instructor'] }}
              </p>
            </div>
          </div>

          <div class="p-5 pt-0">
            <!-- Progress Bar -->
            <div class="w-full h-1.5 bg-surface-container-highest rounded-full overflow-hidden mb-4">
              <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: {{ $course['progress'] }}%"></div>
            </div>

            <button type="button" 
                    @click="activeCourse = {{ json_encode($course) }}; courseModalOpen = true;"
                    class="w-full py-2.5 bg-primary/10 hover:bg-primary text-primary hover:text-white font-bold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5">
              <span class="material-symbols-outlined text-sm">play_circle</span>
              <span>{{ $course['completed'] ? 'Repasar Taller' : 'Continuar Taller' }}</span>
            </button>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <!-- MODAL REPRODUCTOR DE TALLERES ACADEMIA -->
  <div x-show="courseModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" @click.self="courseModalOpen = false">
    <div class="bg-surface-container-lowest rounded-3xl max-w-3xl w-full shadow-2xl border border-outline-variant/20 overflow-hidden" @click.stop>
      <div class="p-5 flex items-center justify-between border-b border-outline-variant/20">
        <div>
          <span class="text-[10px] font-black uppercase tracking-wider text-primary" x-text="activeCourse ? activeCourse.category : ''"></span>
          <h3 class="font-['Manrope'] font-black text-base text-on-background" x-text="activeCourse ? activeCourse.title : ''"></h3>
        </div>
        <button type="button" @click="courseModalOpen = false" class="p-1.5 rounded-full hover:bg-surface-container text-on-surface-variant">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="aspect-video bg-black flex items-center justify-center">
        <template x-if="activeCourse">
          <div class="w-full h-full flex flex-col items-center justify-center text-white space-y-3 p-6 text-center">
            <span class="material-symbols-outlined text-6xl text-primary animate-pulse">smart_display</span>
            <p class="font-bold text-lg" x-text="activeCourse.title"></p>
            <p class="text-xs text-white/70 max-w-md">Módulo de capacitación interactivo para comerciantes de Fusagasugá. Material avalado por el Analista de Datos.</p>
          </div>
        </template>
      </div>

      <div class="p-5 bg-surface-container-low flex items-center justify-between">
        <span class="text-xs text-on-surface-variant" x-text="activeCourse ? 'Instructor: ' + activeCourse.instructor : ''"></span>
        <button type="button" @click="courseModalOpen = false" class="px-5 py-2 bg-primary text-white font-bold text-xs rounded-xl shadow-sm hover:opacity-95 transition-all">
          Cerrar Sesión
        </button>
      </div>
    </div>
  </div>

</div>
@endsection
