@extends('layouts.app')
@section('title','Mis Pedidos')
@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-8">Mis Pedidos</h1>
  @if($orders->isEmpty())
    <div class="text-center py-20 bg-surface-container-lowest rounded-2xl shadow-card">
      <span class="material-symbols-outlined text-on-surface-variant text-8xl mb-4 block">receipt_long</span>
      <p class="text-on-surface-variant text-lg">Aún no tienes pedidos.</p>
      <a href="{{ route('consumer.catalog') }}" class="text-primary font-semibold hover:underline mt-2 inline-block">Empezar a comprar</a>
    </div>
  @else
    <div class="space-y-6">
      @foreach($orders as $order)
      <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
          <div>
            <span class="text-xs text-on-surface-variant uppercase tracking-wider">Pedido #{{ $order->id }}</span>
            <p class="font-['Manrope'] font-bold text-on-background">{{ $order->created_at->format('d M Y') }}</p>
          </div>
          <div class="flex items-center gap-3">
            @php $statusColors=['pending'=>'bg-[#ffdea8] text-[#7c5800]','processing'=>'bg-[#6efcb9]/40 text-[#003f28]','shipped'=>'bg-emerald-100 text-emerald-800','delivered'=>'bg-[#6efcb9]/60 text-[#006c47]','cancelled'=>'bg-[#ffdad6] text-[#ba1a1a]']; @endphp
            <div class="flex flex-col items-end mr-2 border-r border-outline-variant/30 pr-4">
              <span class="text-[10px] uppercase font-bold text-on-surface-variant tracking-wider mb-1">Estado del Envío</span>
              <span class="px-3 py-1 rounded-full text-xs font-bold shadow-sm {{ $statusColors[$order->status] ?? 'bg-surface-container text-on-surface' }}">
                {{ $order->stageLabel() }}
              </span>
            </div>
            <div class="flex flex-col items-end">
              @if($order->discount > 0)
                <span class="text-[11px] font-bold text-[#ba1a1a] bg-[#ffdad6] px-2 py-0.5 rounded-md mb-1">- Descuento Puntos: ${{ number_format($order->discount, 0, ',', '.') }}</span>
              @endif
              <span class="font-bold text-primary text-lg ml-1">${{ number_format($order->total,0,',','.') }}</span>
            </div>
          </div>
        </div>
        <div class="mt-5 pt-4 border-t border-outline-variant/30 flex justify-between items-center">
          <div class="flex gap-3 flex-wrap">
            @foreach($order->items as $item)
            <div class="flex items-center gap-2 bg-surface-container-low rounded-xl px-3 py-2">
              <span class="text-sm text-on-surface-variant font-medium">{{ e($item->product->name ?? 'Producto eliminado') }}</span>
              <span class="text-xs bg-surface-container rounded-full px-2 py-0.5 font-bold">x{{ $item->quantity }}</span>
            </div>
            @endforeach
          </div>
          <div class="flex gap-2 flex-wrap shrink-0 items-center">
            @if($order->tracking_number)
              <button type="button" data-track="{{ route('consumer.orders.tracking', $order->id) }}" data-fallback="{{ $order->tracking_url }}" class="track-btn flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary rounded-xl text-xs font-bold transition-all">
                <span class="material-symbols-outlined text-[18px]">local_shipping</span> Rastrear ({{ strtoupper($order->shipping_carrier) }} {{ $order->tracking_number }})
              </button>
            @endif
            @if($order->invoice_pdf_path)
              <a href="{{ route('consumer.orders.invoice', [$order->id, 'pdf']) }}" class="flex items-center gap-2 px-4 py-2 bg-surface-container hover:bg-surface-container-highest rounded-xl text-xs font-bold transition-all">
                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span> Factura PDF
              </a>
            @endif
            @if($order->invoice_xml_path)
              <a href="{{ route('consumer.orders.invoice', [$order->id, 'xml']) }}" class="flex items-center gap-2 px-4 py-2 bg-surface-container hover:bg-surface-container-highest rounded-xl text-xs font-bold transition-all">
                <span class="material-symbols-outlined text-[18px]">code</span> XML
              </a>
            @endif
          <a href="{{ route('consumer.orders.receipt', $order->id) }}" class="flex items-center gap-2 px-4 py-2 bg-surface-container hover:bg-surface-container-highest text-on-surface-variant rounded-xl text-xs font-bold transition-all shrink-0">
            <span class="material-symbols-outlined text-[18px]">receipt</span>
            Ver Recibo
          </a>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
  @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.track-btn').forEach(function (btn) {
  btn.addEventListener('click', async function () {
    var box = btn.closest('.bg-surface-container-lowest').querySelector('.track-result');
    if (!box) { box = document.createElement('div'); box.className = 'track-result mt-4 p-4 bg-surface-container-low rounded-xl text-sm'; btn.closest('.bg-surface-container-lowest').appendChild(box); }
    box.textContent = 'Consultando transportadora...';
    try {
      var r = await fetch(btn.dataset.track, { headers: { 'Accept': 'application/json' } });
      var d = await r.json();
      if (!r.ok) { box.innerHTML = ''; box.append(d.message || 'Sin información.'); if (d.url) { var a = document.createElement('a'); a.href = d.url; a.target = '_blank'; a.rel = 'noopener'; a.className = 'text-primary font-bold ml-2'; a.textContent = 'Abrir rastreo del transportador'; box.append(a); } return; }
      box.innerHTML = '';
      var h = document.createElement('p'); h.className = 'font-bold'; h.textContent = 'Estado: ' + (d.status || 'sin novedades'); box.append(h);
      (d.events || []).slice(0, 8).forEach(function (e) { var p = document.createElement('p'); p.className = 'text-on-surface-variant'; p.textContent = [e.date, e.time, e.description || e.status].filter(Boolean).join(' · '); box.append(p); });
      if (d.url) { var l = document.createElement('a'); l.href = d.url; l.target = '_blank'; l.rel = 'noopener'; l.className = 'text-primary font-bold'; l.textContent = 'Ver en el sitio del transportador'; box.append(l); }
    } catch (err) { box.textContent = 'No fue posible consultar el rastreo.'; }
  });
});
</script>
@endpush