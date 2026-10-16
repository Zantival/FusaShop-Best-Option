@extends('layouts.app')
@section('title','Mis direcciones')
@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-8">Libreta de direcciones</h1>

  @if(session('success'))<div class="mb-6 p-4 rounded-xl bg-[#6efcb9]/40 text-[#003f28] text-sm font-semibold">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="mb-6 p-4 rounded-xl bg-[#ffdad6] text-[#ba1a1a] text-sm font-semibold">{{ session('error') }}</div>@endif
  @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-[#ffdad6] text-[#ba1a1a] text-sm"><ul class="list-disc pl-5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
  @endif

  <div class="space-y-4 mb-10">
    @forelse($addresses as $a)
      <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
        <details>
          <summary class="flex items-start justify-between gap-4 cursor-pointer list-none">
            <div class="text-sm">
              <strong>{{ e($a->label) }}</strong>
              @if($a->is_default)<span class="ml-2 text-[10px] font-bold uppercase bg-primary/10 text-primary px-2 py-0.5 rounded-full">Principal</span>@endif
              <p class="text-on-surface-variant mt-1">{{ e($a->recipient) }} · {{ e($a->phone) }}</p>
              <p class="text-on-surface-variant">{{ e($a->fullLine()) }}</p>
            </div>
            <span class="text-primary text-sm font-semibold shrink-0">Editar</span>
          </summary>
          <form method="POST" action="{{ route('consumer.addresses.update', $a->id) }}" class="mt-4 grid grid-cols-2 gap-3">
            @csrf @method('PUT')
            @include('consumer.partials.address-fields', ['a' => $a, 'states' => $states])
            <div class="col-span-2 flex items-center justify-between">
              <label class="text-sm flex items-center gap-2"><input type="checkbox" name="is_default" value="1" {{ $a->is_default ? 'checked' : '' }}> Dirección principal</label>
              <button class="px-5 py-2 bg-primary text-white rounded-xl text-sm font-bold">Guardar cambios</button>
            </div>
          </form>
          <form method="POST" action="{{ route('consumer.addresses.destroy', $a->id) }}" class="mt-2" onsubmit="return confirm('¿Eliminar esta dirección?')">
            @csrf @method('DELETE')
            <button class="text-[#ba1a1a] text-sm font-semibold hover:underline">Eliminar dirección</button>
          </form>
        </details>
      </div>
    @empty
      <p class="text-on-surface-variant">Aún no tienes direcciones. Agrega la primera para poder comprar.</p>
    @endforelse
  </div>

  <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
    <h2 class="font-['Manrope'] font-bold text-lg mb-4">Nueva dirección</h2>
    <form method="POST" action="{{ route('consumer.addresses.store') }}" class="grid grid-cols-2 gap-3">
      @csrf
      @include('consumer.partials.address-fields', ['a' => null, 'states' => $states])
      <div class="col-span-2 flex items-center justify-between">
        <label class="text-sm flex items-center gap-2"><input type="checkbox" name="is_default" value="1"> Dirección principal</label>
        <button class="px-5 py-2 bg-primary text-white rounded-xl text-sm font-bold">Guardar dirección</button>
      </div>
    </form>
  </div>
</div>
@endsection
