@extends('layouts.app')
@section('title', 'Mi Cuenta | FusaShop Sumapaz')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ currentTab: 'overview', qrModalOpen: false, activeQrData: null }">

  <!-- Breadcrumb & Top Salutation -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 bg-surface-container-lowest p-6 rounded-3xl shadow-card border border-outline-variant/20">
    <div class="flex items-center gap-4">
      <div class="relative w-16 h-16 rounded-2xl overflow-hidden bg-primary/10 border-2 border-primary/20 shrink-0 flex items-center justify-center shadow-inner">
        @if($user->avatarUrl())
          <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
        @else
          <span class="font-black text-2xl text-primary">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
        @endif
        <div class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white" title="Usuario Verificado"></div>
      </div>
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-2xl sm:text-3xl font-['Manrope'] font-black text-on-background">¡Hola, {{ explode(' ', $user->name)[0] }}!</h1>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/30 text-[#006c47] border border-[#6efcb9] uppercase tracking-wider">Comprador Sumapaz</span>
        </div>
        <p class="text-xs sm:text-sm text-on-surface-variant mt-0.5">Gestiona tus compras, billetera FusaCoins, envíos en tiempo real y facturas DIAN</p>
      </div>
    </div>

    <!-- Quick Stats Pill -->
    <div class="flex items-center gap-3">
      <div class="bg-gradient-to-br from-amber-500/10 to-amber-600/5 border border-amber-500/20 px-5 py-3 rounded-2xl flex items-center gap-3 shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black shadow-md">
          <span class="material-symbols-outlined text-[20px]">toll</span>
        </div>
        <div>
          <span class="text-[10px] uppercase font-bold text-amber-700 tracking-wider">Saldo FusaCoins</span>
          <p class="text-xl font-black text-amber-600">{{ number_format($userPoints, 0, ',', '.') }} <span class="text-xs font-semibold text-on-surface-variant">(${{ number_format($userPoints, 0, ',', '.') }} COP)</span></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Grid Layout: Sidebar Navigation + Content Panel -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    
    <!-- LEFT SIDEBAR -->
    <aside class="lg:col-span-3 bg-surface-container-lowest rounded-3xl p-5 shadow-card border border-outline-variant/20 sticky top-24 space-y-2">
      <div class="px-3 py-2 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Navegación Mi Cuenta</div>
      
      <button type="button" @click="currentTab = 'overview'" :class="currentTab === 'overview' ? 'bg-primary text-white font-bold shadow-md shadow-primary/20' : 'text-on-surface-variant hover:bg-surface-container font-semibold'" class="w-full text-left px-4 py-3 rounded-2xl text-sm flex items-center gap-3 transition-all">
        <span class="material-symbols-outlined text-[20px]">dashboard</span>
        <span>Panel General</span>
      </button>

      <button type="button" @click="currentTab = 'wallet'" :class="currentTab === 'wallet' ? 'bg-primary text-white font-bold shadow-md shadow-primary/20' : 'text-on-surface-variant hover:bg-surface-container font-semibold'" class="w-full text-left px-4 py-3 rounded-2xl text-sm flex items-center justify-between transition-all">
        <div class="flex items-center gap-3">
          <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
          <span>Billetera FusaCoins</span>
        </div>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">{{ number_format($userPoints) }}</span>
      </button>

      <button type="button" @click="currentTab = 'tracking'" :class="currentTab === 'tracking' ? 'bg-primary text-white font-bold shadow-md shadow-primary/20' : 'text-on-surface-variant hover:bg-surface-container font-semibold'" class="w-full text-left px-4 py-3 rounded-2xl text-sm flex items-center justify-between transition-all">
        <div class="flex items-center gap-3">
          <span class="material-symbols-outlined text-[20px]">local_shipping</span>
          <span>Rastreador Envia.com</span>
        </div>
        @if($activeShipments->count() > 0)
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        @endif
      </button>

      <button type="button" @click="currentTab = 'o2o'" :class="currentTab === 'o2o' ? 'bg-primary text-white font-bold shadow-md shadow-primary/20' : 'text-on-surface-variant hover:bg-surface-container font-semibold'" class="w-full text-left px-4 py-3 rounded-2xl text-sm flex items-center gap-3 transition-all">
        <span class="material-symbols-outlined text-[20px]">qr_code_scanner</span>
        <span>Mis Reservas O2O</span>
      </button>

      <button type="button" @click="currentTab = 'invoices'" :class="currentTab === 'invoices' ? 'bg-primary text-white font-bold shadow-md shadow-primary/20' : 'text-on-surface-variant hover:bg-surface-container font-semibold'" class="w-full text-left px-4 py-3 rounded-2xl text-sm flex items-center gap-3 transition-all">
        <span class="material-symbols-outlined text-[20px]">receipt_long</span>
        <span>Facturación DIAN (Factus)</span>
      </button>

      <button type="button" @click="currentTab = 'addresses'" :class="currentTab === 'addresses' ? 'bg-primary text-white font-bold shadow-md shadow-primary/20' : 'text-on-surface-variant hover:bg-surface-container font-semibold'" class="w-full text-left px-4 py-3 rounded-2xl text-sm flex items-center gap-3 transition-all">
        <span class="material-symbols-outlined text-[20px]">pin_drop</span>
        <span>Libreta de Direcciones</span>
      </button>

      <button type="button" @click="currentTab = 'profile'" :class="currentTab === 'profile' ? 'bg-primary text-white font-bold shadow-md shadow-primary/20' : 'text-on-surface-variant hover:bg-surface-container font-semibold'" class="w-full text-left px-4 py-3 rounded-2xl text-sm flex items-center gap-3 transition-all">
        <span class="material-symbols-outlined text-[20px]">manage_accounts</span>
        <span>Perfil y Seguridad</span>
      </button>

      <div class="pt-4 mt-4 border-t border-outline-variant/20">
        <a href="{{ route('consumer.catalog') }}" class="w-full px-4 py-3 rounded-2xl text-xs font-bold text-primary bg-primary/5 hover:bg-primary/10 flex items-center justify-center gap-2 transition-all">
          <span class="material-symbols-outlined text-[18px]">shopping_basket</span> Seguir Comprando
        </a>
      </div>
    </aside>

    <!-- RIGHT MAIN PANEL -->
    <main class="lg:col-span-9 space-y-6">

      <!-- ========================================== -->
      <!-- TAB 1: OVERVIEW (RESUMEN EJECUTIVO) -->
      <!-- ========================================== -->
      <div x-show="currentTab === 'overview'" class="space-y-6">

        <!-- 3 Feature Highlight Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <!-- Card 1: FusaCoins -->
          <div @click="currentTab = 'wallet'" class="cursor-pointer bg-gradient-to-br from-white to-amber-50/50 p-6 rounded-3xl shadow-card border border-amber-200/50 hover:border-amber-400 transition-all group">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-2xl">toll</span>
              </div>
              <span class="text-xs font-bold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-full">Billetera</span>
            </div>
            <p class="text-2xl font-black text-on-background">{{ number_format($userPoints, 0, ',', '.') }}</p>
            <p class="text-xs text-on-surface-variant font-medium mt-1">FusaCoins disponibles para compras o canjes</p>
          </div>

          <!-- Card 2: Envíos en Camino -->
          <div @click="currentTab = 'tracking'" class="cursor-pointer bg-gradient-to-br from-white to-emerald-50/50 p-6 rounded-3xl shadow-card border border-emerald-200/50 hover:border-emerald-400 transition-all group">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-2xl">local_shipping</span>
              </div>
              <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-full">Logística</span>
            </div>
            <p class="text-2xl font-black text-on-background">{{ $activeShipments->count() }}</p>
            <p class="text-xs text-on-surface-variant font-medium mt-1">Paquetes en preparación o en ruta Envia.com</p>
          </div>

          <!-- Card 3: Reservas O2O Activas -->
          <div @click="currentTab = 'o2o'" class="cursor-pointer bg-gradient-to-br from-white to-emerald-50/50 p-6 rounded-3xl shadow-card border border-emerald-200/50 hover:border-emerald-400 transition-all group">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-2xl">confirmation_number</span>
              </div>
              <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-full">Vouchers QR</span>
            </div>
            <p class="text-2xl font-black text-on-background">{{ $o2oOrders->count() }}</p>
            <p class="text-xs text-on-surface-variant font-medium mt-1">Experiencias y reservas presenciales Sumapaz</p>
          </div>
        </div>

        <!-- Real-Time Shipment Alert (if any active) -->
        @if($activeShipments->isNotEmpty())
          @php $heroShipment = $activeShipments->first(); @endphp
          <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-primary/20 overflow-hidden relative">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                <span class="text-xs font-black text-primary uppercase tracking-wider">Rastreo Activo En Tiempo Real · Envia.com</span>
              </div>
              <span class="text-xs font-mono font-bold text-on-surface-variant">Guía: {{ $heroShipment->tracking_number ?? 'Generando...' }}</span>
            </div>

            <!-- Stepper Component -->
            <div class="py-4">
              @php $step = $heroShipment->trackingStep(); @endphp
              <div class="relative flex items-center justify-between">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-surface-container -z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-primary -z-0 transition-all duration-500" style="width: {{ $step >= 4 ? '100%' : ($step == 3 ? '66%' : ($step == 2 ? '33%' : '5%')) }}"></div>

                <!-- Step 1 -->
                <div class="flex flex-col items-center bg-surface-container-lowest px-2 relative z-10">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm {{ $step >= 1 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-surface-container text-on-surface-variant' }}">
                    <span class="material-symbols-outlined text-[18px]">receipt</span>
                  </div>
                  <span class="text-[11px] font-bold text-on-surface mt-1.5">Pagado</span>
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center bg-surface-container-lowest px-2 relative z-10">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm {{ $step >= 2 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-surface-container text-on-surface-variant' }}">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                  </div>
                  <span class="text-[11px] font-bold text-on-surface mt-1.5">En Empaque</span>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center bg-surface-container-lowest px-2 relative z-10">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm {{ $step >= 3 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-surface-container text-on-surface-variant' }}">
                    <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                  </div>
                  <span class="text-[11px] font-bold text-on-surface mt-1.5">En Camino</span>
                </div>

                <!-- Step 4 -->
                <div class="flex flex-col items-center bg-surface-container-lowest px-2 relative z-10">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm {{ $step >= 4 ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-surface-container text-on-surface-variant' }}">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                  </div>
                  <span class="text-[11px] font-bold text-on-surface mt-1.5">Entregado</span>
                </div>
              </div>
            </div>

            <div class="mt-4 pt-4 border-t border-outline-variant/20 flex flex-col sm:flex-row items-center justify-between gap-3">
              <div class="text-xs text-on-surface-variant">
                Transportadora: <strong class="text-on-surface">{{ strtoupper($heroShipment->shipping_carrier ?? 'Envia.com Express') }}</strong> · Pedido #{{ $heroShipment->id }}
              </div>
              <div class="flex items-center gap-2">
                @if($heroShipment->tracking_number)
                  <button type="button" @click="currentTab = 'tracking'" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-sm hover:opacity-95 transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">visibility</span> Ver Línea de Tiempo
                  </button>
                @endif
              </div>
            </div>
          </div>
        @endif

        <!-- Recent Orders Compact List -->
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-outline-variant/20">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-lg font-['Manrope'] font-bold text-on-background">Historial de Órdenes Recientes</h2>
              <p class="text-xs text-on-surface-variant">Detalle de compras y accesos directos a factura DIAN</p>
            </div>
            <a href="{{ route('consumer.orders') }}" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
              Ver todas <span class="material-symbols-outlined text-xs">arrow_forward</span>
            </a>
          </div>

          @if($orders->isEmpty())
            <div class="text-center py-12 border-2 border-dashed border-outline-variant/30 rounded-2xl">
              <span class="material-symbols-outlined text-4xl text-on-surface-variant/40 mb-2">shopping_bag</span>
              <p class="text-sm font-bold text-on-surface">No registras compras aún</p>
              <p class="text-xs text-on-surface-variant mt-1">Explora los comercios locales de Fusagasugá y acumula FusaCoins en tu primera compra.</p>
              <a href="{{ route('consumer.catalog') }}" class="mt-4 inline-block px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl shadow-sm hover:opacity-95 transition-all">Ir al Catálogo</a>
            </div>
          @else
            <div class="space-y-4">
              @foreach($orders->take(5) as $order)
                <div class="bg-surface-container-low rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 border border-outline-variant/10 hover:border-primary/30 transition-all">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="font-mono font-bold text-xs text-on-surface">#{{ $order->id }}</span>
                      <span class="text-xs text-on-surface-variant">· {{ $order->created_at->format('d/m/Y H:i') }}</span>
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $order->payment_status === 'paid' ? 'Pagado' : 'Pendiente' }}
                      </span>
                    </div>
                    <p class="text-sm font-bold text-on-surface">
                      {{ $order->items->first()->product->name ?? 'Producto de la orden' }}
                      @if($order->items->count() > 1)
                        <span class="text-xs font-normal text-on-surface-variant">(+{{ $order->items->count() - 1 }} más)</span>
                      @endif
                    </p>
                  </div>

                  <div class="flex items-center gap-4 justify-between md:justify-end">
                    <div class="text-right">
                      <span class="text-xs text-on-surface-variant">Total</span>
                      <p class="text-base font-black text-primary">${{ number_format($order->total, 0, ',', '.') }} COP</p>
                    </div>

                    <div class="flex items-center gap-2">
                      @if($order->invoice_pdf_path)
                        <a href="{{ route('consumer.orders.invoice', [$order->id, 'pdf']) }}" title="Descargar Factura Oficial Factus DIAN" class="p-2.5 rounded-xl bg-surface-container hover:bg-surface-container-highest text-on-surface transition-all">
                          <span class="material-symbols-outlined text-sm text-red-600">picture_as_pdf</span>
                        </a>
                      @endif
                      <a href="{{ route('consumer.orders.receipt', $order->id) }}" class="px-3.5 py-2 bg-surface-container hover:bg-surface-container-highest rounded-xl text-xs font-bold text-on-surface transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">receipt</span> Recibo
                      </a>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

      </div>

      <!-- ========================================== -->
      <!-- TAB 2: BILLETERA FUSACOINS -->
      <!-- ========================================== -->
      <div x-show="currentTab === 'wallet'" style="display: none;" class="space-y-6">
        
        <!-- FusaCoins Hero Card -->
        <div class="bg-gradient-to-br from-[#10B981] via-[#059669] to-[#047857] text-white rounded-3xl p-8 shadow-xl relative overflow-hidden">
          <div class="absolute right-0 top-0 w-80 h-80 bg-white/10 rounded-full -mr-20 -mt-20 blur-2xl pointer-events-none"></div>
          
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative z-10">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-amber-300 text-2xl">toll</span>
                <span class="text-xs font-black uppercase tracking-widest text-emerald-100">Billetera de Recompensas Sumapaz</span>
              </div>
              <p class="text-4xl sm:text-5xl font-['Manrope'] font-black tracking-tight text-white">{{ number_format($userPoints, 0, ',', '.') }}</p>
              <p class="text-sm font-semibold text-emerald-100 mt-1">Equivalencia oficial: <span class="text-amber-300 font-black">${{ number_format($userPoints, 0, ',', '.') }} COP</span> (Regla 1:1 en caja)</p>
            </div>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-5 rounded-2xl max-w-xs text-xs text-emerald-50 leading-relaxed">
              <div class="font-bold flex items-center gap-1.5 mb-1 text-white">
                <span class="material-symbols-outlined text-sm text-amber-300">verified</span> ¿Cómo redimir?
              </div>
              Tus FusaCoins se aplican directamente en el Checkout como descuento automático en cualquier comercio de la plataforma.
            </div>
          </div>

          <!-- Points Simulator / Calculator -->
          <div class="mt-8 pt-6 border-t border-white/20 relative z-10" x-data="{ simulateSpend: Math.min({{ $userPoints }}, 50000) }">
            <div class="flex items-center justify-between text-xs font-semibold mb-2">
              <span>Simulador de canje para tu próxima compra:</span>
              <span class="font-bold text-amber-300" x-text="'Ahorro directo: $' + new Intl.NumberFormat('es-CO').format(simulateSpend) + ' COP'"></span>
            </div>
            <input type="range" min="0" max="{{ max($userPoints, 1000) }}" step="1000" x-model="simulateSpend" class="w-full accent-amber-400 cursor-pointer">
            <div class="flex justify-between text-[11px] text-emerald-200 mt-1">
              <span>0 FusaCoins</span>
              <span>Canje Máximo: {{ number_format($userPoints) }} FusaCoins</span>
            </div>
          </div>
        </div>

        <!-- Merchants Points Breakdown -->
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-outline-variant/20">
          <h2 class="text-lg font-['Manrope'] font-bold text-on-background mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500">storefront</span> FusaCoins Acumulados por Tienda
          </h2>

          @if($loyaltyPoints->isEmpty())
            <div class="text-center py-8 text-on-surface-variant text-sm">
              Aún no has acumulado puntos específicos por tienda. ¡Haz compras en comercios locales para empezar!
            </div>
          @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              @foreach($loyaltyPoints as $lp)
                <div class="bg-surface-container-low p-4 rounded-2xl border border-outline-variant/10 flex items-center justify-between">
                  <div>
                    <p class="font-bold text-sm text-on-surface">{{ $lp->merchant->companyProfile->company_name ?? 'Comercio Local' }}</p>
                    <span class="text-[11px] text-on-surface-variant">Cliente Frecuente</span>
                  </div>
                  <div class="text-right">
                    <span class="text-lg font-black text-emerald-600">+{{ number_format($lp->points) }}</span>
                    <span class="text-[10px] block font-bold text-on-surface-variant uppercase">Coins</span>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

        <!-- Available Coupons & Rewards -->
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-outline-variant/20">
          <h2 class="text-lg font-['Manrope'] font-bold text-on-background mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">local_activity</span> Cupones Disponibles en la Red Sumapaz
          </h2>

          @if($coupons->isEmpty())
            <div class="text-center py-8 text-on-surface-variant text-sm">
              No hay cupones especiales en este momento. Revisa periódicamente las ofertas relámpago de la vitrina.
            </div>
          @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              @foreach($coupons as $coupon)
                <div class="border border-dashed border-primary/40 bg-primary/5 rounded-2xl p-4 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="font-mono font-black text-sm bg-primary text-white px-2.5 py-1 rounded-lg uppercase tracking-wider">{{ $coupon->code }}</span>
                      <span class="text-xs font-bold text-primary">{{ $coupon->type === 'fixed' ? '$'.number_format($coupon->value).' OFF' : $coupon->value.'% OFF' }}</span>
                    </div>
                    <p class="text-xs text-on-surface-variant font-medium mt-2">
                      {{ $coupon->merchant->companyProfile->company_name ?? 'Válido en comercios aliados' }}
                    </p>
                  </div>
                  <div class="mt-4 pt-3 border-t border-primary/20 flex items-center justify-between text-[11px] text-on-surface-variant">
                    <span>Mínimo: ${{ number_format($coupon->min_order_amount) }}</span>
                    <a href="{{ route('consumer.catalog') }}" class="font-bold text-primary hover:underline">Usar</a>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

      </div>

      <!-- ========================================== -->
      <!-- TAB 3: RASTREADOR DE ENVÍOS EN TIEMPO REAL -->
      <!-- ========================================== -->
      <div x-show="currentTab === 'tracking'" style="display: none;" class="space-y-6">
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-outline-variant/20">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
              <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
                <span class="material-symbols-outlined text-[#10B981]">local_shipping</span> Logística y Despachos Envia.com
              </h2>
              <p class="text-xs text-on-surface-variant mt-1">Rastreo sincronizado con la API oficial de Envia.com con actualización de eventos en tiempo real</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 self-start sm:self-auto">
              API Envia.com Conectada
            </span>
          </div>

          @php $trackableOrders = $orders->whereNotNull('tracking_number'); @endphp

          @if($trackableOrders->isEmpty())
            <div class="text-center py-12 bg-surface-container-low rounded-2xl border-2 border-dashed border-outline-variant/30">
              <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2">package_2</span>
              <p class="text-sm font-bold text-on-surface">No tienes despachos activos con guía asignada</p>
              <p class="text-xs text-on-surface-variant mt-1">Cuando el comerciante prepare tu paquete, recibirás una guía oficial de Envia.com con tracking en vivo.</p>
            </div>
          @else
            <div class="space-y-6">
              @foreach($trackableOrders as $order)
                <div class="bg-surface-container-low rounded-2xl p-6 border border-outline-variant/20 space-y-4">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-outline-variant/20">
                    <div>
                      <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Orden #{{ $order->id }} · {{ $order->created_at->format('d/m/Y') }}</span>
                      <p class="font-bold text-on-surface text-base">Transportadora: {{ strtoupper($order->shipping_carrier ?? 'Envia') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-mono font-bold bg-white px-3 py-1 rounded-lg border border-outline-variant/30">
                        Guía: {{ $order->tracking_number }}
                      </span>
                    </div>
                  </div>

                  <!-- Graphic Stepper -->
                  @php $step = $order->trackingStep(); @endphp
                  <div class="py-4">
                    <div class="relative flex items-center justify-between">
                      <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-surface-container-highest -z-0"></div>
                      <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-primary -z-0 transition-all duration-500" style="width: {{ $step >= 4 ? '100%' : ($step == 3 ? '66%' : ($step == 2 ? '33%' : '5%')) }}"></div>

                      <!-- 1. Confirmado -->
                      <div class="flex flex-col items-center bg-surface-container-low px-2 relative z-10">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 1 ? 'bg-primary text-white shadow-md' : 'bg-surface-container-highest text-on-surface-variant' }}">
                          <span class="material-symbols-outlined text-[16px]">receipt</span>
                        </div>
                        <span class="text-[10px] font-bold text-on-surface mt-1">1. Confirmado</span>
                      </div>

                      <!-- 2. Preparación -->
                      <div class="flex flex-col items-center bg-surface-container-low px-2 relative z-10">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 2 ? 'bg-primary text-white shadow-md' : 'bg-surface-container-highest text-on-surface-variant' }}">
                          <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                        </div>
                        <span class="text-[10px] font-bold text-on-surface mt-1">2. Preparación</span>
                      </div>

                      <!-- 3. En camino -->
                      <div class="flex flex-col items-center bg-surface-container-low px-2 relative z-10">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 3 ? 'bg-primary text-white shadow-md' : 'bg-surface-container-highest text-on-surface-variant' }}">
                          <span class="material-symbols-outlined text-[16px]">local_shipping</span>
                        </div>
                        <span class="text-[10px] font-bold text-on-surface mt-1">3. En Camino</span>
                      </div>

                      <!-- 4. Entregado -->
                      <div class="flex flex-col items-center bg-surface-container-low px-2 relative z-10">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs {{ $step >= 4 ? 'bg-primary text-white shadow-md' : 'bg-surface-container-highest text-on-surface-variant' }}">
                          <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        </div>
                        <span class="text-[10px] font-bold text-on-surface mt-1">4. Entregado</span>
                      </div>
                    </div>
                  </div>

                  <!-- Live Query Button & Results Container -->
                  <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-on-surface-variant">
                      Estado actual: <strong class="text-on-surface">{{ $order->stageLabel() }}</strong>
                    </p>

                    <div class="flex items-center gap-2">
                      <button type="button" 
                              data-track-url="{{ route('consumer.orders.tracking', $order->id) }}"
                              data-external-url="{{ $order->tracking_url }}"
                              class="js-live-track-btn px-4 py-2 bg-[#10B981] hover:bg-emerald-600 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">sync</span> Consultar Estado en Vivo
                      </button>

                      @if($order->tracking_url)
                        <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="px-3.5 py-2 bg-white border border-outline-variant/30 text-on-surface hover:text-primary text-xs font-bold rounded-xl transition-all flex items-center gap-1">
                          <span class="material-symbols-outlined text-[16px]">open_in_new</span> Sitio Carrier
                        </a>
                      @endif
                    </div>
                  </div>

                  <!-- Container for live events result -->
                  <div class="js-track-events-box hidden mt-3 p-4 bg-white rounded-xl border border-outline-variant/20 text-xs"></div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 4: MIS RESERVAS O2O (VOUCHERS CON QR) -->
      <!-- ========================================== -->
      <div x-show="currentTab === 'o2o'" style="display: none;" class="space-y-6">
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-outline-variant/20">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
              <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
                <span class="material-symbols-outlined text-[#F97316]">qr_code_2</span> Mis Reservas y Experiencias O2O
              </h2>
              <p class="text-xs text-on-surface-variant mt-1">Vouchers digitales para presentar y redimir presencialmente en hoteles, restaurantes y viveros del Sumapaz</p>
            </div>
            <a href="{{ route('consumer.catalog', ['category' => 'Turismo & Hotelería']) }}" class="px-4 py-2 bg-[#F97316]/10 text-[#F97316] font-bold rounded-xl text-xs hover:bg-[#F97316]/20 transition-all flex items-center gap-1 self-start sm:self-auto">
              <span class="material-symbols-outlined text-[16px]">explore</span> Explorar Experiencias
            </a>
          </div>

          @if($o2oOrders->isEmpty())
            <div class="text-center py-12 bg-surface-container-low rounded-2xl border-2 border-dashed border-outline-variant/30">
              <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2">hotel</span>
              <p class="text-sm font-bold text-on-surface">No registras reservas turísticas u O2O activas</p>
              <p class="text-xs text-on-surface-variant mt-1">Reserva glampings en Chinauta, mesas en restaurantes típicos o recorridos florales con confirmación inmediata por QR.</p>
              <a href="{{ route('consumer.home') }}#seccion-o2o" class="mt-4 inline-block px-5 py-2.5 bg-[#F97316] text-white text-xs font-bold rounded-xl shadow-md hover:opacity-95 transition-all">Ver Ofertas O2O en Fusagasugá</a>
            </div>
          @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              @foreach($o2oOrders as $order)
                @php 
                  $item = $order->items->first(); 
                  $qrString = "FUSASHOP-VOUCHER-ORD-{$order->id}-USER-{$user->id}-TOTAL-{$order->total}";
                @endphp
                <div class="bg-surface-container-low rounded-2xl p-5 border border-outline-variant/20 flex flex-col justify-between hover:border-primary/40 transition-all relative overflow-hidden group">
                  <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-orange-400/20 to-transparent rounded-bl-3xl pointer-events-none"></div>

                  <div>
                    <div class="flex items-center justify-between mb-3">
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-orange-100 text-orange-800 uppercase tracking-wider">
                        Voucher O2O Válido
                      </span>
                      <span class="text-xs font-mono font-bold text-on-surface-variant">#VOUCHER-{{ $order->id }}</span>
                    </div>

                    <h3 class="font-['Manrope'] font-bold text-base text-on-background line-clamp-1">
                      {{ $item->product->name ?? 'Experiencia Turística Sumapaz' }}
                    </h3>
                    <p class="text-xs text-on-surface-variant mt-0.5 flex items-center gap-1">
                      <span class="material-symbols-outlined text-[14px]">storefront</span>
                      {{ $item->product->merchant->companyProfile->company_name ?? 'Comercio Afiliado' }}
                    </p>

                    <div class="my-4 p-3 bg-white rounded-xl border border-outline-variant/20 flex items-center gap-3">
                      <div class="w-12 h-12 bg-surface-container rounded-lg shrink-0 flex items-center justify-center p-1">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qrString) }}" alt="QR Voucher" class="w-full h-full object-contain">
                      </div>
                      <div class="flex-1 text-[11px] leading-tight">
                        <span class="font-bold text-on-surface block">Código QR de Redención</span>
                        <span class="text-on-surface-variant">Presenta este código al ingresar al establecimiento en Fusagasugá.</span>
                      </div>
                    </div>
                  </div>

                  <div class="pt-3 border-t border-outline-variant/20 flex items-center justify-between">
                    <div>
                      <span class="text-[10px] text-on-surface-variant uppercase font-bold">Total Pagado</span>
                      <p class="font-black text-sm text-primary">${{ number_format($order->total) }} COP</p>
                    </div>

                    <div class="flex items-center gap-2">
                      <button type="button" 
                              @click="activeQrData = { id: '{{ $order->id }}', title: '{{ addslashes($item->product->name ?? 'Experiencia O2O') }}', merchant: '{{ addslashes($item->product->merchant->companyProfile->company_name ?? 'FusaShop') }}', qr: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($qrString) }}', date: '{{ $order->created_at->format('d/m/Y') }}' }; qrModalOpen = true;"
                              class="px-3.5 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-sm hover:opacity-95 transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">qr_code</span> Ver en Pantalla
                      </button>
                      <a href="{{ route('consumer.orders.receipt', $order->id) }}" target="_blank" class="p-2 bg-white border border-outline-variant/20 rounded-xl text-on-surface hover:text-primary transition-all" title="Descargar Recibo / Voucher">
                        <span class="material-symbols-outlined text-sm">print</span>
                      </a>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 5: BANDEJA DE FACTURACIÓN DIAN (FACTUS) -->
      <!-- ========================================== -->
      <div x-show="currentTab === 'invoices'" style="display: none;" class="space-y-6">
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-outline-variant/20">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
              <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600">verified_user</span> Facturación Electrónica DIAN · Factus
              </h2>
              <p class="text-xs text-on-surface-variant mt-1">Facturas con Código Único de Facturación Electrónica (CUFE) validadas ante la DIAN</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 self-start sm:self-auto">
              API Factus Habilitada
            </span>
          </div>

          @if($invoices->isEmpty())
            <div class="text-center py-12 bg-surface-container-low rounded-2xl border-2 border-dashed border-outline-variant/30">
              <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2">description</span>
              <p class="text-sm font-bold text-on-surface">No registras facturas electrónicas emitidas aún</p>
              <p class="text-xs text-on-surface-variant mt-1">Al completar compras aprobadas por Wompi, el sistema Factus genera automáticamente la factura oficial con CUFE y soporte XML.</p>
            </div>
          @else
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-outline-variant/20 text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">
                    <th class="py-3 px-4">Factura #</th>
                    <th class="py-3 px-4">Fecha Emisión</th>
                    <th class="py-3 px-4">Código CUFE DIAN</th>
                    <th class="py-3 px-4">Monto Total</th>
                    <th class="py-3 px-4 text-right">Descargas DIAN</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10 text-xs">
                  @foreach($invoices as $inv)
                    <tr class="hover:bg-surface-container-low transition-colors">
                      <td class="py-3.5 px-4 font-mono font-bold text-on-surface">
                        {{ $inv->invoice_number ?? 'FE-'.$inv->id }}
                      </td>
                      <td class="py-3.5 px-4 text-on-surface-variant">
                        {{ $inv->invoiced_at ? $inv->invoiced_at->format('d/m/Y') : $inv->created_at->format('d/m/Y') }}
                      </td>
                      <td class="py-3.5 px-4 font-mono text-[10px] text-on-surface-variant max-w-[200px] truncate" title="{{ $inv->invoice_cufe }}">
                        {{ $inv->invoice_cufe ? substr($inv->invoice_cufe, 0, 24).'...' : 'Validado en DIAN' }}
                      </td>
                      <td class="py-3.5 px-4 font-bold text-on-surface">
                        ${{ number_format($inv->total, 0, ',', '.') }} COP
                      </td>
                      <td class="py-3.5 px-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                          @if($inv->invoice_pdf_path)
                            <a href="{{ route('consumer.orders.invoice', [$inv->id, 'pdf']) }}" class="px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded-xl font-bold text-xs flex items-center gap-1 transition-all" title="Descargar PDF DIAN">
                              <span class="material-symbols-outlined text-[15px]">picture_as_pdf</span> PDF
                            </a>
                          @endif
                          @if($inv->invoice_xml_path)
                            <a href="{{ route('consumer.orders.invoice', [$inv->id, 'xml']) }}" class="px-3 py-1.5 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 rounded-xl font-bold text-xs flex items-center gap-1 transition-all" title="Descargar XML UBL 2.1">
                              <span class="material-symbols-outlined text-[15px]">code</span> XML
                            </a>
                          @endif
                          @if(!$inv->invoice_pdf_path && !$inv->invoice_xml_path)
                            <a href="{{ route('consumer.orders.receipt', $inv->id) }}" class="px-3 py-1.5 bg-surface-container text-on-surface hover:bg-surface-container-high rounded-xl font-bold text-xs flex items-center gap-1 transition-all">
                              <span class="material-symbols-outlined text-[15px]">receipt</span> Recibo
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

      <!-- ========================================== -->
      <!-- TAB 6: LIBRETA DE DIRECCIONES -->
      <!-- ========================================== -->
      <div x-show="currentTab === 'addresses'" style="display: none;" class="space-y-6">
        <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-card border border-outline-variant/20">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">pin_drop</span> Mis Direcciones de Entrega
              </h2>
              <p class="text-xs text-on-surface-variant mt-1">Direcciones normalizadas en Fusagasugá y la Provincia del Sumapaz para cotización de flete exacto</p>
            </div>
            <a href="{{ route('consumer.addresses') }}" class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-sm hover:opacity-95 transition-all flex items-center gap-1">
              <span class="material-symbols-outlined text-sm">add</span> Gestionar Direcciones
            </a>
          </div>

          @if($addresses->isEmpty())
            <div class="text-center py-12 bg-surface-container-low rounded-2xl border-2 border-dashed border-outline-variant/30">
              <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2">home_pin</span>
              <p class="text-sm font-bold text-on-surface">No has guardado direcciones de entrega</p>
              <p class="text-xs text-on-surface-variant mt-1">Registra tu residencia o negocio en Fusagasugá para cotizar automáticamente en Envia.com durante el checkout.</p>
              <a href="{{ route('consumer.addresses') }}" class="mt-4 inline-block px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl shadow-sm">Agregar Dirección</a>
            </div>
          @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              @foreach($addresses as $addr)
                <div class="bg-surface-container-low p-4 rounded-2xl border {{ $addr->is_default ? 'border-primary shadow-sm bg-primary/5' : 'border-outline-variant/20' }} relative flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <span class="font-bold text-sm text-on-surface">{{ $addr->recipient_name }}</span>
                      @if($addr->is_default)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-primary text-white uppercase tracking-wider">Predeterminada</span>
                      @endif
                    </div>
                    <p class="text-xs text-on-surface font-medium">{{ $addr->street }}</p>
                    <p class="text-xs text-on-surface-variant">{{ $addr->city }}, {{ $addr->department }} - CP {{ $addr->postal_code }}</p>
                    <p class="text-xs text-on-surface-variant mt-1">Tel: {{ $addr->phone }}</p>
                  </div>
                  <div class="mt-4 pt-3 border-t border-outline-variant/20 flex justify-end">
                    <a href="{{ route('consumer.addresses') }}" class="text-xs font-bold text-primary hover:underline">Editar o cambiar</a>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 7: PERFIL PERSONAL Y SEGURIDAD -->
      <!-- ========================================== -->
      <div x-show="currentTab === 'profile'" style="display: none;" class="space-y-6">
        <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-card border border-outline-variant/20">
          <div class="mb-6">
            <h2 class="text-xl font-['Manrope'] font-black text-on-background flex items-center gap-2">
              <span class="material-symbols-outlined text-primary">manage_accounts</span> Datos Personales y Seguridad
            </h2>
            <p class="text-xs text-on-surface-variant mt-1">Actualiza tus credenciales, información de facturación y fotografía de perfil</p>
          </div>

          <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @if($errors->any())
              <div class="bg-error-container text-error p-4 rounded-2xl text-xs font-bold">
                <ul class="list-disc list-inside">
                  @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div class="flex items-center gap-6 pb-6 border-b border-outline-variant/20">
              <div class="w-20 h-20 rounded-2xl overflow-hidden bg-surface-container border-2 border-outline-variant/50 shrink-0 flex items-center justify-center shadow-inner">
                @if($user->avatarUrl())
                  <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                @else
                  <span class="material-symbols-outlined text-4xl text-on-surface-variant">person</span>
                @endif
              </div>
              <div class="flex-1">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Fotografía de Perfil (Avatar)</label>
                <input type="file" name="avatar" accept="image/*" class="block w-full text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:opacity-90 transition-all cursor-pointer">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Nombre Completo</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full bg-surface-container-low rounded-xl border border-outline-variant/30 p-3 text-xs font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all" required>
              </div>

              <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Correo Electrónico</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full bg-surface-container-low rounded-xl border border-outline-variant/30 p-3 text-xs font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all" required>
              </div>

              <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Teléfono Móvil (WhatsApp)</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="300 123 4567" class="w-full bg-surface-container-low rounded-xl border border-outline-variant/30 p-3 text-xs font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all">
              </div>

              <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Dirección de Envío Principal</label>
                <input type="text" name="address" value="{{ old('address', $user->address) }}" placeholder="Ej. Calle 11 # 6-25, Fusagasugá" class="w-full bg-surface-container-low rounded-xl border border-outline-variant/30 p-3 text-xs font-medium text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all">
              </div>
            </div>

            <div class="pt-4 flex justify-end">
              <button type="submit" class="px-6 py-3 bg-primary text-white font-bold text-xs rounded-xl hover:opacity-95 active:scale-95 transition-all shadow-md flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px]">save</span> Guardar Cambios
              </button>
            </div>
          </form>
        </div>
      </div>

    </main>
  </div>

  <!-- QR MODAL POPUP FOR O2O VOUCHERS -->
  <div x-show="qrModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="qrModalOpen = false">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-sm w-full shadow-2xl border border-outline-variant/20 text-center space-y-4" @click.stop>
      <div class="flex items-center justify-between">
        <span class="text-xs font-black uppercase tracking-wider text-orange-600 bg-orange-100 px-3 py-1 rounded-full">Voucher Presencial O2O</span>
        <button type="button" @click="qrModalOpen = false" class="p-1 rounded-full hover:bg-surface-container text-on-surface-variant">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>

      <div class="p-4 bg-surface-container-lowest rounded-2xl border-2 border-dashed border-orange-300 inline-block shadow-inner">
        <template x-if="activeQrData">
          <img :src="activeQrData.qr" alt="Voucher QR" class="w-48 h-48 mx-auto">
        </template>
      </div>

      <div>
        <h4 class="font-bold text-base text-on-background" x-text="activeQrData ? activeQrData.title : ''"></h4>
        <p class="text-xs text-on-surface-variant mt-0.5" x-text="activeQrData ? activeQrData.merchant : ''"></p>
        <p class="text-[11px] text-on-surface-variant/70 mt-2">Muestra este código al anfitrión o cajero para validar tu consumo o reserva inmediata.</p>
      </div>

      <button type="button" @click="window.print()" class="w-full py-2.5 bg-surface-container text-on-surface font-bold text-xs rounded-xl hover:bg-surface-container-high transition-all flex items-center justify-center gap-1.5">
        <span class="material-symbols-outlined text-sm">print</span> Imprimir Voucher
      </button>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Manejo de consulta en tiempo real de eventos Envia.com
  document.querySelectorAll('.js-live-track-btn').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      var trackUrl = btn.dataset.trackUrl;
      var extUrl = btn.dataset.externalUrl;
      var card = btn.closest('.bg-surface-container-low');
      var box = card.querySelector('.js-track-events-box');

      box.classList.remove('hidden');
      box.innerHTML = '<div class="flex items-center gap-2 text-primary font-bold"><span class="material-symbols-outlined animate-spin text-sm">sync</span> Sincronizando con Envia.com API...</div>';

      try {
        var resp = await fetch(trackUrl, {
          headers: { 'Accept': 'application/json' }
        });
        var data = await resp.json();

        if (!resp.ok) {
          box.innerHTML = '<div class="text-red-600 font-bold">' + (data.message || 'Sin novedades de rastreo disponibles.') + '</div>' +
            (extUrl ? '<a href="' + extUrl + '" target="_blank" class="mt-2 inline-block text-primary font-bold hover:underline">Consultar en transportadora &rarr;</a>' : '');
          return;
        }

        var html = '<div class="font-bold text-on-surface mb-2 flex items-center justify-between"><span>Estado: ' + (data.status || 'En tránsito') + '</span><span class="text-primary">' + (data.carrier || '').toUpperCase() + ' ' + (data.tracking_number || '') + '</span></div>';
        
        if (data.events && data.events.length > 0) {
          html += '<div class="space-y-1.5 border-t border-outline-variant/15 pt-2">';
          data.events.slice(0, 5).forEach(function (ev) {
            html += '<div class="flex items-start gap-2 text-on-surface-variant"><span class="material-symbols-outlined text-xs text-primary mt-0.5">adjust</span><div><p class="font-semibold text-on-surface text-[11px]">' + (ev.description || ev.status || 'Actualización') + '</p><p class="text-[10px] text-on-surface-variant/70">' + [ev.date, ev.time].filter(Boolean).join(' ') + '</p></div></div>';
          });
          html += '</div>';
        } else {
          html += '<p class="text-on-surface-variant">La guía se encuentra registrada y en camino al centro de distribución.</p>';
        }

        if (data.url) {
          html += '<a href="' + data.url + '" target="_blank" class="mt-3 inline-block font-bold text-primary text-[11px] hover:underline">Ver ficha completa en carrier &rarr;</a>';
        }

        box.innerHTML = html;
      } catch (err) {
        box.innerHTML = '<div class="text-red-600 font-bold">Error de conexión al consultar el servicio de Envia.com.</div>';
      }
    });
  });
});
</script>
@endpush
