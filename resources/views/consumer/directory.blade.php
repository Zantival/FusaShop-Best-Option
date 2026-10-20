@extends('layouts.app')
@section('title', 'Negocios Registrados en Fusagasugá | FusaShop')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header Compacto -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 bg-white p-4 sm:p-5 rounded-2xl shadow-card border border-slate-100">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 shadow-xs">
        <span class="material-symbols-outlined text-2xl">storefront</span>
      </div>
      <div>
        <h1 class="text-lg sm:text-xl font-['Manrope'] font-black text-slate-900 leading-tight">
          Negocios y Empresas Registradas
        </h1>
        <p class="text-[11px] font-semibold text-slate-500 mt-0.5">
          {{ $merchants->total() }} {{ $merchants->total() === 1 ? 'negocio verificado' : 'negocios verificados' }}
          @if(!empty($search)) · Resultados para "{{ $search }}" @endif
        </p>
      </div>
    </div>

    <!-- Buscador compacto integrado -->
    <form method="GET" action="{{ route('consumer.directory') }}" class="flex items-center gap-2 w-full sm:w-auto">
      <div class="relative flex-1 sm:w-64">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[16px]">search</span>
        <input type="text" name="search" value="{{ $search ?? '' }}"
               placeholder="Buscar negocio o marca..."
               class="w-full bg-slate-50 hover:bg-white focus:bg-white pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 outline-none focus:ring-2 focus:ring-[#10B981]/20 focus:border-[#10B981] transition-all">
      </div>
      <button type="submit" class="px-3.5 py-2 bg-[#006c47] hover:bg-[#005236] text-white text-xs font-bold rounded-xl transition-all shadow-xs shrink-0 cursor-pointer active:scale-95">
        Buscar
      </button>
      @if(!empty($search))
        <a href="{{ route('consumer.directory') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl shrink-0" title="Limpiar búsqueda">
          Limpiar
        </a>
      @endif
    </form>
  </div>

  @if($merchants->isEmpty())
    <div class="text-center py-16 bg-white rounded-3xl p-8 shadow-card border border-slate-100 space-y-4 max-w-lg mx-auto">
      <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center">
        <span class="material-symbols-outlined text-4xl">storefront</span>
      </div>
      <h3 class="text-lg font-bold text-slate-900">No encontramos negocios con ese criterio</h3>
      <p class="text-xs text-slate-500">Prueba ajustando el término de búsqueda o explora todos los comercios registrados en Fusagasugá.</p>
      @if(!empty($search))
        <div class="pt-2">
          <a href="{{ route('consumer.directory') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#10B981] text-white text-xs font-bold rounded-xl shadow-sm hover:opacity-95 transition-all">
            <span class="material-symbols-outlined text-base">refresh</span> Ver todos los negocios
          </a>
        </div>
      @endif
    </div>
  @else
    <!-- Grilla de Tarjetas de Negocios -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($merchants as $merchant)
        <a href="{{ route('consumer.merchant.profile', $merchant->merchant_id) }}"
           class="group bg-white rounded-3xl p-6 shadow-card hover:shadow-xl border border-slate-100 hover:border-[#10B981]/50 transition-all flex flex-col justify-between relative overflow-hidden">
          
          <div>
            <!-- Top Card: Logo + Badges -->
            <div class="flex items-start justify-between gap-4 mb-4">
              <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200/80 overflow-hidden shrink-0 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                @if($merchant->logoUrl())
                  <img src="{{ $merchant->logoUrl() }}" alt="{{ $merchant->company_name }}" class="w-full h-full object-cover">
                @else
                  <div class="w-full h-full bg-gradient-to-tr from-[#10B981] to-[#047857] text-white flex items-center justify-center font-black text-xl">
                    {{ strtoupper(substr($merchant->company_name, 0, 2)) }}
                  </div>
                @endif
              </div>

              <div class="flex flex-col items-end gap-1">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-[#006c47] border border-emerald-200">
                  <span class="material-symbols-outlined text-[12px]">verified</span>
                  <span>KYC Validado</span>
                </span>
                @if(!empty($merchant->business_type))
                  <span class="text-[9.5px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                    {{ $merchant->business_type }}
                  </span>
                @endif
              </div>
            </div>

            <!-- Información del Negocio -->
            <h2 class="text-base sm:text-lg font-['Manrope'] font-black text-slate-900 group-hover:text-[#006c47] transition-colors line-clamp-1">
              {{ $merchant->company_name }}
            </h2>

            @if(!empty($merchant->description))
              <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                {{ $merchant->description }}
              </p>
            @endif

            @if(!empty($merchant->address))
              <p class="text-[11px] text-slate-400 mt-2.5 flex items-center gap-1 truncate">
                <span class="material-symbols-outlined text-xs text-slate-400">location_on</span>
                <span>{{ $merchant->address }}</span>
              </p>
            @endif
          </div>

          <!-- Bottom CTA -->
          <div class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-[#006c47] group-hover:text-[#10B981] transition-colors">
            <span>Visitar Tienda & Productos</span>
            <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
          </div>
        </a>
      @endforeach
    </div>

    <!-- Paginación -->
    @if($merchants->hasPages())
      <div class="mt-8 flex justify-center">
        {{ $merchants->links() }}
      </div>
    @endif
  @endif

</div>
@endsection
