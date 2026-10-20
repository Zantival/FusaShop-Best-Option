<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifica tu correo en FusaShop</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;color:#1e293b;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 15px;">
    <tr>
      <td align="center">
        <!-- Main Container -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 10px 25px -5px rgba(0,0,0,0.06),0 8px 10px -6px rgba(0,0,0,0.04);border:1px solid #e2e8f0;">
          
          <!-- Top Header Brand Banner -->
          <tr>
            <td style="padding:32px 36px 24px;text-align:center;background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                <tr>
                  <td align="center">
                    <!-- Brand Name with Exact Page Colors -->
                    <div style="font-size:32px;font-weight:900;letter-spacing:-0.5px;line-height:1;font-family:'Plus Jakarta Sans',sans-serif,Arial;">
                      <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:6px;letter-spacing:1px;text-transform:uppercase;font-weight:600;">
                      El epicentro comercial del Sumapaz
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Main Content Body -->
          <tr>
            <td style="padding:40px 36px;">
              <!-- Welcome Heading with Styled Brand Name -->
              <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;color:#0f172a;line-height:1.3;text-align:center;">
                ¡Bienvenido a <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span>!
              </h1>

              <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;text-align:center;">
                Hola <strong>{{ $user->name }}</strong>, gracias por unirte a nuestra plataforma como 
                @if($user->isMerchant())
                  <strong style="color:#10B981;">Comercio Aliado de Fusagasugá</strong>.
                @else
                  <strong style="color:#2563EB;">Comprador</strong>.
                @endif
              </p>

              <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#64748b;text-align:center;">
                Para garantizar la seguridad de tu cuenta y activar tu acceso completo, ingresa el siguiente <strong>código de verificación de 6 números</strong> en la plataforma:
              </p>

              <!-- 6-Digit Code Highlight Box -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                <tr>
                  <td align="center">
                    <div style="display:inline-block;background-color:#ecfdf5;border:2px dashed #10B981;border-radius:16px;padding:18px 32px;text-align:center;">
                      <span style="font-family:'Courier New',Courier,monospace;font-size:38px;font-weight:900;letter-spacing:10px;color:#047857;display:block;margin-left:10px;">
                        {{ $code }}
                      </span>
                    </div>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 28px;font-size:12px;color:#94a3b8;text-align:center;">
                ⏱️ Este código expira en <strong>30 minutos</strong> y es de uso exclusivo y confidencial.
              </p>

              <!-- Action Button to Verification Page -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ route('verification.notice') }}" target="_blank" style="display:inline-block;background-color:#10B981;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 32px;border-radius:12px;box-shadow:0 4px 12px rgba(16,185,129,0.25);">
                      Ingresar Código en FusaShop &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <div style="margin-top:36px;padding-top:24px;border-top:1px solid #f1f5f9;font-size:13px;line-height:1.5;color:#64748b;text-align:center;">
                Si tú no creaste esta cuenta en <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span>, puedes ignorar este correo de forma segura.
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:24px 36px;background-color:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;">
              <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#64748b;">
                <span style="color:#10B981;">Fus</span><span style="color:#2563EB;">a</span><span style="color:#F97316;">Shop</span> · Fusagasugá, Cundinamarca, Colombia
              </p>
              <p style="margin:0;font-size:11px;color:#94a3b8;">
                Comercio electrónico seguro, turismo local y logística con Envia.com
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
