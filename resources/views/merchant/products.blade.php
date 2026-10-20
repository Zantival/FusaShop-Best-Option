@extends('layouts.app')
@section('title','Mis Productos')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
  <div class="flex items-center justify-between mb-8">
    <h1 class="text-3xl font-['Manrope'] font-bold text-[#1b1c1c]">Mis Productos</h1>
    <a href="{{ route('merchant.products.create') }}" class="px-5 py-2.5 text-white font-semibold rounded-xl hover:opacity-90 transition-all flex items-center gap-2 shadow-md" style="background:linear-gradient(135deg,#006c47,#00b67a)">
      <span class="material-symbols-outlined text-sm">add</span> Nuevo
    </a>
  </div>
  @if($products->isEmpty())
    <div class="text-center py-20 bg-white rounded-2xl shadow-sm">
      <span class="material-symbols-outlined text-8xl text-[#3c4a41] block mb-3">inventory_2</span>
      <p class="text-xl font-['Manrope'] font-bold text-[#1b1c1c] mb-2">Sin productos</p>
      <a href="{{ route('merchant.products.create') }}" class="font-semibold hover:underline" style="color:#006c47">Crea tu primer producto</a>
    </div>
  @else
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
      @foreach($products as $p)
      <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all group border border-slate-100">
        <div class="aspect-square overflow-hidden relative">
          <x-product-image :product="$p" class="w-full h-full group-hover:scale-[0.85] transition-transform duration-300"/>
          @if(!$p->is_active)<div class="absolute inset-0 bg-black/40 flex items-center justify-center"><span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">Inactivo</span></div>@endif
          <div class="absolute top-2 left-2 flex flex-col gap-1 items-start">
            @if($p->hasDiscount())
              <span class="bg-[#ba1a1a] text-white text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm">-{{ $p->discount_percent }}% OFF</span>
            @endif
            @if($p->stock === 0)
              <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-lg">Agotado</span>
            @elseif($p->stock <= 5)
              <span class="bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-lg">Poco stock</span>
            @endif
          </div>
          @if($p->allows_points)
            <span class="absolute top-2 right-2 bg-amber-500/95 text-white text-[9px] font-black px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-0.5" title="Acepta redención de FusaCoins">
              <span class="material-symbols-outlined text-[11px]">toll</span> FusaCoins
            </span>
          @endif
        </div>
        <div class="p-3">
          <div class="flex items-center justify-between gap-1">
            <span class="text-xs text-[#3c4a41]">{{ $p->category }}</span>
            @if($p->allows_points)
              <span class="text-[9px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Aplica FusaCoins</span>
            @else
              <span class="text-[9px] font-medium text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">Sin FusaCoins</span>
            @endif
          </div>
          <h3 class="font-['Manrope'] font-bold text-[#1b1c1c] text-sm truncate mt-0.5">{{ e($p->name) }}</h3>
          <div class="flex items-end justify-between mt-1.5">
            <div>
              @if($p->hasDiscount())
                <span class="text-[10px] text-slate-400 line-through block leading-none">${{ number_format($p->originalPrice(),0,',','.') }}</span>
                <span class="font-black text-sm text-[#006c47]">${{ number_format($p->effectivePrice(),0,',','.') }}</span>
              @else
                <span class="font-bold text-sm" style="color:#006c47">${{ number_format($p->price,0,',','.') }}</span>
              @endif
            </div>
            <span class="text-xs text-[#3c4a41]">Stock: <strong class="{{ $p->stock==0?'text-red-600':($p->stock<=5?'text-amber-600':'') }}">{{ $p->stock }}</strong></span>
          </div>
          <div class="flex gap-1.5 mt-3">
            <a href="{{ route('merchant.products.edit', $p->id) }}" class="flex-1 py-1.5 bg-[#f0eded] text-[#1b1c1c] text-xs font-semibold rounded-lg text-center hover:bg-[#006c47] hover:text-white transition-all flex items-center justify-center gap-1">
              <span class="material-symbols-outlined text-xs">edit</span> Editar
            </a>
            <form method="POST" action="{{ route('merchant.products.delete', $p->id) }}" onsubmit="return confirm('¿Eliminar?')">
              @csrf @method('DELETE')
              <button type="submit" class="py-1.5 px-2.5 bg-red-50 text-red-600 text-xs rounded-lg hover:bg-red-600 hover:text-white transition-all">
                <span class="material-symbols-outlined text-xs">delete</span>
              </button>
            </form>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
  @endif
</div>
@endsection
