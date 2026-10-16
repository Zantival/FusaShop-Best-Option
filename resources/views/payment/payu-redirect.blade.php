@extends('layouts.app')

@section('title', 'Redirigiendo a PayU...')

@section('content')
<div class="max-w-md mx-auto px-6 py-20 text-center">
    <div class="mb-8">
        <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-primary text-4xl animate-spin">sync</span>
        </div>
        <h1 class="text-2xl font-black text-on-surface font-headline mb-2">Conectando con PayU</h1>
        <p class="text-on-surface-variant">Estamos preparando tu pasarela de pago segura. No cierres esta ventana.</p>
    </div>

    <form id="payu-form" method="post" action="{{ $params['url'] }}">
        @foreach($params as $key => $value)
            @if($key !== 'url')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        
        <noscript>
            <p class="mb-4">Si no eres redirigido automáticamente, haz clic en el botón:</p>
            <button type="submit" class="px-6 py-3 bg-primary text-white font-bold rounded-xl">Continuar al Pago</button>
        </noscript>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('payu-form').submit();
</script>
@endpush
@endsection
