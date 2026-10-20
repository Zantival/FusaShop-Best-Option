<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tu pedido va en camino · FusaShop</title>
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
                Logística & Seguimiento Envia.com
              </div>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:36px;">
              <div style="text-align:center;margin-bottom:20px;">
                <span style="display:inline-block;background-color:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:6px 14px;border-radius:999px;">
                  🚚 Pedido Despachado
                </span>
              </div>

              <h2 style="margin:0 0 12px;font-size:24px;font-weight:800;color:#0f172a;text-align:center;">
                ¡Tu pedido está en camino!
              </h2>

              <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;text-align:center;">
                Hola <strong>{{ $order->user->name }}</strong>, el comercio ha preparado tu paquete y ha sido entregado a la empresa de mensajería para su distribución.
              </p>

              <!-- Tracking Info Card -->
              <table role="presentation" width="100%" cellpadding="16" cellspacing="0" style="background-color:#f8fafc;border-radius:16px;border:1px solid #e2e8f0;margin-bottom:28px;">
                <tr>
                  <td>
                    <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;">Número de Pedido</div>
                    <div style="font-size:15px;font-weight:800;color:#0f172a;margin-top:2px;">#{{ $order->id }} · {{ $order->reference }}</div>
                  </td>
                  <td align="right">
                    <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;">Transportadora</div>
                    <div style="display:inline-block;background-color:#dbeafe;color:#1e40af;font-size:12px;font-weight:800;padding:4px 10px;border-radius:6px;margin-top:2px;text-transform:uppercase;">
                      {{ strtoupper($order->shipping_carrier ?: 'Envia.com') }}
                    </div>
                  </td>
                </tr>
                <tr>
                  <td colspan="2" style="border-top:1px solid #e2e8f0;padding-top:14px;margin-top:10px;">
                    <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;">Número de Guía / Rastreo</div>
                    <div style="font-family:'Courier New',Courier,monospace;font-size:18px;font-weight:900;color:#0f172a;letter-spacing:1px;margin-top:4px;">
                      {{ $order->tracking_number ?? 'Pendiente de emisión' }}
                    </div>
                    @if($order->shipping_address)
                    <div style="font-size:12px;color:#64748b;margin-top:6px;">
                      📍 Destino: <strong>{{ $order->shipping_address }}</strong>
                    </div>
                    @endif
                  </td>
                </tr>
              </table>

              <!-- Action Button -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                <tr>
                  <td align="center">
                    @if($order->tracking_url)
                      <a href="{{ $order->tracking_url }}" target="_blank" style="display:inline-block;background-color:#10B981;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:12px;box-shadow:0 4px 12px rgba(16,185,129,0.25);">
                        Rastrear Envío en Vivo &rarr;
                      </a>
                    @else
                      <a href="{{ route('consumer.orders.receipt', $order->id) }}" target="_blank" style="display:inline-block;background-color:#10B981;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:12px;box-shadow:0 4px 12px rgba(16,185,129,0.25);">
                        Ver Estado del Pedido &rarr;
                      </a>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:12px;color:#94a3b8;text-align:center;">
                También puedes monitorear el progreso de tu despacho en cualquier momento desde tu cuenta en <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span>.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 36px;background-color:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
              <p style="margin:0;font-size:11px;color:#94a3b8;">
                <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span> · El epicentro de compras y logística del Sumapaz
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
