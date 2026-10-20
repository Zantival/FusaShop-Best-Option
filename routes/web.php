<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Consumer\ConsumerController;
use App\Http\Controllers\Merchant\MerchantController;
use App\Http\Controllers\Analyst\AnalystController;

/*
|--------------------------------------------------------------------------
| Web Routes - FusaShop
|--------------------------------------------------------------------------
*/

// Página de inicio con redirección inteligente según rol
Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isMerchant()) {
            return redirect()->route('merchant.dashboard');
        }
        if (auth()->user()->isAnalyst()) {
            return redirect()->route('analyst.dashboard');
        }
    }
    return redirect()->route('consumer.home');
});
Route::get('/privacidad', fn() => view('privacy'))->name('privacy');
Route::get('/terminos', fn() => view('terms'))->name('terms');
Route::get('/tarifas', fn() => view('fees'))->name('fees');
Route::post('/cookie-consent', function(\Illuminate\Http\Request $request) {
    $consent = $request->input('consent', true);
    session(['cookie_consent' => $consent]);
    return response()->json(['ok' => true])->withCookie(cookie()->forever('cookie_consent', $consent));
})->name('cookie.consent');


// --- Autenticación ---
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->name('register.post')->middleware('throttle:5,1');
    Route::get('/forgot-password-offline', [AuthController::class, 'showOfflinePasswordRequest'])->name('password.request.offline');
    Route::post('/forgot-password-offline', [AuthController::class, 'processOfflinePasswordRequest'])->name('password.offline.post');
    Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])->name('social.callback');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Verificación de Correo (Código OTP 6 dígitos) ---
Route::post('/email/verify-code', [\App\Http\Controllers\Auth\AuthController::class, 'verifyEmailCode'])->middleware(['auth', 'throttle:10,1'])->name('verification.verify-code');
Route::post('/email/resend-code', [\App\Http\Controllers\Auth\AuthController::class, 'resendVerificationCode'])->middleware(['auth', 'throttle:6,1'])->name('verification.resend-code');

// --- Verificación de Correo ---
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    // Flash para mostrar mensaje de bienvenida al primer ingreso tras verificar
    session()->flash('welcome_new_user', true);
    return redirect()->route('consumer.home');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');


