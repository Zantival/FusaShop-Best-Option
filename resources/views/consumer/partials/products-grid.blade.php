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
