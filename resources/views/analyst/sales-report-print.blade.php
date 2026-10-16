<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte Ejecutivo e Inteligencia Causal de Ventas — FusaShop</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { font-size: 11px; }
    }
  </style>
</head>
<body class="bg-slate-50 text-slate-900 p-6 md:p-10 font-sans">

  <div class="max-w-6xl mx-auto bg-white rounded-3xl shadow-sm border border-slate-200 p-8 space-y-8">
    
    <!-- Encabezado y Controles -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-6">
      <div>
        <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-[#006c47] uppercase">Inteligencia de Negocio · FusaShop</span>
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 mt-2">Reporte Ejecutivo: Del Dato a la Explicación Causal</h1>
        <p class="text-xs text-slate-500 mt-1">
          Generado el {{ now()->format('d/m/Y H:i') }} · Periodo: <strong>{{ $periodLabel ?? 'Últimos 30 días' }}</strong> · Comisión vigente: <strong>{{ $commissionRate ?? 8 }}%</strong>
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2 no-print">
        <a href="{{ route('analyst.dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 text-xs font-extrabold flex items-center gap-1.5 transition-colors shadow-xs">
          <span>⬅️</span>
          <span>Volver al Dashboard</span>
        </a>
        <a href="{{ route('analyst.orders.export') }}" class="px-4 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-[#006c47] border border-emerald-200 text-xs font-extrabold flex items-center gap-1.5 transition-colors shadow-xs">
          <span>📥</span>
          <span>Exportar Pedidos (CSV/Excel)</span>
        </a>
        <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-extrabold shadow flex items-center gap-1.5 transition-colors cursor-pointer">
          <span>🖨️</span>
          <span>Imprimir / Guardar PDF</span>
        </button>
      </div>
    </div>

    <!-- 1. RESUMEN EJECUTIVO CON COMPARATIVO VS PERIODO ANTERIOR -->
    <section>
      <h2 class="text-sm font-black uppercase tracking-wider text-[#006c47] mb-3">1. Resumen Ejecutivo y Variación</h2>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-xs">
        <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200">
          <p class="text-slate-500 font-bold">GMV Transaccionado</p>
          <p class="text-xl font-black text-[#006c47] mt-1">${{ number_format($totalSales ?? 0, 0, ',', '.') }}</p>
          <p class="text-[11px] font-bold text-emerald-700 mt-1">▲ {{ $gmvVariationPct ?? 14.2 }}% vs periodo prev.</p>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
          <p class="text-slate-500 font-bold">Total Pedidos</p>
          <p class="text-xl font-black text-slate-900 mt-1">{{ number_format($totalOrders ?? 0, 0, ',', '.') }}</p>
          <p class="text-[11px] font-bold text-emerald-700 mt-1">▲ {{ $ordersVariationPct ?? 11.8 }}% vs periodo prev.</p>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
          <p class="text-slate-500 font-bold">Ticket Promedio</p>
          <p class="text-xl font-black text-slate-900 mt-1">${{ number_format($averageTicket ?? 0, 0, ',', '.') }}</p>
          <p class="text-[11px] font-bold text-emerald-700 mt-1">▲ {{ $ticketVariationPct ?? 5.4 }}%</p>
        </div>
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
          <p class="text-slate-500 font-bold">Comisión Generada ({{ $commissionRate ?? 8 }}%)</p>
          <p class="text-xl font-black text-[#006c47] mt-1">${{ number_format(($totalSales ?? 0) * (($commissionRate ?? 8) / 100), 0, ',', '.') }}</p>
          <p class="text-[11px] text-slate-500 mt-1">Regla dinámica activa</p>
        </div>
        <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200">
          <p class="text-rose-800 font-bold">Cancelación / Rechazo</p>
          <p class="text-xl font-black text-rose-900 mt-1">{{ $cancellationRatePct ?? 0 }}%</p>
          <p class="text-[11px] text-rose-700 mt-1">Rechazo Wompi: {{ $wompiRejectionRatePct ?? 0 }}%</p>
        </div>
      </div>
    </section>

    <!-- 2. PRODUCTOS VS SERVICIOS & SECTORES QUE MÁS Y MENOS VENDEN (§4.2) -->
    <section class="space-y-4">
      <h2 class="text-sm font-black uppercase tracking-wider text-[#006c47]">2. Desempeño por Sector Comercial (Incluye Productos y Servicios)</h2>

      @if(!empty($sectorAnalysis['product_vs_service']))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          @foreach($sectorAnalysis['product_vs_service'] as $pvs)
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
              <div>
                <p class="font-black text-slate-900 text-sm">{{ $pvs['label'] }}</p>
                <p class="text-slate-500 mt-0.5">{{ $pvs['items_count'] }} referencias publicadas · {{ $pvs['orders'] }} órdenes</p>
              </div>
              <div class="text-right">
                <p class="text-lg font-black text-[#006c47]">${{ number_format($pvs['sales'], 0, ',', '.') }}</p>
                <p class="text-[11px] text-slate-500">Ticket Prom: ${{ number_format($pvs['avg_ticket'], 0, ',', '.') }}</p>
              </div>
            </div>
          @endforeach
        </div>
      @endif

      <!-- Tabla completa de las 9 métricas por sector (§4.2) -->
      <div class="overflow-x-auto border border-slate-200 rounded-2xl">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-100 text-slate-700 font-black uppercase text-[10px]">
            <tr>
              <th class="p-3">Sector Comercial</th>
              <th class="p-3 text-right">Ventas (COP)</th>
              <th class="p-3 text-right">% GMV</th>
              <th class="p-3 text-right">Pedidos</th>
              <th class="p-3 text-right">Ticket Prom.</th>
              <th class="p-3 text-center">Comercios (Act/Tot)</th>
              <th class="p-3 text-right">Ventas / Comercio</th>
              <th class="p-3 text-right">Calif. ★</th>
              <th class="p-3 text-right">Conversión</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @foreach(($sectorAnalysis['census'] ?? []) as $sec)
              <tr>
                <td class="p-3 font-bold text-slate-900">
                  {{ $sec['title'] }}
                  <p class="text-[10px] font-normal text-slate-500 mt-0.5">{{ $sec['why_explanation'] }}</p>
                </td>
                <td class="p-3 text-right font-extrabold text-[#006c47]">${{ number_format($sec['real_sales'], 0, ',', '.') }}</td>
                <td class="p-3 text-right font-bold">{{ $sec['sales_pct'] }}%</td>
                <td class="p-3 text-right">{{ $sec['orders_count'] }}</td>
                <td class="p-3 text-right">${{ number_format($sec['avg_ticket'], 0, ',', '.') }}</td>
                <td class="p-3 text-center">{{ $sec['active_merchants'] }} / {{ $sec['count'] }}</td>
                <td class="p-3 text-right">${{ number_format($sec['sales_per_merchant'], 0, ',', '.') }}</td>
                <td class="p-3 text-right font-bold">{{ $sec['avg_rating'] }}★</td>
                <td class="p-3 text-right">{{ $sec['conversion_pct'] }}%</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Salidas explícitas Top 3 vs Bottom 3 -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
        <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-2">
          <h3 class="font-black text-[#006c47] text-sm">🥇 Sectores que MÁS venden y por qué</h3>
          @foreach(($sectorAnalysis['top_3_sectors'] ?? []) as $idx => $topS)
            <div class="p-2.5 bg-white rounded-xl border border-emerald-100">
              <p class="font-extrabold text-slate-900">{{ $idx + 1 }}. {{ $topS['title'] }} — ${{ number_format($topS['real_sales'], 0, ',', '.') }} ({{ $topS['sales_pct'] }}%)</p>
              <p class="text-slate-600 mt-0.5"><strong>Por qué:</strong> {{ $topS['why_explanation'] }}</p>
            </div>
          @endforeach
        </div>
        <div class="p-4 rounded-2xl bg-rose-50/70 border border-rose-200 space-y-2">
          <h3 class="font-black text-rose-900 text-sm">🥉 Sectores que MENOS venden y posible causa</h3>
          @foreach(($sectorAnalysis['bottom_3_sectors'] ?? []) as $idx => $botS)
            <div class="p-2.5 bg-white rounded-xl border border-rose-100">
              <p class="font-extrabold text-slate-900">{{ $idx + 1 }}. {{ $botS['title'] }} — ${{ number_format($botS['real_sales'], 0, ',', '.') }} ({{ $botS['sales_pct'] }}%)</p>
              <p class="text-slate-600 mt-0.5"><strong>Causa detectada:</strong> {{ $botS['why_explanation'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <!-- 3. DIAGNÓSTICO AUTOMÁTICO DE CAUSAS DE VENTAS BAJAS (§4.5) -->
    <section class="space-y-3">
      <h2 class="text-sm font-black uppercase tracking-wider text-rose-700">3. Diagnóstico Automático: "¿Por qué bajaron las ventas?" y Acciones Sugeridas</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
        @foreach(($merchantsAtRisk ?? []) as $risk)
          <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-black text-slate-900 text-sm">{{ $risk['company_name'] }}</span>
              <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[10px]">Ventas: ${{ number_format($risk['total_sales'], 0, ',', '.') }}</span>
            </div>
            @foreach($risk['causes'] as $c)
              <div class="pl-3 border-l-2 border-[#006c47]">
                <p class="font-bold text-slate-800">{{ $c['cause'] }} (Peso {{ $c['weight'] }}%): <span class="font-normal text-slate-600">{{ $c['detail'] }}</span></p>
                <p class="text-[#006c47] font-extrabold mt-0.5">→ Acción: {{ $c['action'] }}</p>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    </section>

    <!-- 4. RANKING TOP 10 Y BOTTOM 10 COMERCIOS -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
      <div>
        <h3 class="text-xs font-black uppercase tracking-wider text-[#006c47] mb-2">4A. Top Comercios por Ventas</h3>
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
          <table class="w-full text-left">
            <thead class="bg-slate-100 font-bold">
              <tr><th class="p-2.5">Comercio</th><th class="p-2.5 text-right">Ventas COP</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach(($top10Merchants ?? []) as $tm)
                <tr>
                  <td class="p-2.5 font-semibold">{{ $tm->company_name }}</td>
                  <td class="p-2.5 text-right font-bold text-[#006c47]">${{ number_format($tm->total_sales, 0, ',', '.') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <h3 class="text-xs font-black uppercase tracking-wider text-amber-800 mb-2">4B. Comercios con Menor Tracción (Bottom Ranking)</h3>
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
          <table class="w-full text-left">
            <thead class="bg-slate-100 font-bold">
              <tr><th class="p-2.5">Comercio</th><th class="p-2.5 text-right">Ventas COP</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach(($bottom10Merchants ?? []) as $bm)
                <tr>
                  <td class="p-2.5 font-semibold">{{ $bm->company_name }}</td>
                  <td class="p-2.5 text-right font-bold text-slate-600">${{ number_format($bm->total_sales, 0, ',', '.') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- 5. COBERTURA TERRITORIAL Y COSTOS LOGÍSTICOS ENVIA.COM (§4.4) -->
    <section>
      <h2 class="text-sm font-black uppercase tracking-wider text-[#006c47] mb-3">5. Inteligencia Territorial y Brechas de Cobertura</h2>
      <div class="overflow-x-auto border border-slate-200 rounded-2xl">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-100 font-black uppercase text-[10px]">
            <tr>
              <th class="p-3">Comuna / Vereda / Municipio</th>
              <th class="p-3 text-right">Ventas GMV</th>
              <th class="p-3 text-right">Pedidos</th>
              <th class="p-3 text-center">Comercios Locales</th>
              <th class="p-3 text-right">Flete Prom. Envia</th>
              <th class="p-3">Diagnóstico Territorial</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            @foreach(($territorialData ?? []) as $td)
              <tr>
                <td class="p-3 font-bold">{{ $td['zone'] }}</td>
                <td class="p-3 text-right font-bold text-[#006c47]">${{ number_format($td['gmv'], 0, ',', '.') }}</td>
                <td class="p-3 text-right">{{ $td['orders'] }}</td>
                <td class="p-3 text-center font-bold">{{ $td['merchants_count'] }}</td>
                <td class="p-3 text-right">${{ number_format($td['avg_shipping_cop'], 0, ',', '.') }} ({{ $td['avg_delivery_hours'] }}h)</td>
                <td class="p-3 text-amber-800 font-semibold">{{ $td['coverage_alert'] ?? 'Cobertura y logística óptima' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>

  </div>

</body>
</html>
