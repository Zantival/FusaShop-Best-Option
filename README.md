# FusaShop - E-commerce Platform

Plataforma de comercio electrónico construida con Laravel y Vue.js

## 🚀 Despliegue

Esta aplicación requiere un servidor con:
- PHP 8.2+
- MySQL/PostgreSQL
- Node.js para el build del frontend

### Opciones de despliegue:

1. **Railway** (Recomendado)
2. **Render**
3. **Heroku**
4. **DigitalOcean App Platform**

## 📋 Características

- Sistema de autenticación completo
- Catálogo de productos
- Carrito de compras
- Sistema de pagos
- Chat en tiempo real
- Panel de administración
- Análisis con Python

## 🛠️ Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
php artisan serve
```


## Checkout, pagos y logistica (Wompi + Envia + Factus + Resend)

Flujo: Carrito -> direccion (libreta) -> cotizacion Envia `/ship/rate` -> pedido PENDIENTE con reserva de stock ->
Widget Wompi (firma SHA-256 de integridad) -> `POST /api/webhooks/wompi` -> job `ProcessWompiEvent` ->
pedido pagado + stock descontado -> jobs `GenerateEnviaShipment` y `IssueFactusInvoice` -> `SendOrderApprovedEmail` (Resend).

1. Copia `.env.example` a `.env` y completa las llaves **sandbox** de Wompi, Envia, Factus y Resend.
2. `php artisan migrate --seed`
3. Worker de colas (obligatorio): `php artisan queue:work` y scheduler: `php artisan schedule:work`
4. Registra en Wompi (sandbox) la URL de eventos `{APP_URL}/api/webhooks/wompi`; en Envia `{APP_URL}/api/webhooks/envia` con header `X-Webhook-Secret`.
5. Tests: `php artisan test` (las APIs externas se simulan con `Http::fake`).
6. Docker: `docker compose up --build` (app + queue worker + scheduler + MySQL + Redis).

Auditoria de integraciones y reintentos manuales: `/admin/integrations`. Servidor MCP (JSON-RPC): `POST /api/mcp` con `Authorization: Bearer $MCP_TOKEN`.
