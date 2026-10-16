@extends('layouts.app')
@section('title', 'Voz del Cliente y Análisis por Tema | FusaShop Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">

  <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-4">
    <div>
      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-[#006c47] uppercase">Voz del Cliente & NLP</span>
      <h1 class="text-2xl font-extrabold text-slate-900 mt-1">Análisis de Reseñas por Tema y Cruce con Ventas</h1>
      <p class="text-xs text-slate-500 mt-0.5">Clasificación temática (Precio, Calidad, Envío, Atención, Empaque, Descripción, Servicio) y correlación comercial</p>
    </div>
    <form action="{{ route('analyst.run-ml') }}" method="POST">
      @csrf
      <button type="submit" class="px-4 py-2 rounded-xl bg-[#006c47] hover:bg-[#10B981] text-white text-xs font-extrabold flex items-center gap-1.5 shadow">
        <span class="material-symbols-outlined text-base">psychology</span> Ejecutar Segmentación ML
      </button>
    </form>
  </div>

  <!-- 1. ANÁLISIS POR LOS 7 TEMAS (§4.3) -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
      <h2 class="text-sm font-extrabold text-slate-900 mb-3 flex items-center gap-1.5">
        <span class="material-symbols-outlined text-[#006c47]">topic</span> Desglose por Tema Detectado en Comentarios
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
        @foreach(($globalTopics ?? []) as $gt)
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80">
            <div class="flex items-center justify-between font-bold text-slate-800 mb-1.5">
              <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base text-[#006c47]">{{ $gt['icon'] }}</span>
                {{ $gt['label'] }}
              </span>
              <span class="text-[11px] text-slate-500">{{ $gt['total_mentions'] }} menciones</span>
            </div>
            <div class="flex items-center justify-between text-[11px] font-bold">
              <span class="text-[#006c47]">👍 Positivas: {{ $gt['positive_pct'] }}%</span>
              <span class="text-rose-700">👎 Críticas: {{ $gt['negative_pct'] }}%</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <!-- Nube de Palabras Críticas Recurrentes -->
    <div class="lg:col-span-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col justify-between">
      <div>
        <h2 class="text-sm font-extrabold text-slate-900 mb-2 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-rose-600">cloud</span> Términos Recurrentes en Críticas
        </h2>
        <p class="text-xs text-slate-500 mb-3">Palabras clave extraídas de comentarios ≤ 3★</p>
        <div class="flex flex-wrap gap-2">
          @foreach(($topCriticalWords ?? []) as $word => $freq)
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-rose-50 text-rose-800 border border-rose-200">
              {{ $word }} <span class="text-[10px] opacity-75">({{ $freq }})</span>
            </span>
          @endforeach
        </div>
      </div>

      <div class="mt-4 p-3 rounded-xl bg-emerald-50/70 border border-emerald-200 text-[11px] text-slate-700">
        <p class="font-extrabold text-[#006c47] mb-0.5">📏 Criterio Visible de Tiers de Reputación:</p>
        <p>• <strong>Excelente:</strong> ≥ 4.5★ y mínimo 3 reseñas</p>
        <p>• <strong>Aceptable:</strong> ≥ 4.0★ · <strong>Peligro:</strong> &lt; 3.2★</p>
      </div>
    </div>
  </div>

  <!-- 2. REPORTE POR COMERCIO CON CITAS DESTACADAS Y CRUCE RESEÑA <-> VENTAS -->
  <div class="space-y-4">
    @forelse($report as $r)
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-base font-extrabold text-slate-900">{{ $r['company_name'] }}</h3>
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black {{ $r['tier'] === 'Excelente' ? 'bg-emerald-100 text-[#006c47]' : ($r['tier'] === 'Peligro' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                {{ $r['tier'] }}
              </span>
              <span class="text-[11px] text-slate-400">({{ $r['tier_rule'] }})</span>
            </div>
            <p class="text-xs text-slate-600 mt-1">
              Tema dominante: <strong class="text-[#006c47]">{{ $r['dominant_topic'] }}</strong>
              @if(!empty($r['dominant_critical']))
                · Alerta en: <strong class="text-rose-700">{{ $r['dominant_critical'] }}</strong>
              @endif
            </p>
          </div>
          <div class="text-right text-xs">
            <p class="text-lg font-black text-[#006c47]">{{ $r['avg_rating'] }} ★ <span class="text-xs font-normal text-slate-500">({{ $r['total_reviews'] }} reseñas)</span></p>
            <p class="font-bold text-slate-700">Ventas acumuladas: ${{ number_format($r['total_sales'], 0, ',', '.') }}</p>
          </div>
        </div>

        <!-- Cruce Reseña <-> Ventas -->
        <div class="p-3 rounded-xl bg-emerald-50/50 border border-emerald-200/70 text-xs flex items-center justify-between">
          <span><strong>📊 Cruce Reputación ↔ Ventas:</strong> {{ $r['sales_impact_analysis'] }}</span>
          <span class="font-extrabold text-[#006c47]">Score Global: {{ $r['score'] }}/100</span>
        </div>

        <!-- Citas destacadas positivas y negativas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
          @foreach(($r['positive_quotes'] ?? []) as $pq)
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
              <p class="font-bold text-[#006c47]">★ {{ $pq['rating'] }}/5 — {{ $pq['product'] }}</p>
              <p class="italic text-slate-700 mt-1">"{{ $pq['comment'] }}"</p>
              <p class="text-[10px] text-slate-400 mt-1">— {{ $pq['author'] }} ({{ $pq['date'] }})</p>
            </div>
          @endforeach
          @foreach(($r['negative_quotes'] ?? []) as $nq)
            <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-200">
              <p class="font-bold text-rose-800">★ {{ $nq['rating'] }}/5 — {{ $nq['product'] }}</p>
              <p class="italic text-slate-700 mt-1">"{{ $nq['comment'] }}"</p>
              <p class="text-[10px] text-slate-400 mt-1">— {{ $nq['author'] }} ({{ $nq['date'] }})</p>
            </div>
          @endforeach
        </div>
      </div>
    @empty
      <div class="bg-white p-10 rounded-2xl text-center text-slate-400 text-xs">No hay reseñas registradas aún.</div>
    @endforelse
  </div>

</div>
@endsection
