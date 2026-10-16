@extends('layouts.app')
@section('title','Integraciones')
@section('content')
<div class="max-w-6xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-6">Auditoría de integraciones</h1>

  @if(session('success'))<div class="mb-4 p-4 rounded-xl bg-[#6efcb9]/40 text-[#003f28] text-sm font-semibold">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="mb-4 p-4 rounded-xl bg-[#ffdad6] text-[#ba1a1a] text-sm font-semibold">{{ session('error') }}</div>@endif

  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    @foreach(['wompi'=>'Wompi','envia'=>'Envia.com','factus'=>'Factus','resend'=>'Resend'] as $k => $label)
      <a href="{{ route('analyst.integrations', ['service'=>$k,'status'=>'failed']) }}" class="bg-surface-container-lowest rounded-2xl p-4 shadow-[0_12px_32px_rgba(27,28,28,.06)]">
        <p class="text-xs uppercase font-bold text-on-surface-variant">{{ $label }}</p>
        <p class="text-2xl font-black {{ ($failed24h[$k] ?? 0) > 0 ? 'text-[#ba1a1a]' : 'text-[#006c47]' }}">{{ $failed24h[$k] ?? 0 }}</p>
        <p class="text-[11px] text-on-surface-variant">fallos sin resolver (24 h)</p>
      </a>
    @endforeach
  </div>

  <form method="GET" class="flex gap-3 mb-4">
    <select name="service" class="rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
      <option value="">Todos los servicios</option>
      @foreach(['wompi','envia','factus','resend'] as $s)<option value="{{ $s }}" {{ request('service')===$s?'selected':'' }}>{{ $s }}</option>@endforeach
    </select>
    <select name="status" class="rounded-xl border border-outline-variant/40 px-3 py-2 text-sm">
      <option value="">Todos</option>
      <option value="failed" {{ request('status')==='failed'?'selected':'' }}>Fallidos</option>
      <option value="success" {{ request('status')==='success'?'selected':'' }}>Exitosos</option>
    </select>
    <button class="px-4 py-2 bg-primary text-white rounded-xl text-sm font-bold">Filtrar</button>
  </form>

  <div class="bg-surface-container-lowest rounded-2xl overflow-x-auto shadow-[0_12px_32px_rgba(27,28,28,.06)]">
    <table class="w-full text-sm">
      <thead class="text-left text-xs uppercase text-on-surface-variant">
        <tr><th class="p-3">Fecha</th><th>Servicio</th><th>Acción</th><th>Pedido</th><th>Estado</th><th>Detalle</th><th></th></tr>
      </thead>
      <tbody>
      @forelse($logs as $log)
        <tr class="border-t border-outline-variant/20 align-top">
          <td class="p-3 whitespace-nowrap">{{ $log->created_at->format('d/m H:i:s') }}</td>
          <td class="font-semibold">{{ $log->service }}</td>
          <td>{{ $log->action }}</td>
          <td class="font-mono text-xs">{{ $log->order?->reference ?? '—' }}</td>
          <td>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $log->status === 'failed' ? 'bg-[#ffdad6] text-[#ba1a1a]' : 'bg-[#6efcb9]/40 text-[#003f28]' }}">
              {{ $log->status }}{{ $log->http_status ? ' · '.$log->http_status : '' }}
            </span>
            @if($log->resolved_at)<span class="block text-[10px] text-on-surface-variant">reintentado</span>@endif
          </td>
          <td class="max-w-md text-xs text-on-surface-variant break-words">{{ \Illuminate\Support\Str::limit($log->error ?? json_encode($log->response), 220) }}</td>
          <td class="p-3">
            @if($log->status === 'failed' && !$log->resolved_at && $log->retry_job)
              <form method="POST" action="{{ route('analyst.integrations.retry', $log) }}">@csrf
                <button class="px-3 py-1 bg-primary text-white rounded-lg text-xs font-bold">Reintentar</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="p-6 text-center text-on-surface-variant">Sin registros.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
