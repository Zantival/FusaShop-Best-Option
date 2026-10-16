@extends('layouts.app')
@section('title','Mi Carrito')
@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-8 flex items-center gap-3">
    <span class="material-symbols-outlined text-primary">shopping_cart</span> Mi Carrito
  </h1>

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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-4">
        @foreach($cart->items as $item)
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-[0_12px_32px_rgba(27,28,28,.06)] flex gap-4">
          <div class="w-20 h-20 rounded-xl overflow-hidden shrink-0">
            <x-product-image :product="$item->product" class="w-full h-full"/>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-['Manrope'] font-bold text-on-background truncate">{{ e($item->product->name) }}</h3>
            <p class="text-on-surface-variant text-[11px]">{{ $item->product->category }}</p>
            
            @if($item->selected_options)
              <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach($item->selected_options as $key => $val)
                  <span class="text-[9px] bg-surface-container px-2 py-0.5 rounded border border-outline-variant/30 text-on-surface-variant">
                    <span class="font-black opacity-50">{{ $key }}:</span> {{ $val }}
                  </span>
                @endforeach
              </div>
            @endif

            <p class="text-primary font-black text-lg mt-1">${{ number_format($item->product->price,0,',','.') }}</p>
          </div>
          <div class="flex flex-col items-end justify-between shrink-0">
            <form method="POST" action="{{ route('consumer.cart.remove', $item->id) }}">
              @csrf @method('DELETE')
              <button type="submit" class="text-on-surface-variant hover:text-[#ba1a1a] transition-colors">
                <span class="material-symbols-outlined text-sm">delete</span>
              </button>
            </form>
            <form method="POST" action="{{ route('consumer.cart.update', $item->id) }}" class="flex items-center gap-2">
              @csrf @method('PATCH')
              <button type="button" onclick="changeQty(this,-1)" class="w-8 h-8 bg-surface-container-high rounded-lg flex items-center justify-center hover:bg-primary hover:text-white transition-all">
                <span class="material-symbols-outlined text-sm">remove</span>
              </button>
              <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" onchange="this.form.submit()"
                class="w-12 text-center bg-surface-container-high rounded-lg py-1 border-0 outline-none focus:ring-1 focus:ring-primary text-sm font-semibold"/>
              <button type="button" onclick="changeQty(this,1)" class="w-8 h-8 bg-surface-container-high rounded-lg flex items-center justify-center hover:bg-primary hover:text-white transition-all">
                <span class="material-symbols-outlined text-sm">add</span>
              </button>
            </form>
            <p class="font-bold text-on-background">${{ number_format($item->quantity * $item->product->price,0,',','.') }}</p>
          </div>
        </div>
        @endforeach
      </div>

      <!-- Summary -->
      <div class="lg:col-span-1">
        <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)] sticky top-20">
          <h2 class="font-['Manrope'] font-bold text-on-background text-xl mb-6">Resumen</h2>
          <div class="space-y-3 mb-6">
            @foreach($cart->items as $item)
            <div class="flex justify-between text-sm text-on-surface-variant">
              <span>{{ e($item->product->name) }} x{{ $item->quantity }}</span>
              <span>${{ number_format($item->quantity * $item->product->price,0,',','.') }}</span>
            </div>
            @endforeach
          </div>
          <div class="border-t border-outline-variant/30 pt-4 mb-6">
            <div class="flex justify-between font-bold text-lg text-on-background">
              <span>Total</span>
              <span class="text-primary">${{ number_format($cart->total(),0,',','.') }}</span>
            </div>
          </div>
          <a href="{{ route('consumer.checkout') }}" class="block w-full py-3.5 bg-primary-gradient text-white font-semibold rounded-xl hover:opacity-90 active:scale-95 transition-all text-center flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">payment</span> Proceder al pago
          </a>
          <a href="{{ route('consumer.catalog') }}" class="block text-center text-sm text-on-surface-variant hover:text-primary mt-3 transition-colors">
            Continuar comprando
          </a>
        </div>
      </div>
    </div>
  @endif
</div>

@push('scripts')
<script>
function changeQty(btn,delta){
  const form=btn.closest('form');
  const input=form.querySelector('input[name=quantity]');
  const v=parseInt(input.value)+delta;
  if(v<1)return;
  input.value=v;
  form.submit();
}
</script>
@endpush
@endsection
