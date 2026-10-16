@extends('layouts.app')
@section('title', 'Bitácora de Auditoría Admin | FusaShop')

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">

  <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
    <div>
      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-[#006c47] uppercase">Gobernanza & Seguridad (Ley 1581)</span>
      <h1 class="text-2xl font-extrabold text-slate-900 mt-1">Bitácora de Auditoría de Acciones Administrativas</h1>
      <p class="text-xs text-slate-500 mt-0.5">Registro inmutable de bloqueos, borrados lógicos, decisiones KYC, descargas de RUT y cambios de comisión</p>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 font-black uppercase text-[10px] text-slate-600">
          <tr>
            <th class="p-3.5">Fecha / Hora</th>
            <th class="p-3.5">Administrador</th>
            <th class="p-3.5">Acción</th>
            <th class="p-3.5">Entidad Afectada</th>
            <th class="p-3.5">Motivo / Justificación</th>
            <th class="p-3.5">IP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($logs as $log)
            <tr class="hover:bg-slate-50/70">
              <td class="p-3.5 whitespace-nowrap text-slate-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
              <td class="p-3.5 font-bold text-slate-900">{{ $log->admin->name ?? 'Sistema' }}</td>
              <td class="p-3.5">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-[#006c47]">
                  {{ $log->action }}
                </span>
              </td>
              <td class="p-3.5 text-slate-600">
                @if($log->target_type)
                  {{ class_basename($log->target_type) }} #{{ $log->target_id }}
                @else
                  Configuración Global
                @endif
              </td>
              <td class="p-3.5 text-slate-700 font-medium">{{ $log->reason ?? '-' }}</td>
              <td class="p-3.5 text-slate-400 font-mono text-[11px]">{{ $log->ip_address ?? '127.0.0.1' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="p-8 text-center text-slate-400">Aún no se registran eventos en la bitácora de auditoría.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-slate-100">
      {{ $logs->links() }}
    </div>
  </div>

</div>
@endsection
