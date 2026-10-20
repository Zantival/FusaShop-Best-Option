@extends('layouts.app')
@section('title','Mi Carrito')
@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <h1 class="text-3xl font-['Manrope'] font-bold text-on-background flex items-center gap-3">
      <span class="material-symbols-outlined text-primary text-3xl">shopping_cart</span> Mi Carrito
    </h1>
    @if($cart && $cart->items->isNotEmpty())
      <div class="flex items-center gap-2 text-sm text-on-surface-variant bg-surface-container px-3.5 py-1.5 rounded-full w-fit">
        <span class="material-symbols-outlined text-amber-500 text-base">toll</span>
        <span>Tienes <strong>{{ number_format($userPoints, 0, ',', '.') }}</strong> FusaCoins disponibles</span>
      </div>
    @endif
  </div>

  @if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-[#ffdad6] text-[#ba1a1a] text-sm font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined text-base">error</span>
      {{ session('error') }}
    </div>
  @endif

  @if(!$cart || $cart->items->isEmpty())
    <div class="text-center py-24 bg-surface-container-lowest rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)]">
      <span class="material-symbols-outlined text-on-surface-variant text-8xl mb-6 block">shopping_cart</span>
      <h2 class="text-2xl font-['Manrope'] font-bold text-on-surface mb-2">Tu carrito está vacío</h2>
      <p class="text-on-surface-variant mb-8">Agrega productos para comenzar a comprar</p>
      <a href="{{ route('consumer.catalog') }}" class="px-8 py-3.5 bg-primary-gradient text-white font-semibold rounded-xl hover:opacity-90 transition-all inline-flex items-center gap-2">
        <span class="material-symbols-outlined text-sm">storefront</span> Explorar catálogo
      </a>
    </div>
  @else
    @php
      $sessionSelected = session('checkout_selected_items');
    @endphp
    <form id="cart-checkout-form" method="GET" action="{{ route('consumer.checkout') }}">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Items Column --}}
        <div class="lg:col-span-2 space-y-4">
          {{-- Select all bar --}}
          <div class="bg-surface-container-lowest rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)] flex items-center justify-between">
            <label class="flex items-center gap-3 cursor-pointer select-none">
              <input type="checkbox" id="select-all-checkbox" checked class="w-5 h-5 rounded text-primary border-outline-variant/50 focus:ring-primary">
              <span class="text-sm font-bold text-on-background">Seleccionar todos</span>
            </label>
            <span class="text-xs text-on-surface-variant" id="selected-summary-count">
              {{ $cart->items->count() }} de {{ $cart->items->count() }} seleccionados
            </span>
          </div>

          {{-- Product cards --}}
          @foreach($cart->items as $item)
          <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-[0_12px_32px_rgba(27,28,28,.06)] flex flex-col sm:flex-row gap-4 relative transition-all item-card" id="cart-item-card-{{ $item->id }}">
            <div class="flex items-center sm:self-center">
              <input type="checkbox"
                     name="selected_items[]"
                     value="{{ $item->id }}"
                     {{ !is_array($sessionSelected) || in_array($item->id, $sessionSelected) ? 'checked' : '' }}
                     data-item-id="{{ $item->id }}"
                     data-price="{{ (float) $item->product->effectivePrice() }}"
                     data-qty="{{ (int) $item->quantity }}"
                     data-name="{{ e($item->product->name) }}"
                     data-allows-points="{{ $item->product->allows_points ? '1' : '0' }}"
                     class="cart-item-checkbox w-5 h-5 rounded text-primary border-outline-variant/50 focus:ring-primary cursor-pointer">
            </div>

            <div class="w-20 h-20 rounded-xl overflow-hidden shrink-0 self-center sm:self-auto bg-surface-container">
              <x-product-image :product="$item->product" class="w-full h-full object-cover"/>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h3 class="font-['Manrope'] font-bold text-on-background line-clamp-1">{{ e($item->product->name) }}</h3>
                  <p class="text-on-surface-variant text-[11px]">{{ $item->product->category }}</p>
                </div>
              </div>
              
              @if($item->selected_options)
                <div class="flex flex-wrap gap-1.5 mt-2">
                  @foreach($item->selected_options as $key => $val)
                    <span class="text-[9px] bg-surface-container px-2 py-0.5 rounded border border-outline-variant/30 text-on-surface-variant">
                      <span class="font-black opacity-50">{{ $key }}:</span> {{ $val }}
                    </span>
                  @endforeach
                </div>
              @endif

              <div class="flex flex-wrap items-center gap-2 mt-2">
                @if($item->product->hasDiscount())
                  <span class="text-xs text-slate-400 line-through">${{ number_format($item->product->originalPrice(),0,',','.') }}</span>
                  <span class="text-primary font-black text-lg">${{ number_format($item->product->effectivePrice(),0,',','.') }}</span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#ba1a1a] text-white">-{{ $item->product->discount_percent }}% OFF</span>
                @else
                  <span class="text-primary font-black text-lg">${{ number_format($item->product->effectivePrice(),0,',','.') }}</span>
                @endif
                
                {{-- Badge FusaCoins --}}
                @if($item->product->allows_points)
                  <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-full" title="Puedes redimir FusaCoins como descuento en este producto">
                    <span class="material-symbols-outlined text-xs text-amber-500">toll</span> Aplica FusaCoins
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full" title="El vendedor no acepta redención de puntos en este producto">
                    <span class="material-symbols-outlined text-xs">remove_circle_outline</span> No redime FusaCoins
                  </span>
                @endif
              </div>
            </div>

            <div class="flex sm:flex-col items-center sm:items-end justify-between shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-outline-variant/20">
              <button type="button" onclick="submitDelete({{ $item->id }})" class="text-on-surface-variant hover:text-[#ba1a1a] transition-colors p-1" title="Eliminar del carrito">
                <span class="material-symbols-outlined text-sm">delete</span>
              </button>

              <div class="flex items-center gap-2 my-1">
                <button type="button" onclick="submitQty({{ $item->id }}, -1)" class="w-7 h-7 bg-surface-container-high rounded-lg flex items-center justify-center hover:bg-primary hover:text-white transition-all">
                  <span class="material-symbols-outlined text-xs">remove</span>
                </button>
                <span class="w-8 text-center text-sm font-bold">{{ $item->quantity }}</span>
                <button type="button" onclick="submitQty({{ $item->id }}, 1)" class="w-7 h-7 bg-surface-container-high rounded-lg flex items-center justify-center hover:bg-primary hover:text-white transition-all">
                  <span class="material-symbols-outlined text-xs">add</span>
                </button>
              </div>

              <p class="font-bold text-on-background text-sm sm:text-base">
                ${{ number_format($item->quantity * $item->product->effectivePrice(),0,',','.') }}
              </p>
            </div>
          </div>
          @endforeach
        </div>

        {{-- Summary Column --}}
        <div class="lg:col-span-1">
          <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] sticky top-20 space-y-6">
            <h2 class="font-['Manrope'] font-bold text-on-background text-xl">Resumen de compra</h2>

            {{-- FusaCoins Panel --}}
            <div class="p-4 bg-gradient-to-br from-amber-500/10 to-amber-500/5 rounded-2xl border border-amber-500/20 space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-lg">toll</span>
                  </div>
                  <div>
                    <h3 class="text-xs font-black uppercase text-amber-900 tracking-wider">FusaCoins</h3>
                    <p class="text-[11px] text-amber-800">Saldo: <strong>{{ number_format($userPoints, 0, ',', '.') }}</strong> pts</p>
                  </div>
                </div>
                <span class="text-[10px] font-bold bg-amber-200/60 text-amber-900 px-2 py-0.5 rounded-full">
                  1 pt = $100 COP
                </span>
              </div>

              @if($userPoints > 0)
                <label class="flex items-center justify-between p-2.5 bg-white rounded-xl border border-amber-300/50 cursor-pointer hover:border-amber-400 transition-colors shadow-sm">
                  <div class="pr-2">
                    <span class="text-xs font-bold text-on-surface block">Redimir FusaCoins</span>
                    <span class="text-[10px] text-on-surface-variant block">Hasta $50.000 COP en productos participantes</span>
                  </div>
                  <input type="checkbox" name="use_points" id="cart-use-points" value="1" class="w-5 h-5 text-amber-600 rounded focus:ring-amber-500 cursor-pointer">
                </label>

                <div id="fusacoins-applied-info" class="hidden text-[11px] text-amber-900 bg-amber-100/60 p-2 rounded-lg space-y-0.5">
                  <div class="flex justify-between">
                    <span>Descuento a aplicar:</span>
                    <strong id="fusacoins-discount-value">$0</strong>
                  </div>
                  <div class="flex justify-between text-[10px] text-amber-700">
                    <span>Puntos que se descontarán:</span>
                    <span id="fusacoins-points-to-deduct">0 pts</span>
                  </div>
                </div>
              @else
                <p class="text-[11px] text-amber-800">
                  Aún no tienes FusaCoins. ¡Empieza a acumularlos con esta compra!
                </p>
              @endif

              {{-- Projection of points to earn --}}
              <div class="pt-2 border-t border-amber-500/20 flex items-center gap-2 text-[11px] text-amber-900">
                <span class="material-symbols-outlined text-sm text-emerald-600">add_circle</span>
                <span>Ganarás aprox. <strong id="points-earned-preview" class="text-emerald-700">0</strong> FusaCoins (1 por cada $10.000 COP)</span>
              </div>
            </div>

            {{-- Breakdown --}}
            <div class="space-y-3 pt-2">
              <div class="flex justify-between text-sm text-on-surface-variant">
                <span>Subtotal (<span id="summary-items-count">{{ $cart->items->count() }}</span> productos)</span>
                <span id="summary-subtotal" class="font-semibold text-on-surface">$0</span>
              </div>
              <div class="flex justify-between text-sm text-amber-700 font-semibold hidden" id="summary-discount-row">
                <span class="flex items-center gap-1">
                  <span class="material-symbols-outlined text-sm">toll</span> Descuento FusaCoins
                </span>
                <span id="summary-discount">-$0</span>
              </div>
              <div class="flex justify-between text-xs text-on-surface-variant">
                <span>Envío</span>
                <span class="italic">Se cotiza en el siguiente paso</span>
              </div>
            </div>

            {{-- Total --}}
            <div class="border-t border-outline-variant/30 pt-4">
              <div class="flex justify-between items-baseline font-bold text-lg text-on-background">
                <span>Total productos</span>
                <span class="text-primary text-2xl font-black" id="summary-total">$0</span>
              </div>
              <p class="text-[11px] text-on-surface-variant mt-1">El costo de flete se sumará según tu dirección de Fusagasugá o nacional.</p>
            </div>

            {{-- Buttons --}}
            <button type="submit" id="btn-proceed-checkout" class="w-full py-4 bg-primary-gradient text-white font-bold rounded-xl hover:opacity-95 active:scale-[0.98] transition-all text-center flex items-center justify-center gap-2 shadow-lg shadow-primary/20 disabled:opacity-40 disabled:cursor-not-allowed">
              <span class="material-symbols-outlined text-lg">payment</span>
              <span id="btn-proceed-label">Proceder al pago</span>
            </button>
            
            <a href="{{ route('consumer.catalog') }}" class="block text-center text-sm font-medium text-on-surface-variant hover:text-primary transition-colors">
              Continuar comprando
            </a>
          </div>
        </div>
      </div>
    </form>

    {{-- Hidden forms for delete and qty update --}}
    <form id="action-qty-form" method="POST" action="" class="hidden">
      @csrf @method('PATCH')
      <input type="hidden" name="quantity" id="action-qty-value" value="1">
    </form>

    <form id="action-delete-form" method="POST" action="" class="hidden">
      @csrf @method('DELETE')
    </form>
  @endif
