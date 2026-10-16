@extends('layouts.app')
@section('title', 'Mis PQRS')
@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-['Manrope'] font-bold text-on-background">Mis PQRS</h1>
            <p class="text-on-surface-variant">Peticiones, Quejas, Reclamos y Sugerencias</p>
        </div>
        <a href="{{ route('pqrs.create') }}" class="px-6 py-3 bg-primary text-white font-bold rounded-2xl shadow-lg hover:opacity-95 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined">add_circle</span> Nueva Solicitud
        </a>
    </div>

    @if($pqrs->isEmpty())
        <div class="bg-surface-container-lowest rounded-3xl p-12 text-center shadow-card border border-outline-variant/30">
            <span class="material-symbols-outlined text-6xl text-on-surface-variant/30 mb-4">assignment</span>
            <p class="text-on-surface-variant font-medium text-lg">No has enviado ninguna solicitud aún.</p>
            <p class="text-on-surface-variant/60 text-sm mt-2 mb-6">Si tienes alguna duda o reclamo, estamos para ayudarte.</p>
            <a href="{{ route('pqrs.create') }}" class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                Empezar ahora <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>
    @else
        <div class="grid gap-4">
            @foreach($pqrs as $item)
                @php
                    $statusColors = [
                        'pending'   => ['bg-surface-container-high', 'text-on-surface-variant'],
                        'in_review' => ['bg-blue-100', 'text-blue-700'],
                        'resolved'  => ['bg-green-100', 'text-green-700'],
                        'closed'    => ['bg-gray-100', 'text-gray-600'],
                    ];
                    $c = $statusColors[$item->status] ?? ['bg-surface-container', 'text-on-surface'];
                @endphp
                <a href="{{ route('pqrs.show', $item->id) }}" class="bg-surface-container-lowest p-5 rounded-2xl shadow-sm hover:shadow-md transition-all flex items-center gap-4 border border-outline-variant/20">
                    <div class="w-12 h-12 rounded-xl bg-surface-container flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined">
                            @if($item->type == 'peticion') info @elseif($item->type == 'queja') report @elseif($item->type == 'reclamo') gavel @else lightbulb @endif
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full {{ $c[0] }} {{ $c[1] }}">
                                {{ $item->status }}
                            </span>
                            <span class="text-xs text-on-surface-variant">{{ $item->created_at->format('d M, Y') }}</span>
                        </div>
                        <h3 class="font-bold text-on-surface truncate">{{ $item->subject }}</h3>
                        <p class="text-sm text-on-surface-variant truncate">{{ $item->content }}</p>
                    </div>
                    <span class="material-symbols-outlined text-on-surface-variant/40">chevron_right</span>
                </a>
            @endforeach
        </div>
        <div class="mt-8">
            {{ $pqrs->links() }}
        </div>
    @endif
</div>
@endsection
