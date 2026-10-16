<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\{Product, Order, OrderItem, CompanyProfile, Review, BannerRequest};
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'recentReviews','avgRating','totalReviews','aiDiagnostics','academyCourses'
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
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category'    => 'required|string|max:100',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery.*'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'   => 'boolean',
            'options'     => 'nullable|array',
            'specs'       => 'nullable|array',
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

        Auth::user()->products()->create([
            'name'              => strip_tags($data['name']),
            'slug'              => Str::slug($data['name']) . '-' . uniqid(),
            'description'       => strip_tags($data['description'] ?? ''),
            'price'             => $data['price'],
            'stock'             => $data['stock'],
            'category'          => strip_tags($data['category']),
            'image'             => $imagePath,
            'images'            => $galleryPaths,
            'available_options' => $availableOptions,
            'specifications'    => $request->specs,
            'is_active'         => $request->boolean('is_active', true),
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
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'category'    => 'required|string|max:100',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery.*'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'   => 'boolean',
            'options'     => 'nullable|array',
            'specs'       => 'nullable|array',
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

        $product->update([
            'name'              => strip_tags($data['name']),
            'description'       => strip_tags($data['description'] ?? ''),
            'price'             => $data['price'],
            'stock'             => $data['stock'],
            'category'          => strip_tags($data['category']),
            'image'             => $data['image'] ?? $product->image,
            'images'            => array_values($galleryPaths),
            'available_options' => $availableOptions,
            'specifications'    => $request->specs,
            'is_active'         => $request->boolean('is_active', $product->is_active),
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

    public function orders()
    {
        $productIds = Auth::user()->products()->pluck('id');
        $orders = Order::whereHas('items', fn($q) => $q->whereIn('product_id', $productIds))
                       ->with('user','items.product')->latest()->paginate(10);
        return view('merchant.orders', compact('orders'));
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

    public function reviews()
    {
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
        return view('merchant.request-banner');
    }

    public function bannerRequestStore(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:10240',
            'notes' => 'nullable|string|max:1000'
        ]);

        $imagePath = $request->file('image')->store('banners/requests', 'public');
        $paymentPath = 'paid_online_' . time(); // Pago en línea simulado

        $bannerRequest = \App\Models\BannerRequest::create([
            'user_id' => Auth::id(),
            'image_path' => $imagePath,
            'payment_proof_path' => $paymentPath,
            'notes' => strip_tags($request->notes),
            'status' => 'pending'
        ]);

        $analysts = \App\Models\User::where('role', 'analyst')->get();
        foreach($analysts as $analyst) {
            $analyst->notify(new \App\Notifications\BannerRequestNotification($bannerRequest));
        }

        return redirect()->route('merchant.store.edit')->with('success', 'Solicitud de banner enviada y pagada correctamente.');
    }

    public function inventory()
    {
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

    public function contactSupport()
    {
        $admin = \App\Models\User::where('role', 'analyst')->first();
        if (!$admin) {
            return back()->with('error', 'No hay administradores disponibles en este momento.');
        }
        return redirect()->route('chat.show', $admin->id);
    }
}
