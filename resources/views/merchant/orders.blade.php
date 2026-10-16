@extends('layouts.app')
@section('title','Pedidos')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-8">Gestión de Pedidos</h1>

  @if($orders->isEmpty())
    <div class="text-center py-20 bg-surface-container-lowest rounded-2xl shadow-card">
      <span class="material-symbols-outlined text-on-surface-variant text-8xl mb-4 block">receipt_long</span>
      <p class="text-on-surface-variant text-lg">Aún no hay pedidos.</p>
    </div>
  @else
    <div class="space-y-5">
      @foreach($orders as $order)
      @php $sc=['pending'=>'bg-[#ffdea8] text-[#7c5800]','processing'=>'bg-[#6efcb9]/40 text-[#003f28]','shipped'=>'bg-blue-100 text-blue-700','delivered'=>'bg-[#6efcb9]/60 text-[#006c47]','cancelled'=>'bg-[#ffdad6] text-[#ba1a1a]']; @endphp
      <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
          <div>
            <p class="text-xs text-on-surface-variant uppercase tracking-wider">Pedido #{{ $order->id }} · {{ $order->created_at->format('d M Y H:i') }}</p>
            <p class="font-['Manrope'] font-bold text-on-background">{{ e($order->user->name) }} · {{ e($order->user->email) }}</p>
            <p class="text-sm text-on-surface-variant mt-0.5">{{ e($order->shipping_address) }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $sc[$order->status] ?? '' }}">{{ $order->stageLabel() }}</span>
            <div class="flex flex-col items-end">
              @if($order->discount > 0)
                <span class="text-[11px] font-bold text-[#ba1a1a] bg-[#ffdad6] px-2 py-0.5 rounded-md mb-1">- Puntos usados: {{ $order->points_used }} pts (${{ number_format($order->discount, 0, ',', '.') }})</span>
              @endif
              <span class="font-bold text-primary text-xl">${{ number_format($order->total,0,',','.') }}</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
          @foreach($order->items as $item)
          <div class="flex flex-col bg-surface-container-low rounded-xl px-4 py-3 text-sm">
            <div class="flex items-center gap-2">
              <span class="font-bold text-on-background">{{ e($item->product->name ?? 'Producto') }}</span>
              <span class="bg-surface-container rounded-full px-2 py-0.5 text-[10px] font-black">x{{ $item->quantity }}</span>
              <span class="text-primary font-black ml-auto">${{ number_format($item->price,0,',','.') }}</span>
            </div>
            @if($item->selected_options)
              <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach($item->selected_options as $key => $val)
                  <span class="text-[9px] bg-white px-2 py-0.5 rounded border border-outline-variant/30 text-on-surface-variant">
                    <span class="font-black opacity-50">{{ $key }}:</span> {{ $val }}
                  </span>
                @endforeach
              </div>
            @endif
          </div>
          @endforeach
        </div>

        <form method="POST" action="{{ route('merchant.orders.update', $order->id) }}" class="flex items-center gap-3">
          @csrf @method('PATCH')
          <select name="status" class="px-4 py-2 bg-surface-container-highest rounded-xl border-0 outline-none focus:ring-2 focus:ring-primary text-sm font-medium">
            @php $current = ($order->status==='processing' && $order->packed_at) ? 'packing' : $order->status; @endphp
            @foreach(['pending'=>'Pendiente','processing'=>'Pagado','packing'=>'En empaque','shipped'=>'Enviado','delivered'=>'Entregado','cancelled'=>'Cancelado'] as $val=>$label)
              <option value="{{ $val }}" {{ $current===$val?'selected':'' }}>{{ $label }}</option>
            @endforeach
          </select>
          <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-xl hover:opacity-90 transition-all">
            Actualizar
          </button>
          @if($order->label_url)
            <a href="{{ $order->label_url }}" target="_blank" rel="noopener" class="px-4 py-2 bg-surface-container text-on-surface text-sm font-semibold rounded-xl hover:bg-surface-container-highest flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px]">print</span> Imprimir guía {{ strtoupper($order->shipping_carrier) }} {{ $order->tracking_number }}
            </a>
          @elseif($order->payment_status === 'paid')
            <span class="text-xs text-on-surface-variant">Guía en generación…</span>
          @else
            <span class="text-xs text-on-surface-variant">Esperando pago (Wompi)</span>
          @endif
        </form>
      </div>
      @endforeach
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
  @endif
</div>
@endsection
