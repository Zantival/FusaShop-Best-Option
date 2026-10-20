<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmación de Pago · FusaShop</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;color:#1e293b;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 10px 25px -5px rgba(0,0,0,0.06);border:1px solid #e2e8f0;">
          
          <!-- Brand Header -->
          <tr>
            <td style="padding:28px 36px 20px;text-align:center;background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
              <div style="font-size:30px;font-weight:900;letter-spacing:-0.5px;line-height:1;font-family:'Plus Jakarta Sans',sans-serif,Arial;">
                <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span>
              </div>
              <div style="font-size:11px;color:#94a3b8;margin-top:6px;letter-spacing:1px;text-transform:uppercase;font-weight:600;">
                Confirmación de Orden & Facturación DIAN
              </div>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:36px;">
              <div style="text-align:center;margin-bottom:16px;">
                <span style="display:inline-block;background-color:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:6px 14px;border-radius:999px;">
                  ✓ Pago Aprobado
                </span>
              </div>

              @if(($recipientType ?? 'consumer') === 'merchant')
                <h2 style="margin:0 0 12px;font-size:22px;font-weight:800;color:#0f172a;text-align:center;">
                  ¡Nueva Venta Confirmada!
                </h2>
                <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#475569;text-align:center;">
                  Hola <strong>{{ $recipientName ?? 'Comerciante' }}</strong>, el pago del pedido <strong>{{ $order->reference }}</strong> (Cliente: {{ $order->user->name ?? 'Cliente' }}) fue aprobado por Wompi y facturado electrónicamente ante la DIAN.
                </p>
              @elseif(($recipientType ?? 'consumer') === 'admin')
                <h2 style="margin:0 0 12px;font-size:22px;font-weight:800;color:#0f172a;text-align:center;">
                  Copia E-Commerce · Factura DIAN Emitida
                </h2>
                <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#475569;text-align:center;">
                  Hola <strong>{{ $recipientName ?? 'Administrador FusaShop' }}</strong>, se ha emitido el soporte electrónico DIAN del pedido <strong>{{ $order->reference }}</strong>.
                </p>
              @else
                <h2 style="margin:0 0 12px;font-size:22px;font-weight:800;color:#0f172a;text-align:center;">
                  ¡Gracias por tu compra en <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span>!
                </h2>
                <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#475569;text-align:center;">
                  Hola <strong>{{ $order->user->name }}</strong>, recibimos tu pago para el pedido <strong>{{ $order->reference }}</strong> y el comercio ya está preparando tu despacho.
                </p>
              @endif

              <!-- Order Summary Table -->
              <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse:collapse;margin-bottom:24px;font-size:14px;background-color:#f8fafc;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                <thead>
                  <tr style="background-color:#f1f5f9;color:#475569;font-size:11px;text-transform:uppercase;font-weight:700;">
                    <th align="left" style="padding:10px 14px;">Ítem</th>
                    <th align="right" style="padding:10px 14px;">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($order->items as $item)
                  <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:10px 14px;color:#1e293b;">
                      <strong>{{ $item->product?->name }}</strong> <span style="color:#64748b;">× {{ $item->quantity }}</span>
                    </td>
                    <td align="right" style="padding:10px 14px;font-weight:700;color:#0f172a;">
                      ${{ number_format($item->quantity * $item->price, 0, ',', '.') }} COP
                    </td>
                  </tr>
                  @endforeach
                  <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:8px 14px;color:#64748b;">Envío ({{ strtoupper($order->shipping_carrier ?: 'Envia.com') }})</td>
                    <td align="right" style="padding:8px 14px;color:#475569;">${{ number_format($order->shipping_cost, 0, ',', '.') }} COP</td>
                  </tr>
                  @if($order->discount > 0)
                  <tr style="border-bottom:1px solid #e2e8f0;background-color:#ecfdf5;">
                    <td style="padding:8px 14px;color:#047857;font-weight:600;">Descuento FusaCoins</td>
                    <td align="right" style="padding:8px 14px;color:#047857;font-weight:700;">-${{ number_format($order->discount, 0, ',', '.') }} COP</td>
                  </tr>
                  @endif
                  <tr style="background-color:#ffffff;">
                    <td style="padding:12px 14px;font-size:15px;font-weight:800;color:#0f172a;">Total Pagado</td>
                    <td align="right" style="padding:12px 14px;font-size:16px;font-weight:900;color:#047857;">
                      ${{ number_format($order->total, 0, ',', '.') }} COP
                    </td>
                  </tr>
                </tbody>
              </table>

              @if($order->invoice_number)
                <div style="background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px;margin-bottom:24px;font-size:12px;color:#1e40af;">
                  📄 <strong>Factura Electrónica DIAN {{ $order->invoice_number }}</strong> adjunta (PDF y XML AttachedDocument).<br>
                  <small style="word-break:break-all;color:#3b82f6;">CUFE: {{ $order->invoice_cufe }}</small>
                </div>
              @endif

              <!-- Call to Action -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    @if(($recipientType ?? 'consumer') === 'merchant')
                      <a href="{{ route('merchant.orders') }}" target="_blank" style="display:inline-block;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:12px;">
                        Gestionar Pedido en Panel Vendedor &rarr;
                      </a>
                    @elseif(($recipientType ?? 'consumer') === 'admin')
                      <a href="{{ route('analyst.orders') }}" target="_blank" style="display:inline-block;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:12px;">
                        Ver en Panel Administrativo &rarr;
                      </a>
                    @else
                      <a href="{{ route('consumer.orders.receipt', $order->id) }}" target="_blank" style="display:inline-block;background-color:#10B981;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:14px 32px;border-radius:12px;box-shadow:0 4px 12px rgba(16,185,129,0.25);">
                        Ver Recibo y Seguimiento &rarr;
                      </a>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 36px;background-color:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
              <p style="margin:0;font-size:11px;color:#94a3b8;">
                <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span> · El epicentro comercial de Fusagasugá
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
