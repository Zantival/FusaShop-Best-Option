@extends('layouts.app')
@section('title', 'Solicitar Pauta Publicitaria | FusaShop')

@section('content')
<div class="max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6"
     x-data="{
       previewUrl: null,
       isVideo: false,
       targetType: 'store',
       onlyDiscounted: false,
       searchQuery: '',
       selectedProducts: [],
       cardNumber: '',
       cardExpiry: '',
       cardCvc: '',
       cardHolder: '{{ addslashes($merchant->name) }}',
       handleFile(e) {
         const file = e.target.files[0];
         if (!file) {
           this.previewUrl = null;
           return;
         }
         this.isVideo = file.type.startsWith('video');
         this.previewUrl = URL.createObjectURL(file);
       },
       toggleProduct(id) {
         const idx = this.selectedProducts.indexOf(id);
         if (idx > -1) {
           this.selectedProducts.splice(idx, 1);
         } else {
           this.selectedProducts.push(id);
         }
       },
       formatCardNumber(val) {
         let v = val.replace(/\D/g, '').slice(0, 16);
         return v.replace(/(\d{4})/g, '$1 ').trim();
       },
       formatExpiry(val) {
         let v = val.replace(/\D/g, '').slice(0, 4);
         if (v.length >= 3) return v.slice(0, 2) + '/' + v.slice(2);
         return v;
       }
     }">

  <!-- HEADER -->
  <div class="bg-white p-6 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">campaign</span> Pauta Destacada Sumapaz
        </span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
          Vigencia 30 Días
        </span>
      </div>
      <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-slate-800">
        Solicitar Pauta Publicitaria (Banner Principal)
      </h1>
      <p class="text-slate-500 text-xs md:text-sm mt-1">
        Sube la pieza publicitaria, escribe las instrucciones para el administrador, elige hacia dónde dirigir a tus clientes (tienda entera o productos en oferta) y realiza el pago seguro para su publicación.
      </p>
    </div>

    <a href="{{ route('merchant.dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 self-start md:self-auto">
      <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Tablero
    </a>
  </div>

  @if($errors->any())
    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold space-y-1 shadow-sm">
      <div class="flex items-center gap-2 font-bold mb-1">
        <span class="material-symbols-outlined text-base text-rose-600">error</span>
        Por favor corrige los siguientes inconvenientes:
      </div>
      <ul class="list-disc pl-5 space-y-0.5">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('merchant.banner.request.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <!-- PASO 1: IMAGEN O VIDEO DE LA PUBLICACIÓN (LO PRIMERO QUE DEBE SALIR) -->
    <div class="bg-white rounded-2xl p-6 shadow-[0_8px_24px_rgba(0,0,0,0.04)] border border-slate-200/80 space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">1</span>
          <div>
            <h2 class="font-bold text-slate-800 text-base md:text-lg">Imagen o Video de la Publicación *</h2>
            <p class="text-slate-400 text-xs">Sube la pieza que se mostrará en el carrusel principal de FusaShop.</p>
          </div>
        </div>
        <span class="text-[11px] font-semibold text-slate-400 hidden sm:inline">Proporción panorámica recomendada (3:1)</span>
      </div>

      <!-- Zona de carga de archivo -->
      <div>
        <label class="block cursor-pointer">
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" required
                 @change="handleFile($event)"
                 class="hidden">
          
          <div x-show="!previewUrl"
               class="border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:bg-emerald-50/20 rounded-2xl p-8 text-center transition-all duration-200 flex flex-col items-center justify-center gap-3 group">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
              <span class="material-symbols-outlined text-3xl">add_photo_alternate</span>
            </div>
            <div>
              <p class="font-bold text-slate-700 text-sm">Haz clic aquí para seleccionar tu imagen o video promocional</p>
              <p class="text-xs text-slate-400 mt-1">Formatos admitidos: JPG, PNG, WEBP, MP4, WEBM o MOV (máx. 10MB)</p>
            </div>
            <span class="px-4 py-1.5 rounded-xl bg-emerald-600 text-white font-bold text-xs shadow-sm hover:bg-emerald-700 transition-colors">
              Explorar Archivos
            </span>
          </div>

          <!-- Previsualización en vivo -->
          <div x-show="previewUrl" x-cloak style="display: none;" class="space-y-3">
            <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 aspect-[3/1] max-h-72 flex items-center justify-center shadow-inner">
              <template x-if="!isVideo">
                <img :src="previewUrl" alt="Previsualización del Banner" class="w-full h-full object-cover">
              </template>
              <template x-if="isVideo">
                <video :src="previewUrl" controls class="w-full h-full object-contain"></video>
              </template>
              <div class="absolute top-3 right-3 bg-slate-900/80 backdrop-blur-md text-white text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1">
                <span class="material-symbols-outlined text-xs text-emerald-400">check_circle</span>
                Vista Previa de la Pieza
              </div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500">
              <span>¿Deseas cambiar el archivo? Haz clic sobre el banner o pulsa cambiar.</span>
              <span class="font-bold text-emerald-600 hover:underline">Cambiar Archivo</span>
            </div>
          </div>
        </label>
      </div>
    </div>

    <!-- PASO 2: DESCRIPCIÓN E INSTRUCCIONES PARA EL ADMINISTRADOR -->
    <div class="bg-white rounded-2xl p-6 shadow-[0_8px_24px_rgba(0,0,0,0.04)] border border-slate-200/80 space-y-4">
      <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
        <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">2</span>
        <div>
          <h2 class="font-bold text-slate-800 text-base md:text-lg">Descripción e Instrucciones para el Administrador *</h2>
          <p class="text-slate-400 text-xs">Indica el título o eslogan y las indicaciones exactas de lo que deseas que publique el administrador.</p>
        </div>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
            Título / Eslogan del Banner (Opcional)
          </label>
          <input type="text" name="title" value="{{ old('title') }}"
                 placeholder="Ej: Gran Feria de Plantas & Orquídeas del Sumapaz — 25% OFF"
                 class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 text-slate-800 placeholder-slate-400 font-medium">
          <p class="text-[11px] text-slate-400 mt-1">Este titular se usará en la etiqueta del banner y en las notificaciones a los consumidores.</p>
        </div>

        <div>
          <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
            ¿Qué deseas que comunique el Administrador en la publicación? *
          </label>
          <textarea name="description" rows="4" required
                    placeholder="Escribe aquí las instrucciones detalladas para el administrador. Por ejemplo: 'Por favor publicar este banner en el carrusel de inicio, destacando que tenemos 20% de descuento en la colección de calzado hasta este domingo y que contamos con envíos gratis en Fusagasugá'."
                    class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 text-slate-800 placeholder-slate-400 font-normal leading-relaxed">{{ old('description') }}</textarea>
          <p class="text-[11px] text-slate-400 mt-1">El administrador leerá estas notas para programar y redactar cualquier texto complementario de tu pauta publicitaria.</p>
        </div>
      </div>
    </div>

    <!-- PASO 3: TIPO DE PAUTA Y VINCULACIÓN (BANNER A TIENDA O TARJETA PROMOCIONAL DE PRODUCTOS EN DESCUENTO) -->
    <div class="bg-white rounded-2xl p-6 shadow-[0_8px_24px_rgba(0,0,0,0.04)] border border-slate-200/80 space-y-5">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">3</span>
          <div>
            <h2 class="font-bold text-slate-800 text-base md:text-lg">Modalidad de Pauta: Banner a tu Tienda o Tarjeta Promocional de Productos *</h2>
            <p class="text-slate-400 text-xs">Elige si deseas publicar un Banner que lleve directo a tu tienda o una Tarjeta Promocional Única vinculada a tus productos en descuento.</p>
          </div>
        </div>
      </div>

      <!-- Selector tipo de pauta: Banner Tienda vs Tarjeta Promocional Única vs Banner Productos -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Opción 1: Banner Publicitario directo a la Tienda -->
        <label class="relative rounded-2xl border-2 p-5 cursor-pointer transition-all flex flex-col justify-between gap-3"
               :class="targetType === 'store' ? 'border-emerald-500 bg-emerald-50/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                   :class="targetType === 'store' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'">
                <span class="material-symbols-outlined text-xl">storefront</span>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm">Banner Directo a mi Tienda</h3>
                <p class="text-[11px] text-slate-500">{{ $merchant->companyProfile->company_name ?? $merchant->name }}</p>
              </div>
            </div>
            <input type="radio" name="target_type" value="store" x-model="targetType" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 mt-1">
          </div>
          <div class="text-[11px] text-slate-500 bg-white/80 p-2 rounded-xl border border-slate-200/60 flex items-center gap-1.5 overflow-hidden">
            <span class="material-symbols-outlined text-xs text-emerald-600 shrink-0">link</span>
            <span class="truncate font-mono text-[10px]">{{ $storeUrl }}</span>
          </div>
          <p class="text-[11px] text-slate-400">El analista recibe la foto del banner y el enlace para que al darle clic lleve al consumidor directamente a tu tienda.</p>
        </label>

        <!-- Opción 2: Tarjeta Promocional Única para Productos en Descuento -->
        <label class="relative rounded-2xl border-2 p-5 cursor-pointer transition-all flex flex-col justify-between gap-3"
               :class="targetType === 'promo_card' ? 'border-orange-500 bg-orange-50/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                   :class="targetType === 'promo_card' ? 'bg-[#F97316] text-white' : 'bg-slate-100 text-slate-600'">
                <span class="material-symbols-outlined text-xl">loyalty</span>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm">Tarjeta Promocional Única</h3>
                <p class="text-[11px] text-orange-700 font-semibold">Para tus productos en descuento</p>
              </div>
            </div>
            <input type="radio" name="target_type" value="promo_card" x-model="targetType" class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-slate-300 mt-1">
          </div>
          <div class="text-[11px] text-slate-500 bg-white/80 p-2 rounded-xl border border-slate-200/60 flex items-center justify-between">
            <span class="font-bold text-orange-700" x-text="selectedProducts.length + ' producto(s) vinculado(s)'"></span>
            <span class="px-1.5 py-0.5 bg-orange-100 text-orange-800 rounded text-[9px] font-black">TARJETA ÚNICA</span>
          </div>
          <p class="text-[11px] text-slate-400">Envía la foto del producto(s) y marca tus productos en descuento para que el analista los vincule y al hacer clic lleve directo a ellos.</p>
        </label>

        <!-- Opción 3: Banner Publicitario para Productos Específicos -->
        <label class="relative rounded-2xl border-2 p-5 cursor-pointer transition-all flex flex-col justify-between gap-3"
               :class="targetType === 'products' ? 'border-emerald-500 bg-emerald-50/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                   :class="targetType === 'products' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'">
                <span class="material-symbols-outlined text-xl">local_offer</span>
              </div>
              <div>
                <h3 class="font-bold text-slate-800 text-sm">Banner a Productos</h3>
                <p class="text-[11px] text-slate-500">Enlace directo a productos</p>
              </div>
            </div>
            <input type="radio" name="target_type" value="products" x-model="targetType" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 mt-1">
          </div>
          <div class="text-[11px] text-slate-500 bg-white/80 p-2 rounded-xl border border-slate-200/60 flex items-center justify-between">
            <span class="font-bold text-emerald-700" x-text="selectedProducts.length + ' producto(s) seleccionado(s)'"></span>
            <span class="text-slate-400 text-[10px]">Banner Carrusel</span>
          </div>
          <p class="text-[11px] text-slate-400">Se publica en los espacios de banners (arriba o abajo) llevando a los productos seleccionados.</p>
        </label>
      </div>

      <!-- Selector de Productos en Descuento (Visible si targetType === 'promo_card' o 'products') -->
      <div x-show="targetType === 'products' || targetType === 'promo_card'" x-cloak style="display: none;" x-transition class="pt-4 border-t border-slate-100 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div>
            <h4 class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
              <span class="material-symbols-outlined text-emerald-600 text-base">sell</span>
              <span x-text="targetType === 'promo_card' ? 'Selecciona los productos en descuento para tu Tarjeta Promocional Única:' : 'Selecciona los productos para vincular al Banner:'"></span>
            </h4>
            <p class="text-slate-400 text-xs">El analista recibirá esta lista junto con tu foto para vincularlos directamente.</p>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-700 bg-rose-50 px-3 py-1.5 rounded-xl border border-rose-200 cursor-pointer">
              <input type="checkbox" x-model="onlyDiscounted" class="rounded text-rose-600 focus:ring-rose-500">
              <span>Solo con % Descuento ({{ isset($discountedProducts) ? $discountedProducts->count() : 0 }})</span>
            </label>

            <!-- Buscador de productos -->
            <div class="relative w-full sm:w-56">
              <span class="material-symbols-outlined absolute left-3 top-2 text-slate-400 text-sm">search</span>
              <input type="text" x-model="searchQuery" placeholder="Buscar producto..."
                     class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
            </div>
          </div>
        </div>

        @if($products->isEmpty())
          <div class="p-6 text-center bg-slate-50 rounded-2xl border border-slate-200">
            <span class="material-symbols-outlined text-3xl text-slate-400 mb-1">inventory_2</span>
            <p class="text-sm font-bold text-slate-700">Aún no tienes productos activos registrados.</p>
            <p class="text-xs text-slate-500 mt-1">Crea productos y asígnales un % de descuento en tu inventario o utiliza la opción "Banner Directo a mi Tienda".</p>
          </div>
        @else
          <div class="max-h-80 overflow-y-auto space-y-2 pr-1 border border-slate-200/60 rounded-2xl p-2.5 bg-slate-50/50">
            @foreach($products as $p)
              @php
                $hasDisc = $p->hasDiscount();
                $discPct = (int) ($p->discount_percent ?? 0);
              @endphp
              <div class="flex items-center justify-between p-3 rounded-xl hover:bg-white transition-colors bg-white border {{ $hasDisc ? 'border-emerald-200/80' : 'border-slate-100' }}"
                   x-show="(!onlyDiscounted || {{ $hasDisc ? 'true' : 'false' }}) && (!searchQuery || '{{ strtolower(addslashes($p->name)) }}'.includes(searchQuery.toLowerCase()))"
                   x-cloak>
                <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                  <input type="checkbox" name="product_ids[]" value="{{ $p->id }}"
                         @change="toggleProduct({{ $p->id }})"
                         class="h-4 w-4 text-emerald-600 rounded focus:ring-emerald-500 border-slate-300">
                  
                  <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center relative">
                    @if($p->image_url)
                      <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover">
                    @else
                      <span class="material-symbols-outlined text-slate-400 text-xl">image</span>
                    @endif
                    @if($hasDisc)
                      <span class="absolute top-0 right-0 bg-[#ba1a1a] text-white text-[8px] font-black px-1 rounded-bl">
                        -{{ $discPct }}%
                      </span>
                    @endif
                  </div>

                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <p class="font-bold text-slate-800 text-xs truncate">{{ $p->name }}</p>
                      @if($hasDisc)
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-100 text-rose-700 shrink-0">
                          -{{ $discPct }}% OFF
                        </span>
                      @endif
                    </div>
                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                      @if($hasDisc)
                        <span class="text-[10px] text-slate-400 line-through font-semibold">Precio real: ${{ number_format($p->price, 0, ',', '.') }}</span>
                        <span class="text-[11px] font-extrabold text-emerald-600">Con descuento: ${{ number_format($p->effectivePrice(), 0, ',', '.') }} COP</span>
                      @else
                        <span class="text-[11px] font-extrabold text-slate-700">${{ number_format($p->price, 0, ',', '.') }} COP</span>
                      @endif
                      <span class="text-[10px] text-slate-400 font-mono truncate">· {{ $p->category }}</span>
                    </div>
                  </div>
                </label>

                <div class="shrink-0 text-right pl-3">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600">
                    Stock: {{ $p->stock }}
                  </span>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    <!-- PASO 4: RESUMEN DE LA INVERSIÓN Y PAGO EN LÍNEA SEGURO (Y AHÍ SÍ PROCEDER AL PAGO) -->
    <div class="bg-white rounded-2xl p-6 shadow-[0_8px_24px_rgba(0,0,0,0.04)] border border-slate-200/80 space-y-5">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2.5">
          <span class="w-7 h-7 rounded-full bg-emerald-600 text-white font-extrabold text-xs flex items-center justify-center">4</span>
          <div>
            <h2 class="font-bold text-slate-800 text-base md:text-lg">Resumen de Inversión & Pago en Línea Seguro *</h2>
            <p class="text-slate-400 text-xs">Una vez verificado el pago en línea, la solicitud pasará al administrador para su publicación inmediata.</p>
          </div>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-emerald-700 font-bold bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
          <span class="material-symbols-outlined text-sm">lock</span> Transacción Cifrada SSL
        </div>
      </div>

      <!-- Resumen de Costos -->
      <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="space-y-0.5">
          <p class="text-xs font-bold text-slate-700">Espacio Publicitario Banner Destacado (Página Principal)</p>
          <p class="text-[11px] text-slate-500">Exposición prioritaria para compradores de Fusagasugá y el Sumapaz por 30 días continuos.</p>
        </div>
        <div class="text-right shrink-0">
          <span class="text-[10px] uppercase font-bold text-slate-400 block">Total a Pagar</span>
          <span class="text-xl md:text-2xl font-black text-emerald-600 font-['Manrope']">
            ${{ number_format($bannerFee, 0, ',', '.') }} <span class="text-xs font-bold text-slate-500">COP</span>
          </span>
        </div>
      </div>

      <!-- Formulario de Tarjeta de Crédito / Débito / Pasarela Wompi -->
      <div class="p-5 border border-slate-200 rounded-2xl bg-gradient-to-br from-slate-50/80 to-white space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600">credit_card</span>
            <span class="font-bold text-slate-800 text-sm">Pago con Tarjeta de Crédito / Débito · Pasarela Wompi</span>
          </div>
          <div class="flex items-center gap-1.5 opacity-80">
            <span class="px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200 text-[10px] font-extrabold text-emerald-800">Wompi SHA-256</span>
            <span class="text-[10px] font-bold text-slate-400">Visa / Mastercard / PSE</span>
          </div>
        </div>

        <input type="hidden" name="payment_mode" id="banner_payment_mode" value="wompi_card">

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Nombre del Titular de la Tarjeta *</label>
            <input type="text" name="card_holder" x-model="cardHolder"
                   placeholder="Como aparece en la tarjeta"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 text-slate-800">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Número de Tarjeta *</label>
            <input type="text" name="card_number" x-model="cardNumber" @input="cardNumber = formatCardNumber($event.target.value)" maxlength="19"
                   placeholder="4000 1234 5678 9010"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 font-mono text-slate-800">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-600 mb-1">Vencimiento (MM/AA) *</label>
              <input type="text" name="card_expiry" x-model="cardExpiry" @input="cardExpiry = formatExpiry($event.target.value)" maxlength="5"
                     placeholder="12/28"
                     class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 font-mono text-slate-800 text-center">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-600 mb-1">Código de Seguridad (CVC) *</label>
              <input type="password" name="card_cvc" x-model="cardCvc" maxlength="4"
                     placeholder="123"
                     class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/40 font-mono text-slate-800 text-center">
            </div>
          </div>
        </div>

        <p class="text-[11px] text-slate-400 mt-2 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-xs text-emerald-600">verified_user</span>
          El pago se procesa y concilia con firma de integridad SHA-256 de Wompi Bancolombia.
        </p>
      </div>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div class="flex flex-col sm:flex-row items-center gap-4 pt-2">
      <a href="{{ route('merchant.dashboard') }}" class="w-full sm:w-auto px-6 py-3 font-bold text-xs text-slate-500 hover:text-slate-800 rounded-xl transition-colors text-center">
        Cancelar
      </a>
      <button type="submit"
              onclick="document.getElementById('banner_payment_mode').value='wompi_card'"
              class="w-full sm:flex-1 py-3.5 px-6 bg-[#10B981] hover:bg-[#047857] text-white font-black text-sm rounded-xl shadow-lg hover:shadow-xl active:scale-98 transition-all flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-lg">check_circle</span>
        Pagar ${{ number_format($bannerFee, 0, ',', '.') }} COP y Solicitar Pauta Publicitaria
      </button>
      <button type="submit"
              onclick="document.getElementById('banner_payment_mode').value='wompi_checkout'"
              class="w-full sm:w-auto py-3.5 px-5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-1.5">
        <span class="material-symbols-outlined text-base text-emerald-400">open_in_new</span>
        Ir a Pasarela Wompi (PSE / Nequi)
      </button>
    </div>
  </form>
</div>
@endsection
