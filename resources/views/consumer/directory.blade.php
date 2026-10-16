@extends('layouts.app')
@section('title','Directorio de Marcas')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
  <div class="text-center py-10 bg-white rounded-3xl shadow-sm mb-10" style="background: linear-gradient(145deg, #ffffff, #f0eded);">
    <h1 class="text-4xl font-['Manrope'] font-extrabold text-[#1b1c1c] mb-3">Directorio de Empresas Registradas</h1>
    <p class="text-[#3c4a41] text-lg max-w-2xl mx-auto">Estas son las marcas verificadas y validadas mediante nuestro proceso KYC, autorizadas para comerciar.</p>
  </div>

  @if($merchants->isEmpty())
    <div class="text-center py-20 bg-white rounded-2xl shadow-sm">
      <span class="material-symbols-outlined text-7xl text-[#3c4a41] block mb-3">store_off</span>
      <p class="text-[#3c4a41]">No hay empresas verificadas actualmente.</p>
    </div>
  @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($merchants as $merchant)
      <a href="{{ route('consumer.merchant.profile', $merchant->merchant_id) }}" class="flex bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all group overflow-hidden border border-transparent hover:border-[#006c47] relative">
        <div class="w-20 h-20 rounded-full bg-[#f0eded] overflow-hidden shrink-0 flex items-center justify-center border-2 border-white shadow-sm z-10 relative">
          @if($merchant->logo_path)
            <img src="{{ Storage::url($merchant->logo_path) }}" alt="Logo" class="w-full h-full object-cover">
          @else
            <span class="material-symbols-outlined text-gray-400 text-3xl">storefront</span>
          @endif
        </div>
        <div class="ml-5 flex flex-col justify-center z-10 relative">
          <h2 class="text-xl font-['Manrope'] font-bold text-[#1b1c1c] group-hover:text-[#006c47] transition-colors truncate">{{ e($merchant->company_name) }}</h2>
          <p class="text-sm text-[#3c4a41] mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-xs">verified</span> KYC Validado</p>
        </div>
        {{-- Background effect --}}
        <div class="absolute right-0 top-0 bottom-0 w-32 opacity-5 translate-x-10 group-hover:translate-x-5 transition-transform duration-500">
           <span class="material-symbols-outlined text-9xl">storefront</span>
        </div>
      </a>
      @endforeach
    </div>
    <div class="mt-8 w-full">
      {{ $merchants->links() }}
    </div>
  @endif
</div>
@endsection
