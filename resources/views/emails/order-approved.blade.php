<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#1b1c1c">
  <h2>¡Compra aprobada!</h2>
  <p>Hola {{ $order->user->name }}, recibimos tu pago del pedido <strong>{{ $order->reference }}</strong>.</p>
  <table width="100%" cellpadding="6" style="border-collapse:collapse;border-top:1px solid #ddd">
    @foreach($order->items as $item)
    <tr style="border-bottom:1px solid #eee">
      <td>{{ $item->product?->name }} × {{ $item->quantity }}</td>
      <td align="right">${{ number_format($item->quantity * $item->price, 0, ',', '.') }}</td>
    </tr>
    @endforeach
    <tr><td>Envío ({{ $order->shipping_carrier }})</td><td align="right">${{ number_format($order->shipping_cost, 0, ',', '.') }}</td></tr>
    @if($order->discount > 0)
    <tr><td>Descuento por puntos</td><td align="right">-${{ number_format($order->discount, 0, ',', '.') }}</td></tr>
    @endif
    <tr><td><strong>Total</strong></td><td align="right"><strong>${{ number_format($order->total, 0, ',', '.') }}</strong></td></tr>
  </table>
  @if($order->invoice_number)
    <p>Adjuntamos tu factura electrónica <strong>{{ $order->invoice_number }}</strong> (PDF y XML).<br>
    <small>CUFE: {{ $order->invoice_cufe }}</small></p>
  @else
    <p>Tu factura electrónica está en proceso y estará disponible en tu portal de pedidos.</p>
  @endif
  <p><a href="{{ route('consumer.orders.receipt', $order->id) }}">Ver mi pedido</a></p>
</div>
