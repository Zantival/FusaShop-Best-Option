@extends('layouts.app')
@section('title','Finanzas y Reportes')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
  <div class="mb-8">
    <h1 class="text-3xl font-['Manrope'] font-extrabold text-[#1b1c1c]">Finanzas y Reportes</h1>
    <p class="text-[#3c4a41]">Balance general de tu actividad en FusaShop</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30">
      <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-primary">payments</span>
      </div>
      <p class="text-sm font-bold text-[#3c4a41] uppercase tracking-wider mb-1">Ventas Brutas</p>
      <p class="text-3xl font-black text-[#1b1c1c]">${{ number_format($grossSales, 0, ',', '.') }}</p>
      <p class="text-xs text-[#3c4a41] mt-2">Total de items vendidos</p>
    </div>

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30">
      <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-amber-600">stars</span>
      </div>
      <p class="text-sm font-bold text-[#3c4a41] uppercase tracking-wider mb-1">Dctos. por Puntos</p>
      <p class="text-3xl font-black text-amber-600">-${{ number_format($pointsDiscounts, 0, ',', '.') }}</p>
      <p class="text-xs text-[#3c4a41] mt-2">Costo asumido por fidelización</p>
    </div>

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30">
      <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-blue-600">campaign</span>
      </div>
      <p class="text-sm font-bold text-[#3c4a41] uppercase tracking-wider mb-1">Publicidad</p>
      <p class="text-3xl font-black text-blue-600">-${{ number_format($adSpend, 0, ',', '.') }}</p>
      <p class="text-xs text-[#3c4a41] mt-2">Inversión en banners globales</p>
    </div>

    <div class="bg-primary-gradient rounded-3xl p-6 shadow-lg text-white">
      <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center mb-4">
        <span class="material-symbols-outlined text-white">account_balance_wallet</span>
      </div>
      <p class="text-sm font-bold text-white/80 uppercase tracking-wider mb-1">Ingreso Real</p>
      <p class="text-3xl font-black text-white">${{ number_format($realIncome, 0, ',', '.') }}</p>
      <p class="text-xs text-white/70 mt-2">Ganancia neta estimada</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Low Stock Alerts --}}
    <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-outline-variant/30 overflow-hidden">
      <div class="px-8 py-6 border-b border-outline-variant/30 flex items-center justify-between bg-surface-container-low/30">
        <div class="flex items-center gap-3">
          <span class="material-symbols-outlined text-error">warning</span>
          <h2 class="text-xl font-bold text-[#1b1c1c]">Stock a Comprar (Alertas)</h2>
        </div>
        <span class="px-3 py-1 bg-error-container text-error text-xs font-bold rounded-full">{{ $lowStockProducts->count() }} productos</span>
      </div>
      
      <div class="p-0">
        @if($lowStockProducts->isEmpty())
          <div class="p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-primary/30 mb-4">check_circle</span>
            <p class="text-on-surface-variant font-medium">¡Todo al día! No tienes productos con stock bajo.</p>
          </div>
        @else
          <table class="w-full text-left">
            <thead class="bg-surface-container-lowest text-[#3c4a41] text-xs font-bold uppercase">
              <tr>
                <th class="px-8 py-4">Producto</th>
                <th class="px-8 py-4">Stock Actual</th>
                <th class="px-8 py-4">Estado</th>
                <th class="px-8 py-4 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
              @foreach($lowStockProducts as $p)
              <tr class="hover:bg-surface-container-lowest/50 transition-colors">
                <td class="px-8 py-5">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-surface-container">
                       <x-product-image :product="$p" class="w-full h-full object-cover"/>
                    </div>
                    <span class="font-bold text-[#1b1c1c] text-sm">{{ $p->name }}</span>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <span class="text-lg font-black {{ $p->stock == 0 ? 'text-error' : 'text-amber-600' }}">{{ $p->stock }}</span>
                </td>
                <td class="px-8 py-5">
                  @if($p->stock == 0)
                    <span class="px-2 py-1 bg-error-container text-error text-[10px] font-black uppercase rounded">Agotado</span>
                  @else
                    <span class="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-black uppercase rounded">Crítico</span>
                  @endif
                </td>
                <td class="px-8 py-5 text-right">
                  <a href="{{ route('merchant.inventory') }}" class="text-primary font-bold text-sm hover:underline">Reponer</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    </div>

    {{-- Info Card --}}
    <div class="space-y-6">
      <div class="bg-secondary-gradient rounded-3xl p-8 text-white shadow-lg relative overflow-hidden">
        <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-9xl opacity-10">insights</span>
        <h3 class="text-xl font-bold mb-2">Análisis de Puntos</h3>
        <p class="text-white/80 text-sm mb-6 leading-relaxed">
          Los puntos de descuento son una inversión para fidelizar clientes. Recuerda que por cada 1,000 COP que tus clientes compran, ganan puntos para su siguiente compra.
        </p>
        <div class="bg-white/10 rounded-2xl p-4 border border-white/20">
          <p class="text-xs font-bold uppercase tracking-widest text-white/60 mb-1">Costo Promedio</p>
          <p class="text-xl font-black">5% del Subtotal</p>
        </div>
      </div>

      <div class="bg-white rounded-3xl p-6 shadow-sm border border-outline-variant/30">
        <h3 class="font-bold text-[#1b1c1c] mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">info</span> Ayuda Financiera
        </h3>
        <ul class="space-y-4">
          <li class="flex items-start gap-3">
            <span class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5 shrink-0"></span>
            <p class="text-xs text-[#3c4a41]">Los ingresos reales descuentan automáticamente la inversión publicitaria aprobada.</p>
          </li>
          <li class="flex items-start gap-3">
            <span class="w-1.5 h-1.5 bg-primary rounded-full mt-1.5 shrink-0"></span>
            <p class="text-xs text-[#3c4a41]">Los descuentos por puntos son proporcionales si el cliente compró en varias tiendas a la vez.</p>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
