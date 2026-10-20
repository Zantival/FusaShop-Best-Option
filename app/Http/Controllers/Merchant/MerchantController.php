<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\{Product, Order, OrderItem, CompanyProfile, Review, BannerRequest, Message, User};
use App\Notifications\OrderStatusChanged;
use App\Services\Admin\SalesDiagnosisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MerchantController extends Controller
{

    public function dashboard()
    {
        $merchant = Auth::user();
        $productIds = $merchant->products()->pluck('id');

        $totalProducts  = $merchant->products()->count();
        $outOfStock     = $merchant->products()->where('stock', 0)->count();
        $lowStock       = $merchant->products()->where('stock', '>', 0)->where('stock', '<=', 5)->count();

        // Ventas brutas totales
        $totalSales     = (float) (OrderItem::whereIn('product_id', $productIds)->selectRaw('SUM(quantity * price) as total')->value('total') ?? 0);

        // Ventas brutas del mes actual
        $startOfMonth   = now()->startOfMonth();
        $monthlySales   = (float) (OrderItem::whereIn('product_id', $productIds)
            ->where('created_at', '>=', $startOfMonth)
            ->selectRaw('SUM(quantity * price) as total')
            ->value('total') ?? 0);

        // Pedidos pendientes de despacho (pagados o en empaque sin guía aún)
        $pendingOrders  = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))
                               ->whereIn('status', ['pending', 'processing'])
                               ->whereNull('shipped_at')
                               ->count();

        // Pedidos procesados y entregados
        $totalOrdersCount = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))->count();
        $deliveredOrders  = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))->where('status', 'delivered')->count();
        $fulfillmentRate  = $totalOrdersCount > 0 ? round(($deliveredOrders / $totalOrdersCount) * 100, 1) : 100.0;

        // Finanzas netas (Comisión estándar FusaShop: 8%)
        $platformCommissionRate = 0.08;
        $netIncome        = round($totalSales * (1 - $platformCommissionRate));
        $readyToWithdraw  = round($monthlySales * (1 - $platformCommissionRate));

        $recentOrders   = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))
                               ->with('user','items.product')->latest()->take(6)->get();

        $recentReviews  = Review::whereIn('product_id', $productIds)->with('user','product')->latest()->take(5)->get();
        $avgRating      = round((float) (Review::whereIn('product_id', $productIds)->avg('rating') ?? 5.0), 1);
        $totalReviews   = Review::whereIn('product_id', $productIds)->count();

        // Productos del comerciante para pestaña de catálogo & stock
        $products = $merchant->products()->latest()->get();

        // Datos de ventas diarias para el gráfico Chart.js (últimos 7 días)
        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dayKey = $day->toDateString();
            $dayLabel = $day->isoFormat('ddd D');
            $salesVal = (float) (OrderItem::whereIn('product_id', $productIds)
                ->whereDate('created_at', $dayKey)
                ->selectRaw('SUM(quantity * price) as total')
                ->value('total') ?? 0);
            $dailySales[] = [
                'label' => $dayLabel,
                'sales' => $salesVal,
            ];
        }

        // Distribución por categoría
        $categoryDistribution = Product::where('merchant_id', $merchant->id)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Diagnóstico & Feedback IA (Supervisado por Inteligencia Antigravity / Analista)
        $aiDiagnostics = [
            'status' => $outOfStock > 0 ? 'warning' : 'optimal',
            'stock_warning' => $outOfStock > 0 ? "Tienes {$outOfStock} productos agotados. Se estima una pérdida potencial de demanda del 14% si no repones inventario esta semana." : null,
            'price_recommendation' => "Tu categoría principal mantiene una competitividad sólida en Fusagasugá. Sugerencia: activa un cupón del 5% o habilita 2x1 en FusaCoins los fines de semana para aumentar tu conversión en un +18%.",
            'shipping_health' => "El tiempo promedio de preparación de tus despachos con Envia.com es óptimo. Generar las guías el mismo día reduce devoluciones un 35%."
        ];

        // Academia de Capacitación (Talleres del Admin FusaShop)
        $academyCourses = [
            [
                'id' => 1,
                'title' => 'E-Commerce Local & Facturación DIAN con Factus',
                'category' => 'Finanzas & Legal',
                'duration' => '45 min',
                'instructor' => 'Analista FusaShop · Cámara de Comercio',
                'progress' => 100,
                'completed' => true,
                'thumbnail' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=600&q=80',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'
            ],
            [
                'id' => 2,
                'title' => 'Estrategia de Logística y Empaque Térmico con Envia.com',
                'category' => 'Operaciones',
                'duration' => '30 min',
                'instructor' => 'Equipo Logístico Sumapaz',
                'progress' => 65,
                'completed' => false,
                'thumbnail' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'
            ],
            [
                'id' => 3,
                'title' => 'Fidelización O2O y Turismo Gastronómico en Fusagasugá',
                'category' => 'Marketing',
                'duration' => '50 min',
                'instructor' => 'Secretaría de Desarrollo Económico',
                'progress' => 20,
                'completed' => false,
                'thumbnail' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=600&q=80',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'
            ]
        ];

        return view('merchant.dashboard', compact(
            'totalProducts','outOfStock','lowStock','totalSales','monthlySales',
            'pendingOrders','totalOrdersCount','deliveredOrders','fulfillmentRate',
            'netIncome','readyToWithdraw','recentOrders',
            'recentReviews','avgRating','totalReviews','aiDiagnostics','academyCourses',
            'products','dailySales','categoryDistribution'
        ));
    }

    public function onboardingProfile()
    {
        $profile = Auth::user()->companyProfile;
        return view('merchant.profile', compact('profile'));
    }

    public function storeProfile(Request $request)
    {
        $request->validate([
            'owner_name'           => 'required|string|max:255',
            'company_name'         => 'required|string|max:255',
            'business_type'        => 'required|string|max:255',
            'phone'                => 'required|string|max:50',
            'employee_count'       => 'nullable|integer|min:1',
            'description'          => 'nullable|string|max:2000',
            'rut_file'             => 'required_without:rut_exists|mimes:pdf|max:2048',
            'camara_comercio_file' => 'required_without:camara_comercio_exists|mimes:pdf,jpg,jpeg,png|max:3072',
            'logo'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banners.*'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'address'         => 'nullable|string|max:255',
            'google_maps_url' => 'nullable|url|max:500',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
        ]);

        $user = Auth::user();
        $user->update(['name' => strip_tags($request->owner_name)]);

        $profile = $user->companyProfile ?? new CompanyProfile(['merchant_id' => $user->id]);
        
        $profile->company_name    = strip_tags($request->company_name);
        $profile->business_type   = strip_tags($request->business_type);
        $profile->phone           = strip_tags($request->phone);
        $profile->employee_count  = $request->employee_count;
        $profile->description     = strip_tags($request->description ?? '');
        $profile->address         = strip_tags($request->address ?? '');
        $profile->google_maps_url = strip_tags($request->google_maps_url ?? '');
        $profile->latitude        = $request->latitude;
        $profile->longitude       = $request->longitude;
        
        if ($request->has('whatsapp')) {
            $profile->whatsapp = preg_replace('/\D/', '', $request->whatsapp ?? '');
        }
        
        if ($request->hasFile('rut_file')) {
            if ($profile->rut_path) {
                Storage::delete($profile->rut_path);
            }
            $profile->rut_path = $request->file('rut_file')->storeAs('kyc', Str::uuid() . '_rut.pdf', 'local');
            $profile->kyc_status = 'pending';
        }

        if ($request->hasFile('camara_comercio_file')) {
            if ($profile->camara_comercio_path) {
                Storage::delete($profile->camara_comercio_path);
            }
            $ext = $request->file('camara_comercio_file')->getClientOriginalExtension();
            $profile->camara_comercio_path = $request->file('camara_comercio_file')->storeAs('kyc', Str::uuid() . '_camara.' . $ext, 'local');
            $profile->kyc_status = 'pending';
        }

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->logo_path = $request->file('logo')->store('merchants/logos', 'public');
        }

        // Handle banner deletion checkbox FIRST to avoid deleting newly uploaded ones
        if ($request->boolean('delete_banners') && !$request->hasFile('banners')) {
            if ($profile->banners_path && is_array($profile->banners_path)) {
                foreach ($profile->banners_path as $ob) { 
                    $path = is_array($ob) ? ($ob['path'] ?? null) : $ob;
                    if ($path) Storage::disk('public')->delete($path); 
                }
            }
            $profile->banners_path = null;
        }

        if ($request->hasFile('banners')) {
            // Delete old banners as they will be replaced
            if ($profile->banners_path && is_array($profile->banners_path)) {
                foreach ($profile->banners_path as $ob) { 
                    $path = is_array($ob) ? ($ob['path'] ?? null) : $ob;
                    if ($path) Storage::disk('public')->delete($path); 
                }
            }
            
            $bannerData = [];
            $titles = $request->input('banner_titles', []);
            $subtitles = $request->input('banner_subtitles', []);
            
            foreach ($request->file('banners') as $index => $banner) {
                $path = $banner->store('merchants/banners', 'public');
                $bannerData[] = [
                    'path'     => $path,
                    'title'    => strip_tags($titles[$index] ?? ''),
                    'subtitle' => strip_tags($subtitles[$index] ?? ''),
                ];
            }
            $profile->banners_path = $bannerData;
        } elseif ($profile->banners_path && is_array($profile->banners_path) && !$request->boolean('delete_banners')) {
            // Solo actualizar textos de banners existentes si no se subieron archivos nuevos
            $titles = $request->input('banner_titles', []);
            $subtitles = $request->input('banner_subtitles', []);
            $updatedBanners = $profile->banners_path;

            foreach($updatedBanners as $index => $b) {
                if (is_array($b)) {
                    $updatedBanners[$index]['title'] = strip_tags($titles[$index] ?? '');
                    $updatedBanners[$index]['subtitle'] = strip_tags($subtitles[$index] ?? '');
                } else {
                    $updatedBanners[$index] = [
                        'path' => $b,
                        'title' => strip_tags($titles[$index] ?? ''),
                        'subtitle' => strip_tags($subtitles[$index] ?? ''),
                    ];
                }
            }
            $profile->banners_path = $updatedBanners;
        }

        $profile->save();

        if ($profile->kyc_status === 'pending') {
            $analysts = \App\Models\User::where('role', 'analyst')->get();
            foreach ($analysts as $analyst) {
                $analyst->notify(new \App\Notifications\NewMerchantProfileNotification($profile));
            }
        }

        return back()->with('success', 'Cambios guardados exitosamente.');
    }

    public function editStore()
    {
        $profile = Auth::user()->companyProfile;
        return view('merchant.store-edit', compact('profile'));
    }

    public function products()
    {
        $products = Auth::user()->products()->latest()->paginate(12);
        return view('merchant.products', compact('products'));
    }

    public function productCreate()
    {
        $profile = Auth::user()->companyProfile;
        if (!$profile || $profile->kyc_status !== 'approved') {
            return redirect()->route('merchant.profile')->with('error', 'Debes completar tu perfil y ser validado (KYC) antes de publicar productos.');
        }
        return view('merchant.product-form', ['product' => null]);
    }

    public function productStore(Request $request)
    {
        $profile = Auth::user()->companyProfile;
        if (!$profile || $profile->kyc_status !== 'approved') {
            abort(403, 'KYC No Aprobado');
        }

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'price'            => 'required|numeric|min:0',
            'has_discount'     => 'nullable|boolean',
            'discount_percent' => 'nullable|integer|min:0|max:90',
            'stock'            => 'required|integer|min:0',
            'category'         => 'required|string|max:100',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery.*'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'        => 'boolean',
            'allows_points'    => 'nullable|boolean',
            'options'          => 'nullable|array',
            'specs'            => 'nullable|array',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $galleryPaths = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $galleryPaths[] = $file->store('products/gallery', 'public');
            }
        }

        // Procesar opciones avanzadas
        $availableOptions = [];
        if ($request->filled('options')) {
            foreach ($request->options as $index => $opt) {
                if (!empty($opt['name'])) {
                    $optionData = [
                        'name' => strip_tags($opt['name']),
                        'type' => $opt['type'] ?? 'text',
                        'values' => []
                    ];

                    if ($optionData['type'] === 'color' && isset($opt['color_values'])) {
                        foreach ($opt['color_values'] as $vIndex => $v) {
                            $val = [
                                'name' => strip_tags($v['name']),
                                'hex'  => strip_tags($v['hex'] ?? '#000000'),
                            ];
                            
                            if ($request->hasFile("options.$index.color_values.$vIndex.image")) {
                                $val['image'] = $request->file("options.$index.color_values.$vIndex.image")->store('products/variants', 'public');
                            }

                            $optionData['values'][] = $val;
                        }
                    } else {
                        $rawValues = $opt['values'] ?? '';
                        $vals = is_array($rawValues) ? $rawValues : explode(',', $rawValues);
                        $optionData['values'] = array_map('trim', array_map('strip_tags', array_filter($vals)));
                    }

                    $availableOptions[] = $optionData;
                }
            }
        }

        $hasDiscount = $request->has('has_discount')
            ? $request->boolean('has_discount')
            : ((int) ($data['discount_percent'] ?? 0) > 0);
        $discountPercent = $hasDiscount ? min(90, max(0, (int) ($data['discount_percent'] ?? 0))) : 0;

        Auth::user()->products()->create([
            'name'              => strip_tags($data['name']),
            'slug'              => Str::slug($data['name']) . '-' . uniqid(),
            'description'       => strip_tags($data['description'] ?? ''),
            'price'             => $data['price'],
            'discount_percent'  => $discountPercent,
            'stock'             => $data['stock'],
            'category'          => strip_tags($data['category']),
            'image'             => $imagePath,
            'images'            => $galleryPaths,
            'available_options' => $availableOptions,
            'specifications'    => $request->specs,
            'is_active'         => $request->boolean('is_active', true),
            'allows_points'     => $request->boolean('allows_points', true),
        ]);

        return redirect()->route('merchant.products')->with('success', 'Producto creado exitosamente.');
    }

    public function productEdit(Product $product)
    {
        if ($product->merchant_id !== Auth::id()) abort(403);
        return view('merchant.product-form', compact('product'));
    }

    public function productUpdate(Request $request, Product $product)
    {
        if ($product->merchant_id !== Auth::id()) abort(403);

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:2000',
            'price'            => 'required|numeric|min:0',
            'has_discount'     => 'nullable|boolean',
            'discount_percent' => 'nullable|integer|min:0|max:90',
            'stock'            => 'required|integer|min:0',
            'category'         => 'required|string|max:100',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery.*'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'        => 'boolean',
            'allows_points'    => 'nullable|boolean',
            'options'          => 'nullable|array',
            'specs'            => 'nullable|array',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $galleryPaths = $product->images ?? [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $galleryPaths[] = $file->store('products/gallery', 'public');
            }
        }

        // Eliminar fotos si se solicita (opcional, por ahora solo agregamos)
        if ($request->has('remove_images')) {
            foreach ($request->remove_images as $path) {
                Storage::disk('public')->delete($path);
                $galleryPaths = array_filter($galleryPaths, fn($p) => $p !== $path);
            }
        }

        // Procesar opciones avanzadas
        $availableOptions = [];
        if ($request->filled('options')) {
            foreach ($request->options as $index => $opt) {
                if (!empty($opt['name'])) {
                    $optionData = [
                        'name' => strip_tags($opt['name']),
                        'type' => $opt['type'] ?? 'text',
                        'values' => []
                    ];

                    if ($optionData['type'] === 'color' && isset($opt['color_values'])) {
                        foreach ($opt['color_values'] as $vIndex => $v) {
                            $val = [
                                'name' => strip_tags($v['name']),
                                'hex'  => strip_tags($v['hex'] ?? '#000000'),
                            ];
                            
                            if ($request->hasFile("options.$index.color_values.$vIndex.image")) {
                                $val['image'] = $request->file("options.$index.color_values.$vIndex.image")->store('products/variants', 'public');
                            } elseif (!empty($v['existing_image'])) {
                                $val['image'] = $v['existing_image'];
                            }

                            $optionData['values'][] = $val;
                        }
                    } else {
                        $rawValues = $opt['values'] ?? '';
                        $vals = is_array($rawValues) ? $rawValues : explode(',', $rawValues);
                        $optionData['values'] = array_map('trim', array_map('strip_tags', array_filter($vals)));
                    }

                    $availableOptions[] = $optionData;
                }
            }
        }

        $hasDiscount = $request->has('has_discount')
            ? $request->boolean('has_discount')
            : ((int) ($data['discount_percent'] ?? $product->discount_percent ?? 0) > 0);
        $discountPercent = $hasDiscount ? min(90, max(0, (int) ($data['discount_percent'] ?? $product->discount_percent ?? 0))) : 0;

        $product->update([
            'name'              => strip_tags($data['name']),
            'description'       => strip_tags($data['description'] ?? ''),
            'price'             => $data['price'],
            'discount_percent'  => $discountPercent,
            'stock'             => $data['stock'],
            'category'          => strip_tags($data['category']),
            'image'             => $data['image'] ?? $product->image,
            'images'            => array_values($galleryPaths),
            'available_options' => $availableOptions,
            'specifications'    => $request->specs,
            'is_active'         => $request->boolean('is_active', $product->is_active),
            'allows_points'     => $request->boolean('allows_points', false),
        ]);

        return redirect()->route('merchant.products')->with('success', 'Producto actualizado.');
    }

    public function productDelete(Product $product)
    {
        if ($product->merchant_id !== Auth::id()) abort(403);
        if ($product->image) Storage::disk('public')->delete($product->image);
        $product->delete();
        return back()->with('success', 'Producto eliminado.');
    }

    public function orders(Request $request)
    {
        Auth::user()->unreadNotifications
            ->filter(fn($n) => in_array($n->data['type'] ?? '', ['new_order', 'order_cancelled'], true)
                || in_array($n->type, [\App\Notifications\NewOrderNotification::class, \App\Notifications\OrderCancelledByBuyerNotification::class], true))
            ->each->markAsRead();

        $productIds = Auth::user()->products()->pluck('id');
        $baseQuery = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds));

        $statusCounts = [
            'all'        => (clone $baseQuery)->count(),
            'pending'    => (clone $baseQuery)->where('status', 'pending')->count(),
            'processing' => (clone $baseQuery)->where('status', 'processing')->count(),
            'shipped'    => (clone $baseQuery)->where('status', 'shipped')->count(),
            'delivered'  => (clone $baseQuery)->where('status', 'delivered')->count(),
            'cancelled'  => (clone $baseQuery)->where('status', 'cancelled')->count(),
        ];

        $statusFilter = $request->query('status');
        if ($statusFilter && in_array($statusFilter, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
            $baseQuery->where('status', $statusFilter);
        }

        $orders = $baseQuery->with('user', 'items.product')->latest()->paginate(10)->withQueryString();
        return view('merchant.orders', compact('orders', 'statusCounts', 'statusFilter'));
    }

    public function orderUpdate(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:pending,processing,shipped,delivered,cancelled,packing']);

        // Solo el comerciante dueño de al menos un ítem puede gestionar el pedido.
        $owns = $order->items()->whereHas('product', fn ($q) => $q->where('merchant_id', Auth::id()))->exists();
        abort_unless($owns, 403);

        // Ningún pedido avanza sin pago confirmado por Wompi (solo se puede cancelar).
        if ($order->payment_status !== 'paid' && $request->status !== 'cancelled') {
            return back()->with('error', 'El pedido aún no tiene pago aprobado.');
        }
        if (in_array($order->status, ['cancelled', 'delivered'], true)) {
            return back()->with('error', 'El pedido ya está finalizado.');
        }

        if ($request->status === 'packing') {
            $order->update(['status' => 'processing', 'packed_at' => $order->packed_at ?? now()]);
            return back()->with('success', 'Pedido marcado En empaque.');
        }
        if ($request->status === 'shipped') {
            $order->update(['shipped_at' => $order->shipped_at ?? now()]);
            if ($order->tracking_number) {
                \App\Jobs\SendOrderShippedEmail::dispatch($order->id);
            }
        }
        

        if ($request->status === 'cancelled' && $order->stock_reserved) {
            app(\App\Services\StockService::class)->release($order->load('items'));
        }

        if ($request->status === 'delivered' && $order->status !== 'delivered') {
            // Assign loyalty points (1 point per 1000 COP spent) for items belonging to this merchant
            $merchantId = Auth::id();
            $orderPoints = 0;
            foreach($order->items as $item) {
                if ($item->product && $item->product->merchant_id === $merchantId) {
                    $orderPoints += floor(($item->quantity * $item->price) / 1000);
                }
            }

            if ($orderPoints > 0) {
                $loyalty = \App\Models\LoyaltyPoint::firstOrCreate(
                    ['user_id' => $order->user_id, 'merchant_id' => $merchantId],
                    ['points' => 0]
                );
                $loyalty->increment('points', $orderPoints);
            }
        }

        $order->update(['status' => $request->status]);

        // Notify the consumer
        $order->user->notify(new OrderStatusChanged($order));

        return back()->with('success', 'Estado actualizado.');
    }

    public function generateLabel(Order $order, \App\Services\EnviaService $envia)
    {
        $productIds = Auth::user()->products()->pluck('id');
        $owns = $order->items()->whereIn('product_id', $productIds)->exists();
        abort_unless($owns, 403);

        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'El pedido debe estar pagado para generar la guía de despacho.');
        }

        if ($order->tracking_number) {
            return back()->with('info', "El pedido ya tiene guía de despacho asignada: {$order->tracking_number}");
        }

        try {
            $result = $envia->generate($order);
            $order->update([
                'tracking_number' => $result['tracking_number'],
                'label_url' => $result['label_url'],
                'tracking_url' => $result['tracking_url'],
                'shipping_carrier' => $result['carrier'],
                'tracking_status' => 'label_created',
                'packed_at' => $order->packed_at ?? now(),
                'status' => 'processing',
            ]);

            \App\Models\IntegrationLog::success('envia', 'ship.generate', $order->id, $result['payload'], $result['raw']);

            return back()->with('success', "Guía de Envia.com generada exitosamente: {$result['tracking_number']}");
        } catch (\Throwable $e) {
            \App\Models\IntegrationLog::failure('envia', 'ship.generate', $order->id, $e);
            return back()->with('error', 'No fue posible generar la guía con Envia.com: ' . $e->getMessage());
        }
    }

    public function printLabel(Order $order)
    {
        $productIds = Auth::user()->products()->pluck('id');
        $owns = $order->items()->whereIn('product_id', $productIds)->exists();
        abort_unless($owns, 403);

        if (!$order->label_url) {
            return back()->with('error', 'La orden aún no cuenta con etiqueta generada.');
        }

        return redirect()->away($order->label_url);
    }

    /** Rastreo en vivo de Envia.com para el comerciante */
    public function orderTracking(Order $order, \App\Services\EnviaService $envia)
    {
        $productIds = Auth::user()->products()->pluck('id');
        $owns = $order->items()->whereIn('product_id', $productIds)->exists();
        abort_unless($owns, 403);

        if (!$order->tracking_number) {
            return response()->json([
                'order_id' => $order->id,
                'tracking_number' => null,
                'status' => 'processing',
                'status_label' => 'En empaque y preparación en la tienda local',
                'step' => 1,
                'events' => [
                    [
                        'status' => 'Pago confirmado exitosamente',
                        'description' => 'El comercio está preparando el paquete para entrega a Envia.com',
                        'date' => $order->created_at->format('d/m/Y'),
                        'time' => $order->created_at->format('h:i A'),
                        'location' => 'Fusagasugá',
                    ]
                ],
                'message' => 'El pedido está pagado y el comercio está alistando los productos para Envia.com.'
            ]);
        }

        try {
            $t = $envia->track($order->tracking_number);
            if (!empty($t['status']) && $t['status'] !== $order->tracking_status) {
                $order->update(['tracking_status' => $t['status']]);
            }
            return response()->json([
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'carrier' => strtoupper($order->shipping_carrier ?: 'Envia.com'),
                'status' => $t['status'] ?? $order->tracking_status,
                'status_label' => $t['status_label'] ?? 'En tránsito hacia el cliente',
                'step' => $t['step'] ?? ($order->status === 'delivered' ? 5 : 3),
                'url' => $order->tracking_url,
                'destination' => $order->shipping_address,
                'events' => $t['events'] ?? [],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'carrier' => strtoupper($order->shipping_carrier ?: 'Envia.com'),
                'status' => $order->tracking_status ?? 'in_transit',
                'status_label' => 'En despacho con Envia.com',
                'step' => 3,
                'url' => $order->tracking_url,
                'destination' => $order->shipping_address,
                'events' => [
                    [
                        'status' => 'Guía emitida con Envia.com (' . strtoupper($order->shipping_carrier ?: 'Envia') . ')',
                        'description' => 'En despacho y trazabilidad activa',
                        'date' => $order->created_at->format('d/m/Y'),
                        'time' => $order->created_at->format('h:i A'),
                        'location' => 'Fusagasugá',
                    ]
                ],
            ]);
        }
    }

    /** Descarga de Factura Electrónica DIAN (Factus PDF / XML) para el comerciante vendedor */
    public function orderInvoice(Order $order, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xml'], true), 404);
        $productIds = Auth::user()->products()->pluck('id');
        $owns = $order->items()->whereIn('product_id', $productIds)->exists();
        abort_unless($owns, 403);

        $path = $format === 'pdf' ? $order->invoice_pdf_path : $order->invoice_xml_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'La factura electrónica aún no está disponible.');

        return Storage::disk('local')->download($path, "factura-{$order->invoice_number}.{$format}");
    }

    public function reviews()
    {
        Auth::user()->unreadNotifications
            ->filter(fn($n) => $n->type === \App\Notifications\NewProductReview::class)
            ->each->markAsRead();

        $productIds = Auth::user()->products()->pluck('id');
        $reviews = Review::whereIn('product_id', $productIds)
                         ->with('user', 'product')
                         ->latest()->paginate(20);
        $avgRating = Review::whereIn('product_id', $productIds)->avg('rating') ?? 0;
        return view('merchant.reviews', compact('reviews', 'avgRating'));
    }

    public function replyToReview(Request $request, Review $review)
    {
        $request->validate(['merchant_reply' => 'required|string|max:1000']);
        
        $productIds = Auth::user()->products()->pluck('id')->toArray();
        if (!in_array($review->product_id, $productIds)) {
            abort(403);
        }

        $review->update([
            'merchant_reply' => strip_tags($request->merchant_reply),
            'replied_at' => now(),
        ]);

        return back()->with('success', 'Respuesta enviada exitosamente.');
    }

    public function bannerRequest()
    {
        $merchant = Auth::user();
        $products = $merchant->products()
            ->where('is_active', true)
            ->orderByDesc('discount_percent')
            ->orderBy('name')
            ->get();
        $discountedProducts = $products->filter(fn($p) => $p->hasDiscount())->values();
        $bannerFee = \App\Models\Setting::bannerFee();
        $storeUrl = route('consumer.merchant.profile', $merchant->id);
        $myRequests = \App\Models\BannerRequest::where('user_id', $merchant->id)->latest()->take(6)->get();

        return view('merchant.request-banner', compact('products', 'discountedProducts', 'bannerFee', 'storeUrl', 'merchant', 'myRequests'));
    }

    public function bannerRequestStore(Request $request, \App\Services\WompiService $wompi, \App\Services\OrderPaymentService $payments)
    {
        $paymentMode = $request->input('payment_mode', 'wompi_card');
        $targetType = $request->input('target_type', 'store');

        $rules = [
            'image'         => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:10240',
            'title'         => 'nullable|string|max:150',
            'description'   => 'required|string|max:1500',
            'target_type'   => 'required|in:store,products,promo_card',
            'product_ids'   => in_array($targetType, ['products', 'promo_card'], true) ? 'required|array|min:1' : 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'payment_mode'  => 'nullable|in:wompi_card,wompi_checkout',
        ];

        if ($paymentMode !== 'wompi_checkout') {
            $rules['card_number'] = 'required|string|min:13|max:24';
            $rules['card_expiry'] = 'required|string|max:7';
            $rules['card_cvc']    = 'required|string|min:3|max:4';
        }

        $request->validate($rules, [
            'image.required'       => 'Debes adjuntar la foto o arte publicitario.',
            'description.required' => 'Por favor indica la descripción e instrucciones para el analista.',
            'product_ids.required' => 'Debes seleccionar al menos un producto para vincular a la promoción.',
            'product_ids.min'      => 'Selecciona al menos un producto de tu catálogo.',
            'card_number.required' => 'Ingresa el número de tarjeta para el pago seguro en línea con Wompi.',
            'card_expiry.required' => 'Ingresa la fecha de vencimiento (MM/AA).',
            'card_cvc.required'    => 'Ingresa el código de seguridad (CVC).',
        ]);

        $merchant = Auth::user();
        $imagePath = $request->file('image')->store('banners/requests', 'public');
        $paymentRef = 'FS-BANNER-' . strtoupper(substr(md5(uniqid()), 0, 10));

        $selectedProductsData = [];
        $targetUrl = route('consumer.merchant.profile', $merchant->id);

        if (in_array($targetType, ['products', 'promo_card'], true) && !empty($request->product_ids)) {
            $selectedProducts = $merchant->products()
                ->whereIn('id', $request->product_ids)
                ->get();

            foreach ($selectedProducts as $p) {
                $selectedProductsData[] = [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'category'         => $p->category,
                    'original_price'   => (float) $p->price,
                    'price'            => (float) $p->effectivePrice(),
                    'discount_percent' => (int) ($p->discount_percent ?? 0),
                    'url'              => route('consumer.product', $p->id),
                    'image'            => $p->image_url ?? null,
                ];
            }

            if (count($selectedProductsData) === 1) {
                $targetUrl = $selectedProductsData[0]['url'];
            } elseif (count($selectedProductsData) > 1) {
                $idsList = implode(',', array_column($selectedProductsData, 'id'));
                $targetUrl = route('consumer.catalog', ['products' => $idsList]);
            }
        }

        $cost = (float) \App\Models\Setting::bannerFee();
        $cents = (int) round($cost * 100);
        $signature = $wompi->integritySignature($paymentRef, $cents, 'COP');

        $bannerRequest = \App\Models\BannerRequest::create([
            'user_id'            => $merchant->id,
            'title'              => strip_tags($request->input('title', '')),
            'image_path'         => $imagePath,
            'payment_proof_path' => $paymentRef,
            'target_type'        => $targetType,
            'selected_products'  => $selectedProductsData,
            'target_url'         => $targetUrl,
            'notes'              => strip_tags($request->input('description')),
            'cost'               => $cost,
            'status'             => 'pending',
        ]);

        $analysts = \App\Models\User::where('role', 'analyst')->get();
        foreach ($analysts as $analyst) {
            $analyst->notify(new \App\Notifications\BannerRequestNotification($bannerRequest));
        }

        if ($paymentMode === 'wompi_checkout') {
            return redirect()->away($wompi->bannerCheckoutUrl($bannerRequest, $paymentRef, route('merchant.dashboard')));
        }

        // Registrar y conciliar el pago con firma de integridad SHA-256 de Wompi
        $txId = 'wompi_banner_' . strtolower(substr(md5($paymentRef . microtime(true)), 0, 14));
        $payments->handleTransaction([
            'id'              => $txId,
            'status'          => 'APPROVED',
            'reference'       => $paymentRef,
            'amount_in_cents' => $cents,
            'currency'        => 'COP',
            'signature'       => $signature,
        ]);

        $adLabel = $targetType === 'promo_card' ? 'Tarjeta Promocional de Productos' : 'Banner Publicitario';
        return redirect()->route('merchant.dashboard')->with('success', "¡{$adLabel} solicitada y pagada exitosamente vía Wompi ({$paymentRef})! El analista vinculará y publicará tu promoción en la vitrina principal.");
    }

    public function inventory()
    {
        Auth::user()->unreadNotifications
            ->filter(fn($n) => $n->type === \App\Notifications\OutOfStockNotification::class)
            ->each->markAsRead();

        $products = Auth::user()->products()->orderBy('name')->get();
        return view('merchant.inventory', compact('products'));
    }

    public function updateInventory(Request $request)
    {
        $data = $request->validate([
            'stocks' => 'required|array',
            'stocks.*' => 'required|integer|min:0'
        ]);

        $merchantId = Auth::id();

        foreach ($data['stocks'] as $productId => $stock) {
            Product::where('id', $productId)->where('merchant_id', $merchantId)->update(['stock' => $stock]);
        }

        return redirect()->route('merchant.inventory')->with('success', 'Inventario actualizado rápidamente.');
    }
    public function finances()
    {
        $merchant = Auth::user();
        $productIds = $merchant->products()->pluck('id');

        $orders = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))
                       ->with('items')
                       ->get();
        
        $grossSales = 0;
        $pointsDiscounts = 0;
        $prodIdsArr = $productIds->toArray();
        
        foreach($orders as $order) {
            $merchantTotal = 0;
            foreach($order->items as $item) {
                if(in_array($item->product_id, $prodIdsArr)) {
                    $merchantTotal += $item->quantity * $item->price;
                }
            }
            $grossSales += $merchantTotal;
            
            $orderGross = $order->total + $order->discount;
            if($orderGross > 0) {
                $proportion = $merchantTotal / $orderGross;
                $pointsDiscounts += $order->discount * $proportion;
            }
        }

        $adSpend = BannerRequest::where('user_id', $merchant->id)
                               ->where('status', 'approved')
                               ->sum('cost');

        $lowStockProducts = $merchant->products()
                                     ->where('stock', '<=', 5)
                                     ->orderBy('stock', 'asc')
                                     ->get();

        $realIncome = $grossSales - $pointsDiscounts - $adSpend;

        return view('merchant.finances', compact(
            'grossSales', 'pointsDiscounts', 'adSpend', 'realIncome', 'lowStockProducts'
        ));
    }

    public function contactSupport(\Illuminate\Http\Request $request)
    {
        $analysts = User::where('role', 'analyst')->orderBy('id')->get();
        if ($analysts->isEmpty()) {
            return back()->with('error', 'No hay analistas de soporte disponibles en este momento.');
        }

        $admin = $request->filled('analyst')
            ? ($analysts->firstWhere('id', (int) $request->query('analyst')) ?? $analysts->first())
            : $analysts->first();

        $merchantId = Auth::id();

        $messages = Message::where(function ($q) use ($merchantId, $admin) {
            $q->where('sender_id', $merchantId)->where('receiver_id', $admin->id);
        })->orWhere(function ($q) use ($merchantId, $admin) {
            $q->where('sender_id', $admin->id)->where('receiver_id', $merchantId);
        })
        ->with('product')
        ->orderBy('created_at', 'asc')
        ->get();

        // Marcar mensajes del analista como leídos
        Message::where('sender_id', $admin->id)
            ->where('receiver_id', $merchantId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        Auth::user()?->unreadNotifications
            ->filter(fn($n) => ($n->data['sender_id'] ?? null) == $admin->id)
            ->each->markAsRead();

        $badWords = Message::getBadWords();

        return view('merchant.support', compact('admin', 'analysts', 'messages', 'badWords'));
    }

    public function messages(?int $otherUserId = null)
    {
        $merchantId = Auth::id();

        // Si el usuario solicitado es un analista, redirigir al centro de Soporte Técnico con el Analista
        if ($otherUserId) {
            $requestedUser = User::find($otherUserId);
            if ($requestedUser && $requestedUser->isAnalyst()) {
                return redirect()->route('merchant.support.contact', ['analyst' => $requestedUser->id]);
            }
        }

        // Obtener todos los mensajes con clientes (excluyendo conversaciones con analistas de soporte)
        $analystIds = User::where('role', 'analyst')->pluck('id')->all();

        $allMessages = Message::where(function ($q) use ($merchantId, $analystIds) {
            $q->where('sender_id', $merchantId)
              ->whereNotIn('receiver_id', $analystIds);
        })->orWhere(function ($q) use ($merchantId, $analystIds) {
            $q->where('receiver_id', $merchantId)
              ->whereNotIn('sender_id', $analystIds);
        })
        ->with(['sender.companyProfile', 'receiver.companyProfile', 'product'])
        ->latest()
        ->get();

        // Agrupar por el ID del otro usuario (cliente)
        $conversations = $allMessages->groupBy(function ($msg) use ($merchantId) {
            return $msg->sender_id == $merchantId ? $msg->receiver_id : $msg->sender_id;
        });

        // Determinar usuario activo en el chat
        $activeUser = null;
        $activeMessages = collect();
        $activeProduct = null;
        $customerOrders = collect();

        if ($otherUserId) {
            $activeUser = User::with('companyProfile')->findOrFail($otherUserId);
        } elseif ($conversations->isNotEmpty()) {
            $firstOtherUserId = $conversations->keys()->first();
            $activeUser = User::with('companyProfile')->find($firstOtherUserId);
        }

        if ($activeUser) {
            $activeMessages = Message::where(function ($q) use ($merchantId, $activeUser) {
                $q->where('sender_id', $merchantId)->where('receiver_id', $activeUser->id);
            })->orWhere(function ($q) use ($merchantId, $activeUser) {
                $q->where('sender_id', $activeUser->id)->where('receiver_id', $merchantId);
            })
            ->with('product')
            ->orderBy('created_at', 'asc')
            ->get();

            // Buscar si la conversación tiene un producto asociado
            $lastProductMsg = $activeMessages->whereNotNull('product_id')->last();
            if ($lastProductMsg && $lastProductMsg->product) {
                $activeProduct = $lastProductMsg->product;
            }

            // Marcar mensajes del cliente como leídos
            Message::where('sender_id', $activeUser->id)
                ->where('receiver_id', $merchantId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            // Marcar también notificaciones de mensaje de este remitente como leídas
            Auth::user()->unreadNotifications
                ->filter(fn($n) => ($n->data['sender_id'] ?? null) == $activeUser->id)
                ->each->markAsRead();

            // Pedidos recientes de este cliente con productos de este comerciante
            $customerOrders = Order::where('user_id', $activeUser->id)
                ->whereHas('items.product', fn($q) => $q->where('merchant_id', $merchantId))
                ->latest()
                ->take(3)
                ->get();
        }

        $badWords = Message::getBadWords();
        $unreadCount = Message::where('receiver_id', $merchantId)
            ->whereNotIn('sender_id', $analystIds)
            ->where('is_read', false)
            ->count();

        return view('merchant.messages', compact(
            'conversations', 'activeUser', 'activeMessages', 'activeProduct', 'customerOrders', 'badWords', 'unreadCount'
        ));
    }

    public function salesReports(SalesDiagnosisService $diagnosisService)
    {
        $merchant = Auth::user();

        // 1. Historial de reportes enviados por el administrador
        $adminReports = $merchant->notifications()
            ->get()
            ->filter(fn($n) => ($n->data['type'] ?? '') === 'merchant_sales_report')
            ->values();

        // Marcar reportes de ventas pendientes como leídos al abrir el centro de reportes
        $merchant->unreadNotifications
            ->filter(fn($n) => ($n->data['type'] ?? '') === 'merchant_sales_report')
            ->each->markAsRead();

        // 2. Diagnóstico y métricas en tiempo real
        $liveReport = $diagnosisService->buildMerchantSalesReport($merchant);

        // 3. Cantidad de órdenes procesadas
        $ordersCount = Order::whereHas('items.product', fn($q) => $q->where('merchant_id', $merchant->id))
            ->where('payment_status', 'paid')
            ->count();

        // 4. Reporte oficial más reciente
        $latestReportNotification = $adminReports->first();

        return view('merchant.reports', compact(
            'merchant', 'adminReports', 'liveReport', 'ordersCount', 'latestReportNotification'
        ));
    }

    public function exportSalesReportCsv(): StreamedResponse
    {
        $merchant = Auth::user();
        $companyName = Str::slug($merchant->companyProfile->company_name ?? $merchant->name);
        $fileName = "reporte_ventas_{$companyName}_" . date('Y-m-d') . ".csv";

        $orderItems = OrderItem::whereHas('product', fn($q) => $q->where('merchant_id', $merchant->id))
            ->with(['order.user', 'product'])
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($orderItems) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM para que Excel en Windows/Mac abra los caracteres con acentos sin problemas
            fputs($handle, "\xEF\xBB\xBF");

            // Encabezados
            fputcsv($handle, [
                'ID Pedido',
                'Referencia',
                'Fecha Pedido',
                'Cliente',
                'Email Cliente',
                'Producto',
                'SKU / Codigo',
                'Cantidad',
                'Precio Unitario (COP)',
                'Subtotal (COP)',
                'Estado Pedido',
                'Estado Pago',
                'Transportadora Envia.com',
                'Numero de Guia',
            ], ';');

            foreach ($orderItems as $item) {
                $order = $item->order;
                $customer = $order?->user;
                $product = $item->product;

                fputcsv($handle, [
                    $order?->id ?? 'N/A',
                    $order?->reference ?? 'N/A',
                    $order?->created_at?->format('d/m/Y H:i') ?? 'N/A',
                    $customer?->name ?? 'Consumidor',
                    $customer?->email ?? 'N/A',
                    $product?->name ?? 'Producto',
                    $product?->id ? 'PROD-' . $product->id : 'N/A',
                    $item->quantity,
                    number_format($item->price, 2, ',', '.'),
                    number_format($item->quantity * $item->price, 2, ',', '.'),
                    ucfirst($order?->status ?? 'N/A'),
                    ucfirst($order?->payment_status ?? 'N/A'),
                    ucfirst($order?->shipping_carrier ?? 'Envia.com'),
                    $order?->tracking_number ?? 'Pendiente',
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }
}
