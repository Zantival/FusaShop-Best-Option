@extends('layouts.app')
@section('title', 'Panel de Notificaciones | FusaShop')
@section('content')

@php
  $user = auth()->user();
  $isAnalyst = $user && $user->isAnalyst();
  $pendingKycCount = \App\Models\CompanyProfile::where('kyc_status', 'pending')->count();
  $failedIntegrationsCount = \App\Models\IntegrationLog::where('status', 'failed')->whereNull('resolved_at')->where('created_at', '>=', now()->subDay())->count();
  $openPqrsCount = \App\Models\PQRS::whereIn('status', ['pending', 'in_review'])->count();
  $pendingBannersCount = \App\Models\BannerRequest::where('status', 'pending')->count();
  $totalAdminAlerts = $pendingKycCount + $failedIntegrationsCount + $openPqrsCount + $pendingBannersCount;
@endphp

<div class="max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">

  <!-- ENCABEZADO PRINCIPAL -->
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="p-2 rounded-2xl bg-emerald-100 text-[#006c47]">
          <span class="material-symbols-outlined text-2xl">notifications_active</span>
        </span>
        <div>
          <h1 class="text-2xl font-bold font-['Manrope'] text-slate-900">
            Panel de Notificaciones y Alertas
          </h1>
          <p class="text-xs text-slate-500">
            {{ $isAnalyst ? 'Centro de control operativo, auditoría y novedades del sistema administrativo' : 'Historial de actualizaciones de pedidos, mensajes y actividades' }}
          </p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      @if($user && $user->unreadNotifications->count() > 0)
      <form action="{{ route('notifications.readAll') }}" method="POST">
        @csrf
        <button type="submit" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-[#006c47] border border-emerald-200 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-colors cursor-pointer shadow-2xs">
          <span class="material-symbols-outlined text-sm">done_all</span>
          <span>Marcar todo como leído</span>
        </button>
      </form>
      @endif

      @if($isAnalyst)
      <a href="{{ route('analyst.dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-colors shadow-2xs">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        <span>Volver al Dashboard</span>
      </a>
      @endif
    </div>
  </div>

  <!-- TARJETAS DE ALERTAS OPERATIVAS (SOLO PARA ADMINS) -->
  @if($isAnalyst)
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <a href="{{ route('analyst.users', ['kyc_status' => 'pending']) }}" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-[#10B981] shadow-2xs transition-all flex items-center justify-between group">
      <div>
        <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider block">KYC Pendientes</span>
        <span class="text-xl font-black text-slate-900 group-hover:text-[#006c47]">{{ $pendingKycCount }}</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">badge</span>
      </div>
    </a>

    <a href="{{ route('analyst.integrations', ['status' => 'failed']) }}" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-[#10B981] shadow-2xs transition-all flex items-center justify-between group">
      <div>
        <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider block">Fallos API (24h)</span>
        <span class="text-xl font-black {{ $failedIntegrationsCount > 0 ? 'text-rose-600' : 'text-slate-900' }} group-hover:text-[#006c47]">{{ $failedIntegrationsCount }}</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">webhook</span>
      </div>
    </a>

    <a href="{{ route('analyst.pqrs.index') }}" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-[#10B981] shadow-2xs transition-all flex items-center justify-between group">
      <div>
        <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider block">PQRS Abiertas</span>
        <span class="text-xl font-black text-slate-900 group-hover:text-[#006c47]">{{ $openPqrsCount }}</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#006c47] flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">mark_email_unread</span>
      </div>
    </a>

    <a href="{{ route('analyst.banners') }}" class="bg-white p-4 rounded-2xl border border-slate-200 hover:border-[#10B981] shadow-2xs transition-all flex items-center justify-between group">
      <div>
        <span class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider block">Banners x Aprobar</span>
        <span class="text-xl font-black text-slate-900 group-hover:text-[#006c47]">{{ $pendingBannersCount }}</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">ad_units</span>
      </div>
    </a>
  </div>
  @endif

  <!-- BANDEJA DE NOTIFICACIONES INDIVIDUALES -->
  <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
      <h2 class="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
        <span class="material-symbols-outlined text-base text-[#006c47]">inbox</span>
        <span>Bandeja de Eventos Recientes</span>
      </h2>
      <span class="text-xs text-slate-400 font-semibold">
        {{ $user ? $user->unreadNotifications()->count() : 0 }} pendientes por ver
      </span>
    </div>

    <div class="space-y-3">
      @forelse($user ? $user->unreadNotifications()->get() : [] as $notification)
        @php
          $openUrl = route('notifications.open', $notification->id);
          $isSalesReport = ($notification->data['type'] ?? '') === 'merchant_sales_report';
        @endphp

        <div class="p-4 rounded-2xl border transition-all bg-emerald-50/40 border-emerald-200 hover:border-[#10B981] shadow-2xs flex items-start gap-4 group">
          <a href="{{ $openUrl }}" class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center mt-0.5 bg-emerald-100 text-[#006c47] group-hover:bg-[#006c47] group-hover:text-white transition-colors" title="Abrir mensaje">
            <span class="material-symbols-outlined text-xl">
              {{ $notification->data['icon'] ?? 'notifications' }}
            </span>
          </a>

          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <a href="{{ $openUrl }}" class="font-extrabold text-slate-900 group-hover:text-[#006c47] text-sm hover:underline transition-colors">
                {{ $notification->data['title'] ?? 'Notificación del Sistema' }}
              </a>
              <span class="text-[11px] font-semibold text-slate-400 shrink-0">
                {{ $notification->created_at->diffForHumans() }}
              </span>
            </div>

            <a href="{{ $openUrl }}" class="block text-xs text-slate-600 hover:text-slate-900 mt-1 leading-relaxed">
              {{ $notification->data['message'] ?? '' }}
            </a>

            <div class="mt-2.5">
              <a href="{{ $openUrl }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-[#006c47] hover:bg-[#10B981] text-white shadow-2xs transition-colors">
                <span class="material-symbols-outlined text-sm">visibility</span>
                <span>{{ $isSalesReport ? 'Abrir Reporte de Ventas y Diagnóstico Causal →' : 'Ver Mensaje / Detalle →' }}</span>
              </a>
            </div>
          </div>

          <div class="shrink-0">
             <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
               @csrf
               <button type="submit" class="p-2 text-slate-400 hover:text-[#006c47] hover:bg-emerald-100 rounded-xl transition-colors cursor-pointer" title="Marcar como leída y ocultar">
                 <span class="material-symbols-outlined text-lg">check</span>
               </button>
             </form>
          </div>
        </div>
      @empty
        <div class="text-center py-14 bg-slate-50/60 rounded-2xl border border-slate-200/80">
          <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">notifications_paused</span>
          <h3 class="text-base font-bold text-slate-700">Sin notificaciones pendientes por ahora</h3>
          <p class="text-xs text-slate-500 mt-1">Ya has revisado todos tus mensajes y alertas recientes.</p>
        </div>
      @endforelse
    </div>
  </div>

</div>
@endsection
