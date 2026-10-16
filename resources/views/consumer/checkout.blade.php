@extends('layouts.app')
@section('title','Checkout')
@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-8">Finalizar Compra</h1>

  @if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-[#ffdad6] text-[#ba1a1a] text-sm font-semibold">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-[#ffdad6] text-[#ba1a1a] text-sm">
      <ul class="list-disc pl-5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <form method="POST" action="{{ route('consumer.checkout.process') }}" id="checkout-form" class="space-y-6">
      @csrf

      {{-- 1. Dirección --}}
      <section class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-['Manrope'] font-bold text-lg">1. Dirección de envío</h2>
          <a href="{{ route('consumer.addresses') }}" class="text-primary text-sm font-semibold hover:underline">Administrar</a>
        </div>
        <div class="space-y-2">
          @foreach($addresses as $addr)
            <label class="flex gap-3 p-3 rounded-xl border border-outline-variant/30 cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5">
              <input type="radio" name="address_id" value="{{ $addr->id }}" class="mt-1" {{ old('address_id', $addresses->first()->id) == $addr->id ? 'checked' : '' }} required>
              <span class="text-sm">
                <strong>{{ e($addr->label) }}</strong> · {{ e($addr->recipient) }}<br>
                <span class="text-on-surface-variant">{{ e($addr->fullLine()) }}</span>
              </span>
            </label>
          @endforeach
        </div>
      </section>

      {{-- 2. Envío --}}
      <section class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
        <h2 class="font-['Manrope'] font-bold text-lg mb-4">2. Método de envío</h2>
        <div id="rates" class="space-y-2 text-sm text-on-surface-variant">Cotizando envío…</div>
        <button type="button" id="requote" class="mt-3 text-primary text-sm font-semibold hover:underline">Volver a cotizar</button>
      </section>

      {{-- 3. Facturación electrónica --}}
      <section class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
        <h2 class="font-['Manrope'] font-bold text-lg mb-4">3. Datos para factura electrónica</h2>
        <div class="grid grid-cols-3 gap-3">
          <select name="document_type" class="col-span-1 rounded-xl border border-outline-variant/40 px-3 py-3 text-sm" required>
            @foreach(['CC'=>'Cédula (CC)','NIT'=>'NIT','CE'=>'Cédula extranjería','PP'=>'Pasaporte','TI'=>'T. identidad','TE'=>'T. extranjería'] as $k=>$v)
              <option value="{{ $k }}" {{ old('document_type','CC')===$k?'selected':'' }}>{{ $v }}</option>
            @endforeach
          </select>
          <input type="text" name="document" value="{{ old('document') }}" required maxlength="20" pattern="[A-Za-z0-9\-]{5,20}" placeholder="Número de documento"
                 class="col-span-2 rounded-xl border border-outline-variant/40 px-4 py-3 text-sm">
        </div>
      </section>

      <p class="text-xs text-on-surface-variant flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">lock</span>
        Pagarás en el checkout seguro de Wompi (tarjeta, PSE, Nequi). FusaShop no almacena datos de tu tarjeta.
      </p>

      @if($availablePoints > 0)
      <label class="flex items-center justify-between p-4 bg-surface-container rounded-2xl cursor-pointer">
        <span class="text-sm"><strong>Usar mis {{ number_format($availablePoints,0,',','.') }} puntos</strong><br>
          <span class="text-on-surface-variant">Descuento de hasta ${{ number_format($availablePoints * 50, 0, ',', '.') }}</span></span>
        <input type="checkbox" name="use_points" id="use-points-check" value="1" {{ old('use_points') ? 'checked' : '' }}>
      </label>
      @endif

      <input type="hidden" name="shipping_rate" id="shipping-rate-input" value="{{ old('shipping_rate') }}">
      <button type="submit" id="pay-btn" disabled class="w-full py-4 bg-primary-gradient text-white font-semibold rounded-xl hover:opacity-90 active:scale-95 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2 text-lg disabled:opacity-40 disabled:cursor-not-allowed">
        <span class="material-symbols-outlined">lock</span> <span id="pay-label">Selecciona un envío</span>
      </button>
    </form>

    {{-- Resumen --}}
    <div>
      <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] sticky top-24">
        <h2 class="font-['Manrope'] font-bold text-on-background text-xl mb-6">Resumen del pedido</h2>
        <div class="space-y-4 mb-6">
          @foreach($cart->items as $item)
          <div class="flex gap-3">
            <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0"><x-product-image :product="$item->product" class="w-full h-full"/></div>
            <div class="flex-1">
              <p class="font-semibold text-on-background text-sm">{{ e($item->product->name) }}</p>
              <p class="text-on-surface-variant text-xs">Cantidad: {{ $item->quantity }}</p>
            </div>
            <span class="font-bold text-on-background">${{ number_format($item->quantity*$item->product->price,0,',','.') }}</span>
          </div>
          @endforeach
        </div>
        <div class="border-t border-outline-variant/30 pt-4 space-y-2 text-sm">
          <div class="flex justify-between"><span>Subtotal</span><span>${{ number_format($cart->total(),0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Envío</span><span id="ship-line">—</span></div>
          <div class="flex justify-between text-primary" id="disc-row" style="display:none"><span>Descuento puntos</span><span id="disc-line"></span></div>
          <div class="flex justify-between font-bold text-xl text-on-background pt-2"><span>Total a pagar</span>
            <span class="text-primary" id="final-total">${{ number_format($cart->total(),0,',','.') }}</span></div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
  var fmt = function (n) { return '$' + Math.round(n).toLocaleString('es-CO'); };
  var subtotal = {{ (float) $cart->total() }};
  var points = {{ (int) $availablePoints }};
  var ratesBox = document.getElementById('rates');
  var rateInput = document.getElementById('shipping-rate-input');
  var payBtn = document.getElementById('pay-btn');
  var payLabel = document.getElementById('pay-label');
  var usePts = document.getElementById('use-points-check');
  var selected = null;

  function recompute() {
    var discount = (usePts && usePts.checked) ? Math.min(points * 50, subtotal) : 0;
    var ship = selected ? selected.price : 0;
    document.getElementById('ship-line').textContent = selected ? fmt(ship) : '—';
    document.getElementById('disc-row').style.display = discount ? 'flex' : 'none';
    document.getElementById('disc-line').textContent = '-' + fmt(discount);
    var total = subtotal - discount + ship;
    document.getElementById('final-total').textContent = fmt(total);
    payBtn.disabled = !selected;
    payLabel.textContent = selected ? 'Pagar ' + fmt(total) + ' con Wompi' : 'Selecciona un envío';
  }

  function render(rates) {
    ratesBox.innerHTML = '';
    selected = null; rateInput.value = '';
    if (!rates.length) { ratesBox.textContent = 'No hay transportadoras disponibles para esta dirección.'; recompute(); return; }
    rates.forEach(function (r, i) {
      var label = document.createElement('label');
      label.className = 'flex items-center justify-between p-3 rounded-xl border border-outline-variant/30 cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5 text-on-surface';
      var radio = document.createElement('input'); radio.type = 'radio'; radio.name = 'rate_choice'; radio.className = 'mr-3';
      radio.addEventListener('change', function () { selected = r; rateInput.value = r.carrier.toLowerCase() + '|' + r.service; recompute(); });
      var left = document.createElement('span'); left.className = 'flex-1';
      left.textContent = r.carrier.toUpperCase() + ' · ' + r.service + (r.days ? ' · ' + r.days + ' día(s)' : '');
      var price = document.createElement('strong'); price.textContent = fmt(r.price);
      label.append(radio, left, price); ratesBox.append(label);
      if (i === 0) { radio.checked = true; radio.dispatchEvent(new Event('change')); }
    });
  }

  async function quote() {
    var checked = document.querySelector('input[name=address_id]:checked');
    if (!checked) return;
    ratesBox.textContent = 'Cotizando envío…'; selected = null; recompute();
    try {
      var res = await fetch(@json(route('consumer.checkout.quote')), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ address_id: checked.value })
      });
      var data = await res.json();
      if (!res.ok) { ratesBox.textContent = data.message || 'No se pudo cotizar.'; return; }
      render(data.rates);
    } catch (e) { ratesBox.textContent = 'Error de red al cotizar el envío.'; }
  }

  document.querySelectorAll('input[name=address_id]').forEach(function (r) { r.addEventListener('change', quote); });
  document.getElementById('requote').addEventListener('click', quote);
  if (usePts) usePts.addEventListener('change', recompute);
  quote();
})();
</script>
@endpush
