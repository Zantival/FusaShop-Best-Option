@extends('layouts.app')

@section('title', 'Recibo de Compra #' . $order->id)

@section('content')
<div class="max-w-3xl mx-auto px-6 py-12">
    <!-- Action Buttons (Hidden on print) -->
    <div class="flex justify-between items-center mb-8 no-print">
        <a href="{{ route('consumer.orders') }}" class="flex items-center gap-2 text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
            Volver a mis pedidos
        </a>
        <button onclick="window.print()" class="flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/20 hover:opacity-90 active:scale-95 transition-all">
            <span class="material-symbols-outlined">print</span>
            Imprimir Recibo
        </button>
    </div>

    <!-- Receipt Card -->
    <div class="bg-white rounded-3xl shadow-2xl border border-surface-container overflow-hidden print:shadow-none print:border-none">
        <!-- Header -->
        <div class="bg-primary/5 p-8 border-b border-surface-container flex flex-col sm:flex-row justify-between gap-6">
            <div>
                <img src="/logo-fusa.png" alt="FusaShop" class="h-10 mb-4 brightness-0 opacity-80">
                <h1 class="text-2xl font-black text-on-background">Recibo de Compra</h1>
                <p class="text-sm text-on-surface-variant">#{{ str_pad($order->id, 8, '0', STR_PAD_LEFT) }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-bold text-on-background uppercase tracking-wider">Fecha de Emisión</p>
                <p class="text-on-surface-variant">{{ $order->created_at->format('d/m/Y - h:i A') }}</p>
            </div>
        </div>

        <!-- Details -->
        <div class="p-8 grid grid-cols-1 sm:grid-cols-2 gap-12">
            <div>
                <h3 class="text-xs font-black text-on-surface-variant uppercase tracking-widest mb-4">Información del Cliente</h3>
                <p class="font-bold text-on-surface">{{ auth()->user()->name }}</p>
                <p class="text-sm text-on-surface-variant">{{ auth()->user()->email }}</p>
                @if($order->shipping_address)
                    <div class="mt-4">
                        <h4 class="text-[10px] font-bold text-on-surface-variant uppercase">Dirección de Envío</h4>
                        <p class="text-sm text-on-surface">{{ $order->shipping_address }}</p>
                    </div>
                @endif
            </div>
            <div class="text-right">
                <h3 class="text-xs font-black text-on-surface-variant uppercase tracking-widest mb-4">Método de Pago</h3>
                <div class="flex items-center justify-end gap-2 text-on-surface font-bold">
                    <span class="material-symbols-outlined text-primary">
                        {{ $order->payment_method === 'card' ? 'credit_card' : ($order->payment_method === 'transfer' ? 'account_balance' : 'payments') }}
                    </span>
                    {{ ucfirst($order->payment_method === 'card' ? 'Tarjeta' : ($order->payment_method === 'transfer' ? 'Transferencia' : 'Efectivo')) }}
                </div>
                <div class="mt-4">
                    <span class="px-3 py-1 bg-green-100 text-green-700 text-[10px] font-black rounded-full uppercase">Completado</span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="px-8 pb-8">
            <div class="border rounded-2xl overflow-hidden border-surface-container">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-on-surface-variant uppercase">Artículo</th>
                            <th class="px-6 py-4 text-[10px] font-black text-on-surface-variant uppercase text-center">Cant.</th>
                            <th class="px-6 py-4 text-[10px] font-black text-on-surface-variant uppercase text-right">Precio</th>
                            <th class="px-6 py-4 text-[10px] font-black text-on-surface-variant uppercase text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container">
                        @foreach($order->items as $item)
                        <tr class="hover:bg-surface-container/20 transition-colors">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-on-surface">{{ $item->product->name }}</p>
                                <p class="text-[10px] text-on-surface-variant">{{ $item->product->merchant->companyProfile->company_name ?? 'FusaShop Merchant' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center text-sm">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-right text-sm font-medium">${{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-on-surface">${{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="mt-8 flex flex-col items-end gap-2">
                @php $subtotal = $order->total + $order->discount; @endphp
                <div class="flex justify-between w-full max-w-[200px] text-sm text-on-surface-variant">
                    <span>Subtotal:</span>
                    <span>${{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                @if($order->discount > 0)
                <div class="flex justify-between w-full max-w-[200px] text-sm text-red-600 font-bold">
                    <span>Descuento ({{ $order->points_used }} pts):</span>
                    <span>-${{ number_format($order->discount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between w-full max-w-[200px] text-[10px] text-green-600 font-bold mt-1">
                    <span>Puntos ganados:</span>
                    <span>+{{ $order->points_earned }} pts</span>
                </div>
                <div class="flex justify-between w-full max-w-[200px] text-[10px] text-primary font-black uppercase tracking-tighter">
                    <span>Saldo acumulado:</span>
                    <span>{{ $userPoints }} pts</span>
                </div>
                <div class="flex justify-between w-full max-w-[200px] border-t border-surface-container pt-2 mt-2">
                    <span class="text-lg font-black text-on-background">TOTAL:</span>
                    <span class="text-xl font-black text-primary">${{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-surface-container-low p-8 text-center border-t border-surface-container">
            <p class="text-xs text-on-surface-variant">Gracias por confiar en <strong>FusaShop</strong>. Si tienes dudas, contáctanos a soporte@fusashop.com</p>
        </div>
    </div>

    <!-- Extra space for printing -->
    <div class="h-20 no-print"></div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .max-w-3xl { max-width: 100% !important; margin: 0 !important; width: 100% !important; }
    main { margin: 0 !important; padding: 0 !important; }
    header, footer { display: none !important; }
}
</style>
@endsection
