<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#1b1c1c">
  <h2>Stock crítico</h2>
  <p>El producto <strong>{{ $product->name }}</strong> tiene <strong>{{ $product->availableStock() }}</strong> unidades disponibles
     (umbral: {{ $product->low_stock_threshold }}).</p>
  <p><a href="{{ route('merchant.inventory') }}">Gestionar inventario</a></p>
</div>