// --- Notificaciones y Pasarela Sandbox (Compartido para todos los usuarios autenticados) ---
Route::middleware('auth')->group(function () {
    Route::get('/payment/wompi-sandbox', [\App\Http\Controllers\WompiSandboxController::class, 'show'])->name('wompi.sandbox.checkout');
    Route::post('/payment/wompi-sandbox', [\App\Http\Controllers\WompiSandboxController::class, 'complete'])->name('wompi.sandbox.complete');
    Route::get('/notifications', function (\Illuminate\Http\Request $request) {
        if ($request->wantsJson() || $request->has('json')) {
            return response()->json([
                'unread' => auth()->user()->unreadNotifications->count(),
                'notifications' => auth()->user()->unreadNotifications()->take(10)->get()
            ]);
        }
        if (auth()->check() && auth()->user()->isAnalyst()) {
            return redirect()->route('analyst.notifications');
        }
        return view('notifications.index');
    })->name('notifications.index');

    Route::get('/notifications/{id}/open', function ($id) {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $data = $notification->data ?? [];
        $type = $data['type'] ?? '';

        if ($type === 'merchant_sales_report') {
            $user->unreadNotifications
                ->filter(fn($n) => ($n->data['type'] ?? '') === 'merchant_sales_report')
                ->each->markAsRead();
            $targetUrl = $data['url'] ?? route('merchant.sales-report.show', ['user' => $user->id]);
            if (!str_contains($targetUrl, 'notification_id=')) {
                $targetUrl .= (str_contains($targetUrl, '?') ? '&' : '?') . 'notification_id=' . $notification->id;
            }
            return redirect()->to($targetUrl);
        }

        if (isset($data['sender_id']) || $notification->type === \App\Notifications\NewMessageNotification::class) {
            $senderId = $data['sender_id'] ?? null;
            if ($senderId) {
                \App\Models\Message::where('sender_id', $senderId)
                    ->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

                $user->unreadNotifications
                    ->filter(fn($n) => ($n->data['sender_id'] ?? null) == $senderId)
                    ->each->markAsRead();

                if ($user->isMerchant()) {
                    $senderUser = \App\Models\User::find($senderId);
                    if ($senderUser && $senderUser->isAnalyst()) {
                        return redirect()->route('merchant.support.contact', ['analyst' => $senderId]);
                    }
                    return redirect()->route('merchant.messages', $senderId);
                }

                return redirect()->route('chat.show', $senderId);
            }
            return $user->isMerchant()
                ? redirect()->route('merchant.messages')
                : redirect()->route('chat.index');
        }

        if ($type === 'banner_request' && isset($data['request_id'])) {
            return redirect()->route('analyst.banner-requests.show', $data['request_id']);
        }

        if ($type === 'new_merchant_kyc') {
            return redirect()->route('analyst.users', ['kyc_status' => 'pending']);
        }

        if ($type === 'kyc_decision') {
            return ($data['status'] ?? '') === 'approved'
                ? redirect()->route('merchant.dashboard')
                : redirect()->route('merchant.profile');
        }

        if (isset($data['pqrs_id'])) {
            return $user->isAnalyst()
                ? redirect()->route('analyst.pqrs.index')
                : redirect()->route('pqrs.show', $data['pqrs_id']);
        }

        if (isset($data['order_id']) || $type === 'new_order' || $type === 'order_cancelled') {
            if ($user->isMerchant()) {
                return $type === 'order_cancelled'
                    ? redirect()->route('merchant.orders', ['status' => 'cancelled'])
                    : redirect()->route('merchant.orders');
            }
            if ($user->isAnalyst()) {
                return redirect()->route('analyst.orders');
            }
            return redirect()->route('consumer.orders');
        }

        if (isset($data['product_id'])) {
            if ($user->isMerchant()) {
                return $notification->type === \App\Notifications\NewProductReview::class
                    ? redirect()->route('merchant.reviews')
                    : redirect()->route('merchant.inventory');
            }
            return redirect()->route('consumer.product', $data['product_id']);
        }

        if (!empty($data['url'])) {
            return redirect()->to($data['url']);
        }

        return redirect()->route('notifications.index');
    })->name('notifications.open');
    
    Route::post('/notifications/{id}/read', function ($id) {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back();
    })->name('notifications.read');
    
    Route::post('/notifications/read-all', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.readAll');

    // Perfil Personal (Avatar, Nombre, etc.) - Universal
    Route::get('/account/profile',      [ConsumerController::class, 'profile'])->name('account.profile');
    Route::get('/account/profile/edit', [ConsumerController::class, 'editProfile'])->name('account.profile.edit');
    Route::put('/account/profile',      [ConsumerController::class, 'updateProfile'])->name('account.profile.update');

    // Chat
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{user}', [\App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
    Route::get('/chat/{user}/poll', [\App\Http\Controllers\ChatController::class, 'poll'])->name('chat.poll');
    Route::post('/chat', [\App\Http\Controllers\ChatController::class, 'store'])->name('chat.store');
    Route::delete('/chat/message/{id}', [\App\Http\Controllers\ChatController::class, 'destroy'])->name('chat.destroy');
    
    // PQRS
    Route::get('/pqrs',          [\App\Http\Controllers\PQRSController::class, 'index'])->name('pqrs.index');
    Route::get('/pqrs/create',   [\App\Http\Controllers\PQRSController::class, 'create'])->name('pqrs.create');
    Route::post('/pqrs',         [\App\Http\Controllers\PQRSController::class, 'store'])->name('pqrs.store');
    Route::get('/pqrs/{pqrs}',   [\App\Http\Controllers\PQRSController::class, 'show'])->name('pqrs.show');

    // Reporte Individual de Ventas y Diagnóstico Causal para Comerciante / Analista
    Route::get('/reporte-ventas-comercio/{user}', [AnalystController::class, 'showMerchantSalesReport'])->name('merchant.sales-report.show');
});

// --- Cliente (Consumer) y Tienda Pública ---
// Nota: 'shop' es el prefijo. Las rutas internas no necesitan 'shop/' de nuevo.
Route::prefix('shop')->name('consumer.')->group(function () {
    Route::get('/',                    [ConsumerController::class, 'home'])->name('home');
    Route::get('/catalog',             [ConsumerController::class, 'catalog'])->name('catalog');
    Route::get('/assistant/search',    [ConsumerController::class, 'assistantSearch'])->middleware('throttle:30,1')->name('assistant.search');
    Route::get('/product/{id}',        [ConsumerController::class, 'productDetail'])->name('product');
    Route::get('/support/contact',     [ConsumerController::class, 'contactSupport'])->name('support.contact');
    Route::get('/directory',           [ConsumerController::class, 'merchantDirectory'])->name('directory');
    
    // RUTA CORREGIDA: Se asegura la estructura para evitar el error 404
    Route::get('/directory/brand/{id}', [ConsumerController::class, 'merchantProfile'])->name('merchant.profile');

    // Rutas protegidas para Clientes
    Route::middleware(['auth', 'verified', 'role:consumer'])->group(function () {
        Route::get('/cart',                [ConsumerController::class, 'cartIndex'])->name('cart');
        Route::post('/cart/select',         [ConsumerController::class, 'cartSelect'])->name('cart.select');
        Route::post('/cart/add',           [ConsumerController::class, 'cartAdd'])->name('cart.add');
        Route::patch('/cart/item/{item}',  [ConsumerController::class, 'cartUpdate'])->name('cart.update');
        Route::delete('/cart/item/{item}', [ConsumerController::class, 'cartRemove'])->name('cart.remove');
        Route::get('/checkout',            [\App\Http\Controllers\Consumer\CheckoutController::class, 'show'])->name('checkout');
        Route::post('/checkout/quote',     [\App\Http\Controllers\Consumer\CheckoutController::class, 'quote'])->middleware('throttle:20,1')->name('checkout.quote');
        Route::post('/checkout',           [\App\Http\Controllers\Consumer\CheckoutController::class, 'process'])->middleware('throttle:10,1')->name('checkout.process');
        Route::get('/checkout/result',     [\App\Http\Controllers\Consumer\CheckoutController::class, 'result'])->name('checkout.result');
        Route::get('/orders/{id}/invoice/{format}', [\App\Http\Controllers\Consumer\OrderPortalController::class, 'invoice'])->name('orders.invoice');
        Route::get('/orders/{id}/tracking', [\App\Http\Controllers\Consumer\OrderPortalController::class, 'tracking'])->name('orders.tracking');
        Route::get('/addresses',           [\App\Http\Controllers\Consumer\AddressController::class, 'index'])->name('addresses');
        Route::post('/addresses',          [\App\Http\Controllers\Consumer\AddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{id}',      [\App\Http\Controllers\Consumer\AddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{id}',   [\App\Http\Controllers\Consumer\AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::get('/orders',              [ConsumerController::class, 'orders'])->name('orders');
        Route::patch('/orders/{order}/cancel', [ConsumerController::class, 'cancelOrder'])->name('orders.cancel');
        Route::get('/orders/{id}/receipt', [ConsumerController::class, 'receipt'])->name('orders.receipt');
        Route::post('/product/{id}/review', [ConsumerController::class, 'productReview'])->name('product.review');
    });
});

// --- Comerciante (Merchant / MiPyme) ---
Route::middleware(['auth', 'verified', 'role:merchant'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/profile',         [MerchantController::class, 'onboardingProfile'])->name('profile');
    Route::post('/profile',        [MerchantController::class, 'storeProfile'])->name('profile.store');
    Route::get('/support/contact', [MerchantController::class, 'contactSupport'])->name('support.contact');

    // Rutas que requieren aprobación de documentos (KYC/RUT)
    Route::middleware([\App\Http\Middleware\EnsureKycApproved::class])->group(function() {
        Route::get('/dashboard',               [MerchantController::class, 'dashboard'])->name('dashboard');
        Route::get('/store/edit',              [MerchantController::class, 'editStore'])->name('store.edit');
        Route::get('/products',                [MerchantController::class, 'products'])->name('products');
        Route::get('/inventory',               [MerchantController::class, 'inventory'])->name('inventory');
        Route::post('/inventory',              [MerchantController::class, 'updateInventory'])->name('inventory.update');
        Route::get('/products/create',         [MerchantController::class, 'productCreate'])->name('products.create');
        Route::post('/products',               [MerchantController::class, 'productStore'])->name('products.store');
        Route::get('/products/{product}/edit', [MerchantController::class, 'productEdit'])->name('products.edit');
        Route::put('/products/{product}',      [MerchantController::class, 'productUpdate'])->name('products.update');
        Route::delete('/products/{product}',   [MerchantController::class, 'productDelete'])->name('products.delete');
        Route::get('/orders',                  [MerchantController::class, 'orders'])->name('orders');
        Route::patch('/orders/{order}/status', [MerchantController::class, 'orderUpdate'])->name('orders.update');
        Route::post('/orders/{order}/generate-label', [MerchantController::class, 'generateLabel'])->name('orders.generate-label');
        Route::get('/orders/{order}/label',           [MerchantController::class, 'printLabel'])->name('orders.label');
        Route::get('/orders/{order}/tracking',        [MerchantController::class, 'orderTracking'])->name('orders.tracking');
        Route::get('/orders/{order}/invoice/{format}',[MerchantController::class, 'orderInvoice'])->name('orders.invoice');
        Route::get('/reviews',                 [MerchantController::class, 'reviews'])->name('reviews');
        Route::post('/reviews/{review}/reply', [MerchantController::class, 'replyToReview'])->name('reviews.reply');
        Route::get('/finances',                [MerchantController::class, 'finances'])->name('finances');
        Route::get('/banner-request',          [MerchantController::class, 'bannerRequest'])->name('banner.request');
        Route::post('/banner-request',         [MerchantController::class, 'bannerRequestStore'])->name('banner.request.store');
        Route::get('/messages/{user?}',        [MerchantController::class, 'messages'])->name('messages');
        Route::get('/reports',                 [MerchantController::class, 'salesReports'])->name('reports');
        Route::get('/reports/export-csv',      [MerchantController::class, 'exportSalesReportCsv'])->name('reports.export-csv');
    });
});

// --- Administrador (Analyst) ---
Route::middleware(['auth', 'role:analyst'])->prefix('admin')->name('analyst.')->group(function () {
    Route::get('/dashboard',                      [AnalystController::class, 'dashboard'])->name('dashboard');
    Route::get('/search',                         [AnalystController::class, 'globalSearch'])->name('search');
    Route::post('/settings',                      [AnalystController::class, 'updateSettings'])->name('settings.update');
    Route::post('/how-to-start-guide',            [AnalystController::class, 'updateHowToStartGuide'])->name('how-to-start.update');
    Route::get('/audit-logs',                     [AnalystController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/notifications',                  fn() => view('notifications.index'))->name('notifications');

    Route::get('/users',                          [AnalystController::class, 'users'])->name('users');
    Route::get('/users/create',                   [AnalystController::class, 'userCreate'])->name('users.create');
    Route::post('/users',                         [AnalystController::class, 'userStore'])->name('users.store');
    Route::get('/users/{user}/edit',              [AnalystController::class, 'userEdit'])->name('users.edit');
    Route::put('/users/{user}',                   [AnalystController::class, 'userUpdate'])->name('users.update');
    Route::delete('/users/{user}',                [AnalystController::class, 'userDelete'])->name('users.delete');
    Route::post('/users/{id}/restore',            [AnalystController::class, 'userRestore'])->name('users.restore');
    Route::patch('/users/{user}/toggle-block',    [AnalystController::class, 'userToggleBlock'])->name('users.toggle-block');
    Route::patch('/users/{user}/toggle-verification', [AnalystController::class, 'userToggleVerification'])->name('users.toggle-verification');
    Route::get('/users/{user}/rut',               [AnalystController::class, 'userRut'])->name('users.rut');
    Route::post('/users/{user}/kyc',              [AnalystController::class, 'updateKyc'])->name('users.kyc');
    Route::post('/users/{user}/sector',           [AnalystController::class, 'updateMerchantSector'])->name('users.sector');

    Route::get('/orders',                         [AnalystController::class, 'orders'])->name('orders');
    Route::get('/orders/export',                  [AnalystController::class, 'ordersExportCsv'])->name('orders.export');
    Route::get('/orders/{order}/invoice/{format}',[AnalystController::class, 'orderInvoice'])->name('orders.invoice');
    Route::get('/payments',                       [AnalystController::class, 'payments'])->name('payments');

    Route::get('/integrations',                   [\App\Http\Controllers\Analyst\IntegrationController::class, 'index'])->name('integrations');
    Route::post('/integrations/retry-bulk',       [\App\Http\Controllers\Analyst\IntegrationController::class, 'retryBulk'])->name('integrations.retry-bulk');
    Route::post('/integrations/{log}/retry',      [\App\Http\Controllers\Analyst\IntegrationController::class, 'retry'])->name('integrations.retry');

    Route::get('/sales-report-print',             [AnalystController::class, 'salesReportPrint'])->name('sales-report.print');
    Route::post('/merchants/{user}/send-report',  [AnalystController::class, 'sendMerchantSalesReport'])->name('merchants.send-report');
    Route::post('/run-ml',                        [AnalystController::class, 'runMl'])->name('run-ml');
    Route::get('/reviews-report',                 [AnalystController::class, 'reviewsReport'])->name('reviews-report');

    Route::get('/banners',                        [AnalystController::class, 'banners'])->name('banners');
    Route::get('/banner-requests/{id}',           [AnalystController::class, 'bannerRequestShow'])->name('banner-requests.show');
    Route::post('/banner-requests/{id}/approve',  [AnalystController::class, 'bannerRequestApprove'])->name('banner-requests.approve');
    Route::post('/banner-requests/{id}/reject',   [AnalystController::class, 'bannerRequestReject'])->name('banner-requests.reject');
    Route::post('/banners',                       [AnalystController::class, 'bannerStore'])->name('banners.store');
    Route::post('/global-logo',                   [AnalystController::class, 'updateGlobalLogo'])->name('global.logo.store');
    Route::patch('/banners/{banner}/toggle',      [AnalystController::class, 'bannerToggle'])->name('banners.toggle');
    Route::delete('/banners/{banner}',            [AnalystController::class, 'bannerDelete'])->name('banners.delete');
    Route::post('/promotional-cards',                         [AnalystController::class, 'promotionalCardStore'])->name('promotional-cards.store');
    Route::patch('/promotional-cards/{id}/toggle',            [AnalystController::class, 'promotionalCardToggle'])->name('promotional-cards.toggle');
    Route::delete('/promotional-cards/{id}',                  [AnalystController::class, 'promotionalCardDelete'])->name('promotional-cards.delete');
    Route::post('/promotional-cards/auto-generate',           [AnalystController::class, 'promotionalCardsAutoGenerate'])->name('promotional-cards.auto-generate');
    Route::post('/promotional-cards/apply-category-discount', [AnalystController::class, 'applyCategoryDiscount'])->name('promotional-cards.apply-discount');
    
    // Gestión de PQRS
    Route::get('/pqrs',                           [\App\Http\Controllers\PQRSController::class, 'adminIndex'])->name('pqrs.index');
    Route::post('/pqrs/{pqrs}/reply',             [\App\Http\Controllers\PQRSController::class, 'adminReply'])->name('pqrs.reply');
});

// --- Servidor de Archivos Locales (Simulación de Storage público) ---
Route::get('/files/{disk}/{path}', function (string $disk, string $path) {
    $fullPath = storage_path("app/public/{$disk}/{$path}");
    if (!file_exists($fullPath)) abort(404);
    return response()->file($fullPath);
})->where('path', '.*')->name('files.serve');

Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path("app/public/{$path}");
    if (!file_exists($fullPath)) abort(404);
    return response()->file($fullPath);
})->where('path', '.*')->name('storage.serve');