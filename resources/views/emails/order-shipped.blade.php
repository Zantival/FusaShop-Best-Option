<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#1b1c1c">
  <h2>Tu pedido va en camino</h2>
  <p>Hola {{ $order->user->name }}, despachamos el pedido <strong>{{ $order->reference }}</strong>.</p>
  <p>Transportadora: <strong>{{ strtoupper($order->shipping_carrier) }}</strong><br>
     Número de guía: <strong>{{ $order->tracking_number }}</strong></p>
  @if($order->tracking_url)
    <p><a href="{{ $order->tracking_url }}">Rastrear mi envío</a></p>
  @endif
</div>
