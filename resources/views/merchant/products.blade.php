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
      <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all group">
        <div class="aspect-square overflow-hidden relative">
          <x-product-image :product="$p" class="w-full h-full group-hover:scale-[0.85] transition-transform duration-300"/>
          @if(!$p->is_active)<div class="absolute inset-0 bg-black/40 flex items-center justify-center"><span class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">Inactivo</span></div>@endif
          @if($p->stock === 0)<span class="absolute top-2 left-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-lg">Agotado</span>
          @elseif($p->stock <= 5)<span class="absolute top-2 left-2 bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-lg">Poco stock</span>@endif
        </div>
        <div class="p-3">
          <span class="text-xs text-[#3c4a41]">{{ $p->category }}</span>
          <h3 class="font-['Manrope'] font-bold text-[#1b1c1c] text-sm truncate mt-0.5">{{ e($p->name) }}</h3>
          <div class="flex items-center justify-between mt-1.5">
            <span class="font-bold" style="color:#006c47">${{ number_format($p->price,0,',','.') }}</span>
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
