@extends('layouts.app')
@section('title','Catálogo')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">

  <!-- Breadcrumb hacia Inicio -->
  <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
    <a href="{{ route('consumer.home') }}" class="flex items-center gap-1 font-bold text-slate-700 hover:text-[#10B981] transition-colors">
      <span class="material-symbols-outlined text-sm">home</span>
      <span>Inicio</span>
    </a>
    <span>/</span>
    <span class="text-slate-400">Catálogo</span>
    @if(request('category'))
      <span>/</span>
      <span class="text-[#10B981] font-bold">{{ request('category') }}</span>
    @endif
  </nav>

  @if(isset($directory) && count($directory) > 0)
    <div class="mb-8">
      <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-primary">verified</span>
        <h3 class="text-lg font-bold text-on-background font-['Manrope']">Vendedores Oficiales</h3>
      </div>
      <div class="flex gap-6 overflow-x-auto pb-4 scrollbar-hide">
        @foreach($directory as $brand)
          <a href="{{ route('consumer.merchant.profile', $brand->merchant_id) }}" class="flex flex-col items-center gap-2 group shrink-0">
            <div class="w-20 h-20 rounded-full bg-surface-container border border-outline-variant/50 flex items-center justify-center overflow-hidden group-hover:border-primary group-hover:ring-4 group-hover:ring-primary-fixed transition-all shadow-sm">
              @if($brand->logo_path)
                <img src="{{ Storage::url($brand->logo_path) }}" alt="{{ $brand->company_name }}" class="w-full h-full object-cover">
              @else
                <span class="material-symbols-outlined text-on-surface-variant text-3xl group-hover:text-primary transition-colors">store</span>
              @endif
            </div>
            <span class="text-xs font-bold text-center text-on-surface w-24 truncate">{{ $brand->company_name }}</span>
          </a>
        @endforeach
      </div>
    </div>
  @endif

  <div class="flex flex-col md:flex-row gap-8">
    <aside class="w-full md:w-56 shrink-0">
      <div class="bg-white rounded-2xl p-5 shadow-sm sticky top-20">
        <h2 class="font-['Manrope'] font-bold text-[#1b1c1c] mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined" style="color:#006c47">tune</span> Filtros
        </h2>
        <form method="GET" action="{{ route('consumer.catalog') }}" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-[#3c4a41] uppercase mb-1.5">Buscar</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#3c4a41] text-sm">search</span>
              <input type="text" name="search" value="{{ request('search') }}" class="w-full pl-9 pr-3 py-2 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 text-sm" style="--tw-ring-color:#006c47" placeholder="Buscar..."/>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-[#3c4a41] uppercase mb-1.5">Categoría</label>
            <select name="category" class="w-full py-2 px-3 bg-[#f0eded] rounded-xl border-0 outline-none text-sm">
              <option value="">Todas</option>
              @foreach($categories as $cat)<option value="{{ $cat }}" {{ request('category')===$cat?'selected':'' }}>{{ $cat }}</option>@endforeach
            </select>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div><label class="text-xs font-semibold text-[#3c4a41] block mb-1">Mín $</label><input type="number" name="min_price" value="{{ request('min_price') }}" min="0" class="w-full py-2 px-2 bg-[#f0eded] rounded-xl border-0 outline-none text-sm" placeholder="0"/></div>
            <div><label class="text-xs font-semibold text-[#3c4a41] block mb-1">Máx $</label><input type="number" name="max_price" value="{{ request('max_price') }}" min="0" class="w-full py-2 px-2 bg-[#f0eded] rounded-xl border-0 outline-none text-sm" placeholder="∞"/></div>
          </div>
          <button type="submit" class="w-full py-2 text-white font-semibold rounded-xl hover:opacity-90 text-sm" style="background:linear-gradient(135deg,#006c47,#00b67a)">Filtrar</button>
          @if(request()->anyFilled(['search','category','min_price','max_price']))
            <a href="{{ route('consumer.catalog') }}" class="block text-center text-xs text-[#3c4a41] hover:underline">✕ Limpiar</a>
          @endif
        </form>
      </div>
    </aside>

    <div class="flex-1">
      <p class="text-[#3c4a41] text-sm mb-5">{{ $products->total() }} producto{{ $products->total()!=1?'s':'' }} encontrados</p>
      @if($products->isEmpty())
        <div class="text-center py-20 bg-white rounded-2xl shadow-sm">
          <span class="material-symbols-outlined text-7xl text-[#3c4a41] block mb-3">search_off</span>
          <p class="text-[#3c4a41]">No se encontraron productos.</p>
          <a href="{{ route('consumer.catalog') }}" class="hover:underline mt-2 inline-block text-sm font-semibold" style="color:#006c47">Ver todos</a>
        </div>
      @else
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-5">
          @foreach($products as $p)
          <x-product-card :product="$p" />
          @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
      @endif
    </div>
  </div>
</div>
@endsection