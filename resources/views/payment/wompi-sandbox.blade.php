@extends('layouts.app')
@section('title', 'Pasarela Wompi Sandbox | FusaShop')

@section('content')
<div class="max-w-xl mx-auto px-4 py-12">
  <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 p-6 text-white">
      <div class="flex items-center justify-between mb-2">
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
          Modo Sandbox Activo (Sin API Keys en .env)
        </span>
        <span class="text-xs font-mono text-slate-300">SHA-256 Verificado</span>
      </div>
      <h1 class="text-xl font-extrabold font-['Manrope']">Simulador Oficial Wompi Bancolombia</h1>
      <p class="text-xs text-slate-300 mt-1">
        Cuando agregues <code class="text-emerald-300">WOMPI_PUBLIC_KEY</code> y <code class="text-emerald-300">WOMPI_INTEGRITY_SECRET</code> en tu archivo <code class="text-emerald-300">.env</code>, esta pantalla redirigirá automáticamente a <code class="text-emerald-300">checkout.wompi.co</code>.
      </p>
    </div>

    <div class="p-6 space-y-5">
      <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
        <div class="flex justify-between">
          <span class="text-slate-500 font-semibold">Referencia de Pago:</span>
          <span class="font-mono font-bold text-slate-900">{{ $reference }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500 font-semibold">Monto Total (con descuentos e impuestos):</span>
          <span class="font-black text-emerald-700 text-base">${{ number_format($amountCop, 0, ',', '.') }} {{ $currency }}</span>
        </div>
        <div class="flex justify-between items-center pt-2 border-t border-slate-200/70">
          <span class="text-slate-400 text-[11px]">Firma Criptográfica SHA-256:</span>
          <span class="font-mono text-[10px] text-slate-500 truncate max-w-[240px]" title="{{ $signature }}">{{ $signature }}</span>
        </div>
      </div>

      @if(!$signatureValid)
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold">
          La firma de integridad SHA-256 no coincide. La transacción fue bloqueada por seguridad.
        </div>
      @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
          <form method="POST" action="{{ route('wompi.sandbox.complete') }}">
            @csrf
            <input type="hidden" name="reference" value="{{ $reference }}">
            <input type="hidden" name="amount_in_cents" value="{{ $amountInCents }}">
            <input type="hidden" name="currency" value="{{ $currency }}">
            <input type="hidden" name="signature" value="{{ $signature }}">
            <input type="hidden" name="redirect_url" value="{{ $redirectUrl }}">
            <input type="hidden" name="status" value="APPROVED">
            <button type="submit" class="w-full py-3.5 px-4 bg-[#006c47] hover:bg-[#10B981] text-white font-black text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
              <span class="material-symbols-outlined text-base">check_circle</span>
              Aprobar Pago (Sandbox)
            </button>
          </form>

          <form method="POST" action="{{ route('wompi.sandbox.complete') }}">
            @csrf
            <input type="hidden" name="reference" value="{{ $reference }}">
            <input type="hidden" name="amount_in_cents" value="{{ $amountInCents }}">
            <input type="hidden" name="currency" value="{{ $currency }}">
            <input type="hidden" name="signature" value="{{ $signature }}">
            <input type="hidden" name="redirect_url" value="{{ $redirectUrl }}">
            <input type="hidden" name="status" value="DECLINED">
            <button type="submit" class="w-full py-3.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
              <span class="material-symbols-outlined text-base">cancel</span>
              Simular Pago Rechazado
            </button>
          </form>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
