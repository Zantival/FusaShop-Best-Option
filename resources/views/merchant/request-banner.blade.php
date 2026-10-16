@extends('layouts.app')
@section('title','Solicitar Espacio Publicitario')
@section('content')
<div class="max-w-3xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-2 flex items-center gap-2">
    <span class="material-symbols-outlined text-primary">campaign</span> Solicitar Banner Publicitario
  </h1>
  <p class="text-on-surface-variant mb-8">Sube tu banner para la página principal y adjunta el comprobante de pago. Un analista revisará tu solicitud.</p>

  <form method="POST" action="{{ route('merchant.banner.request.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-card space-y-5">
      <h2 class="font-bold text-on-background font-['Manrope'] text-lg">Información del Banner</h2>

      <div>
        <label class="block text-sm font-semibold text-on-surface-variant mb-1">Imagen o Video Publicitario del Banner *</label>
        <p class="text-xs text-on-surface-variant/70 mb-2">Sube una imagen horizontal (JPG/PNG/WEBP) o un video promocional corto (MP4/WEBM/MOV, máx. 10MB).</p>
        <input type="file" name="image" accept="image/*,video/*" required
               class="w-full text-sm text-on-surface-variant file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer">
      </div>

      <div>
        <label class="block text-sm font-semibold text-on-surface-variant mb-1">Pago en Línea Seguro *</label>
        <div class="p-4 border border-outline-variant rounded-xl bg-surface-container-low/30 space-y-3">
          <div class="flex items-center gap-2 mb-2">
            <span class="material-symbols-outlined text-primary">credit_card</span>
            <span class="font-semibold text-sm">Tarjeta de Crédito / Débito</span>
          </div>
          <input type="text" placeholder="Número de Tarjeta (ej: 4000 1234 ...)" required class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
          <div class="flex gap-3">
             <input type="text" placeholder="MM/AA" required class="w-1/2 rounded-lg border border-outline-variant px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
             <input type="text" placeholder="CVC" required class="w-1/2 rounded-lg border border-outline-variant px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
          </div>
        </div>
        <p class="text-xs text-on-surface-variant/70 mt-2">El pago se procesará y aprobará de inmediato sin necesidad de adjuntar comprobante.</p>
      </div>

      <div>
        <label class="block text-sm font-semibold text-on-surface-variant mb-1">Notas Adicionales (Opcional)</label>
        <textarea name="notes" rows="4" placeholder="Ej: Me gustaría que el banner enlace a mi categoría de herramientas..."
                  class="w-full rounded-xl border border-outline-variant px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">{{ old('notes') }}</textarea>
      </div>
    </div>

    @if($errors->any())
      <div class="bg-error-container text-error rounded-xl p-4 text-sm">
        <ul class="list-disc pl-4 space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <div class="flex items-center gap-4">
        <a href="{{ route('merchant.store.edit') }}" class="px-6 py-3 font-semibold text-on-surface-variant hover:text-primary transition-colors">Cancelar</a>
        <button type="submit" class="flex-1 py-3 bg-primary-gradient text-white font-bold rounded-xl hover:opacity-90 active:scale-95 transition-all shadow-md flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">send</span> Enviar Solicitud
        </button>
    </div>
  </form>
</div>
@endsection
