@extends('layouts.app')

@section('title', 'Resultado del Pago')

@section('content')
@if($status === 'pending')<meta http-equiv="refresh" content="6">@endif
<div class="max-w-md mx-auto px-6 py-20 text-center">
    <div class="mb-10">
        @if($status === 'success')
            <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-green-200">
                <span class="material-symbols-outlined text-5xl">check_circle</span>
            </div>
            <h1 class="text-3xl font-black text-on-surface font-headline mb-4">¡Pago Exitoso!</h1>
        @elseif($status === 'error')
            <div class="w-24 h-24 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-red-200">
                <span class="material-symbols-outlined text-5xl">cancel</span>
            </div>
            <h1 class="text-3xl font-black text-on-surface font-headline mb-4">Pago Rechazado</h1>
        @else
            <div class="w-24 h-24 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-amber-200">
                <span class="material-symbols-outlined text-5xl">pending</span>
            </div>
            <h1 class="text-3xl font-black text-on-surface font-headline mb-4">Pago en Proceso</h1>
        @endif
        
        <p class="text-on-surface-variant text-lg">{{ $message }}</p>
    </div>

    <div class="bg-surface-container-low p-6 rounded-3xl border border-outline-variant/10 mb-8 text-left">
        <div class="flex justify-between items-center mb-2">
            <span class="text-xs font-bold text-on-surface-variant uppercase">Referencia</span>
            <span class="text-sm font-mono font-bold text-on-surface">{{ $reference }}</span>
        </div>
        <p class="text-[10px] text-on-surface-variant text-center mt-4 pt-4 border-t border-outline-variant/10">Wompi - FusaShop Secure Payments</p>
    </div>

    <div class="space-y-3">
        <a href="{{ route('consumer.orders') }}" class="block w-full py-4 bg-primary text-white font-bold rounded-2xl shadow-lg hover:opacity-90 transition-all">Ver mis pedidos</a>
        <a href="{{ route('consumer.catalog') }}" class="block w-full py-4 bg-surface-container text-on-surface font-bold rounded-2xl hover:bg-surface-container-high transition-all">Seguir comprando</a>
    </div>
</div>
@endsection
