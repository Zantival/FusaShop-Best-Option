@extends('layouts.app')
@section('title', 'Mis Pedidos & Rastreo de Envíos | FusaShop')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8" x-data="{
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

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="text-3xl font-['Manrope'] font-black text-on-background">Mis Pedidos & Despachos</h1>
      <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
        Rastrea en tiempo real tus compras despachadas mediante la red logística oficial de <strong>Envia.com</strong>
      </p>
    </div>
    <a href="{{ route('consumer.catalog') }}" class="px-4 py-2 bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-bold rounded-xl transition-all flex items-center gap-2 self-start sm:self-auto shadow-sm">
      <span class="material-symbols-outlined text-sm">storefront</span> Seguir Comprando
    </a>
  </div>

  @if($orders->isEmpty())
    <div class="text-center py-20 bg-white rounded-3xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 p-8">
      <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#006c47] flex items-center justify-center mx-auto mb-4">
        <span class="material-symbols-outlined text-3xl">shopping_bag</span>
      </div>
      <h3 class="text-lg font-bold text-slate-800">Aún no has realizado pedidos</h3>
      <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Explora nuestro catálogo con cientos de comercios locales de Fusagasugá y el Sumapaz.</p>
      <a href="{{ route('consumer.catalog') }}" class="mt-4 inline-flex items-center gap-1.5 px-5 py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-bold rounded-xl transition-all shadow-sm">
        <span>Ir al Catálogo</span>
        <span class="material-symbols-outlined text-sm">arrow_forward</span>
      </a>
    </div>
  @else
    <div class="space-y-6">
      @foreach($orders as $order)
        @php
          $statusColors = [
            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
            'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
            'shipped' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'delivered' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
          ];
        @endphp

        <div class="bg-white rounded-3xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-outline-variant/20 space-y-4">
          <!-- Encabezado de la Orden -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/15">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono font-black text-slate-900 text-base">Pedido #{{ $order->id }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $statusColors[$order->status] ?? 'bg-slate-100 text-slate-700' }}">
                  {{ $order->stageLabel() }}
                </span>
                @if($order->tracking_number)
                  <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                    <span class="material-symbols-outlined text-[12px]">local_shipping</span>
                    {{ strtoupper($order->shipping_carrier ?: 'Envia') }}: {{ $order->tracking_number }}
                  </span>
                @endif
              </div>
              <p class="text-xs text-slate-500 mt-1">
                Realizado el {{ $order->created_at->format('d M Y - h:i A') }} · Entrega en: <strong>{{ e($order->shipping_address ?? 'Fusagasugá') }}</strong>
              </p>
            </div>

            <div class="text-left sm:text-right">
              @if($order->discount > 0)
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md inline-block mb-1">
                  Ahorro FusaCoins: -${{ number_format($order->discount, 0, ',', '.') }}
                </span>
              @endif
              <div class="text-xl font-black text-[#006c47]">
                ${{ number_format($order->total, 0, ',', '.') }} <span class="text-xs font-normal text-slate-400">COP</span>
              </div>
            </div>
          </div>

          <!-- Ítems Comprados -->
          <div class="flex gap-2 flex-wrap">
            @foreach($order->items as $item)
              <div class="flex items-center gap-2.5 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs">
                <div class="w-7 h-7 rounded-lg bg-white overflow-hidden shrink-0 border border-slate-200">
                  @if($item->product && $item->product->image_url)
                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                  @else
                    <div class="w-full h-full flex items-center justify-center text-slate-300">
                      <span class="material-symbols-outlined text-xs">image</span>
                    </div>
                  @endif
                </div>
                <span class="font-bold text-slate-800 truncate max-w-[200px]">{{ $item->product->name ?? 'Producto' }}</span>
                <span class="text-[10px] font-black bg-slate-200 text-slate-800 rounded-full px-2 py-0.5">x{{ $item->quantity }}</span>
              </div>
            @endforeach
          </div>

          <!-- Barra de Acciones y Rastreo Oficial -->
          <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3" x-data="{ showCancelBox: false }">
            
            <div class="flex flex-wrap items-center gap-2">
              @if($order->status === 'cancelled')
                <div class="flex items-center gap-1.5 text-xs text-rose-700 bg-rose-50 px-3 py-1.5 rounded-xl border border-rose-200 font-bold">
                  <span class="material-symbols-outlined text-base">cancel</span>
                  <span>Pedido cancelado{{ $order->notes ? ' · ' . $order->notes : '' }}</span>
                </div>
              @elseif($order->tracking_number)
                <!-- Botón de Rastreo en Vivo Envia.com -->
                <button type="button"
                        @click="openTracking('{{ route('consumer.orders.tracking', $order->id) }}', {{ $order->id }})"
                        class="px-4 py-2 bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-extrabold rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                  <span class="material-symbols-outlined text-base">travel_explore</span>
                  <span>Rastrear Envío (Envia.com)</span>
                </button>
              @elseif($order->payment_status === 'paid')
                <div class="flex items-center gap-2 text-xs text-blue-700 bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-200 font-bold">
                  <span class="material-symbols-outlined text-base animate-spin">sync</span>
                  <span>Guía en proceso con Envia.com...</span>
                </div>
              @endif

              <a href="{{ route('consumer.orders.receipt', $order->id) }}" class="flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                <span class="material-symbols-outlined text-base text-slate-500">receipt</span>
                <span>Recibo</span>
              </a>

              @if($order->canBeCancelledByBuyer())
                <button type="button"
                        @click="showCancelBox = !showCancelBox"
                        class="flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
                  <span class="material-symbols-outlined text-base">cancel</span>
                  <span>Cancelar Pedido</span>
                </button>
              @endif
            </div>

            <!-- Descargas de Factura Electrónica (DIAN / Factus) -->
            <div class="flex items-center gap-2">
              @if($order->invoice_pdf_path)
                <a href="{{ route('consumer.orders.invoice', [$order->id, 'pdf']) }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-bold rounded-xl transition-all">
                  <span class="material-symbols-outlined text-sm text-rose-600">picture_as_pdf</span> Factura PDF
                </a>
              @endif
              @if($order->invoice_xml_path)
                <a href="{{ route('consumer.orders.invoice', [$order->id, 'xml']) }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-bold rounded-xl transition-all">
                  <span class="material-symbols-outlined text-sm text-blue-600">code</span> XML
                </a>
              @endif
            </div>

            @if($order->canBeCancelledByBuyer())
              <div x-show="showCancelBox" x-cloak style="display: none;" class="w-full pt-3 mt-2 border-t border-rose-100">
                <form action="{{ route('consumer.orders.cancel', $order->id) }}" method="POST" class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  @csrf
                  @method('PATCH')
                  <div class="flex-1 space-y-1">
                    <p class="text-xs font-extrabold text-rose-900 flex items-center gap-1.5">
                      <span class="material-symbols-outlined text-sm">warning</span>
                      ¿Deseas cancelar el Pedido #{{ $order->id }}?
                    </p>
                    <p class="text-[11px] text-rose-700">Se notificará de inmediato al comerciante, se liberará el stock y se te devolverán los FusaCoins canjeados.</p>
                    <input type="text" name="reason" maxlength="255" placeholder="Motivo de cancelación (opcional, ej: me equivoqué de dirección)"
                           class="mt-1.5 w-full px-3 py-1.5 text-xs bg-white border border-rose-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-400">
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="showCancelBox = false" class="px-3 py-2 bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 text-xs font-bold rounded-xl cursor-pointer">
                      Conservar pedido
                    </button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold rounded-xl shadow-xs transition-all flex items-center gap-1 cursor-pointer">
                      <span class="material-symbols-outlined text-sm">delete_forever</span>
                      Confirmar Cancelación
                    </button>
                  </div>
                </form>
              </div>
            @endif

          </div>

        </div>
      @endforeach

      <div class="mt-6">{{ $orders->links() }}</div>
    </div>
  @endif

  <!-- ========================================================================= -->
  <!-- MODAL DE RASTREO EN VIVO PARA EL CONSUMIDOR (ENVIA.COM API)               -->
  <!-- ========================================================================= -->
  <div x-show="trackingModalOpen" 
       x-cloak 
       style="display: none;" 
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" 
       @click.self="trackingModalOpen = false">
    
    <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200" @click.stop>
      
      <!-- Modal Header -->
      <div class="p-5 bg-gradient-to-r from-[#006c47] to-[#047857] text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center backdrop-blur-xs">
            <span class="material-symbols-outlined text-xl">local_shipping</span>
          </div>
          <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-200">Logística Envia.com</span>
            <h3 class="text-base font-black font-['Manrope']">Rastreo de tu Pedido en Vivo</h3>
          </div>
        </div>
        <button type="button" @click="trackingModalOpen = false" class="p-1 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-colors">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-6">
        
        <!-- Loading State -->
        <template x-if="trackingLoading">
          <div class="py-12 text-center space-y-3">
            <span class="material-symbols-outlined text-4xl text-[#006c47] animate-spin">progress_activity</span>
            <p class="text-xs font-bold text-slate-600">Obteniendo coordenadas y novedades de Envia.com...</p>
          </div>
        </template>

        <!-- Tracking Content -->
        <template x-if="!trackingLoading && trackingData">
          <div class="space-y-6">

            <!-- Fila de Detalles: Guía y Transportadora -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200/80 text-xs">
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase">Transportadora</span>
                <p class="font-black text-slate-900" x-text="trackingData.carrier || 'Envia.com'"></p>
              </div>
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase">Número de Guía</span>
                <p class="font-mono font-black text-[#006c47]" x-text="trackingData.tracking_number || 'En proceso'"></p>
              </div>
              <div class="col-span-2 sm:col-span-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase">Estado</span>
                <p class="font-extrabold text-blue-700" x-text="trackingData.status_label || trackingData.status || 'En camino'"></p>
              </div>
            </div>

            <!-- STEPPER VISUAL (5 ETAPAS) -->
            <div class="pt-2">
              <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-4">Progreso del Envío</h4>
              
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
                    <span class="material-symbols-outlined text-[15px]">inventory</span>
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

            <!-- Historial de Novedades del Transportador -->
            <div class="space-y-3">
              <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Puntos de Control & Novedades</h4>

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
                  Sin novedades reportadas por la transportadora.
                </div>
              </div>
            </div>

            <!-- Botón Externo de Envia -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs" x-show="trackingData.url">
              <span class="text-slate-500">¿Deseas ver el portal directo del transportador?</span>
              <a :href="trackingData.url" target="_blank" rel="noopener" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl flex items-center gap-1.5 transition-all">
                <span>Rastreo Envia.com</span>
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