@extends('layouts.app')
@section('title', 'Inventario Rápido')
@section('content')
<div class="max-w-6xl mx-auto px-6 py-8">
  <div class="flex items-center justify-between mb-8">
    <h1 class="text-3xl font-['Manrope'] font-bold text-on-background flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-3xl">inventory</span> Inventario Rápido
    </h1>
    <a href="{{ route('merchant.products') }}" class="text-primary font-bold hover:underline">Volver a Productos</a>
  </div>

  <p class="text-on-surface-variant mb-6">Actualiza las existencias de tus productos de forma rápida y sencilla.</p>

  <form method="POST" action="{{ route('merchant.inventory.update') }}">
    @csrf
    <div class="bg-surface-container-lowest rounded-2xl shadow-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-container-low">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-on-surface-variant w-16">IMG</th>
              <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">Producto</th>
              <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">Categoría</th>
              <th class="px-4 py-3 text-left font-semibold text-on-surface-variant w-32">Stock Actual</th>
            </tr>
          </thead>
          <tbody>
            @forelse($products as $product)
            <tr class="border-t border-outline-variant/20 hover:bg-surface-container-low/50">
              <td class="px-4 py-3">
                @if($product->image)
                  <img src="{{ Storage::url($product->image) }}" class="w-10 h-10 object-cover rounded-lg border border-outline-variant/30">
                @else
                  <div class="w-10 h-10 bg-surface-container-low rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-on-surface-variant/50">image</span>
                  </div>
                @endif
              </td>
              <td class="px-4 py-3 font-semibold text-on-background">{{ $product->name }}</td>
              <td class="px-4 py-3 text-on-surface-variant">{{ $product->category }}</td>
              <td class="px-4 py-3">
                <input type="number" name="stocks[{{ $product->id }}]" value="{{ $product->stock }}" min="0" required
                       class="w-full rounded-lg border {{ $product->stock == 0 ? 'border-error text-error bg-error-container/20' : 'border-outline-variant' }} px-3 py-1.5 focus:ring-2 focus:ring-primary/40 focus:outline-none">
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="4" class="px-4 py-12 text-center text-on-surface-variant">No tienes productos registrados.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($products->isNotEmpty())
      <div class="p-4 border-t border-outline-variant/20 bg-surface-container-lowest flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-primary-gradient text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-md flex items-center gap-2">
          <span class="material-symbols-outlined text-sm">save</span> Guardar Inventario
        </button>
      </div>
      @endif
    </div>
  </form>
</div>
@endsection