</div>

@push('scripts')
<script>
const USER_POINTS = {{ (int) $userPoints }};
const POINT_VALUE = 100;
const EARN_RATE = 10000;
const MAX_POINTS_DISCOUNT = 50000;

function fmt(n) {
  return '$' + Math.round(n).toLocaleString('es-CO');
}

function updateCartCalculations() {
  const checkboxes = document.querySelectorAll('.cart-item-checkbox');
  const selectAll = document.getElementById('select-all-checkbox');
  const usePointsCheckbox = document.getElementById('cart-use-points');
  const btnProceed = document.getElementById('btn-proceed-checkout');
  const btnLabel = document.getElementById('btn-proceed-label');

  let selectedCount = 0;
  let selectedUnits = 0;
  let subtotal = 0;
  let eligibleSubtotal = 0;

  checkboxes.forEach(cb => {
    const card = document.getElementById('cart-item-card-' + cb.value);
    const qty = parseInt(cb.getAttribute('data-qty')) || 1;
    if (cb.checked) {
      selectedCount++;
      selectedUnits += qty;
      const price = parseFloat(cb.getAttribute('data-price')) || 0;
      const allowsPoints = cb.getAttribute('data-allows-points') === '1';
      const itemTotal = price * qty;
      
      subtotal += itemTotal;
      if (allowsPoints) {
        eligibleSubtotal += itemTotal;
      }
      if (card) card.classList.remove('opacity-50');
    } else {
      if (card) card.classList.add('opacity-50');
    }
  });

  // Actualizar el número del icono del carrito en la barra de navegación (Desktop y Mobile)
  const cartBadges = document.querySelectorAll('.cart-badge');
  cartBadges.forEach(badge => {
    if (selectedUnits > 0) {
      badge.textContent = selectedUnits > 9 ? '9+' : selectedUnits;
      badge.classList.remove('hidden');
    } else {
      badge.textContent = '0';
      badge.classList.add('hidden');
    }
  });

  // Select all state
  if (selectAll) {
    selectAll.checked = (selectedCount > 0 && selectedCount === checkboxes.length);
    selectAll.indeterminate = (selectedCount > 0 && selectedCount < checkboxes.length);
  }

  // Count text
  const countEl = document.getElementById('selected-summary-count');
  if (countEl) countEl.textContent = `${selectedCount} de ${checkboxes.length} seleccionados`;
  
  const summaryCountEl = document.getElementById('summary-items-count');
  if (summaryCountEl) summaryCountEl.textContent = selectedCount;

  // FusaCoins Discount
  let discount = 0;
  let pointsUsed = 0;
  const isRedeeming = usePointsCheckbox && usePointsCheckbox.checked;

  if (isRedeeming && USER_POINTS > 0 && eligibleSubtotal > 0) {
    const maxDiscount = Math.min(USER_POINTS * POINT_VALUE, eligibleSubtotal, MAX_POINTS_DISCOUNT);
    discount = Math.max(0, maxDiscount);
    pointsUsed = Math.floor(discount / POINT_VALUE);
  }

  const fusacoinsInfo = document.getElementById('fusacoins-applied-info');
  const fusacoinsVal = document.getElementById('fusacoins-discount-value');
  const fusacoinsPts = document.getElementById('fusacoins-points-to-deduct');
  if (fusacoinsInfo) {
    if (isRedeeming && discount > 0) {
      fusacoinsInfo.classList.remove('hidden');
      if (fusacoinsVal) fusacoinsVal.textContent = fmt(discount);
      if (fusacoinsPts) fusacoinsPts.textContent = pointsUsed + ' pts';
    } else {
      fusacoinsInfo.classList.add('hidden');
    }
  }

  // FusaCoins Earned Projection
  const pointsEarned = Math.floor(subtotal / EARN_RATE);
  const earnedPreview = document.getElementById('points-earned-preview');
  if (earnedPreview) earnedPreview.textContent = pointsEarned;

  // Summary lines
  const summarySubtotal = document.getElementById('summary-subtotal');
  if (summarySubtotal) summarySubtotal.textContent = fmt(subtotal);

  const discountRow = document.getElementById('summary-discount-row');
  const discountEl = document.getElementById('summary-discount');
  if (discountRow && discountEl) {
    if (discount > 0) {
      discountRow.classList.remove('hidden');
      discountEl.textContent = '-' + fmt(discount);
    } else {
      discountRow.classList.add('hidden');
    }
  }

  const finalTotal = Math.max(0, subtotal - discount);
  const summaryTotal = document.getElementById('summary-total');
  if (summaryTotal) summaryTotal.textContent = fmt(finalTotal);

  // Button state
  if (btnProceed && btnLabel) {
    if (selectedCount === 0) {
      btnProceed.disabled = true;
      btnLabel.textContent = 'Selecciona al menos un producto';
    } else {
      btnProceed.disabled = false;
      btnLabel.textContent = `Proceder al pago (${selectedCount})`;
    }
  }

  // Sincronizar en segundo plano la selección con la sesión del backend
  if (window._syncSelectTimeout) clearTimeout(window._syncSelectTimeout);
  window._syncSelectTimeout = setTimeout(() => {
    const selectedIds = Array.from(checkboxes).filter(cb => cb.checked).map(cb => parseInt(cb.value));
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
      fetch(@json(route('consumer.cart.select')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ selected_items: selectedIds })
      }).catch(() => {});
    }
  }, 200);
}

