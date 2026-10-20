@extends('layouts.app')
@section('title', 'Gestión de Pedidos & Logística Envia.com | FusaShop')

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6" x-data="{
  trackingModalOpen: false,
  trackingLoading: false,
  trackingData: null,
  activeOrderId: null,
  async openTracking(url, orderId) {
    this.activeOrderId = orderId;
    this.trackingModalOpen = true;
    this.trackingLoading = true;
    this.trackingData = null;
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();
      this.trackingData = data;
    } catch (e) {
      this.trackingData = { message: 'No se pudo conectar con el servicio de rastreo de Envia.com en este momento.' };
    } finally {
      this.trackingLoading = false;
    }
  }
}">

  <!-- HEADER -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-on-background">Logística & Despachos de Pedidos</h1>
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200 uppercase tracking-wider flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">sync</span> API Envia.com Activa
        </span>
      </div>
      <p class="text-on-surface-variant text-xs md:text-sm mt-1">
        Monitoreo automatizado de envíos. Las guías se emiten y actualizan automáticamente mediante el API oficial de Envia.com.
      </p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('merchant.dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm">dashboard</span> Volver al Tablero
      </a>
    </div>
  </div>

  <!-- FILTROS DE ESTADO Y CONTADOR DE CANCELACIONES -->
  @php
    $counts = $statusCounts ?? [
      'all' => $orders->total(),
      'pending' => 0,
      'processing' => 0,
      'shipped' => 0,
      'delivered' => 0,
      'cancelled' => 0,
    ];
    $currentStatus = $statusFilter ?? request('status', '');
    $filterTabs = [
      '' => ['label' => 'Todos', 'count' => $counts['all'] ?? 0, 'icon' => 'list_alt', 'activeClass' => 'bg-slate-900 text-white border-slate-900'],
      'pending' => ['label' => 'Pendientes', 'count' => $counts['pending'] ?? 0, 'icon' => 'schedule', 'activeClass' => 'bg-amber-600 text-white border-amber-600'],
      'processing' => ['label' => 'En Preparación', 'count' => $counts['processing'] ?? 0, 'icon' => 'inventory_2', 'activeClass' => 'bg-blue-600 text-white border-blue-600'],
      'shipped' => ['label' => 'En Tránsito', 'count' => $counts['shipped'] ?? 0, 'icon' => 'local_shipping', 'activeClass' => 'bg-indigo-600 text-white border-indigo-600'],
      'delivered' => ['label' => 'Entregados', 'count' => $counts['delivered'] ?? 0, 'icon' => 'task_alt', 'activeClass' => 'bg-emerald-600 text-white border-emerald-600'],
      'cancelled' => ['label' => 'Cancelados', 'count' => $counts['cancelled'] ?? 0, 'icon' => 'cancel', 'activeClass' => 'bg-rose-600 text-white border-rose-600'],
    ];
  @endphp

  <div class="flex flex-wrap items-center gap-2 bg-white p-3 rounded-2xl shadow-[0_8px_24px_rgba(27,28,28,.04)] border border-outline-variant/20">
    @foreach($filterTabs as $tabKey => $tab)
      @php
        $isActive = ($currentStatus === $tabKey);
        $url = $tabKey === '' ? route('merchant.orders') : route('merchant.orders', ['status' => $tabKey]);
      @endphp
      <a href="{{ $url }}"
         class="px-3.5 py-2 rounded-xl text-xs font-extrabold border transition-all flex items-center gap-1.5 {{ $isActive ? $tab['activeClass'] : ($tabKey === 'cancelled' && $tab['count'] > 0 ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100') }}">
        <span class="material-symbols-outlined text-sm">{{ $tab['icon'] }}</span>
        <span>{{ $tab['label'] }}</span>
        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $isActive ? 'bg-white/20 text-white' : ($tabKey === 'cancelled' && $tab['count'] > 0 ? 'bg-rose-200 text-rose-900' : 'bg-slate-200 text-slate-700') }}">
          {{ $tab['count'] }}
        </span>
      </a>
    @endforeach
  </div>

  @if($orders->isEmpty())
    <div class="text-center py-20 bg-white rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20">
      <span class="material-symbols-outlined text-slate-300 text-7xl mb-3 block">local_shipping</span>
      <h3 class="text-lg font-bold text-slate-800">No hay pedidos en esta categoría</h3>
      <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Cuando un cliente compre o actualice el estado de un pedido en tu vitrina, aparecerá reflejado aquí.</p>
    </div>
  @else
    <div class="space-y-5">
      @foreach($orders as $order)
        @php
          $statusBadges = [
            'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'label' => 'Esperando Pago'],
            'processing' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Guía Generada / En Preparación'],
            'shipped' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'label' => 'En Tránsito con Transportadora'],
            'delivered' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Entregado al Cliente'],
            'cancelled' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-800', 'label' => 'Cancelado por el Comprador'],
          ];
          $badge = $statusBadges[$order->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'label' => ucfirst($order->status)];
        @endphp

        <div class="bg-white rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border {{ $order->status === 'cancelled' ? 'border-rose-300 bg-rose-50/10' : 'border-outline-variant/20' }} space-y-4">
          <!-- Encabezado de la Tarjeta del Pedido -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-outline-variant/15">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono font-extrabold text-slate-900 text-sm">Pedido #{{ $order->id }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $badge['bg'] }} {{ $badge['text'] }}">
                  {{ $badge['label'] }}
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ $order->created_at->format('d M Y - h:i A') }} · Cliente: <strong>{{ e($order->user->name ?? 'Cliente') }}</strong> ({{ $order->user->phone ?? 'Sin teléfono' }})
              </p>
            </div>

            <div class="text-right">
              <span class="text-xs text-slate-400 block">Total de la Orden</span>
              <span class="text-lg font-black {{ $order->status === 'cancelled' ? 'text-rose-700 line-through' : 'text-slate-900' }}">${{ number_format($order->total, 0, ',', '.') }} COP</span>
            </div>
          </div>

          @if($order->status === 'cancelled')
            <div class="p-3.5 bg-rose-50 rounded-xl border border-rose-200 flex items-start gap-3 text-xs text-rose-900">
              <span class="material-symbols-outlined text-lg text-rose-600 shrink-0 mt-0.5">cancel</span>
              <div class="space-y-0.5">
                <p class="font-extrabold text-rose-800">Este pedido fue cancelado por el comprador</p>
                <p class="text-rose-700">{{ $order->notes ?: 'El comprador canceló el pedido antes del despacho.' }}</p>
                <p class="text-[11px] text-rose-600 font-semibold">El inventario de tus productos fue restaurado automáticamente en tu catálogo.</p>
              </div>
            </div>
          @endif

          <!-- Dirección de Envío y Destino -->
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-slate-700">
              <span class="material-symbols-outlined text-base text-[#10B981]">pin_drop</span>
              <span><strong>Destino de Entrega:</strong> {{ e($order->shipping_address ?? 'Fusagasugá / Sumapaz') }}</span>
            </div>
            <span class="text-[10px] text-slate-400 font-bold uppercase">Envia.com Logistics</span>
          </div>

          <!-- Ítems del Pedido -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($order->items as $item)
              <div class="flex items-center gap-3 bg-slate-50 p-2.5 rounded-xl border border-slate-100 text-xs">
                <div class="w-10 h-10 rounded-lg bg-white overflow-hidden shrink-0 border border-slate-200">
                  @if($item->product && $item->product->image_url)
                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                  @else
                    <div class="w-full h-full flex items-center justify-center text-slate-400">
                      <span class="material-symbols-outlined text-base">inventory_2</span>
                    </div>
                  @endif
                </div>
                <div class="min-w-0 flex-1">
                  <p class="font-bold text-slate-900 truncate">{{ $item->product->name ?? 'Producto' }}</p>
                  <p class="text-slate-500 text-[11px] font-semibold">${{ number_format($item->price, 0, ',', '.') }} x {{ $item->quantity }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-800 text-[10px] font-black">
                  x{{ $item->quantity }}
                </span>
              </div>
            @endforeach
          </div>

          <!-- BARRA DE LOGÍSTICA ENVIA.COM Y RASTREO (SIN FORMULARIOS MANUALES) -->
          <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl {{ $order->status === 'cancelled' ? 'bg-rose-100 text-rose-600' : 'bg-blue-50 text-blue-600' }} flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-lg">{{ $order->status === 'cancelled' ? 'block' : 'local_shipping' }}</span>
              </div>
              <div>
                @if($order->status === 'cancelled')
                  <p class="text-xs font-bold text-rose-700">Despacho anulado · Pedido cancelado</p>
                  <p class="text-[10px] text-slate-500">No debes despachar este pedido; el stock ya fue devuelto a tu inventario</p>
                @elseif($order->tracking_number)
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-black text-slate-900 font-mono">{{ $order->tracking_number }}</span>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-blue-100 text-blue-800">
                      {{ strtoupper($order->shipping_carrier ?: 'Envia') }}
                    </span>
                  </div>
                  <p class="text-[11px] text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Sincronizado con API Envia.com
                  </p>
                @elseif($order->payment_status === 'paid')
                  <p class="text-xs font-bold text-slate-700">Generando guía con Envia.com...</p>
                  <p class="text-[10px] text-slate-400">El servicio en segundo plano está conectando con la transportadora</p>
                @else
                  <p class="text-xs font-bold text-amber-700">Esperando confirmación de pago (Wompi)</p>
                  <p class="text-[10px] text-slate-400">La guía se emitirá automáticamente una vez confirmado el pago</p>
                @endif
              </div>
            </div>

            <!-- Botones de Acción: Factura Electrónica DIAN (Factus), Ver Rastreo e Imprimir Guía Térmica -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
              @if($order->invoice_pdf_path)
                <a href="{{ route('merchant.orders.invoice', [$order->id, 'pdf']) }}"
                   class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs font-extrabold rounded-xl transition-all flex items-center gap-1.5"
                   title="CUFE: {{ $order->invoice_cufe }}">
                  <span class="material-symbols-outlined text-base text-emerald-700">receipt_long</span>
                  <span>Factura PDF ({{ $order->invoice_number ?: 'DIAN' }})</span>
                </a>
              @endif

              @if($order->invoice_xml_path)
                <a href="{{ route('merchant.orders.invoice', [$order->id, 'xml']) }}"
                   class="px-2.5 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 text-[11px] font-bold rounded-xl transition-all flex items-center gap-1"
                   title="Descargar AttachedDocument XML UBL 2.1">
                  <span class="material-symbols-outlined text-sm text-slate-600">code</span>
                  <span>XML</span>
                </a>
              @endif

              @if($order->tracking_number)
                <!-- Botón de Rastreo en Vivo -->
                <button type="button"
                        @click="openTracking('{{ route('merchant.orders.tracking', $order->id) }}', {{ $order->id }})"
                        class="px-4 py-2 bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-extrabold rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                  <span class="material-symbols-outlined text-base">travel_explore</span>
                  <span>Ver Rastreo Envia.com</span>
                </button>
              @endif

              @if($order->label_url)
                <!-- Botón de Etiqueta Térmica Oficial -->
                <a href="{{ $order->label_url }}" target="_blank" rel="noopener"
                   class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-slate-600">print</span>
                  <span>Etiqueta Térmica</span>
                </a>
              @endif

              @if($order->tracking_url)
                <!-- Enlace al Portal de Envia -->
                <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener"
                   class="p-2 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Abrir en Portal Envia.com">
                  <span class="material-symbols-outlined text-lg">open_in_new</span>
                </a>
              @endif
            </div>

          </div>

        </div>
      @endforeach

      <div class="mt-6">{{ $orders->links() }}</div>
    </div>
  @endif

  <!-- ========================================================================= -->
  <!-- MODAL DE RASTREO OFICIAL ENVIA.COM (STEPPER VISUAL & EVENTOS EN VIVO)     -->
  <!-- ========================================================================= -->
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
                <!-- Línea de fondo -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-200 z-0"></div>
                <!-- Línea activa -->
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-[#10B981] transition-all duration-500 z-0"
                     :style="'width: ' + (((trackingData.step || 3) - 1) * 25) + '%'"></div>

                <!-- Paso 1: Pago Aprobado -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all bg-[#006c47] text-white shadow-sm ring-4 ring-white">
                    <span class="material-symbols-outlined text-[15px]">credit_score</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1 text-slate-800">Pagado</span>
                </div>

                <!-- Paso 2: Guía Envia.com -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 2 ? 'bg-[#006c47] text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">receipt_long</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 2 ? 'text-slate-800' : 'text-slate-400'">Guía</span>
                </div>

                <!-- Paso 3: En Tránsito -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 3 ? 'bg-[#006c47] text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">local_shipping</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 3 ? 'text-slate-800' : 'text-slate-400'">Tránsito</span>
                </div>

                <!-- Paso 4: En Reparto -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 4 ? 'bg-[#006c47] text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">two_wheeler</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 4 ? 'text-slate-800' : 'text-slate-400'">Reparto</span>
                </div>

                <!-- Paso 5: Entregado -->
                <div class="relative z-10 flex flex-col items-center">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all ring-4 ring-white"
                       :class="(trackingData.step || 3) >= 5 ? 'bg-emerald-500 text-white shadow-sm' : 'bg-slate-200 text-slate-500'">
                    <span class="material-symbols-outlined text-[15px]">done_all</span>
                  </div>
                  <span class="text-[10px] font-extrabold mt-1" :class="(trackingData.step || 3) >= 5 ? 'text-emerald-700 font-black' : 'text-slate-400'">Entregado</span>
                </div>

              </div>
            </div>

            <!-- Historial de Eventos en Tiempo Real -->
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
@endsection
