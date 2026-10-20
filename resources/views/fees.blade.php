@extends('layouts.app')

@section('title', 'Tarifas & FusaCoins')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-4xl">
    <div class="bg-surface-container-lowest p-8 md:p-12 rounded-3xl shadow-card border border-outline-variant/20">
        <h1 class="text-3xl md:text-4xl font-black text-primary mb-8 font-headline">Tarifas &amp; FusaCoins</h1>

        <div class="prose prose-slate max-w-none text-on-surface-variant leading-relaxed space-y-6">
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3">Tarifas para comercios</h2>
                <p>Registrarte como vendedor en FusaShop es gratuito. Las comisiones por venta y los costos de pasarela de pago (Wompi) y logística (Envia.com) se detallan en el Merchant Center, en la sección de finanzas.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3">FusaCoins</h2>
                <p>Los FusaCoins son el programa de beneficios de FusaShop para compradores y comercios de la comunidad. Para conocer tu saldo y condiciones, consulta tu cuenta o escríbenos por PQRS.</p>
            </section>
            <div class="flex flex-wrap gap-3 pt-2">
                <a href="{{ route('register', ['role' => 'merchant']) }}" class="px-5 py-3 bg-primary text-white rounded-xl font-bold text-sm no-underline">Vender en FusaShop</a>
                <a href="{{ route('pqrs.create') }}" class="px-5 py-3 border border-outline-variant rounded-xl font-bold text-sm text-on-surface no-underline">Tengo preguntas</a>
            </div>
        </div>
    </div>
</div>
@endsection