// Master checkbox
const selectAll = document.getElementById('select-all-checkbox');
if (selectAll) {
  selectAll.addEventListener('change', function () {
    const checkboxes = document.querySelectorAll('.cart-item-checkbox');
    checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
    updateCartCalculations();
  });
}

// Individual checkboxes
document.querySelectorAll('.cart-item-checkbox').forEach(cb => {
  cb.addEventListener('change', updateCartCalculations);
});

// Points toggle
const usePointsCheckbox = document.getElementById('cart-use-points');
if (usePointsCheckbox) {
  usePointsCheckbox.addEventListener('change', updateCartCalculations);
}

// Actions for Delete and Quantity
function submitDelete(itemId) {
  if (!confirm('¿Deseas quitar este producto del carrito?')) return;
  const form = document.getElementById('action-delete-form');
  form.action = `/shop/cart/item/${itemId}`;
  form.submit();
}

function submitQty(itemId, delta) {
  const cb = document.querySelector(`.cart-item-checkbox[value="${itemId}"]`);
  const currentQty = cb ? parseInt(cb.getAttribute('data-qty')) || 1 : 1;
  const newQty = currentQty + delta;
  if (newQty < 1) {
    submitDelete(itemId);
    return;
  }
  const form = document.getElementById('action-qty-form');
  form.action = `/shop/cart/item/${itemId}`;
  document.getElementById('action-qty-value').value = newQty;
  form.submit();
}

// Initial calculation on page load
document.addEventListener('DOMContentLoaded', updateCartCalculations);
updateCartCalculations();
</script>
@endpush
@endsection
