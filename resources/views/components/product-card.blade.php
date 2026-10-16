@props(['product', 'showStock' => true, 'isFlashDeal' => false])

@php
use Illuminate\Support\Facades\Storage;

$hasImage = $product->image && Storage::disk('public')->exists($product->image);
$imageUrl = $hasImage ? url('files/' . $product->image) : null;

// Cálculo de FusaCoins a ganar (100 coins por cada $10.000 COP)
$fusaCoinsEarned = max(10, (int) floor(($product->price / 10000) * 100));

// Simulación de descuento y precio de lista
$hasDiscount = $isFlashDeal || ($product->id % 2 === 0);
$discountPercent = $hasDiscount ? (20 + (($product->id * 7) % 25)) : 0;
$originalPrice = $hasDiscount ? round($product->price * (1 + ($discountPercent / 100)), -2) : null;

// Cuotas sin interés Wompi
$cuotaValue = round($product->price / 12);

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
        <span class="bg-gradient-to-r from-amber-500 to-[#F97316] text-white text-[9px] font-extrabold px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-1">
            <span class="material-symbols-outlined text-[11px]">stars</span>
            +{{ $fusaCoinsEarned }} FusaCoins
        </span>
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

        @if($isFlashDeal)
            <div class="absolute bottom-2 left-2 right-2 bg-slate-900/80 backdrop-blur-sm text-white px-2 py-1 rounded-xl text-[10px] font-bold flex items-center justify-between">
                <span class="text-amber-400 flex items-center gap-0.5">
                    <span class="material-symbols-outlined text-xs">bolt</span> Relámpago
                </span>
                <span>Termina pronto</span>
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
                @if($originalPrice)
                    <span class="text-xs text-slate-400 line-through block leading-none mb-0.5">
                        ${{ number_format($originalPrice, 0, ',', '.') }}
                    </span>
                @endif
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-extrabold text-slate-900 font-heading">
                        ${{ number_format($product->price, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] font-bold text-slate-500">COP</span>
                </div>
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

        <!-- Acciones: Carrito + Compra 1-Clic -->
        <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
            @if($stockLeft > 0)
                <form method="POST" action="{{ route('consumer.cart.add') }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="w-full py-2 px-3 bg-slate-100 hover:bg-[#10B981] text-slate-700 hover:text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm" title="Agregar al Carrito">
                        <span class="material-symbols-outlined text-base">add_shopping_cart</span>
                        <span>Al Carrito</span>
                    </button>
                </form>

                <a href="{{ route('consumer.product', $product->id) }}" class="py-2 px-3 bg-[#10B981] hover:bg-[#00843D] text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center shrink-0 shadow-sm" title="Comprar en 1 Clic">
                    <span>Comprar</span>
                </a>
            @else
                <span class="w-full py-2 px-3 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold text-center block">
                    Agotado
                </span>
            @endif
        </div>

    </div>
</div>
