<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\{Product, Cart, CartItem, Order, OrderItem, CompanyProfile, Review, User, GlobalBanner};
use App\Events\ProductOutOfStock;
use App\Notifications\{NewProductReview, NewOrderNotification};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConsumerController extends Controller
{
    public function home()
    {
        $featured = Product::active()->with('merchant.companyProfile')->latest()->take(8)->get();
        $flashDeals = Product::active()->with('merchant.companyProfile')
            ->orderByDesc('discount_percent')
            ->latest()
            ->take(4)
            ->get();
        $categories = Product::active()->select('category')->distinct()->pluck('category');
        $globalBanners = GlobalBanner::where('is_active', true)->orderBy('sort_order')->take(8)->get();
        $topBanners = $globalBanners->take(4)->values();
        $bottomBanners = $globalBanners->slice(4, 4)->values();
        return view('consumer.home', compact('featured', 'flashDeals', 'categories', 'globalBanners', 'topBanners', 'bottomBanners'));
    }

    public function catalog(Request $request)
    {
        $q = Product::active()->with('merchant.companyProfile');
        
        if ($request->filled('search'))        $q->search($request->search);
        if ($request->filled('category'))      $q->where('category', $request->category);
        if ($request->filled('min_price'))     $q->where('price', '>=', $request->min_price);
        if ($request->filled('max_price'))     $q->where('price', '<=', $request->max_price);
        if ($request->boolean('on_sale'))      $q->where('discount_percent', '>', 0);
        if ($request->boolean('allows_points')) $q->where('allows_points', true);
        if ($request->filled('merchant_id'))   $q->where('merchant_id', (int) $request->merchant_id);
        if ($request->filled('products')) {
            $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->products))));
            if (!empty($ids)) {
                $q->whereIn('id', $ids);
            }
        }
        
        $products = $q->paginate(12)->withQueryString();
        $categories = Product::active()->select('category')->distinct()->pluck('category');

        // Solo los primeros 10 para el carrusel de vendedores
        $directory = CompanyProfile::where('kyc_status', 'approved')->select('id','merchant_id','company_name','logo_path')->take(10)->get();

        if ($request->boolean('partial') || $request->ajax()) {
            return view('consumer.partials.products-grid', compact('products', 'categories'));
        }

        return view('consumer.catalog', compact('products', 'categories', 'directory'));
    }

    public function assistantSearch(Request $request)
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:150',
        ]);

        $query = mb_strtolower(trim($data['q']), 'UTF-8');
        $normalized = \Illuminate\Support\Str::ascii($query);
        $user = Auth::user();
        $role = $user ? $user->role : 'consumer';

        // Administrador disponible para escalamiento o soporte
        $adminId = \App\Models\User::where('role', 'analyst')->value('id');

        // ==========================================
        // 1. ROL: COMERCIANTE / VENDEDOR
        // ==========================================
        if ($role === 'merchant') {
            return $this->handleMerchantAssistant($query, $normalized, $user, $adminId);
        }

        // ==========================================
        // 2. ROL: ADMINISTRADOR / ANALISTA
        // ==========================================
        if ($role === 'analyst') {
            return $this->handleAdminAssistant($query, $normalized, $user);
        }

        // ==========================================
        // 3. ROL: COMPRADOR / CLIENTE (O INVITADO)
        // ==========================================
        return $this->handleConsumerAssistant($query, $normalized, $data['q'], $adminId);
    }

    private function handleMerchantAssistant(string $query, string $normalized, \App\Models\User $user, ?int $adminId)
    {
        $links = [];
        $escalate = false;

        // Sugerencias para optimizar productos y vender más
        if (preg_match('/\b(mejorar|vender mas|mas ventas|ventas|foto|fotos|imagen|imagenes|descripcion|descripciones|titulo|presentacion|consejo|consejos|tip|tips|estrategia|marketing|variante|variantes|colores|presentar)\b/', $normalized)) {
            $answer = "📈 **Consejos para mejorar la presentación de tus productos y vender más en FusaShop:**\n\n"
                . "1. 📸 **Fotografías profesionales**: Usa fotos nítidas con luz natural y fondo claro. Sube fotos desde varios ángulos y, si tu producto tiene variantes de color, asigna la foto real de cada color.\n"
                . "2. ✍️ **Títulos y descripciones atractivas**: Especifica medidas exactas, materiales de confección o elaboración, instrucciones de cuidado y tiempos de despacho.\n"
                . "3. 🪙 **Activa la redención de FusaCoins**: En la edición de tu producto, marca la opción *'Permitir redimir FusaCoins en este producto'*. Los compradores buscan productos con la insignia dorada para aprovechar sus puntos.\n"
                . "4. 📦 **Inventario y stock al día**: Mantén el stock real actualizado para evitar cancelaciones involuntarias y mantener una reputación impecable.";
            $links[] = ['label' => '📦 Gestionar mis productos', 'url' => route('merchant.products')];
            $links[] = ['label' => '➕ Publicar nuevo producto', 'url' => route('merchant.products.create')];
        } elseif (preg_match('/\b(envia|envio|envios|guia|guias|despacho|despachos|paquete|flete|recoleccion|transportadora)\b/', $normalized)) {
            $answer = "🚚 **Envíos y Logística con Envia.com:**\n\n"
                . "Cuando un cliente paga su compra, el sistema genera la guía de Envia.com automáticamente. Puedes descargar e imprimir el rótulo en PDF desde tu módulo de pedidos, embalar adecuadamente el paquete y entregarlo a la transportadora asignada (o llevarlo a una sede Envia de Fusagasugá).";
            $links[] = ['label' => '📦 Ver pedidos para despachar', 'url' => route('merchant.orders')];
        } elseif (preg_match('/\b(factura|facturas|factus|dian|cufe|electronica|iva|impuesto|xml)\b/', $normalized)) {
            $answer = "🧾 **Facturación Electrónica con Factus:**\n\n"
                . "FusaShop emite la factura electrónica legal ante la DIAN mediante Factus inmediatamente después de confirmado el pago con Wompi. Cada factura cuenta con código CUFE, PDF y XML oficial. Los compradores pueden descargarla desde su historial de pedidos.";
            $links[] = ['label' => '📊 Ver métricas en dashboard', 'url' => route('merchant.dashboard')];
        } elseif (preg_match('/\b(comision|comisiones|pago|pagos|retiro|retirar|dinero|saldo|banco|transferencia|liquidar)\b/', $normalized)) {
            $answer = "💰 **Comisiones y Retiro de Fondos:**\n\n"
                . "La plataforma deduce la comisión pactada por cada pedido entregado con éxito. En tu Dashboard puedes consultar el saldo neto disponible y solicitar la dispersión de tus ingresos directamente a tu cuenta bancaria.";
            $links[] = ['label' => '💳 Consultar saldo y finanzas', 'url' => route('merchant.dashboard')];
        } elseif (preg_match('/\b(kyc|rut|camara|comercio|documento|documentos|verificar|verificacion|aprobar|perfil)\b/', $normalized)) {
            $answer = "📑 **Validación Comercial (KYC):**\n\n"
                . "Para habilitar la publicación de productos y recepción de fondos, debes subir tu RUT y certificado de Cámara de Comercio vigente en tu perfil. El equipo administrativo valida los documentos en un plazo máximo de 24 horas hábiles.";
            $links[] = ['label' => '🏢 Ver mi perfil y documentos', 'url' => route('merchant.profile')];
        } elseif (preg_match('/\b(soporte|ayuda|administrador|admin|asesor|humano|problema|error|reclamo)\b/', $normalized)) {
            $answer = "👨‍💼 **Soporte Administrativo Directo:**\n\n"
                . "Si presentas inconvenientes con tu cuenta, liquidaciones o necesitas soporte operativo prioritario, puedes abrir una conversación directa en tiempo real con la administración de FusaShop.";
            $escalate = true;
            $links[] = [
                'label' => '💬 Abrir chat con el Administrador',
                'url' => $adminId ? route('chat.show', $adminId) : route('chat.index'),
                'primary' => true
            ];
        } else {
            $answer = "Hola " . e($user->name) . " 👋 Soy FusaBot para Comerciantes. Estoy aquí para ayudarte a optimizar la presentación de tus productos para vender más, resolver dudas sobre logística con Envia.com, facturación Factus o tus comisiones y retiros. ¿Qué deseas consultar?";
            $links[] = ['label' => '📈 Cómo vender más', 'url' => '#', 'prompt' => '¿Cómo mejorar la presentación de mis productos para vender más?'];
            $links[] = ['label' => '📦 Guías Envia.com', 'url' => route('merchant.orders')];
            $links[] = ['label' => '🏢 Perfil comercial', 'url' => route('merchant.profile')];
        }

        return response()->json([
            'answer' => $answer,
            'links' => $links,
            'products' => [], // Los comerciantes no reciben recomendaciones de compra
            'escalate' => $escalate,
        ]);
    }

    private function handleAdminAssistant(string $query, string $normalized, \App\Models\User $user)
    {
        $links = [];
        $paidOrdersCount = \App\Models\Order::where('payment_status', 'paid')->count();
        $totalSales = (float) \App\Models\Order::where('payment_status', 'paid')->sum('total');
        $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
        $pendingKyc = \App\Models\CompanyProfile::where('kyc_status', 'pending')->count();
        $openPqrs = \App\Models\PQRS::whereIn('status', ['open', 'in_progress'])->count();

        if (preg_match('/\b(ventas|metricas|ingresos|totales|dinero|recaudo|facturado)\b/', $normalized)) {
            $answer = "📊 **Resumen Financiero y de Operaciones:**\n\n"
                . "- **Ventas Pagadas Totales**: $" . number_format($totalSales, 0, ',', '.') . " COP\n"
                . "- **Pedidos Pagados Aprobados**: {$paidOrdersCount}\n"
                . "- **Pedidos Pendientes**: {$pendingOrders}\n"
                . "- **PQRS en Gestión**: {$openPqrs}\n\n"
                . "Puedes ver el detalle completo en los tableros analíticos:";
            $links[] = ['label' => '📊 Dashboard General', 'url' => route('analyst.dashboard')];
        } elseif (preg_match('/\b(kyc|comercio|comercios|verificar|vendedor|vendedores|documento|documentos)\b/', $normalized)) {
            $answer = "🏢 **Verificaciones KYC de Comerciantes:**\n\n"
                . "Actualmente hay **{$pendingKyc} comercios pendientes** por validación de documentos (RUT y Cámara de Comercio). Recuerda auditar la autenticidad antes de autorizar la publicación de catálogo.";
            $links[] = ['label' => '🏢 Validar Comercios en Panel', 'url' => route('analyst.dashboard')];
        } elseif (preg_match('/\b(pqrs|reclamo|reclamos|queja|quejas|solicitud|ticket|tickets)\b/', $normalized)) {
            $answer = "📑 **Bandeja de PQRS y Solicitudes:**\n\n"
                . "Hay **{$openPqrs} casos activos** que requieren seguimiento o respuesta según los términos legales de SLA (Ley 1755).";
            $links[] = ['label' => '📑 Ir a Gestión de PQRS', 'url' => route('pqrs.index')];
        } elseif (preg_match('/\b(log|logs|error|errores|fallo|fallos|auditoria|wompi|envia|factus)\b/', $normalized)) {
            $failedLogsCount = \App\Models\IntegrationLog::where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count();
            $answer = "🛡️ **Auditoría e Integraciones:**\n\n"
                . "Se han registrado **{$failedLogsCount} incidentes** en logs de integración (Wompi / Factus / Envia.com) durante los últimos 7 días. Los pedidos en estado de reintento pueden ser reprocesados desde el módulo de logs.";
            $links[] = ['label' => '🛡️ Ver Auditoría en Dashboard', 'url' => route('analyst.dashboard')];
        } else {
            $answer = "Hola Administrador " . e($user->name) . " 🛡️\n\n"
                . "Soy el Asistente Interno FusaBot. Tengo acceso a las métricas del sistema:\n"
                . "• Pedidos pagados: **{$paidOrdersCount}** ($" . number_format($totalSales, 0, ',', '.') . " COP)\n"
                . "• Comercios pendientes KYC: **{$pendingKyc}**\n"
                . "• PQRS en curso: **{$openPqrs}**\n\n"
                . "¿Qué consulta o tarea administrativa deseas realizar?";
            $links[] = ['label' => '📊 Dashboard Analista', 'url' => route('analyst.dashboard')];
            $links[] = ['label' => '📑 Bandeja PQRS', 'url' => route('pqrs.index')];
            $links[] = ['label' => '💬 Mensajes de soporte', 'url' => route('chat.index')];
        }

        return response()->json([
            'answer' => $answer,
            'links' => $links,
            'products' => [],
            'escalate' => false,
        ]);
    }

    private function handleConsumerAssistant(string $query, string $normalized, string $rawQuery, ?int $adminId)
    {
        $links = [];
        $escalate = false;

        // 1. Detección de Problemas Mayores o Solicitud de Humano / Administrador
        $isCriticalIssue = (bool) preg_match(
            '/\b(estafa|robo|cobro|duplicado|indebido|no llega|no llego|extraviad|perdid|danad|rot|grave|demanda|abogado|humano|persona|asesor|administrador|soporte en vivo|agente|hablar con alguien|hablar con persona|atencion humana)\b/i',
            $normalized
        );

        if ($isCriticalIssue) {
            $escalate = true;
            $answer = "⚠️ **Atención Prioritaria con Soporte Administrativo:**\n\n"
                . "Lamento sinceramente el inconveniente con tu compra o pedido. Para resolver este caso con la mayor rapidez y seguridad, he habilitado la conexión directa para que te atienda un **Administrador en Vivo** a través de nuestro chat interno, o puedas formalizar tu solicitud mediante radicación de PQRS:";

            if (Auth::check()) {
                $links[] = [
                    'label' => '💬 Hablar con el Administrador en Vivo',
                    'url' => $adminId ? route('chat.show', $adminId) : route('chat.index'),
                    'primary' => true
                ];
            } else {
                $links[] = [
                    'label' => '💬 Iniciar Sesión para Chat en Vivo',
                    'url' => route('login'),
                    'primary' => true
                ];
            }

            $links[] = ['label' => '📝 Radicar PQRS Oficial', 'url' => route('pqrs.create')];
            $links[] = ['label' => 'ℹ️ Centro de Ayuda', 'url' => route('consumer.support.contact')];

            return response()->json([
                'answer' => $answer,
                'links' => $links,
                'products' => [],
                'escalate' => true,
            ]);
        }

        // 2. FAQ: FusaCoins
        if (preg_match('/\b(fusacoin|fusacoins|coins|puntos|redimir|descuento puntos|acumular)\b/', $normalized)) {
            $answer = "🌸 **Programa de FusaCoins de FusaShop:**\n\n"
                . "• **Cómo ganar**: Acumulas **1 FusaCoin por cada $10.000 COP** de compra en pedidos confirmados.\n"
                . "• **Valor de descuento**: Cada 1 FusaCoin equivale a **$100 COP de descuento**.\n"
                . "• **Tope por compra**: Puedes redimir hasta **$50.000 COP de descuento** (500 FusaCoins) en cada pedido sobre productos participantes marcados con la insignia dorada de FusaCoins.\n"
                . "• **Uso fácil**: Al momento de pagar o en tu carrito, activa la opción *'Redimir FusaCoins'* y se descontará antes de pagar.";
            $links[] = ['label' => '🛒 Explorar productos con FusaCoins', 'url' => route('consumer.catalog')];
            return response()->json(['answer' => $answer, 'links' => $links, 'products' => [], 'escalate' => false]);
        }

        // 3. FAQ: Cancelaciones, Devoluciones y Garantías
        if (preg_match('/\b(cancelar|cancelacion|anular|devolucion|devolver|reembolso|garantia)\b/', $normalized)) {
            $answer = "🔄 **Políticas de Cancelación y Garantías:**\n\n"
                . "• **Antes del despacho**: Puedes solicitar la cancelación del pedido desde tu historial mientras el comerciante no haya generado la guía de transporte.\n"
                . "• **Pedidos sin pago**: Se cancelan automáticamente al vencer el tiempo de reserva y el stock se libera.\n"
                . "• **Garantías y devoluciones**: Si el producto recibido presenta averías o no corresponde con la descripción, cuentas con la garantía y mediación de FusaShop mediante PQRS oficial.";
            $links[] = ['label' => '📦 Mis Pedidos', 'url' => Auth::check() ? route('consumer.orders') : route('login')];
            $links[] = ['label' => '📝 Radicar PQRS', 'url' => route('pqrs.create')];
            return response()->json(['answer' => $answer, 'links' => $links, 'products' => [], 'escalate' => false]);
        }

        // 4. FAQ: Envíos con Envia.com
        if (preg_match('/\b(envio|envios|entrega|entregas|despacho|flete|tiempo|tiempos|cuanto tarda|cobertura|llegada|transportadora)\b/', $normalized)) {
            $answer = "🚚 **Envíos y Cobertura con Envia.com:**\n\n"
                . "En FusaShop todos los envíos son gestionados por **Envia.com** con cobertura urbana en Fusagasugá, zonas turísticas (Chinauta, Silvania) y despacho a nivel nacional.\n\n"
                . "• **Tarifa**: Se calcula de forma transparente según el peso volumétrico y tu dirección en el checkout.\n"
                . "• **Tiempos**: Entregas locales en 24 a 48 horas y nacionales de 2 a 4 días hábiles una vez despachado.";
            $links[] = ['label' => '🛍️ Ir a la Tienda', 'url' => route('consumer.catalog')];
            return response()->json(['answer' => $answer, 'links' => $links, 'products' => [], 'escalate' => false]);
        }

        // 5. FAQ: Métodos de Pago con Wompi
        if (preg_match('/\b(pago|pagos|pagar|metodo|metodos|nequi|daviplata|pse|tarjeta|tarjetas|wompi|credito|debito|bancolombia)\b/', $normalized)) {
            $answer = "💳 **Métodos de Pago Seguros con Wompi:**\n\n"
                . "Tu dinero está 100% protegido. Aceptamos:\n"
                . "• Transferencias PSE desde cualquier banco del país.\n"
                . "• Nequi y Daviplata en línea.\n"
                . "• Tarjetas de crédito y débito Visa, Mastercard y American Express.\n"
                . "• Botón Bancolombia.\n\n"
                . "Al pagar, tu pedido se confirma al instante y recibes la factura electrónica legal en tu correo.";
            $links[] = ['label' => '🛍️ Explorar Catálogo', 'url' => route('consumer.catalog')];
            return response()->json(['answer' => $answer, 'links' => $links, 'products' => [], 'escalate' => false]);
        }

        // 6. FAQ: Registro como Vendedor
        if (preg_match('/\b(vender|vendedor|vendedora|registrar|registrarme|mi negocio|publicar)\b/', $normalized)) {
            $answer = "🏪 **¿Quieres vender tus productos en FusaShop?**\n\n"
                . "Únete a la red comercial del Sumapaz. Regístrate seleccionando el perfil de comerciante, sube tu RUT y Cámara de Comercio, y comienza a publicar tu catálogo para llegar a miles de compradores.";
            $links[] = ['label' => '📝 Registrarme como comerciante', 'url' => route('register')];
            return response()->json(['answer' => $answer, 'links' => $links, 'products' => [], 'escalate' => false]);
        }

        // 7. FAQ: Perfil y Gestión de Cuenta
        if (preg_match('/\b(perfil|cuenta|nombre|correo|email|telefono|direccion|avatar|foto)\b/', $normalized)) {
            $answer = "👤 **Gestión de Mi Perfil:**\n\n"
                . "Puedes cambiar tu nombre, correo, teléfono, direcciones de envío guardadas y foto desde Mi Perfil. Edita los campos y guarda los cambios al final del formulario.";
            $links[] = [
                'label' => 'Abrir Mi Perfil',
                'url' => Auth::check() ? route('account.profile') : route('login'),
            ];
            return response()->json(['answer' => $answer, 'links' => $links, 'products' => [], 'escalate' => false]);
        }

        // 8. FAQ: Cómo comprar / Pedidos / Carrito
        if (preg_match('/\b(comprar|compra|como comprar|como realizo una compra|pedido|pedidos|carrito|checkout)\b/', $normalized)) {
            $answer = "🛍️ **Cómo comprar en FusaShop:**\n\n"
                . "Para comprar, abre el catálogo, elige un producto y agrégalo al carrito. Después confirma tu dirección de entrega y el método de pago para completar el pedido con total seguridad.";
            $links[] = ['label' => 'Ir al catálogo', 'url' => route('consumer.catalog')];
            return response()->json(['answer' => $answer, 'links' => $links, 'products' => [], 'escalate' => false]);
        }

        // 9. Búsqueda y Recomendación de Productos en Catálogo
        $words = preg_split('/[^a-z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = [
            'como', 'que', 'quiero', 'quisiera', 'puedo', 'puedes', 'podria', 'podrias',
            'tienes', 'tiene', 'hay', 'ver', 'mostrar', 'muestrame', 'busco', 'buscar',
            'necesito', 'una', 'uno', 'unas', 'unos', 'el', 'la', 'los', 'las', 'de',
            'del', 'para', 'por', 'con', 'en', 'me', 'mi', 'un', 'al', 'alguna',
            'algun', 'favor', 'venden', 'vender', 'comprar', 'compra',
        ];
        $terms = array_values(array_unique(array_filter(
            $words,
            fn (string $word) => mb_strlen($word) >= 3 && !in_array($word, $stopWords, true)
        )));

        $singularTerms = [
            'prendas' => 'prenda',
            'camisas' => 'camisa',
            'camisetas' => 'camiseta',
            'pantalones' => 'pantalon',
            'vestidos' => 'vestido',
            'hoteles' => 'hotel',
            'glampings' => 'glamping',
            'servicios' => 'servicio',
            'restaurantes' => 'restaurante',
            'productos' => 'producto',
            'articulos' => 'articulo',
        ];
        $terms = array_values(array_unique(array_map(
            fn (string $word) => $singularTerms[$word] ?? $word,
            $terms
        )));

        $topics = [
            'ropa' => [
                'keywords' => ['ropa', 'prenda', 'camisa', 'camiseta', 'pantalon', 'vestido', 'falda', 'chaqueta', 'zapato', 'calzado', 'moda'],
                'categories' => ['ropa', 'moda', 'vestuario', 'calzado'],
            ],
            'turismo' => [
                'keywords' => ['hotel', 'hostal', 'glamping', 'hospedaje', 'alojamiento', 'habitacion', 'cabana', 'turismo'],
                'categories' => ['turismo', 'hotel', 'glamping', 'hospedaje', 'alojamiento'],
            ],
            'servicios' => [
                'keywords' => ['servicio', 'servicios', 'restaurante', 'comida', 'peluqueria', 'reparacion', 'experiencia'],
                'categories' => ['servicio', 'restaurante', 'gastronomia', 'experiencia'],
            ],
        ];

        $categoryHints = [];
        foreach ($topics as $topic) {
            if (array_intersect($terms, $topic['keywords'])) {
                $categoryHints = array_merge($categoryHints, $topic['categories']);
            }
        }

        $productKeywords = array_merge(...array_map(fn ($topic) => $topic['keywords'], array_values($topics)));
        $isProductRequest = (bool) array_intersect($terms, $productKeywords)
            || (bool) array_intersect($terms, ['producto', 'productos', 'articulo', 'articulos']);

        $products = collect();
        if (!empty($terms)) {
            $products = \App\Models\Product::active()
                ->where(function ($builder) use ($terms, $categoryHints) {
                    foreach ($terms as $term) {
                        $builder->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhere('category', 'like', "%{$term}%");
                    }

                    foreach ($categoryHints as $hint) {
                        $builder->orWhere('category', 'like', "%{$hint}%");
                    }
                })
                ->orderByDesc('stock')
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'description', 'category', 'price', 'discount_percent', 'image', 'images']);
        }

        if ($products->isNotEmpty()) {
            $answer = "🌸 Encontré estas excelentes opciones en los comercios de Fusagasugá para ti:";
            $links[] = ['label' => 'Ver todo el catálogo', 'url' => route('consumer.catalog', ['search' => $rawQuery])];
        } elseif ($isProductRequest) {
            $answer = "No encontré coincidencias con ese término en el catálogo en este momento. Prueba con otro nombre o categoría.";
            $links[] = ['label' => 'Explorar catálogo completo', 'url' => route('consumer.catalog')];
        } else {
            $answer = "Puedo ayudarte a buscar productos locales, cotizar envíos o resolver dudas de tus compras. ¿Qué te gustaría encontrar hoy?";
            $links[] = ['label' => 'Explorar catálogo completo', 'url' => route('consumer.catalog')];
            $links[] = ['label' => 'Ver ofertas especiales', 'url' => route('consumer.home') . '#ofertas'];
        }

        return response()->json([
            'answer' => $answer,
            'links' => $links,
            'products' => $products->map(function (\App\Models\Product $product) {
                $imagePath = collect(array_merge([$product->image], $product->images ?? []))
                    ->filter(fn ($path) => is_string($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path))
                    ->first();

                return [
                    'name' => $product->name,
                    'category' => $product->category,
                    'description' => \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 90),
                    'original_price' => $product->hasDiscount() ? number_format($product->originalPrice(), 0, ',', '.') : null,
                    'discount_percent' => $product->discount_percent ?? 0,
                    'price' => number_format($product->effectivePrice(), 0, ',', '.'),
                    'image' => $imagePath ? url('files/' . ltrim($imagePath, '/')) : null,
                    'url' => route('consumer.product', $product->id),
                ];
            })->values(),
            'escalate' => false,
        ]);
    }

    public function productDetail($id)
    {
        $product = Product::active()->with('reviews.user')->findOrFail($id);
        $related = Product::active()->where('category', $product->category)->where('id','!=',$id)->take(4)->get();
        
        $hasPurchased = false;
        if (Auth::check()) {
            $hasPurchased = Order::where('user_id', Auth::id())
                ->where('status', 'delivered')
                ->whereHas('items', function ($q) use ($id) {
                    $q->where('product_id', $id);
                })->exists();
        }

        return view('consumer.product-detail', compact('product', 'related', 'hasPurchased'));
    }

    // --- MÉTODOS DEL CARRITO ---
    public function cartIndex()
    {
        $user = Auth::user();
        $cart = $user->cart()->with('items.product')->firstOrCreate(['user_id' => $user->id]);
        $userPoints = $user->totalPoints();
        return view('consumer.cart', compact('cart', 'userPoints'));
    }

    public function cartAdd(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id', 
            'quantity'   => 'integer|min:1',
            'options'    => 'nullable|array'
        ]);
        
        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        
        $selectedOptions = $request->input('options', []);
        
        // Intentamos encontrar un item existente con las mismas opciones
        // Para simplificar, buscamos por JSON exacto si es posible, o simplemente creamos uno nuevo
        // En MySQL/Laravel, comparar JSON exacto:
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('selected_options', json_encode($selectedOptions))
            ->first();

        if ($item) {
            $item->increment('quantity', $request->input('quantity', 1));
        } else {
            $item = CartItem::create([
                'cart_id'          => $cart->id,
                'product_id'       => $request->product_id,
                'quantity'         => $request->input('quantity', 1),
                'selected_options' => $selectedOptions
            ]);
        }

        if ($request->boolean('buy_now')) {
            session(['selected_cart_items' => [$item->id]]);
            return redirect()->route('consumer.checkout');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '¡Producto agregado al carrito con éxito!',
                'cart_count' => $cart->items()->sum('quantity')
            ]);
        }

        return back()->with('success', '¡Producto agregado al carrito con éxito!');
    }

    public function cartUpdate(Request $request, CartItem $item)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        if ($item->cart->user_id !== Auth::id()) abort(403);
        $item->update(['quantity' => $request->quantity]);
        return back()->with('success', 'Carrito actualizado.');
    }

    public function cartRemove(CartItem $item)
    {
        if ($item->cart->user_id !== Auth::id()) abort(403);
        $item->delete();
        return back()->with('success', 'Producto eliminado.');
    }

    public function cartSelect(Request $request)
    {
        $selected = $request->input('selected_items', []);
        session(['checkout_selected_items' => is_array($selected) ? array_map('intval', $selected) : []]);
        return response()->json(['success' => true]);
    }

    // El checkout (Wompi + Envia) vive en CheckoutController.

    public function orders()
    {
        $orders = Order::where('user_id', Auth::id())->with('items.product')->latest()->paginate(10);
        return view('consumer.orders', compact('orders'));
    }

    public function cancelOrder(Request $request, Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403, 'No tienes permiso para cancelar este pedido.');

        if (!$order->canBeCancelledByBuyer()) {
            return back()->with('error', 'Este pedido ya no puede cancelarse directamente porque ya fue despachado, tiene guía asignada o ya se encuentra finalizado.');
        }

        $reason = trim(strip_tags((string) $request->input('reason', 'Cancelado por el comprador')));
        if ($reason === '') {
            $reason = 'Cancelado por el comprador';
        }

        $order->load('items.product.merchant', 'user');

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $reason) {
            // 1. Liberar o restituir stock de los productos
            if ($order->stock_reserved) {
                app(\App\Services\StockService::class)->release($order);
            } elseif ($order->payment_status === 'paid') {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
            }

            // 2. Reintegrar FusaCoins si el comprador había redimido puntos en este pedido
            if ($order->points_used > 0) {
                $firstMerchantId = $order->items->pluck('product.merchant_id')->filter()->first();
                if ($firstMerchantId) {
                    $loyalty = \App\Models\LoyaltyPoint::firstOrCreate(
                        ['user_id' => $order->user_id, 'merchant_id' => $firstMerchantId],
                        ['points' => 0]
                    );
                    $loyalty->increment('points', (int) $order->points_used);
                }
            }

            // 3. Marcar la orden como cancelada con la nota del comprador
            $order->update([
                'status' => 'cancelled',
                'notes'  => $reason,
            ]);
        });

        // 4. Notificar a todos los comerciantes cuyos productos estaban en el pedido
        $merchants = $order->items
            ->map(fn ($item) => $item->product?->merchant)
            ->filter()
            ->unique('id');

        foreach ($merchants as $merchant) {
            try {
                $merchant->notify(new \App\Notifications\OrderCancelledByBuyerNotification($order, $reason));
            } catch (\Throwable $e) {
                // La notificación en base de datos se guarda aunque el transporte de correo no esté activo
            }
        }

        return back()->with('success', "El Pedido #{$order->id} ha sido cancelado exitosamente y el comercio fue notificado.");
    }

    public function receipt($id)
    {
        $order = Order::where('user_id', Auth::id())
            ->with(['items.product.merchant.companyProfile'])
            ->findOrFail($id);
            
        $userPoints = \App\Models\LoyaltyPoint::where('user_id', Auth::id())->sum('points');
            
        return view('consumer.receipt', compact('order', 'userPoints'));
    }

    // --- PERFIL Y DASHBOARD DE USUARIO ---
    public function profile(Request $request)
    {
        $user = Auth::user();
        $initialTab = $request->query('tab', 'overview');
        $userPoints = $user->totalPoints();
        $loyaltyPoints = \App\Models\LoyaltyPoint::where('user_id', $user->id)
            ->with('merchant.companyProfile')
            ->where('points', '>', 0)
            ->get();

        $orders = Order::where('user_id', $user->id)
            ->with(['items.product.merchant.companyProfile'])
            ->latest()
            ->take(15)
            ->get();

        // Envíos activos o con número de guía de Envia.com
        $activeShipments = Order::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereIn('status', ['processing', 'shipped'])
                  ->orWhereNotNull('tracking_number');
            })
            ->where('status', '!=', 'delivered')
            ->with(['items.product'])
            ->latest()
            ->take(5)
            ->get();

        // Facturas electrónicas emitidas por Factus (con CUFE / número)
        $invoices = Order::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNotNull('invoice_number')
                  ->orWhereNotNull('invoice_cufe')
                  ->orWhereNotNull('invoice_pdf_path');
            })
            ->latest()
            ->take(10)
            ->get();

        // Reservas O2O: órdenes cuyos ítems pertenezcan a hotelería, gastronomía, turismo o experiencias
        $o2oOrders = Order::where('user_id', $user->id)
            ->whereHas('items.product', function ($q) {
                $q->where('category', 'LIKE', '%Turismo%')
                  ->orWhere('category', 'LIKE', '%Gastronom%')
                  ->orWhere('category', 'LIKE', '%Restaurante%')
                  ->orWhere('category', 'LIKE', '%Hotel%')
                  ->orWhere('category', 'LIKE', '%Glamping%')
                  ->orWhere('category', 'LIKE', '%O2O%');
            })
            ->with(['items.product.merchant.companyProfile'])
            ->latest()
            ->take(6)
            ->get();

        // Si no hay pedidos O2O específicos por categoría, proveemos las órdenes pagadas recientes como vouchers válidos
        if ($o2oOrders->isEmpty()) {
            $o2oOrders = Order::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->with(['items.product.merchant.companyProfile'])
                ->latest()
                ->take(3)
                ->get();
        }

        $addresses = $user->shippingAddresses()->latest()->get();

        $coupons = \App\Models\Coupon::where('is_active', true)
            ->where(function($q) { 
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); 
            })
            ->with('merchant.companyProfile')
            ->take(6)
            ->get();

        return view('consumer.profile', compact(
            'user', 
            'initialTab',
            'userPoints', 
            'loyaltyPoints', 
            'orders', 
            'activeShipments', 
            'invoices', 
            'o2oOrders', 
            'addresses', 
            'coupons'
        ));
    }

    public function editProfile()
    {
        $user = Auth::user();
        return view('consumer.profile-edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email,'.$user->id,
            'phone'            => 'required|string|max:50',
            'address'          => 'nullable|string|max:255',
            'avatar'           => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:4096',
            'remove_avatar'    => 'nullable|boolean',
            'current_password' => 'nullable|required_with:password|current_password',
            'password'         => 'nullable|string|min:8|confirmed',
        ], [
            'name.required'                     => 'El nombre completo es obligatorio.',
            'email.required'                    => 'El correo electrónico es obligatorio.',
            'email.unique'                      => 'Este correo electrónico ya está registrado por otra cuenta.',
            'phone.required'                    => 'El número de teléfono móvil o WhatsApp es obligatorio.',
            'avatar.image'                      => 'El archivo de imagen no es válido.',
            'avatar.max'                        => 'La imagen de perfil no debe superar los 4 MB.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.min'                      => 'La nueva contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed'                => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        $updateData = [
            'name'    => strip_tags($data['name']),
            'email'   => strip_tags($data['email']),
            'phone'   => strip_tags($data['phone']),
            'address' => strip_tags($data['address'] ?? ''),
        ];

        // Opción de eliminar avatar
        if ($request->boolean('remove_avatar')) {
            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $updateData['avatar'] = null;
        }

        // Subida de nueva imagen de avatar
        if ($request->hasFile('avatar')) {
            if ($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $path;
        }

        // Cambio de contraseña opcional
        if (!empty($data['password'])) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $user->update($updateData);

        // Si es comerciante y tiene perfil comercial, sincronizar también el teléfono de contacto
        if ($user->isMerchant() && $user->companyProfile) {
            $user->companyProfile->update([
                'phone'   => $updateData['phone'],
                'address' => $updateData['address'] ?: $user->companyProfile->address,
            ]);
        }

        return redirect()->route('account.profile.edit')->with('success', '¡Tu perfil y datos personales se actualizaron correctamente!');
    }

    // --- DIRECTORIO Y PERFIL DE MARCA ---
    public function merchantDirectory(Request $request)
    {
        $search = $request->query('search');
        $query = CompanyProfile::where('kyc_status', 'approved')
            ->select('id', 'merchant_id', 'company_name', 'logo_path', 'description', 'address', 'phone', 'business_type');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        $merchants = $query->latest()->paginate(12)->withQueryString();
        return view('consumer.directory', compact('merchants', 'search'));
    }

    /**
     * MÉTODO CORREGIDO: Busca por merchant_id para evitar el error 404
     */
    public function merchantProfile($id)
    {
        $profile = CompanyProfile::where('merchant_id', $id)->firstOrFail();

        $products = Product::active()
            ->where('merchant_id', $profile->merchant_id)
            ->paginate(12);

        return view('consumer.merchant-profile', compact('profile', 'products'));
    }

    // --- CALIFICACIONES (REVIEWS) ---
    public function productReview(Request $request, $id)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        $product = Product::active()->findOrFail($id);
        
        $hasPurchased = Order::where('user_id', Auth::id())
            ->where('status', 'delivered')
            ->whereHas('items', function ($q) use ($id) {
                $q->where('product_id', $id);
            })->exists();

        if (!$hasPurchased) {
            abort(403, 'Debes comprar y recibir este producto para poder calificarlo.');
        }

        $review = Review::create([
            'product_id' => $product->id,
            'user_id'    => Auth::id(),
            'rating'     => $request->rating,
            'comment'    => strip_tags($request->comment ?? '')
        ]);

        $merchant = User::find($product->merchant_id);
        if ($merchant) {
            $merchant->notify(new NewProductReview($review->load('product', 'user')));
        }

        return back()->with('success', 'Gracias por tu valoración.');
    }

    public function contactSupport()
    {
        $admin = \App\Models\User::where('role', 'analyst')->first();
        if (!$admin) {
            return back()->with('error', 'No hay administradores disponibles en este momento.');
        }
        return redirect()->route('chat.show', $admin->id);
    }
}
