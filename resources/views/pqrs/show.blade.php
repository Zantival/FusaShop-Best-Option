@extends('layouts.app')
@section('title', 'Detalle PQRS')
@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
    <div class="mb-8 flex items-center justify-between">
        <a href="{{ route('pqrs.index') }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
        </a>
        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest">PQRS #{{ str_pad($pqrs->id, 5, '0', STR_PAD_LEFT) }}</span>
    </div>

    <div class="bg-surface-container-lowest rounded-3xl shadow-card overflow-hidden border border-outline-variant/30">
        <div class="p-8 border-b border-outline-variant/20">
            <div class="flex items-center gap-3 mb-4">
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-primary/10 text-primary">
                    {{ $pqrs->type }}
                </span>
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider 
                    @if($pqrs->status == 'pending') bg-surface-container-high text-on-surface-variant 
                    @elseif($pqrs->status == 'resolved') bg-green-100 text-green-700 
                    @else bg-blue-100 text-blue-700 @endif">
                    {{ $pqrs->status }}
                </span>
            </div>
            <h1 class="text-2xl font-bold text-on-surface mb-2">{{ $pqrs->subject }}</h1>
            <p class="text-xs text-on-surface-variant">Enviado el {{ $pqrs->created_at->format('d/m/Y \a \l\a\s H:i') }}</p>
        </div>

        <div class="p-8 bg-surface-container-low/30">
            <h3 class="text-xs font-black text-on-surface-variant uppercase tracking-widest mb-4">Tu Mensaje:</h3>
            <div class="bg-white p-6 rounded-2xl border border-outline-variant/20 text-on-surface leading-relaxed">
                {{ $pqrs->content }}
            </div>
        </div>

        @if($pqrs->admin_response)
        <div class="p-8 bg-primary/5">
            <div class="flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary">support_agent</span>
                <h3 class="text-xs font-black text-primary uppercase tracking-widest">Respuesta del Administrador:</h3>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-primary/20 text-on-surface shadow-sm">
                <p class="leading-relaxed">{{ $pqrs->admin_response }}</p>
                <div class="mt-4 pt-4 border-t border-outline-variant/10 text-[10px] text-on-surface-variant/60 flex items-center justify-between">
                    <span>Atendido por Equipo FusaShop</span>
                    @if($pqrs->resolved_at)
                        <span>Resuelto el {{ $pqrs->resolved_at->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>
        </div>
        @else
        <div class="p-8 text-center border-t border-outline-variant/10">
            <span class="material-symbols-outlined text-4xl text-on-surface-variant/20 mb-2">hourglass_empty</span>
            <p class="text-sm text-on-surface-variant/60">Aún no hay una respuesta oficial. Estamos trabajando en tu solicitud.</p>
        </div>
        @endif
    </div>
</div>
@endsection
