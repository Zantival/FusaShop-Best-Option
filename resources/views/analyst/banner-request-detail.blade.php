@extends('layouts.app')
@section('title', 'Detalle de Solicitud de Banner')
@section('content')
<div class="max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">
  <div class="flex items-center gap-3">
    <a href="{{ route('analyst.banners') }}" class="text-slate-500 hover:text-emerald-600 transition-colors flex items-center justify-center p-2 rounded-full hover:bg-emerald-50">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <div>
      <h1 class="text-2xl md:text-3xl font-['Manrope'] font-bold text-slate-800 flex items-center gap-2">
        <span class="material-symbols-outlined text-emerald-600 text-3xl">campaign</span> Solicitud de Pauta Publicitaria
      </h1>
      <p class="text-xs md:text-sm text-slate-500">Revisa la pieza, el mensaje del comerciante, las rutas de destino preconfiguradas y aprueba la publicación.</p>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-slate-200/80 overflow-hidden">
    <!-- Encabezado con datos del comerciante y estado -->
    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
      <div>
        <div class="flex items-center gap-2">
          <h2 class="text-lg md:text-xl font-bold text-slate-800">
            Comercio: {{ $requestInfo->user->companyProfile->company_name ?? $requestInfo->user->name ?? 'Comerciante' }}
          </h2>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
            {{ $requestInfo->user->email }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
          Solicitud #{{ $requestInfo->id }} &bull; Radicada el {{ $requestInfo->created_at->format('d/m/Y, h:i A') }} &bull; Inversión: ${{ number_format($requestInfo->cost ?: 150000, 0, ',', '.') }} COP
        </p>
      </div>

      <div class="flex items-center gap-2">
        @if($requestInfo->status === 'pending')
          <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> PENDIENTE DE PUBLICACIÓN
          </span>
        @elseif($requestInfo->status === 'approved')
          <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">check_circle</span> APROBADA & PUBLICADA
          </span>
        @else
          <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-200">
            RECHAZADA
          </span>
        @endif
      </div>
    </div>
    
    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- COLUMNA IZQUIERDA: PROPUESTA VISUAL Y DATOS DE LA PAUTA -->
      <div class="space-y-6">
        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-emerald-600">image</span>
            Pieza Publicitaria Subida por el Comerciante
          </h3>
          @if($requestInfo->image_path)
            @php $isRequestVideo = in_array(pathinfo($requestInfo->image_path, PATHINFO_EXTENSION), ['mp4', 'webm', 'mov']); @endphp
            @if($isRequestVideo)
              <div class="border border-slate-200 rounded-2xl overflow-hidden bg-black aspect-[3/1] max-h-72 flex items-center justify-center shadow-sm">
                <video src="{{ Storage::url($requestInfo->image_path) }}" class="w-full h-full object-contain" controls autoplay loop muted></video>
              </div>
            @else
              <a href="{{ Storage::url($requestInfo->image_path) }}" target="_blank" class="block border border-slate-200 rounded-2xl overflow-hidden hover:opacity-95 transition-opacity shadow-sm bg-slate-900 aspect-[3/1] max-h-72">
                <img src="{{ Storage::url($requestInfo->image_path) }}" class="w-full h-full object-cover" alt="Banner Solicitado">
              </a>
            @endif
          @else
            <div class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 text-slate-400 italic text-sm">
              No se adjuntó propuesta publicitaria.
            </div>
          @endif
        </div>

        @if($requestInfo->title)
          <div>
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Título / Eslogan Sugerido</h3>
            <p class="text-sm font-bold text-slate-800 bg-slate-50 p-3 rounded-xl border border-slate-200">
              {{ $requestInfo->title }}
            </p>
          </div>
        @endif

        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1.5 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-emerald-600">description</span>
            Instrucciones y Mensaje del Comerciante para el Administrador
          </h3>
          <div class="p-4 bg-emerald-50/40 border border-emerald-200/80 rounded-2xl text-xs md:text-sm text-slate-700 leading-relaxed font-normal">
            {{ $requestInfo->notes ?: 'El comerciante no especificó instrucciones adicionales.' }}
          </div>
        </div>

        <!-- DESTINO SOLICITADO: TIENDA ENTERA, PRODUCTOS O TARJETA PROMOCIONAL ÚNICA -->
        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm text-emerald-600">alt_route</span>
            Modalidad y Productos Enviados por el Comerciante
          </h3>

          @if(in_array($requestInfo->target_type, ['products', 'promo_card'], true) && !empty($requestInfo->selected_products))
            <div class="p-4 rounded-2xl border {{ $requestInfo->target_type === 'promo_card' ? 'border-orange-200 bg-orange-50/30' : 'border-blue-200 bg-blue-50/30' }} space-y-3">
              <div class="flex items-center justify-between flex-wrap gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $requestInfo->target_type === 'promo_card' ? 'bg-orange-100 text-orange-800 border-orange-200' : 'bg-blue-100 text-blue-800 border-blue-200' }} border flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs">{{ $requestInfo->target_type === 'promo_card' ? 'loyalty' : 'local_offer' }}</span>
                  {{ $requestInfo->target_type === 'promo_card' ? 'Tarjeta Promocional Única' : 'Banner de Productos' }} ({{ count($requestInfo->selected_products) }} productos)
                </span>
                <span class="text-[11px] font-semibold text-slate-500">Al dar clic llevará a estos productos</span>
              </div>

              <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                @foreach($requestInfo->selected_products as $p)
                  <div class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200/80 text-xs shadow-2xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                      @if(!empty($p['image']))
                        <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="w-9 h-9 rounded-lg object-cover shrink-0 border border-slate-200">
                      @else
                        <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0 text-slate-400">
                          <span class="material-symbols-outlined text-sm">inventory_2</span>
                        </div>
                      @endif
                      <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                          <p class="font-bold text-slate-800 truncate">{{ $p['name'] }}</p>
                          @if(!empty($p['discount_percent']))
                            <span class="px-1.5 py-0.2 bg-rose-100 text-rose-700 rounded text-[9px] font-black">-{{ $p['discount_percent'] }}% OFF</span>
                          @endif
                        </div>
                        <div class="flex items-center gap-2">
                          @if(!empty($p['original_price']) && $p['original_price'] > ($p['price'] ?? 0))
                            <span class="text-[10px] text-slate-400 line-through">${{ number_format($p['original_price'], 0, ',', '.') }}</span>
                          @endif
                          <span class="text-[11px] font-bold text-emerald-600">${{ number_format($p['price'] ?? 0, 0, ',', '.') }} COP</span>
                        </div>
                      </div>
                    </div>
                    <a href="{{ $p['url'] ?? route('consumer.product', $p['id']) }}" target="_blank" class="text-emerald-700 hover:text-emerald-900 text-[11px] font-bold flex items-center gap-0.5 shrink-0 pl-2">
                      Ver <span class="material-symbols-outlined text-xs">open_in_new</span>
                    </a>
                  </div>
                @endforeach
              </div>
            </div>
          @else
            <div class="p-4 rounded-2xl border border-emerald-200 bg-emerald-50/30 space-y-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">storefront</span>
                Enlace Directo a la Tienda Oficial
              </span>
              <p class="text-xs text-slate-600 font-medium">
                Al darle clic al banner llevará directamente a la tienda de {{ $requestInfo->user->companyProfile->company_name ?? $requestInfo->user->name }}.
              </p>
              <div class="text-[11px] font-mono text-slate-600 bg-white p-2 rounded-xl border border-slate-200/80 truncate">
                {{ route('consumer.merchant.profile', $requestInfo->user_id) }}
              </div>
            </div>
          @endif
        </div>
      </div>

      <!-- COLUMNA DERECHA: ESTADO DE PAGO Y VINCULACIÓN / PUBLICACIÓN -->
      <div class="space-y-6">
        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-2">Estado del Pago en Línea</h3>
          <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl font-bold text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-emerald-600 text-lg">verified</span>
              <span>Pago Seguro Verificado (${{ number_format($requestInfo->cost ?: 150000, 0, ',', '.') }} COP)</span>
            </div>
            <span class="text-[10px] font-mono opacity-80">{{ $requestInfo->payment_proof_path }}</span>
          </div>
        </div>

        @php
          $preselectedIds = is_array($requestInfo->selected_products) ? array_column($requestInfo->selected_products, 'id') : [];
        @endphp

        <div class="pt-2">
          <form method="POST" action="{{ route('analyst.banner-requests.approve', $requestInfo->id) }}" class="space-y-5"
                x-data="{
                  publishAs: '{{ $requestInfo->target_type === 'promo_card' ? 'promo_card' : 'banner' }}',
                  linkChoice: '{{ in_array($requestInfo->target_type, ['products', 'promo_card'], true) && !empty($requestInfo->selected_products) ? 'product' : 'store' }}',
                  selectedProductUrl: '{{ $requestInfo->target_url ?: route('consumer.merchant.profile', $requestInfo->user_id) }}'
                }">
            @csrf

            @if($requestInfo->status === 'pending')
              <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-4">
                <!-- Selector: Publicar como Banner o como Tarjeta Promocional Única -->
                <div>
                  <label class="block text-xs font-extrabold text-slate-800 mb-2">¿Dónde deseas publicar esta solicitud?</label>
                  <div class="grid grid-cols-2 gap-2.5">
                    <label class="p-3 rounded-xl border bg-white cursor-pointer text-xs font-bold flex items-center gap-2"
                           :class="publishAs === 'banner' ? 'border-emerald-600 bg-emerald-50/30 text-emerald-900' : 'border-slate-200 text-slate-600'">
                      <input type="radio" name="publish_as" value="banner" x-model="publishAs" class="text-emerald-600">
                      <span>Banner Vitrina (1-8)</span>
                    </label>
                    <label class="p-3 rounded-xl border bg-white cursor-pointer text-xs font-bold flex items-center gap-2"
                           :class="publishAs === 'promo_card' ? 'border-orange-500 bg-orange-50/30 text-orange-900' : 'border-slate-200 text-slate-600'">
                      <input type="radio" name="publish_as" value="promo_card" x-model="publishAs" class="text-orange-600">
                      <span>Tarjeta Promocional Única</span>
                    </label>
                  </div>
                </div>

                <!-- Vincular Productos en Descuento del Comerciante -->
                @if(isset($merchantProducts) && $merchantProducts->isNotEmpty())
                  <div class="pt-2 border-t border-slate-200/60 space-y-2">
                    <label class="block text-xs font-extrabold text-slate-800">
                      Vincular productos del comerciante (al hacer clic llevará a estos productos):
                    </label>
                    <div class="max-h-44 overflow-y-auto space-y-1.5 border border-slate-200 rounded-xl p-2 bg-white">
                      @foreach($merchantProducts as $mp)
                        <label class="flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-xs">
                          <div class="flex items-center gap-2 min-w-0">
                            <input type="checkbox" name="product_ids[]" value="{{ $mp->id }}"
                                   {{ in_array($mp->id, $preselectedIds) ? 'checked' : '' }}
                                   class="rounded text-emerald-600">
                            <span class="font-bold text-slate-800 truncate">{{ $mp->name }}</span>
                            @if($mp->hasDiscount())
                              <span class="px-1.5 py-0.2 bg-rose-100 text-rose-700 rounded text-[9px] font-black">-{{ $mp->discount_percent }}% OFF</span>
                            @endif
                          </div>
                          <span class="text-[10px] font-extrabold text-emerald-700 shrink-0">${{ number_format($mp->effectivePrice(), 0, ',', '.') }}</span>
                        </label>
                      @endforeach
                    </div>
                  </div>
                @endif

                <h4 class="text-xs font-extrabold text-slate-800 flex items-center gap-1.5 pt-2 border-t border-slate-200/60">
                  <span class="material-symbols-outlined text-emerald-600 text-base">link</span>
                  Enlace de Redirección al Hacer Clic
                </h4>

                <div class="space-y-2">
                  <!-- Opción Tienda -->
                  <label class="flex items-start gap-2.5 p-2.5 rounded-xl border bg-white cursor-pointer transition-colors"
                         :class="linkChoice === 'store' ? 'border-emerald-500 bg-emerald-50/20' : 'border-slate-200'">
                    <input type="radio" name="link_type" value="profile" x-model="linkChoice"
                           class="text-emerald-600 focus:ring-emerald-500 h-4 w-4 border-slate-300 mt-0.5">
                    <div class="min-w-0 flex-1">
                      <span class="font-bold text-xs text-slate-800 block">Directamente a la Tienda del Comerciante</span>
                      <span class="text-[10px] text-slate-400 font-mono truncate block">{{ route('consumer.merchant.profile', $requestInfo->user_id) }}</span>
                    </div>
                  </label>

                  <!-- Opción Productos seleccionados -->
                  <label class="flex items-start gap-2.5 p-2.5 rounded-xl border bg-white cursor-pointer transition-colors"
                         :class="linkChoice === 'product' ? 'border-emerald-500 bg-emerald-50/20' : 'border-slate-200'">
                    <input type="radio" name="link_type" value="product" x-model="linkChoice"
                           class="text-emerald-600 focus:ring-emerald-500 h-4 w-4 border-slate-300 mt-0.5">
                    <div class="min-w-0 flex-1">
                      <span class="font-bold text-xs text-slate-800 block">Directamente a los Productos Vinculados en Descuento</span>
                      <span class="text-[10px] text-slate-400 font-mono truncate block">{{ $requestInfo->target_url }}</span>
                    </div>
                  </label>

                  <!-- Opción Personalizada -->
                  <label class="flex items-start gap-2.5 p-2.5 rounded-xl border bg-white cursor-pointer transition-colors"
                         :class="linkChoice === 'custom' ? 'border-emerald-500 bg-emerald-50/20' : 'border-slate-200'">
                    <input type="radio" name="link_type" value="custom" x-model="linkChoice"
                           class="text-emerald-600 focus:ring-emerald-500 h-4 w-4 border-slate-300 mt-0.5">
                    <div class="min-w-0 flex-1">
                      <span class="font-bold text-xs text-slate-800 block">Enlace Personalizado Manual</span>
                      <div class="mt-1.5" x-show="linkChoice === 'custom'" x-cloak>
                        <input type="url" name="custom_link_url" placeholder="https://fusashop.com/..."
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs focus:ring-2 focus:ring-emerald-500/40 focus:outline-none">
                      </div>
                    </div>
                  </label>
                </div>

                <!-- Título del Banner / Tarjeta a publicar -->
                <div class="pt-2 border-t border-slate-200/60">
                  <label class="block text-xs font-bold text-slate-700 mb-1">Título en la Vitrina Principal</label>
                  <input type="text" name="banner_title"
                         value="{{ $requestInfo->title ?: ('Promoción - ' . ($requestInfo->user->companyProfile->company_name ?? $requestInfo->user->name)) }}"
                         class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500/40 focus:outline-none font-semibold text-slate-800">
                </div>
              </div>

              <!-- Botones de Acción para el Administrador -->
              <div class="pt-2 flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        class="flex-1 py-3 px-5 bg-[#10B981] hover:bg-[#047857] text-white font-black text-xs md:text-sm rounded-xl shadow-md hover:shadow-lg active:scale-98 transition-all flex items-center justify-center gap-2 cursor-pointer">
                  <span class="material-symbols-outlined text-lg">verified</span>
                  <span x-text="publishAs === 'promo_card' ? 'Vincular Productos y Publicar Tarjeta Promocional' : 'Aprobar y Publicar Banner en Vitrina'"></span>
                </button>
              </div>
            @else
              <div class="p-4 bg-slate-100 rounded-xl text-center text-xs font-bold text-slate-500">
                Esta solicitud ya ha sido completada y procesada.
              </div>
            @endif
          </form>

          @if($requestInfo->status === 'pending')
            <!-- Formulario de Rechazo Opcional -->
            <form method="POST" action="{{ route('analyst.banner-requests.reject', $requestInfo->id) }}" class="mt-3 pt-3 border-t border-slate-200/60">
              @csrf
              <div class="flex items-center gap-2">
                <input type="text" name="rejection_reason" placeholder="Motivo de rechazo si la pieza no cumple requisitos..." required
                       class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/30 text-slate-800">
                <button type="submit" onclick="return confirm('¿Rechazar esta solicitud de banner?')"
                        class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 transition-colors shrink-0">
                  Rechazar
                </button>
              </div>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
