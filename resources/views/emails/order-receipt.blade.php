<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1b1c1c; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e2e1; border-radius: 16px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { color: #006c47; font-size: 24px; font-weight: 900; text-decoration: none; }
        .order-info { background: #f6f3f2; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #3c4a41; text-transform: uppercase; padding-bottom: 10px; }
        td { padding: 10px 0; border-top: 1px solid #e5e2e1; }
        .total { font-size: 18px; font-weight: bold; color: #006c47; text-align: right; }
        .footer { text-align: center; font-size: 12px; color: #3c4a41; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="#" class="logo">FusaShop</a>
            <h2>Tu recibo de pago</h2>
            <p>Hola {{ $order->user->name }}, gracias por confiar en el comercio local de Fusagasugá.</p>
        </div>

        <div class="order-info">
            <p><strong>Pedido:</strong> #{{ $order->id }}</p>
            <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Método de Pago:</strong> {{ strtoupper($order->payment_method) }}</p>
            <p><strong>Dirección:</strong> {{ $order->shipping_address }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->quantity * $item->price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 20px;">
            <p class="total">Total Pagado: ${{ number_format($order->total, 0, ',', '.') }} COP</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} FusaShop - Fusagasugá, Cundinamarca.</p>
            <p>Este es un recibo generado automáticamente por nuestro sistema de pagos seguros.</p>
        </div>
    </div>
</body>
</html>
