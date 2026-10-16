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
        $categories = Product::active()->select('category')->distinct()->pluck('category');
        $globalBanners = GlobalBanner::where('is_active', true)->orderBy('sort_order')->get();
        return view('consumer.home', compact('featured', 'categories', 'globalBanners'));
    }

    public function catalog(Request $request)
    {
        $q = Product::active()->with('merchant.companyProfile');
        
        if ($request->filled('search'))    $q->search($request->search);
        if ($request->filled('category'))  $q->where('category', $request->category);
        if ($request->filled('min_price')) $q->where('price', '>=', $request->min_price);
        if ($request->filled('max_price')) $q->where('price', '<=', $request->max_price);
        
        $products = $q->paginate(12)->withQueryString();
        $categories = Product::active()->select('category')->distinct()->pluck('category');

        // Solo los primeros 10 para el carrusel de vendedores
        $directory = CompanyProfile::where('kyc_status', 'approved')->select('id','merchant_id','company_name','logo_path')->take(10)->get();

        return view('consumer.catalog', compact('products', 'categories', 'directory'));
    }

    public function assistantSearch(Request $request)
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:150',
        ]);

        $query = mb_strtolower($data['q'], 'UTF-8');
        $normalized = \Illuminate\Support\Str::ascii($query);
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
        if ($isProductRequest && $terms) {
            $products = Product::active()
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
                ->get(['id', 'name', 'description', 'category', 'price', 'image', 'images']);
        }

        $answer = 'Puedo ayudarte a encontrar productos y servicios locales. Cuéntame qué buscas o consulta el catálogo.';
        $links = [];

        if (preg_match('/\b(comprar|compra|pedido|pedidos|carrito|checkout)\b/', $normalized)) {
            $answer = 'Para comprar, abre el catálogo, elige un producto y agrégalo al carrito. Después confirma tu dirección y el método de pago para completar el pedido.';
            $links[] = ['label' => 'Ir al catálogo', 'url' => route('consumer.catalog')];
        } elseif (preg_match('/\b(vender|vendedor|vendedora|registrar|registrarme|mi negocio)\b/', $normalized)) {
            $answer = 'Para vender en FusaShop, crea una cuenta y selecciona el perfil de comerciante. Luego completa el perfil de tu negocio y la información solicitada para su aprobación.';
            $links[] = ['label' => 'Registrarme como vendedor', 'url' => route('register')];
        } elseif (preg_match('/\b(perfil|cuenta|nombre|correo|email|telefono|direccion|avatar|foto)\b/', $normalized)) {
            $answer = 'Puedes cambiar tu nombre, correo, teléfono, dirección y foto desde Mi Perfil. Edita los campos y guarda los cambios al final del formulario.';
            $links[] = [
                'label' => 'Abrir Mi Perfil',
                'url' => auth()->check() ? route('account.profile') : route('login'),
            ];
        } elseif (preg_match('/\b(fusacoin|coins|puntos|redimir)\b/', $normalized)) {
            $answer = 'Tus FusaCoins se acumulan con las compras elegibles. Puedes consultar tu saldo en tu perfil y aplicarlos durante el pago cuando la opción esté disponible.';
            $links[] = ['label' => 'Ver catálogo', 'url' => route('consumer.catalog')];
        } elseif (preg_match('/\b(envio|envios|entrega|entregas|despacho|guia)\b/', $normalized)) {
            $answer = 'Las opciones, tarifas y tiempos de entrega dependen del producto y de tu dirección; se muestran durante el proceso de compra.';
            $links[] = ['label' => 'Ver catálogo', 'url' => route('consumer.catalog')];
        } elseif ($isProductRequest) {
            $answer = $products->isEmpty()
                ? 'No encontré coincidencias con ese término en el catálogo. Prueba con otro nombre o categoría.'
                : 'Encontré estas opciones en el catálogo. Selecciona una para ver sus detalles:';
            $links[] = ['label' => 'Ver todo el catálogo', 'url' => route('consumer.catalog', ['search' => $data['q']])];
        }

        return response()->json([
            'answer' => $answer,
            'links' => $links,
            'products' => $products->map(function (Product $product) {
                $imagePath = collect(array_merge([$product->image], $product->images ?? []))
                    ->filter(fn ($path) => is_string($path) && Storage::disk('public')->exists($path))
                    ->first();

                return [
                    'name' => $product->name,
                    'category' => $product->category,
                    'description' => \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 100),
                    'price' => number_format((float) $product->price, 0, ',', '.'),
                    'image' => $imagePath ? url('files/' . ltrim($imagePath, '/')) : null,
                    'url' => route('consumer.product', $product->id),
                ];
            })->values(),
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
        $cart = Auth::user()->cart()->with('items.product')->firstOrCreate(['user_id' => Auth::id()]);
        return view('consumer.cart', compact('cart'));
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto agregado al carrito.',
                'cart_count' => $cart->items()->sum('quantity')
            ]);
        }

        return back()->with('success', 'Producto agregado al carrito.');
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

    // El checkout (Wompi + Envia) vive en CheckoutController.

    public function orders()
    {
        $orders = Order::where('user_id', Auth::id())->with('items.product')->latest()->paginate(10);
        return view('consumer.orders', compact('orders'));
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
    public function profile()
    {
        $user = Auth::user();
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

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,'.Auth::id(),
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'avatar'  => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        
        $updateData = [
            'name'    => strip_tags($data['name']),
            'email'   => strip_tags($data['email']),
            'phone'   => strip_tags($data['phone'] ?? ''),
            'address' => strip_tags($data['address'] ?? ''),
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $path;
        }

        $user->update($updateData);

        return redirect()->route('account.profile')->with('success', 'Perfil actualizado.');
    }

    // --- DIRECTORIO Y PERFIL DE MARCA ---
    public function merchantDirectory()
    {
        $merchants = CompanyProfile::where('kyc_status', 'approved')
            ->select('id','merchant_id','company_name','logo_path','description')
            ->paginate(12);
        return view('consumer.directory', compact('merchants'));
    }

    /**
     * MÉTODO CORREGIDO: Busca por merchant_id para evitar el error 404
     */
    public function merchantProfile($id)
    {
        $profile = CompanyProfile::where('kyc_status', 'approved')
            ->where('merchant_id', $id)
            ->firstOrFail();

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
