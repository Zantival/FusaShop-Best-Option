@extends('layouts.app')

@section('title', 'Reporte de Ventas y Diagnóstico Causal - ' . ($report['company_name'] ?? 'Comercio'))

@section('content')
<div class="bg-[#F9FAFB] min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- Barra de acciones superior -->
        <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
            <div class="flex items-center gap-3">
                @if(auth()->user()->isAnalyst())
                    <a href="{{ route('analyst.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
                        ← Volver al Centro de Mando
                    </a>
                @else
                    <a href="{{ route('merchant.reports') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
                        ← Volver a Reportes de Ventas
                    </a>
                @endif
                <a href="{{ route('notifications.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 shadow-sm transition-all">
                    🔔 Bandeja de Notificaciones
                </a>
            </div>
            <div class="flex items-center gap-3">
                @if(auth()->id() === $merchant->id && $merchant->role === 'merchant')
                    <a href="{{ route('merchant.inventory') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-black text-emerald-700 hover:bg-emerald-100 transition-all">
                        📦 Gestionar Mi Inventario
                    </a>
                    <a href="{{ route('merchant.reports.export-csv') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-black hover:bg-emerald-700 shadow-sm transition-all">
                        📥 Descargar CSV (Excel)
                    </a>
                @endif
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-black hover:bg-emerald-700 shadow-sm transition-all">
                    🖨️ Imprimir / Guardar PDF
                </button>
            </div>
        </div>

        <!-- Encabezado Oficial del Reporte -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ ($report['report_type'] ?? '') === 'congratulations' || ($report['variation_pct'] ?? 0) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ ($report['report_type'] ?? '') === 'congratulations' ? '🏆 Desempeño Destacado' : '📊 Diagnóstico Causal y Plan de Mejora' }}
                        </span>
                        <span class="text-xs font-bold text-slate-400">Sector: {{ $report['sector'] ?? 'Comercio Local' }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Reporte de Ventas: {{ $report['company_name'] }}
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Propietario: <span class="font-bold text-slate-700">{{ $report['merchant_name'] }}</span> · 
                        Emitido por: <span class="font-bold text-emerald-700">{{ $report['sent_by_name'] ?? 'Dirección de Analítica FusaShop' }}</span> · 
                        Fecha: <span class="font-bold text-slate-700">{{ $report['sent_at_human'] ?? $report['generated_at'] }}</span>
                    </p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 text-right">
                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Ventas Totales Acumuladas</span>
                    <span class="text-2xl font-black text-emerald-600">${{ number_format($report['total_sales'] ?? 0, 0, ',', '.') }} <span class="text-xs font-bold text-slate-400">COP</span></span>
                    <div class="mt-0.5 text-[11px] font-bold {{ ($report['variation_pct'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ ($report['variation_pct'] ?? 0) >= 0 ? '▲ +' : '▼ ' }}{{ $report['variation_pct'] ?? 0 }}% vs periodo anterior
                    </div>
                </div>
            </div>

            <!-- KPIs Principales -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                    <span class="block text-[10px] font-black text-emerald-700 uppercase tracking-widest">Ingreso Neto Comercio (92%)</span>
                    <span class="text-xl font-black text-slate-900 mt-1 block">${{ number_format($report['net_sales'] ?? 0, 0, ',', '.') }}</span>
                    <span class="text-[11px] text-emerald-700 font-medium">Libre de comisión de plataforma</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Pedidos & Unidades</span>
                    <span class="text-xl font-black text-slate-900 mt-1 block">{{ $report['total_orders'] ?? 0 }} pedidos</span>
                    <span class="text-[11px] text-slate-500 font-medium">{{ $report['total_units_sold'] ?? 0 }} unidades vendidas · Ticket ${{ number_format($report['avg_ticket'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Estado de Catálogo y Stock</span>
                    <span class="text-xl font-black text-slate-900 mt-1 block">{{ $report['in_stock_count'] ?? 0 }} / {{ $report['products_count'] ?? 0 }} activos</span>
                    <span class="text-[11px] {{ ($report['out_of_stock_count'] ?? 0) > 0 ? 'text-red-600 font-bold' : 'text-emerald-600 font-medium' }}">
                        {{ $report['out_of_stock_count'] ?? 0 }} agotados · {{ $report['low_stock_count'] ?? 0 }} en stock bajo
                    </span>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Reputación y Servicio</span>
                    <span class="text-xl font-black text-slate-900 mt-1 block">⭐ {{ number_format($report['avg_rating'] ?? 5, 1) }} / 5.0</span>
                    <span class="text-[11px] text-slate-500 font-medium">{{ $report['reviews_count'] ?? 0 }} reseñas · {{ $report['open_pqrs'] ?? 0 }} PQRS abiertas</span>
                </div>
            </div>
        </div>

        <!-- Diagnóstico Causal: ¿Por qué subieron o por qué bajaron las ventas? -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Factores de Impulso / Por qué subieron las ventas -->
            <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-lg">📈</span>
                    <div>
                        <h2 class="text-base font-black text-slate-900">¿Por qué aumentaron o se impulsaron tus ventas?</h2>
                        <p class="text-xs text-slate-500">Factores positivos detectados automáticamente en tu tienda</p>
                    </div>
                </div>
                <ul class="space-y-3">
                    @foreach(($report['growth_drivers'] ?? []) as $driver)
                        <li class="flex items-start gap-2.5 text-xs text-slate-700 bg-emerald-50/60 p-3 rounded-xl border border-emerald-100 leading-relaxed">
                            <span class="text-emerald-600 font-black mt-0.5">✔</span>
                            <div>
                                @if(is_array($driver))
                                    <p class="font-bold text-slate-900">{{ $driver['cause'] ?? '' }}</p>
                                    <p class="text-slate-600 mt-0.5">{{ $driver['detail'] ?? '' }}</p>
                                    @if(!empty($driver['action']))
                                        <p class="text-[11px] font-extrabold text-emerald-700 mt-1">💡 {{ $driver['action'] }}</p>
                                    @endif
                                @else
                                    <span>{{ $driver }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Factores de Freno / Por qué bajaron las ventas -->
            <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-amber-100">
                    <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-black text-lg">⚠️</span>
                    <div>
                        <h2 class="text-base font-black text-slate-900">¿Por qué bajaron o qué frena tus ventas?</h2>
                        <p class="text-xs text-slate-500">Cuellos de botella detectados y oportunidades de mejora</p>
                    </div>
                </div>
                <ul class="space-y-3">
                    @foreach(($report['decline_causes'] ?? []) as $cause)
                        <li class="flex items-start gap-2.5 text-xs text-slate-700 bg-amber-50/60 p-3 rounded-xl border border-amber-100 leading-relaxed">
                            <span class="text-amber-600 font-black mt-0.5">⚡</span>
                            <div>
                                @if(is_array($cause))
                                    <p class="font-bold text-slate-900">{{ $cause['cause'] ?? '' }}</p>
                                    <p class="text-slate-600 mt-0.5">{{ $cause['detail'] ?? '' }}</p>
                                    @if(!empty($cause['action']))
                                        <p class="text-[11px] font-extrabold text-amber-800 mt-1">⚡ Acción: {{ $cause['action'] }}</p>
                                    @endif
                                @else
                                    <span>{{ $cause }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Desglose de Ventas por Producto / Servicio del Comercio -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-black text-slate-900">📦 Desglose de Ventas por Producto / Servicio</h2>
                    <p class="text-xs text-slate-500">Rendimiento individual de cada referencia en tu catálogo</p>
                </div>
                <span class="text-xs font-bold text-slate-400">{{ count($report['products_breakdown'] ?? []) }} referencias</span>
            </div>

            @if(!empty($report['products_breakdown']))
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-[10px] font-black uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-3">Producto / Servicio</th>
                                <th class="py-3 px-3 text-right">Precio Unitario</th>
                                <th class="py-3 px-3 text-center">Stock Actual</th>
                                <th class="py-3 px-3 text-center">Unidades Vendidas</th>
                                <th class="py-3 px-3 text-right">Ingresos Generados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach($report['products_breakdown'] as $prod)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3 px-3 font-bold text-slate-800">{{ $prod['name'] }}</td>
                                    <td class="py-3 px-3 text-right text-slate-600">${{ number_format($prod['price'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-center">
                                        @if($prod['stock'] <= 0)
                                            <span class="px-2 py-0.5 rounded bg-red-100 text-red-700 font-black text-[10px]">AGOTADO (0)</span>
                                        @elseif($prod['stock'] <= 3)
                                            <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-black text-[10px]">BAJO ({{ $prod['stock'] }})</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px]">{{ $prod['stock'] }} disp.</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-center font-black text-slate-900">{{ $prod['units_sold'] ?? $prod['units'] ?? 0 }}</td>
                                    <td class="py-3 px-3 text-right font-black text-emerald-600">${{ number_format($prod['revenue'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-500">
                    Este comercio aún no tiene productos registrados en su catálogo.
                </div>
            @endif
        </div>

        <!-- Informe Completo y Observaciones Oficiales del Administrador -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-black text-slate-900">📋 Informe Ejecutivo y Plan de Acción</h2>
                    <p class="text-xs text-slate-500">Comunicado oficial enviado por la Administración de FusaShop</p>
                </div>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 font-mono text-xs text-slate-800 whitespace-pre-line leading-relaxed">{{ $report['custom_message'] ?? $report['diagnostic_report_text'] }}</div>
        </div>

    </div>
</div>
@endsection
