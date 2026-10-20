@extends('layouts.app')

@section('title', 'Términos del Servicio')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-4xl">
    <div class="bg-surface-container-lowest p-8 md:p-12 rounded-3xl shadow-card border border-outline-variant/20">
        <h1 class="text-3xl md:text-4xl font-black text-primary mb-8 font-headline">Términos del Servicio</h1>

        <div class="prose prose-slate max-w-none text-on-surface-variant leading-relaxed space-y-6">
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3">1. Aceptación</h2>
                <p>Al registrarte o usar FusaShop aceptas estos términos. FusaShop es un marketplace que conecta compradores con comercios de Fusagasugá y la región del Sumapaz.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3">2. Cuentas</h2>
                <p>Eres responsable de la veracidad de tus datos y de la seguridad de tu cuenta. Los comercios deben completar su perfil y verificación para vender.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3">3. Compras, pagos y envíos</h2>
                <p>Los pagos se procesan a través de Wompi, los envíos mediante Envia.com y la facturación electrónica con Factus (DIAN). Cada comercio es responsable de la calidad y disponibilidad de sus productos.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3">4. Garantías y reclamos</h2>
                <p>Puedes radicar peticiones, quejas, reclamos y sugerencias desde la sección <a href="{{ route('pqrs.create') }}" class="text-primary font-bold underline">PQRS &amp; Garantías</a>.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3">5. Datos personales</h2>
                <p>El tratamiento de tus datos se rige por nuestra <a href="{{ route('privacy') }}" class="text-primary font-bold underline">Política de Privacidad</a>.</p>
            </section>
        </div>
    </div>
</div>
@endsection
