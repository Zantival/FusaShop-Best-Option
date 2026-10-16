@extends('layouts.app')

@section('title', 'Política de Privacidad')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-4xl">
    <div class="bg-surface-container-lowest p-8 md:p-12 rounded-3xl shadow-card border border-outline-variant/20">
        <h1 class="text-3xl md:text-4xl font-black text-primary mb-8 font-headline">Política de Privacidad y Tratamiento de Datos</h1>
        
        <div class="prose prose-slate max-w-none text-on-surface-variant leading-relaxed space-y-6">
            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">security</span>
                    1. Responsable del Tratamiento
                </h2>
                <p>FusaShop, con domicilio en Fusagasugá, Cundinamarca, es el responsable del tratamiento de sus datos personales recolectados a través de nuestra plataforma e-commerce.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">analytics</span>
                    2. Finalidades de la Recolección
                </h2>
                <p>Sus datos serán utilizados para las siguientes finalidades:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Gestión de la cuenta de usuario y autenticación.</li>
                    <li>Procesamiento de pedidos, pagos y envíos.</li>
                    <li>Comunicación entre consumidores y comerciantes (Chat).</li>
                    <li>Envío de notificaciones sobre el estado de sus compras.</li>
                    <li>Mejora de la experiencia de usuario mediante análisis de datos.</li>
                    <li>Cumplimiento de obligaciones legales y contables.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">gavel</span>
                    3. Derechos del Titular (Ley 1581 de 2012)
                </h2>
                <p>De acuerdo con la legislación colombiana, usted tiene derecho a:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Conocer, actualizar y rectificar sus datos personales.</li>
                    <li>Solicitar prueba de la autorización otorgada.</li>
                    <li>Ser informado sobre el uso que se le ha dado a sus datos.</li>
                    <li>Presentar quejas ante la Superintendencia de Industria y Comercio.</li>
                    <li>Revocar la autorización y/o solicitar la supresión del dato cuando no se respeten los principios, derechos y garantías constitucionales y legales.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">cookie</span>
                    4. Uso de Cookies
                </h2>
                <p>FusaShop utiliza cookies para mejorar la navegación, recordar sus preferencias y analizar el tráfico del sitio. Usted puede configurar su consentimiento a través del banner que aparece al ingresar a la plataforma.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">contact_support</span>
                    5. Contacto para Solicitudes ARCO
                </h2>
                <p>Para ejercer sus derechos de Acceso, Rectificación, Cancelación u Oposición, puede contactarnos a través de:</p>
                <div class="mt-4 p-4 bg-primary/5 rounded-2xl border border-primary/10">
                    <p class="font-bold text-primary">Correo electrónico: <span class="text-on-surface-variant font-normal">privacidad@fusashop.com.co</span></p>
                    <p class="font-bold text-primary">Dirección: <span class="text-on-surface-variant font-normal">Calle de la Tecnología, Fusagasugá</span></p>
                </div>
            </section>
        </div>

        <div class="mt-12 pt-8 border-t border-outline-variant/20 flex justify-center">
            <a href="{{ route('register') }}" class="px-8 py-3 bg-primary-gradient text-white font-bold rounded-2xl shadow-md hover:scale-105 transition-transform">
                Volver al Registro
            </a>
        </div>
    </div>
</div>
@endsection
