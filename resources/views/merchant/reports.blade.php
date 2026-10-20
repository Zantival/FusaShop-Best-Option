@extends('layouts.app')
@section('title', 'Reportes de Ventas & Centro de Descargas | FusaShop')

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">

  <!-- HEADER OFICIAL -->
  <div class="bg-white p-6 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-[#006c47] border border-emerald-200 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">assessment</span> Informes & Analítica Comercial
        </span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
          Auditoría FusaShop
        </span>
      </div>
      <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-slate-800">
        Reportes de Ventas del Negocio
      </h1>
      <p class="text-slate-500 text-xs md:text-sm mt-1">
        Consulta los diagnósticos causales enviados por la administración, el rendimiento de tus ventas y descarga tus reportes en Excel/CSV o PDF.
      </p>
    </div>

    <!-- BOTONES PRINCIPALES DE DESCARGA -->
    <div class="flex flex-wrap items-center gap-2.5">
      <a href="{{ route('merchant.reports.export-csv') }}"
         class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition-all flex items-center gap-1.5"
         title="Descargar archivo CSV compatible con Microsoft Excel">
        <span class="material-symbols-outlined text-sm">download</span>
        <span>Descargar CSV (Excel)</span>
      </a>

      <a href="{{ route('merchant.sales-report.show', $merchant->id) }}"
         target="_blank"
         class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition-all flex items-center gap-1.5"
         title="Ver e imprimir reporte oficial en formato PDF">
        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
        <span>Reporte Ejecutivo PDF</span>
      </a>

      <a href="{{ route('merchant.dashboard') }}"
         class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">dashboard</span>
        <span>Tablero</span>
      </a>
    </div>
  </div>

  <!-- TARJETAS DE RENDIMIENTO CLAVE -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Ventas Totales -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ventas Acumuladas</span>
        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-lg">payments</span>
        </div>
      </div>
      <p class="text-2xl font-black text-slate-900 font-['Manrope']">
        ${{ number_format($liveReport['total_sales'] ?? 0, 0, ',', '.') }} <span class="text-xs font-bold text-slate-400">COP</span>
      </p>
      <div class="mt-2 flex items-center gap-1.5 text-xs font-bold {{ ($liveReport['variation_pct'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
        <span class="material-symbols-outlined text-sm">{{ ($liveReport['variation_pct'] ?? 0) >= 0 ? 'trending_up' : 'trending_down' }}</span>
        <span>{{ ($liveReport['variation_pct'] ?? 0) >= 0 ? '+' : '' }}{{ $liveReport['variation_pct'] ?? 0 }}% vs periodo anterior</span>
      </div>
    </div>

    <!-- Pedidos Pagados -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pedidos Pagados</span>
        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-lg">local_shipping</span>
        </div>
      </div>
      <p class="text-2xl font-black text-slate-900 font-['Manrope']">
        {{ $liveReport['orders_count'] ?? $ordersCount }}
      </p>
      <p class="text-xs text-slate-500 mt-2">Despachados o en ruta con Envia.com</p>
    </div>

    <!-- Ticket Promedio -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ticket Promedio</span>
        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-lg">receipt</span>
        </div>
      </div>
      <p class="text-2xl font-black text-slate-900 font-['Manrope']">
        ${{ number_format($liveReport['average_ticket'] ?? 0, 0, ',', '.') }} <span class="text-xs font-bold text-slate-400">COP</span>
      </p>
      <p class="text-xs text-slate-500 mt-2">Valor promedio por transacción</p>
    </div>

    <!-- Unidades Vendidas -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Unidades Vendidas</span>
        <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-lg">inventory_2</span>
        </div>
      </div>
      <p class="text-2xl font-black text-slate-900 font-['Manrope']">
        {{ $liveReport['items_sold'] ?? 0 }} <span class="text-xs font-bold text-slate-400">uds</span>
      </p>
      <p class="text-xs text-slate-500 mt-2">Productos despachados</p>
    </div>
  </div>

  <!-- SECCIÓN 1: REPORTES ENVIADOS POR EL ADMINISTRADOR -->
  <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-[#006c47] flex items-center justify-center">
          <span class="material-symbols-outlined text-xl">mark_email_read</span>
        </div>
        <div>
          <h2 class="font-bold text-slate-800 text-base md:text-lg">Informes Oficiales Emitidos por el Administrador</h2>
          <p class="text-slate-400 text-xs">Diagnósticos de ventas, recomendaciones estratégicas y auditorías que te ha enviado el equipo directivo.</p>
        </div>
      </div>
      <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
        {{ $adminReports->count() }} reporte(s) registrado(s)
      </span>
    </div>

    @if($adminReports->isEmpty())
      <div class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2">
        <span class="material-symbols-outlined text-3xl text-slate-400">notifications_paused</span>
        <p class="text-sm font-bold text-slate-700">Aún no se han emitido reportes manuales directos para tu comercio.</p>
        <p class="text-xs text-slate-500 max-w-lg mx-auto">
          El equipo de administración de FusaShop genera periódicamente auditorías personalizadas. Puedes consultar tu diagnóstico automatizado en tiempo real a continuación o abrir el reporte ejecutivo completo.
        </p>
        <div class="pt-2">
          <a href="{{ route('merchant.sales-report.show', $merchant->id) }}"
             class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#006c47] hover:bg-[#047857] text-white text-xs font-bold rounded-xl transition-all">
            <span class="material-symbols-outlined text-sm">visibility</span>
            Ver Mi Reporte Ejecutivo en Vivo
          </a>
        </div>
      </div>
    @else
      <div class="space-y-3">
        @foreach($adminReports as $notif)
          @php
            $data = $notif->data ?? [];
            $reportData = $data['report_data'] ?? [];
            $customMessage = $reportData['custom_message'] ?? ($data['message'] ?? null);
            $reportType = $reportData['report_type'] ?? 'diagnostic';
            $senderName = $reportData['sent_by_name'] ?? 'Administración FusaShop';
            $sentDate = $reportData['sent_at_human'] ?? $notif->created_at->format('d/m/Y h:i A');
          @endphp
          <div class="p-5 rounded-2xl border border-slate-200 hover:border-emerald-300 transition-all bg-gradient-to-r from-white via-slate-50/30 to-emerald-50/20 space-y-3 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $reportType === 'congratulations' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                  {{ $reportType === 'congratulations' ? '🏆 Desempeño Destacado' : '📊 Diagnóstico Causal de Ventas' }}
                </span>
                <span class="text-xs font-bold text-slate-700">Emitido por: {{ $senderName }}</span>
              </div>
              <span class="text-xs text-slate-400 font-medium">Fecha: {{ $sentDate }}</span>
            </div>

            @if($customMessage)
              <div class="p-3.5 bg-emerald-50/70 border border-emerald-200/80 rounded-xl text-xs md:text-sm text-slate-700 leading-relaxed">
                <strong class="text-[#006c47] block mb-1 font-bold">Mensaje del Administrador:</strong>
                {{ $customMessage }}
              </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
              <div class="flex items-center gap-3 text-xs text-slate-500">
                <span>Ventas evaluadas: <strong class="text-slate-800">${{ number_format($reportData['total_sales'] ?? $liveReport['total_sales'] ?? 0, 0, ',', '.') }} COP</strong></span>
                <span>&bull;</span>
                <span>Variación: <strong class="{{ ($reportData['variation_pct'] ?? $liveReport['variation_pct'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ ($reportData['variation_pct'] ?? $liveReport['variation_pct'] ?? 0) >= 0 ? '+' : '' }}{{ $reportData['variation_pct'] ?? $liveReport['variation_pct'] ?? 0 }}%</strong></span>
              </div>

              <a href="{{ route('merchant.sales-report.show', ['user' => $merchant->id, 'notification_id' => $notif->id]) }}"
                 class="px-4 py-2 bg-[#006c47] hover:bg-[#047857] text-white text-xs font-black rounded-xl shadow-2xs hover:shadow transition-all flex items-center gap-1.5 ml-auto">
                <span class="material-symbols-outlined text-sm">open_in_new</span>
                <span>Abrir Reporte Formal Completo</span>
              </a>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <!-- SECCIÓN 2: DIAGNÓSTICO EN VIVO (FORTALEZAS & CUELLOS DE BOTELLA) -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- ¿Qué impulsa tus ventas? -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-lg">thumb_up</span>
        </div>
        <div>
          <h3 class="font-bold text-slate-800 text-sm md:text-base">¿Por qué aumentaron o qué impulsa tus ventas?</h3>
          <p class="text-slate-400 text-xs">Puntos fuertes detectados por el motor de inteligencia de FusaShop.</p>
        </div>
      </div>

      <div class="space-y-2.5">
        @forelse($liveReport['strengths'] ?? [] as $st)
          <div class="p-3 bg-emerald-50/40 rounded-xl border border-emerald-100 text-xs flex items-start gap-2.5">
            <span class="material-symbols-outlined text-emerald-600 text-base shrink-0 mt-0.5">check_circle</span>
            <span class="text-slate-700 leading-snug">{{ $st }}</span>
          </div>
        @empty
          <p class="text-xs text-slate-400 italic p-4 text-center">Registra más pedidos para identificar tus fortalezas comerciales.</p>
        @endforelse
      </div>
    </div>

    <!-- ¿Qué frena tus ventas? -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
          <span class="material-symbols-outlined text-lg">priority_high</span>
        </div>
        <div>
          <h3 class="font-bold text-slate-800 text-sm md:text-base">¿Por qué bajaron o qué frena tus ventas?</h3>
          <p class="text-slate-400 text-xs">Oportunidades de mejora detectadas en inventario, precios y despacho.</p>
        </div>
      </div>

      <div class="space-y-2.5">
        @forelse($liveReport['bottlenecks'] ?? [] as $bn)
          <div class="p-3 bg-amber-50/40 rounded-xl border border-amber-100 text-xs flex items-start gap-2.5">
            <span class="material-symbols-outlined text-amber-600 text-base shrink-0 mt-0.5">warning</span>
            <span class="text-slate-700 leading-snug">{{ $bn }}</span>
          </div>
        @empty
          <p class="text-xs text-emerald-600 font-bold p-4 text-center flex items-center justify-center gap-1">
            <span class="material-symbols-outlined text-base">verified</span>
            No se detectaron cuellos de botella críticos en este momento.
          </p>
        @endforelse
      </div>
    </div>
  </div>

  <!-- SECCIÓN 3: CENTRO DE DESCARGAS Y EXPORTACIÓN DE DATOS -->
  <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
    <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
      <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">table_view</span>
      </div>
      <div>
        <h2 class="font-bold text-slate-800 text-base md:text-lg">Centro de Descarga de Datos de Ventas</h2>
        <p class="text-slate-400 text-xs">Exporta tus transacciones comerciales en formatos universales para contabilidad, auditoría o archivo.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
      <!-- Tarjeta Descarga CSV -->
      <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between gap-4">
        <div class="space-y-1.5">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-600 text-xl">grid_on</span>
            <h3 class="font-bold text-slate-800 text-sm">Libro de Ventas en CSV (Excel)</h3>
          </div>
          <p class="text-xs text-slate-500 leading-relaxed">
            Descarga un archivo estructurado con el desglose ítem por ítem: referencia, fecha, datos del comprador, SKU, cantidades, subtotales, estado del pago y guía de despacho Envia.com.
          </p>
        </div>

        <a href="{{ route('merchant.reports.export-csv') }}"
           class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-sm">download</span>
          <span>Descargar Archivo CSV</span>
        </a>
      </div>

      <!-- Tarjeta Descarga PDF -->
      <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between gap-4">
        <div class="space-y-1.5">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-900 text-xl">picture_as_pdf</span>
            <h3 class="font-bold text-slate-800 text-sm">Reporte Ejecutivo Oficial (PDF)</h3>
          </div>
          <p class="text-xs text-slate-500 leading-relaxed">
            Genera e imprime el informe oficial membretado con gráficos de desempeño, productos más vendidos, mensaje del analista y diagnóstico causal de FusaShop.
          </p>
        </div>

        <a href="{{ route('merchant.sales-report.show', $merchant->id) }}"
           target="_blank"
           class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-sm">print</span>
          <span>Abrir & Guardar como PDF</span>
        </a>
      </div>
    </div>
  </div>

</div>
@endsection
