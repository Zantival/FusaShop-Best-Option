<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alerta de Stock Crítico · FusaShop</title>
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
                Centro de Operaciones Comerciales
              </div>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:36px;">
              <div style="text-align:center;margin-bottom:20px;">
                <span style="display:inline-block;background-color:#fff1f2;border:1px solid #fecdd3;color:#e11d48;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:6px 14px;border-radius:999px;">
                  ⚠️ Alerta de Inventario Crítico
                </span>
              </div>

              <h2 style="margin:0 0 12px;font-size:22px;font-weight:800;color:#0f172a;text-align:center;">
                Tu producto se está agotando
              </h2>

              <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#475569;text-align:center;">
                Hola <strong>{{ $product->merchant?->name ?? 'Comerciante' }}</strong>, te notificamos que las existencias de tu producto en <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span> han alcanzado el umbral mínimo de seguridad.
              </p>

              <!-- Stock Card -->
              <table role="presentation" width="100%" cellpadding="14" cellspacing="0" style="background-color:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:28px;">
                <tr>
                  <td>
                    <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;">Producto</div>
                    <div style="font-size:16px;font-weight:800;color:#0f172a;margin-top:2px;">{{ $product->name }}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Categoría: {{ $product->category }}</div>
                  </td>
                  <td align="right" style="vertical-align:middle;">
                    <div style="display:inline-block;background-color:{{ $product->availableStock() <= 0 ? '#ffe4e6' : '#fef3c7' }};color:{{ $product->availableStock() <= 0 ? '#9f1239' : '#92400e' }};font-weight:900;font-size:22px;padding:8px 16px;border-radius:12px;border:1px solid {{ $product->availableStock() <= 0 ? '#fda4af' : '#fde68a' }};">
                      {{ $product->availableStock() }} <span style="font-size:12px;font-weight:700;">disp.</span>
                    </div>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 28px;font-size:13px;line-height:1.5;color:#64748b;text-align:center;">
                Para evitar perder ventas y mantener tu reputación como comercio activo en Fusagasugá, reabastece las unidades disponibles en tu catálogo.
              </p>

              <!-- Action Button -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ route('merchant.inventory') }}" target="_blank" style="display:inline-block;background-color:#0f172a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 28px;border-radius:12px;">
                      Actualizar Inventario Ahora &rarr;
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 36px;background-color:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
              <p style="margin:0;font-size:11px;color:#94a3b8;">
                <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span> · Panel de Comerciantes de Fusagasugá
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
