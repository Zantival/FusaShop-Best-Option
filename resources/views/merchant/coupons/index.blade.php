@extends('layouts.app')

@section('title', 'Gestión de Cupones')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black text-on-surface font-headline tracking-tight">Mis Cupones</h1>
            <p class="text-on-surface-variant">Crea y gestiona códigos de descuento para tus clientes</p>
        </div>
        <button onclick="document.getElementById('coupon-modal').classList.remove('hidden')" class="px-6 py-3 bg-primary-gradient text-white font-bold rounded-2xl flex items-center gap-2 shadow-lg hover:scale-105 transition-transform">
            <span class="material-symbols-outlined">add_circle</span>
            Nuevo Cupón
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($coupons as $coupon)
            <div class="bg-surface-container-lowest p-6 rounded-3xl shadow-card border border-outline-variant/20 relative overflow-hidden group">
                @if(!$coupon->is_active)
                    <div class="absolute inset-0 bg-surface/60 backdrop-blur-[2px] z-10 flex items-center justify-center">
                        <span class="px-4 py-1 bg-error/10 text-error font-bold rounded-full border border-error/20">Inactivo</span>
                    </div>
                @endif

                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-2xl">confirmation_number</span>
                    </div>
                    <div class="flex gap-2 z-20">
                        <form action="{{ route('merchant.coupons.toggle', $coupon->id) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="p-2 bg-surface-container-high rounded-xl hover:bg-primary/10 hover:text-primary transition-colors" title="Alternar estado">
                                <span class="material-symbols-outlined text-sm">{{ $coupon->is_active ? 'visibility_off' : 'visibility' }}</span>
                            </button>
                        </form>
                        <form action="{{ route('merchant.coupons.destroy', $coupon->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este cupón?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 bg-surface-container-high rounded-xl hover:bg-error/10 hover:text-error transition-colors">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mb-4">
                    <span class="text-xs font-black text-primary uppercase tracking-widest">Código</span>
                    <h3 class="text-2xl font-black text-on-surface font-headline">{{ $coupon->code }}</h3>
                </div>

                <div class="space-y-3 pt-4 border-t border-outline-variant/20">
                    <div class="flex justify-between text-sm">
                        <span class="text-on-surface-variant font-medium">Descuento</span>
                        <span class="font-bold text-on-surface">{{ $coupon->type === 'fixed' ? '$'.number_format($coupon->value) : $coupon->value.'%' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-on-surface-variant font-medium">Min. Compra</span>
                        <span class="font-bold text-on-surface">${{ number_format($coupon->min_order_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-on-surface-variant font-medium">Uso</span>
                        <span class="font-bold text-on-surface">{{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}</span>
                    </div>
                    @if($coupon->expires_at)
                    <div class="flex justify-between text-sm">
                        <span class="text-on-surface-variant font-medium">Expira</span>
                        <span class="font-bold text-on-surface {{ $coupon->expires_at->isPast() ? 'text-error' : '' }}">{{ $coupon->expires_at->format('d/m/Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-surface-container rounded-full flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-on-surface-variant text-4xl">inventory_2</span>
                </div>
                <h3 class="text-xl font-bold text-on-surface">No tienes cupones</h3>
                <p class="text-on-surface-variant mt-1">Empieza creando uno para incentivar tus ventas.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal -->
<div id="coupon-modal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-on-surface/40 backdrop-blur-sm" onclick="document.getElementById('coupon-modal').classList.add('hidden')"></div>
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6">
        <div class="bg-surface-container-lowest rounded-3xl shadow-card p-8 border border-outline-variant/20">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-black text-on-surface font-headline">Crear Nuevo Cupón</h2>
                <button onclick="document.getElementById('coupon-modal').classList.add('hidden')" class="p-2 hover:bg-surface-container-low rounded-full transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form action="{{ route('merchant.coupons.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-on-surface-variant mb-2">Código del Cupón</label>
                    <input type="text" name="code" required placeholder="EJ: FUSASHOP10" class="w-full px-4 py-3 bg-surface-container-high rounded-2xl border-0 ring-1 ring-outline-variant/30 focus:ring-2 focus:ring-primary outline-none transition-all uppercase">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-on-surface-variant mb-2">Tipo</label>
                        <select name="type" class="w-full px-4 py-3 bg-surface-container-high rounded-2xl border-0 ring-1 ring-outline-variant/30 focus:ring-2 focus:ring-primary outline-none transition-all">
                            <option value="fixed">Fijo ($)</option>
                            <option value="percentage">Porcentaje (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-on-surface-variant mb-2">Valor</label>
                        <input type="number" name="value" step="0.01" required value="0" class="w-full px-4 py-3 bg-surface-container-high rounded-2xl border-0 ring-1 ring-outline-variant/30 focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-on-surface-variant mb-2">Mínimo de Compra</label>
                    <input type="number" name="min_order_amount" step="0.01" value="0" class="w-full px-4 py-3 bg-surface-container-high rounded-2xl border-0 ring-1 ring-outline-variant/30 focus:ring-2 focus:ring-primary outline-none transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-on-surface-variant mb-2">Límite de Uso</label>
                        <input type="number" name="usage_limit" placeholder="Opcional" class="w-full px-4 py-3 bg-surface-container-high rounded-2xl border-0 ring-1 ring-outline-variant/30 focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-on-surface-variant mb-2">Expira</label>
                        <input type="date" name="expires_at" class="w-full px-4 py-3 bg-surface-container-high rounded-2xl border-0 ring-1 ring-outline-variant/30 focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full py-4 bg-primary-gradient text-white font-bold rounded-2xl shadow-lg hover:opacity-90 active:scale-95 transition-all mt-4">
                    Crear Cupón
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
