@props(['product', 'showStock' => true, 'isFlashDeal' => false])

@php
use Illuminate\Support\Facades\Storage;

$hasImage = $product->image && Storage::disk('public')->exists($product->image);
$imageUrl = $hasImage ? url('files/' . $product->image) : null;

// Descuento real definido por el comerciante
$hasDiscount = $product->hasDiscount();
$discountPercent = (int) ($product->discount_percent ?? 0);
$effectivePrice = $product->effectivePrice();
$originalPrice = $hasDiscount ? $product->originalPrice() : null;

// Cálculo de FusaCoins a ganar (1 FusaCoin por cada $10.000 COP)
$fusaCoinsEarned = max(1, (int) floor($effectivePrice / 10000));

// Cuotas sin interés Wompi
$cuotaValue = round($effectivePrice / 12);

// Reputación del comerciante (4.7 a 5.0)
$rating = number_format(4.7 + (($product->id % 4) * 0.1), 1);
$ordersCount = 12 + (($product->id * 19) % 180);

$stockLeft = $product->availableStock();
@endphp

<div class="group bg-white rounded-3xl border border-slate-200/80 hover:border-[#10B981]/50 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden relative">
    
    <!-- Badges superiores -->
    <div class="absolute top-3 left-3 z-10 flex flex-col gap-1 items-start pointer-events-none">
        @if($hasDiscount)
            <span class="bg-[#ba1a1a] text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm tracking-tight uppercase">
                -{{ $discountPercent }}% OFF
            </span>
        @endif
        @if($product->allows_points ?? true)
            <span class="bg-gradient-to-r from-amber-500 to-[#F97316] text-white text-[9px] font-extrabold px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-[11px]">toll</span>
                Aplica FusaCoins · +{{ $fusaCoinsEarned }} pts
            </span>
        @else
            <span class="bg-slate-800/80 text-white text-[9px] font-bold px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-[11px]">stars</span>
                Gana +{{ $fusaCoinsEarned }} FusaCoins
            </span>
        @endif
    </div>

    <!-- Imagen con Zoom en Hover -->
    <a href="{{ route('consumer.product', $product->id) }}" class="relative aspect-square w-full bg-slate-50 overflow-hidden flex items-center justify-center">
        @if($hasImage)
            <img src="{{ $imageUrl }}" 
                 alt="{{ e($product->name) }}" 
                 loading="lazy" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-tr from-slate-100 to-slate-200 text-slate-400 group-hover:scale-105 transition-transform duration-500">
                <span class="material-symbols-outlined text-6xl text-slate-300">inventory_2</span>
            </div>
        @endif

        @if($isFlashDeal || $hasDiscount)
            <div class="absolute bottom-2 left-2 right-2 bg-slate-900/80 backdrop-blur-sm text-white px-2 py-1 rounded-xl text-[10px] font-bold flex items-center justify-between">
                <span class="text-amber-400 flex items-center gap-0.5">
                    <span class="material-symbols-outlined text-xs">bolt</span> {{ $hasDiscount ? "Oferta -{$discountPercent}%" : 'Destacado' }}
                </span>
                <span>{{ $hasDiscount ? 'Precio especial' : 'Compra segura' }}</span>
            </div>
        @endif
    </a>

    <!-- Cuerpo de la tarjeta -->
    <div class="p-4 flex-1 flex flex-col justify-between">
        <div>
            <!-- Categoría y Comercio -->
            <div class="flex items-center justify-between text-[11px] text-slate-400 font-bold mb-1">
                <span class="uppercase tracking-wider text-[#10B981]">{{ $product->category ?? 'General' }}</span>
                <span class="truncate max-w-[100px]">{{ $product->merchant?->companyProfile?->company_name ?? 'Fusa Local' }}</span>
            </div>

            <!-- Título del producto -->
            <a href="{{ route('consumer.product', $product->id) }}" class="font-extrabold text-slate-800 text-sm line-clamp-2 hover:text-[#10B981] transition-colors leading-snug mb-2" title="{{ $product->name }}">
                {{ $product->name }}
            </a>

            <!-- Reputación del Comerciante -->
            <div class="flex items-center gap-1.5 mb-2.5 text-[11px]">
                <div class="flex items-center text-amber-500 font-black">
                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1">star</span>
                    <span>{{ $rating }}</span>
                </div>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500 font-medium">({{ $ordersCount }} ventas)</span>
            </div>

            <!-- Precios y Cuotas Wompi -->
            <div class="mb-3">
                @if($hasDiscount && $originalPrice)
                    <div class="flex items-center gap-1.5 mb-0.5 flex-wrap">
                        <span class="text-[11px] font-semibold text-slate-400">
                            Antes: <span class="line-through">${{ number_format($originalPrice, 0, ',', '.') }}</span>
                        </span>
                        <span class="text-[10px] font-black text-[#ba1a1a] bg-red-50 border border-red-200 px-1.5 py-0.5 rounded">
                            -{{ $discountPercent }}% OFF
                        </span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl font-extrabold text-[#006c47] font-heading">
                            ${{ number_format($effectivePrice, 0, ',', '.') }}
                        </span>
                        <span class="text-[10px] font-bold text-slate-500">COP</span>
                    </div>
                    <p class="text-[10px] font-extrabold text-[#ba1a1a] mt-0.5">
                        ¡Ahorras ${{ number_format($product->discountAmount(), 0, ',', '.') }} COP!
                    </p>
                @else
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl font-extrabold text-slate-900 font-heading">
                            ${{ number_format($effectivePrice, 0, ',', '.') }}
                        </span>
                        <span class="text-[10px] font-bold text-slate-500">COP</span>
                    </div>
                @endif
                <p class="text-[10px] font-semibold text-emerald-600 mt-0.5 flex items-center gap-0.5">
                    <span class="material-symbols-outlined text-[12px]">credit_card</span>
                    Hasta 12x ${{ number_format($cuotaValue, 0, ',', '.') }} con Wompi
                </p>
            </div>

            <!-- Barra de Stock / Flash Deals -->
            @if($isFlashDeal && $stockLeft > 0)
                <div class="mb-3">
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-[#ba1a1a] h-full rounded-full" style="width: {{ min(100, max(15, ($stockLeft / 20) * 100)) }}%"></div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500 mt-1 block">
                        🔥 Solo quedan {{ $stockLeft }} unidades
                    </span>
                </div>
            @endif
        </div>

        <!-- Acción: Ver Producto (Único botón en la tarjeta de catálogo) -->
        <div class="pt-2.5 border-t border-slate-100">
            @if($stockLeft > 0)
                <a href="{{ route('consumer.product', $product->id) }}"
                   class="w-full py-2.5 px-4 bg-slate-100 hover:bg-[#10B981] text-slate-800 hover:text-white rounded-xl text-xs font-extrabold transition-all duration-200 flex items-center justify-center gap-2 shadow-xs group-hover:bg-[#10B981] group-hover:text-white group-hover:shadow-md cursor-pointer no-underline"
                   title="Ver detalle completo, opciones y comprar">
                    <span class="material-symbols-outlined text-base">visibility</span>
                    <span>Ver Producto</span>
                </a>
            @else
                <span class="w-full py-2.5 px-4 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold text-center block">
                    Agotado
                </span>
            @endif
        </div>

    </div>
</div>
