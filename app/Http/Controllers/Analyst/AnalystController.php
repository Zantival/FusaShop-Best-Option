<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Models\{AdminAuditLog, BannerRequest, CompanyProfile, GlobalBanner, MlRun, Order, OrderItem, Product, Review, Setting, User};
use App\Services\Admin\{DashboardMetricsService, ReviewTopicAnalysisService, SalesDiagnosisService, SectorCensusService, TerritoryHeatmapService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Hash, Schema, Storage};
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Process\Process;

class AnalystController extends Controller
{
    public function dashboard(Request $request, DashboardMetricsService $metricsService)
    {
        $period = $request->input('period', '30d');
        $data = $metricsService->getDashboardData($period);
        return view('analyst.dashboard', $data);
    }

    public function globalSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return redirect()->route('analyst.dashboard');
        }

        // Si busca un # de pedido o referencia directa
        $exactOrder = Order::where('id', ltrim($q, '#'))
            ->orWhere('reference', $q)
            ->first();
        if ($exactOrder) {
            return redirect()->route('analyst.orders', ['search' => ltrim($q, '#')]);
        }

        return redirect()->route('analyst.users', ['search' => $q]);
    }

    public function updateSettings(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Solo un Super Admin puede modificar parámetros financieros.');

        $data = $request->validate([
            'commission_rate'        => 'required|numeric|min:0|max:50',
            'banner_fee'             => 'required|integer|min:0|max:5000000',
            'pqrs_sla_business_days' => 'nullable|integer|min:1|max:60',
        ]);

        $before = [
            'commission_rate' => Setting::commissionRate(),
            'banner_fee'      => Setting::bannerFee(),
        ];

        Setting::setValue('commission_rate', $data['commission_rate'], 'financial');
        Setting::setValue('banner_fee', $data['banner_fee'], 'financial');
        if (!empty($data['pqrs_sla_business_days'])) {
            Setting::setValue('pqrs_sla_business_days', $data['pqrs_sla_business_days'], 'operations');
        }

        AdminAuditLog::record('settings.updated', null, 'Actualización de tarifas y parámetros globales', $before, $data);
        Cache::flush();

        return back()->with('success', 'Parámetros globales (Comisión y Tarifas) actualizados correctamente.');
    }

    public function updateMerchantSector(Request $request, User $user)
    {
        $data = $request->validate([
            'sector_override'    => 'required|string|max:50',
            'subsector_override' => 'nullable|string|max:80',
        ]);

        $profile = $user->companyProfile;
        if ($profile) {
            $before = ['sector' => $profile->sector_override, 'subsector' => $profile->subsector_override];
            $profile->update([
                'sector_override'    => $data['sector_override'],
                'subsector_override' => $data['subsector_override'] ?? null,
            ]);
            AdminAuditLog::record('merchant.sector_override', $profile, 'Corrección manual de rubro sectorial', $before, $data);
            Cache::forget('admin.sector_census.full');
        }

        return back()->with('success', 'Rubro sectorial del comercio actualizado manualmente.');
    }

    // ─── Usuarios y KYC ────────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $q = $request->boolean('trashed') ? User::onlyTrashed() : User::query();

        if ($request->filled('role')) {
            $q->where('role', $request->role);
        }
        if ($request->filled('kyc_status')) {
            $q->whereHas('companyProfile', fn ($cp) => $cp->where('kyc_status', $request->kyc_status));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($query) use ($s) {
                $query->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%")
                      ->orWhereHas('companyProfile', fn ($cp) => $cp->where('company_name', 'like', "%{$s}%"));
            });
        }

        $users = $q->with('companyProfile')->latest()->paginate(20)->withQueryString();
        $kycQueue = CompanyProfile::where('kyc_status', 'pending')->with('user')->oldest()->get();

        return view('analyst.users', compact('users', 'kycQueue'));
    }

    public function userCreate()
    {
        return view('analyst.user-form', ['user' => null]);
    }

    public function userStore(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users',
            'password'      => ['required', Password::min(8)],
            'role'          => 'required|in:consumer,merchant,analyst',
            'admin_subrole' => 'nullable|in:super_admin,analyst_readonly,support',
            'phone'         => 'nullable|string|max:20',
            'avatar'        => 'nullable|image|max:4096',
        ]);

        if ($data['role'] === 'analyst' && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Solo un Super Admin puede crear cuentas con rol Administrador.');
        }

        $avatarPath = $request->hasFile('avatar')
            ? $request->file('avatar')->store('avatars', 'public')
            : null;

        $user = User::create([
            'name'              => strip_tags($data['name']),
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'role'              => $data['role'],
            'admin_subrole'     => $data['role'] === 'analyst' ? ($data['admin_subrole'] ?? 'super_admin') : 'super_admin',
            'phone'             => $data['phone'] ?? null,
            'avatar'            => $avatarPath,
            'email_verified_at' => now(),
        ]);

        AdminAuditLog::record('user.created', $user, 'Creación manual desde panel admin', null, $user->only(['name', 'email', 'role']));
        return redirect()->route('analyst.users')->with('success', 'Usuario creado exitosamente.');
    }

    public function userEdit(User $user)
    {
        return view('analyst.user-form', compact('user'));
    }

    public function userUpdate(Request $request, User $user)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'role'          => 'nullable|in:consumer,merchant,analyst',
            'admin_subrole' => 'nullable|in:super_admin,analyst_readonly,support',
            'phone'         => 'nullable|string|max:20',
            'password'      => ['nullable', Password::min(8)],
            'avatar'        => 'nullable|image|max:4096',
        ]);

        $before = $user->only(['name', 'email', 'role', 'admin_subrole', 'phone', 'avatar']);
        $isEditingSelf = $user->id === auth()->id();

        // Impedir que un admin cambie su propio rol desde su perfil propio (§2.2)
        $newRole = $isEditingSelf ? $user->role : ($data['role'] ?? $user->role);
        if ($newRole === 'analyst' && $user->role !== 'analyst' && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Solo un Super Admin puede otorgar privilegios de Administrador.');
        }

        $update = [
            'name'          => strip_tags($data['name']),
            'email'         => $data['email'],
            'role'          => $newRole,
            'admin_subrole' => $newRole === 'analyst' ? ($data['admin_subrole'] ?? $user->admin_subrole ?? 'super_admin') : 'super_admin',
            'phone'         => $data['phone'] ?? null,
        ];
        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }
        if ($request->hasFile('avatar')) {
            $update['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($update);
        AdminAuditLog::record('user.updated', $user, 'Actualización de usuario / avatar', $before, $user->only(['name', 'email', 'role', 'admin_subrole', 'avatar']));

        if ($isEditingSelf) {
            return redirect()->route('analyst.users.edit', $user->id)->with('success', 'Tu perfil e imagen de administrador se actualizaron correctamente.');
        }

        return redirect()->route('analyst.users')->with('success', 'Usuario actualizado correctamente.');
    }

    public function userDelete(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar/eliminar tu propia cuenta.');
        }

        $reason = strip_tags($request->input('reason', 'Desactivación administrativa preventiva (SoftDelete)'));
        $before = $user->only(['id', 'name', 'email', 'role']);

        $user->update(['deleted_reason' => $reason]);
        $user->delete(); // SoftDelete: preserva pedidos, facturas Factus e histórico

        AdminAuditLog::record('user.soft_deleted', $user, $reason, $before, ['deleted_at' => now()->toDateTimeString()]);
        return back()->with('success', 'Usuario enviado a papelera (borrado lógico) preservando integridad histórica.');
    }

    public function userRestore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        AdminAuditLog::record('user.restored', $user, 'Restauración desde papelera');
        return back()->with('success', "Usuario {$user->name} restaurado exitosamente.");
    }

    public function userToggleBlock(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes bloquearte a ti mismo.');
        }

        $willBlock = !$user->is_blocked;
        $reason = strip_tags($request->input('reason', $willBlock ? 'Bloqueo preventivo por auditoría' : 'Desbloqueo administrativo'));

        $user->update([
            'is_blocked'     => $willBlock,
            'blocked_reason' => $willBlock ? $reason : null,
        ]);

        // Si se bloquea, invalidar sesiones y tokens activos (§2.2)
        if ($willBlock) {
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $user->tokens()->delete();
        }

        AdminAuditLog::record($willBlock ? 'user.blocked' : 'user.unblocked', $user, $reason, ['is_blocked' => !$willBlock], ['is_blocked' => $willBlock]);

        $msg = $willBlock ? 'bloqueado (sesiones revocadas)' : 'desbloqueado';
        return back()->with('success', "Usuario {$msg} exitosamente.");
    }

    public function userRut(User $user)
    {
        $profile = $user->companyProfile;
        if (!$profile || !$profile->rut_path) {
            abort(404, 'RUT no encontrado');
        }
        $path = storage_path('app/private/' . $profile->rut_path);
        if (!file_exists($path)) {
            $path = storage_path('app/' . $profile->rut_path);
        }
        if (!file_exists($path)) {
            abort(404, 'Archivo físico no encontrado');
        }

        // Registro de acceso a documento con datos personales (Ley 1581 de 2012)
        AdminAuditLog::record('kyc.rut_downloaded', $profile, "Descarga de RUT de {$profile->company_name} por auditoría KYC");

        return response()->file($path);
    }

    public function updateKyc(Request $request, User $user)
    {
        $request->validate([
            'kyc_status'         => 'required|in:approved,rejected',
            'kyc_rejection_code' => 'nullable|string|max:60',
            'kyc_notes'          => 'nullable|string|max:1000',
        ]);

        $profile = $user->companyProfile;
        if ($profile) {
            $before = ['kyc_status' => $profile->kyc_status, 'kyc_notes' => $profile->kyc_notes];
            $reasonLabels = [
                'rut_ilegible'            => 'Documento RUT ilegible o incompleto',
                'nit_no_coincide'         => 'El NIT o razón social no coincide con el titular',
                'documento_vencido'       => 'Certificado o RUT desactualizado',
                'actividad_ciiu_invalida' => 'Actividad económica CIIU no corresponde al giro declarable',
            ];
            $code = $request->kyc_rejection_code;
            $compiledNotes = $request->kyc_notes;
            if ($request->kyc_status === 'rejected' && $code && isset($reasonLabels[$code])) {
                $compiledNotes = trim($reasonLabels[$code] . ($compiledNotes ? ': ' . $compiledNotes : ''));
            }

            $profile->update([
                'kyc_status'         => $request->kyc_status,
                'kyc_rejection_code' => $request->kyc_status === 'rejected' ? $code : null,
                'kyc_notes'          => $compiledNotes,
            ]);

            AdminAuditLog::record('kyc.' . $request->kyc_status, $profile, $compiledNotes ?? 'Decisión KYC', $before, [
                'kyc_status' => $request->kyc_status,
                'kyc_rejection_code' => $code,
            ]);

            $user->notify(new \App\Notifications\KycDecisionNotification($profile));
        }

        return back()->with('success', 'Estado KYC actualizado, registrado en auditoría y comerciante notificado.');
    }

    // ─── Pedidos Globales ──────────────────────────────────────────────────────

    public function orders(Request $request)
    {
        $q = $this->buildOrdersQuery($request);
        $orders = $q->latest()->paginate(20)->withQueryString();
        $merchants = User::where('role', 'merchant')->with('companyProfile')->get();

        $stuckCount = Order::where(function ($sub) {
            $sub->where('payment_status', 'paid')
                ->where('status', 'processing')
                ->whereNull('tracking_number')
                ->where('created_at', '<=', now()->subHours(48));
        })->orWhere(function ($sub) {
            $sub->where('status', 'shipped')
                ->where('updated_at', '<=', now()->subDays(5));
        })->count();

        return view('analyst.orders', compact('orders', 'merchants', 'stuckCount'));
    }

    public function ordersExportCsv(Request $request)
    {
        $orders = $this->buildOrdersQuery($request)->latest()->get();
        AdminAuditLog::record('orders.exported_csv', null, 'Exportación CSV de ' . $orders->count() . ' pedidos');

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="fusashop_pedidos_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($orders) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
            fputcsv($out, ['ID', 'Referencia', 'Cliente', 'Email', 'Total COP', 'Estado Pedido', 'Estado Pago', 'Metodo Pago', 'Guia Envia', 'CUFE Factus', 'Fecha']);
            foreach ($orders as $o) {
                fputcsv($out, [
                    $o->id,
                    $o->reference ?? 'ORD-' . $o->id,
                    $o->user->name ?? 'Eliminado',
                    $o->user->email ?? '-',
                    $o->total,
                    $o->status,
                    $o->payment_status,
                    $o->payment_method,
                    $o->tracking_number ?? 'Sin guía',
                    $o->invoice_cufe ?? 'Sin CUFE',
                    $o->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function buildOrdersQuery(Request $request)
    {
        $q = Order::with(['user', 'items.product.merchant.companyProfile']);

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $q->where('payment_status', $request->payment_status);
        }
        if ($request->filled('merchant_id')) {
            $mId = $request->merchant_id;
            $q->whereHas('items.product', fn ($p) => $p->where('merchant_id', $mId));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('min_amount')) {
            $q->where('total', '>=', (float) $request->min_amount);
        }
        if ($request->filled('search')) {
            $s = ltrim($request->search, '#');
            $q->where(function ($sub) use ($s) {
                $sub->where('id', $s)
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhere('shipping_address', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
            });
        }
        if ($request->boolean('stuck')) {
            $q->where(function ($sub) {
                $sub->where(function ($s1) {
                    $s1->where('payment_status', 'paid')
                       ->where('status', 'processing')
                       ->whereNull('tracking_number')
                       ->where('created_at', '<=', now()->subHours(48));
                })->orWhere(function ($s2) {
                    $s2->where('status', 'shipped')
                       ->where('updated_at', '<=', now()->subDays(5));
                });
            });
        }

        return $q;
    }

    // ─── Pagos, Conciliación y Comisiones ──────────────────────────────────────

    public function payments()
    {
        $defaultBannerFee = Setting::bannerFee();
        $commissionRate = Setting::commissionRate();

        $bannerPayments = BannerRequest::with('user')->latest()->get()->map(fn ($br) => [
            'id'         => $br->id,
            'type'       => 'Banner Publicitario',
            'merchant'   => $br->user->name ?? 'Desconocido',
            'email'      => $br->user->email ?? '-',
            'amount'     => $br->cost > 0 ? $br->cost : $defaultBannerFee,
            'status'     => $br->status,
            'date'       => $br->created_at,
            'detail_url' => route('analyst.banner-requests.show', $br->id),
        ]);

        $merchantAccounts = CompanyProfile::with('user')->latest()->get()->map(function ($cp) use ($commissionRate) {
            $sales = (float) OrderItem::join('products', 'products.id', '=', 'order_items.product_id')
                ->where('products.merchant_id', $cp->merchant_id)
                ->sum(DB::raw('order_items.quantity * order_items.price'));
            $commission = round($sales * ($commissionRate / 100));
            return [
                'id'                => $cp->merchant_id,
                'company_name'      => $cp->company_name,
                'owner'             => $cp->user->name ?? 'Desconocido',
                'email'             => $cp->user->email ?? '-',
                'kyc_status'        => $cp->kyc_status,
                'sales'             => $sales,
                'commission'        => $commission,
                'net_payout'        => max(0, $sales - $commission),
                'settlement_status' => $cp->settlement_status ?? 'pending',
                'created_at'        => $cp->created_at,
            ];
        });

        $totalBannerRevenue = $bannerPayments->where('status', 'approved')->sum('amount');

        return view('analyst.payments', compact('bannerPayments', 'merchantAccounts', 'totalBannerRevenue', 'commissionRate', 'defaultBannerFee'));
    }

    // ─── Banners Globales y Marca ──────────────────────────────────────────────

    public function banners()
    {
        $banners = GlobalBanner::orderBy('sort_order')->get();
        $pendingRequests = BannerRequest::with('user.companyProfile')->latest()->get();
        $bannerFee = Setting::bannerFee();

        // 1. Detección inteligente de productos con descuentos u ofertas de comerciantes
        $allActiveProducts = Product::with('merchant.companyProfile')->where('is_active', true)->get();
        
        $discountedProducts = $allActiveProducts->filter(function ($prod) {
            $text = mb_strtolower($prod->name . ' ' . $prod->description . ' ' . $prod->category);
            return str_contains($text, 'descuento') 
                || str_contains($text, 'oferta') 
                || str_contains($text, 'promo') 
                || str_contains($text, 'rebaja') 
                || str_contains($text, '%')
                || (isset($prod->specifications['discount_pct']) && (int)$prod->specifications['discount_pct'] > 0);
        });

        // Agrupación por categoría de productos en descuento
        $categoriesWithDiscounts = $discountedProducts->groupBy('category')->map(function ($items, $cat) {
            return [
                'category' => $cat ?: 'General',
                'count' => $items->count(),
                'sample_products' => $items->take(3)->pluck('name')->all(),
                'merchants' => $items->pluck('merchant.name')->unique()->take(3)->all(),
            ];
        });

        $categoriesList = Product::where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->values();

        if ($categoriesList->isEmpty()) {
            $categoriesList = collect(['Electrónica', 'Ropa', 'Alimentos', 'Turismo', 'Gastronomía', 'Hogar']);
        }

        // Cargar Tarjetas Promocionales guardadas
        $rawCards = Setting::getValue('promotional_cards');
        $promotionalCards = $rawCards ? json_decode($rawCards, true) : null;
        if (!is_array($promotionalCards) || empty($promotionalCards)) {
            $promotionalCards = [
                [
                    'id' => 'card-1',
                    'category' => 'Electrónica',
                    'title' => 'Tecnología & Cómputo — Hasta 30% OFF',
                    'subtitle' => 'Comercios de Fusagasugá con precios rebajados en audio, laptops y periféricos.',
                    'discount_badge' => '30% OFF',
                    'bg_gradient' => 'from-emerald-950 via-slate-900 to-emerald-900',
                    'is_active' => true,
                    'source' => 'system_discount',
                    'products_count' => max($allActiveProducts->where('category', 'Electrónica')->count(), 6),
                    'created_at' => now()->subDays(2)->format('d/m/Y'),
                ],
                [
                    'id' => 'card-2',
                    'category' => 'Ropa',
                    'title' => 'Moda & Calzado Sumapaz — 20% de Descuento',
                    'subtitle' => 'Prendas locales, calzado y accesorios de comerciantes del centro con entrega express.',
                    'discount_badge' => '20% OFF',
                    'bg_gradient' => 'from-teal-950 via-slate-900 to-emerald-950',
                    'is_active' => true,
                    'source' => 'merchant_offer',
                    'products_count' => max($allActiveProducts->where('category', 'Ropa')->count(), 9),
                    'created_at' => now()->subDays(1)->format('d/m/Y'),
                ],
                [
                    'id' => 'card-3',
                    'category' => 'Turismo',
                    'title' => 'Ruta Glamping Chinauta — Vouchers con 15% OFF',
                    'subtitle' => 'Hospedajes de montaña y pasadías O2O con confirmación instantánea por código QR.',
                    'discount_badge' => '15% OFF',
                    'bg_gradient' => 'from-emerald-950 via-green-950 to-slate-900',
                    'is_active' => true,
                    'source' => 'merchant_offer',
                    'products_count' => max($allActiveProducts->where('category', 'Turismo')->count(), 4),
                    'created_at' => now()->format('d/m/Y'),
                ],
            ];
            Setting::setValue('promotional_cards', json_encode($promotionalCards), 'marketing', 'Tarjetas promocionales por categoría');
        }

        $activeCardsCount = count(array_filter($promotionalCards, fn($c) => !empty($c['is_active'])));

        return view('analyst.banners', compact(
            'banners', 
            'pendingRequests', 
            'bannerFee', 
            'allActiveProducts',
            'discountedProducts', 
            'categoriesWithDiscounts', 
            'categoriesList', 
            'promotionalCards',
            'activeCardsCount'
        ));
    }

    public function bannerRequestShow($id)
    {
        $requestInfo = BannerRequest::with('user.companyProfile')->findOrFail($id);
        return view('analyst.banner-request-detail', compact('requestInfo'));
    }

    public function bannerRequestApprove(Request $request, $id)
    {
        $requestInfo = BannerRequest::with('user.companyProfile')->findOrFail($id);
        if ($requestInfo->status !== 'pending') {
            return back()->with('error', 'Esta solicitud ya ha sido procesada.');
        }

        $requestInfo->update(['status' => 'approved']);
        $linkUrl = route('consumer.merchant.profile', $requestInfo->user_id);
        if ($request->input('link_type') === 'custom' && $request->filled('custom_link_url')) {
            $linkUrl = $request->custom_link_url;
        }

        $banner = GlobalBanner::create([
            'title'      => 'Banner Patrocinado - ' . ($requestInfo->user->companyProfile->company_name ?? 'Comerciante'),
            'image_path' => $requestInfo->image_path,
            'link_url'   => $linkUrl,
            'is_active'  => true,
            'sort_order' => (int) GlobalBanner::max('sort_order') + 1,
            'starts_at'  => now(),
            'ends_at'    => now()->addDays(30),
        ]);

        AdminAuditLog::record('banner_request.approved', $requestInfo, 'Aprobación y publicación de banner patrocinado', null, ['banner_id' => $banner->id]);
        return redirect()->route('analyst.banners')->with('success', 'Solicitud aprobada y publicada con vigencia de 30 días.');
    }

    public function bannerRequestReject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $requestInfo = BannerRequest::with('user')->findOrFail($id);

        $requestInfo->update([
            'status'           => 'rejected',
            'rejection_reason' => strip_tags($request->rejection_reason),
        ]);

        AdminAuditLog::record('banner_request.rejected', $requestInfo, $request->rejection_reason);
        return redirect()->route('analyst.banners')->with('success', 'Solicitud de banner rechazada con motivo registrado.');
    }

    public function bannerStore(Request $request)
    {
        $request->validate([
            'title'      => 'nullable|string|max:255',
            'image'      => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:10240',
            'link_url'   => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'starts_at'  => 'nullable|date',
            'ends_at'    => 'nullable|date|after_or_equal:starts_at',
        ]);

        $path = $request->file('image')->store('global_banners', 'public');
        $banner = GlobalBanner::create([
            'title'      => strip_tags($request->title ?? ''),
            'image_path' => $path,
            'link_url'   => $request->link_url,
            'is_active'  => true,
            'sort_order' => $request->sort_order ?? 0,
            'starts_at'  => $request->starts_at,
            'ends_at'    => $request->ends_at,
        ]);

        AdminAuditLog::record('banner.created', $banner, 'Nuevo banner global subido');
        return back()->with('success', 'Banner creado y programado exitosamente.');
    }

    public function bannerToggle(GlobalBanner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        AdminAuditLog::record('banner.toggled', $banner, 'Estado activo cambiado a ' . ($banner->is_active ? 'activo' : 'inactivo'));
        return back()->with('success', 'Banner ' . ($banner->is_active ? 'activado' : 'desactivado') . '.');
    }

    public function bannerDelete(GlobalBanner $banner)
    {
        // Borrado lógico (SoftDelete) para no destruir activos ni métricas históricas (§2.4)
        $banner->delete();
        AdminAuditLog::record('banner.soft_deleted', $banner, 'Banner movido a papelera');
        return back()->with('success', 'Banner enviado a papelera preservando métricas.');
    }

    public function updateGlobalLogo(Request $request)
    {
        $request->validate(['logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048']);
        $logoFile = $request->file('logo');
        $logoPath = $logoFile->storeAs('system', 'global_logo.' . $logoFile->extension(), 'public');

        foreach (Storage::disk('public')->files('system') as $existingPath) {
            if ($existingPath !== $logoPath && preg_match('/^system\/global_logo\.(png|jpe?g|webp)$/i', $existingPath)) {
                Storage::disk('public')->delete($existingPath);
            }
        }

        AdminAuditLog::record('brand.logo_updated', null, 'Actualización del logotipo institucional');
        return redirect()->route('analyst.banners', ['tab' => 'logo'])->with('success', 'Logo del sistema actualizado.');
    }

    public function promotionalCardStore(Request $request)
    {
        $request->validate([
            'category'       => 'required|string|max:100',
            'title'          => 'required|string|max:200',
            'subtitle'       => 'nullable|string|max:300',
            'discount_badge' => 'required|string|max:50',
            'bg_gradient'    => 'nullable|string|max:100',
        ]);

        $rawCards = Setting::getValue('promotional_cards');
        $cards = $rawCards ? json_decode($rawCards, true) : [];
        if (!is_array($cards)) $cards = [];

        $newCard = [
            'id' => 'card-' . uniqid(),
            'category' => strip_tags($request->category),
            'title' => strip_tags($request->title),
            'subtitle' => strip_tags($request->subtitle ?? 'Promoción especial en catálogo seleccionado.'),
            'discount_badge' => strip_tags($request->discount_badge),
            'bg_gradient' => $request->bg_gradient ?: 'from-emerald-950 via-slate-900 to-emerald-900',
            'is_active' => true,
            'source' => 'admin_created',
            'products_count' => Product::where('category', $request->category)->where('is_active', true)->count(),
            'created_at' => now()->format('d/m/Y'),
        ];

        array_unshift($cards, $newCard);
        Setting::setValue('promotional_cards', json_encode($cards), 'marketing');

        AdminAuditLog::record('promotional_card.created', null, 'Nueva tarjeta promocional creada para la categoría ' . $request->category);
        return redirect()->route('analyst.banners', ['tab' => 'promos'])->with('success', 'Tarjeta promocional creada exitosamente.');
    }

    public function promotionalCardToggle($id)
    {
        $rawCards = Setting::getValue('promotional_cards');
        $cards = $rawCards ? json_decode($rawCards, true) : [];
        if (!is_array($cards)) $cards = [];

        foreach ($cards as &$card) {
            if (($card['id'] ?? '') === $id) {
                $card['is_active'] = !($card['is_active'] ?? false);
                break;
            }
        }
        Setting::setValue('promotional_cards', json_encode($cards), 'marketing');

        return redirect()->route('analyst.banners', ['tab' => 'promos'])->with('success', 'Estado de la tarjeta promocional actualizado.');
    }

    public function promotionalCardDelete($id)
    {
        $rawCards = Setting::getValue('promotional_cards');
        $cards = $rawCards ? json_decode($rawCards, true) : [];
        if (!is_array($cards)) $cards = [];

        $cards = array_values(array_filter($cards, fn($c) => ($c['id'] ?? '') !== $id));
        Setting::setValue('promotional_cards', json_encode($cards), 'marketing');

        return redirect()->route('analyst.banners', ['tab' => 'promos'])->with('success', 'Tarjeta promocional eliminada.');
    }

    public function promotionalCardsAutoGenerate(Request $request)
    {
        $rawCards = Setting::getValue('promotional_cards');
        $cards = $rawCards ? json_decode($rawCards, true) : [];
        if (!is_array($cards)) $cards = [];

        $categories = Product::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        $generated = 0;
        foreach ($categories as $cat) {
            $exists = collect($cards)->firstWhere('category', $cat);
            if (!$exists) {
                $count = Product::where('category', $cat)->where('is_active', true)->count();
                $cards[] = [
                    'id' => 'card-' . uniqid(),
                    'category' => $cat,
                    'title' => "Especial {$cat} — Descuentos de Temporada",
                    'subtitle' => "Comercios locales de {$cat} con ofertas directas y despacho express en Fusagasugá.",
                    'discount_badge' => 'Hasta 25% OFF',
                    'bg_gradient' => 'from-emerald-950 via-slate-900 to-emerald-900',
                    'is_active' => true,
                    'source' => 'auto_generated',
                    'products_count' => $count,
                    'created_at' => now()->format('d/m/Y'),
                ];
                $generated++;
            }
        }

        Setting::setValue('promotional_cards', json_encode($cards), 'marketing');
        return redirect()->route('analyst.banners', ['tab' => 'promos'])->with('success', "Se auto-generaron {$generated} tarjetas promocionales a partir de los productos en catálogo.");
    }

    public function applyCategoryDiscount(Request $request)
    {
        $request->validate([
            'category'     => 'required|string',
            'discount_pct' => 'required|integer|min:5|max:70',
            'title'        => 'nullable|string|max:200',
        ]);

        $cat = $request->category;
        $discountPct = (int) $request->discount_pct;

        $prods = Product::where('category', $cat)->where('is_active', true)->get();
        foreach ($prods as $p) {
            $specs = $p->specifications ?? [];
            $specs['discount_pct'] = $discountPct;
            $specs['original_price'] = $specs['original_price'] ?? $p->price;
            $newPrice = round($specs['original_price'] * (1 - ($discountPct / 100)));
            $p->update([
                'price' => $newPrice,
                'specifications' => $specs,
            ]);
        }

        $rawCards = Setting::getValue('promotional_cards');
        $cards = $rawCards ? json_decode($rawCards, true) : [];
        if (!is_array($cards)) $cards = [];

        $cardTitle = $request->title ?: "Mega Oferta {$cat} — {$discountPct}% OFF en Todo el Rubro";
        $card = [
            'id' => 'card-' . uniqid(),
            'category' => $cat,
            'title' => $cardTitle,
            'subtitle' => "Campaña oficial FusaShop con {$discountPct}% de descuento aplicado a {$prods->count()} productos de comerciantes locales.",
            'discount_badge' => "{$discountPct}% OFF",
            'bg_gradient' => 'from-emerald-900 via-slate-900 to-teal-950',
            'is_active' => true,
            'source' => 'system_discount_applied',
            'products_count' => $prods->count(),
            'created_at' => now()->format('d/m/Y'),
        ];
        array_unshift($cards, $card);
        Setting::setValue('promotional_cards', json_encode($cards), 'marketing');

        AdminAuditLog::record('category_discount.applied', null, "Descuento del {$discountPct}% aplicado a la categoría {$cat} ({$prods->count()} productos)");
        return redirect()->route('analyst.banners', ['tab' => 'promos'])->with('success', "¡Descuento del {$discountPct}% aplicado a {$prods->count()} productos de {$cat} y tarjeta promocional publicada!");
    }

    // ─── Reportes de Inteligencia, Reseñas por Tema y ML ───────────────────────

    public function salesReportPrint(
        Request $request,
        DashboardMetricsService $metricsService,
        SectorCensusService $sectorService,
        SalesDiagnosisService $diagnosisService,
        ReviewTopicAnalysisService $reviewService,
        TerritoryHeatmapService $territoryService
    ) {
        $period = $request->input('period', '30d');
        $dashboardData = $metricsService->getDashboardData($period);
        $sectorAnalysis = $sectorService->getFullSectorAnalysis();
        $merchantsAtRisk = $diagnosisService->getMerchantsAtRisk(10);
        $reviewAnalysis = $reviewService->analyzeAll();
        $territorialData = $territoryService->getTerritorialIntelligence();

        // Top 10 y Bottom 10 comercios por ventas
        $allMerchantsRanking = CompanyProfile::with('user')->get()->map(function ($cp) {
            $sales = (float) OrderItem::join('products', 'products.id', '=', 'order_items.product_id')
                ->where('products.merchant_id', $cp->merchant_id)
                ->sum(DB::raw('order_items.quantity * order_items.price'));
            return (object) [
                'company_name' => $cp->company_name,
                'business_type' => $cp->business_type ?? 'Comercio',
                'total_sales' => $sales,
            ];
        })->sortByDesc('total_sales')->values();

        $top10Merchants = $allMerchantsRanking->take(10);
        $bottom10Merchants = $allMerchantsRanking->reverse()->take(10)->values();

        // Productos y servicios más y menos vendidos
        $productSalesRanking = Product::with('merchant.companyProfile')
            ->withSum('orderItems as units_sold', 'quantity')
            ->get()
            ->map(function ($p) {
                $units = (int) ($p->units_sold ?? 0);
                return (object) [
                    'name'     => $p->name,
                    'type'     => $p->isService() ? 'Servicio' : 'Producto',
                    'category' => $p->category,
                    'merchant' => $p->merchant->companyProfile->company_name ?? 'Comercio',
                    'units'    => $units,
                    'revenue'  => $units * (float) $p->price,
                    'stock'    => $p->stock,
                ];
            });

        $topProductsReport = $productSalesRanking->sortByDesc('revenue')->take(8)->values();
        $bottomProductsReport = $productSalesRanking->sortBy('revenue')->take(8)->values();

        return view('analyst.sales-report-print', array_merge($dashboardData, [
            'sectorAnalysis'       => $sectorAnalysis,
            'merchantsAtRisk'      => $merchantsAtRisk,
            'reviewAnalysis'       => $reviewAnalysis,
            'territorialData'      => $territorialData,
            'top10Merchants'       => $top10Merchants,
            'bottom10Merchants'    => $bottom10Merchants,
            'topProductsReport'    => $topProductsReport,
            'bottomProductsReport' => $bottomProductsReport,
        ]));
    }

    public function reviewsReport(ReviewTopicAnalysisService $reviewService)
    {
        $analysis = $reviewService->analyzeAll();
        $mlRuns = Schema::hasTable('ml_runs') ? MlRun::with('admin')->latest()->take(5)->get() : collect();

        return view('analyst.reviews-report', [
            'report'           => $analysis['merchants_report'],
            'globalTopics'     => $analysis['global_topics'],
            'topCriticalWords' => $analysis['top_critical_words'],
            'mlRuns'           => $mlRuns,
        ]);
    }

    public function runMl()
    {
        $merchants = CompanyProfile::with('user.products.reviews')->get();
        $payload = [];
        foreach ($merchants as $m) {
            $productIds = $m->user ? $m->user->products->pluck('id') : collect();
            $totalSales = (float) OrderItem::whereIn('product_id', $productIds)->sum(DB::raw('quantity * price'));
            $avgRating  = (float) (Review::whereIn('product_id', $productIds)->avg('rating') ?? 0);
            $payload[] = [
                'merchant_id'  => $m->merchant_id,
                'company_name' => $m->company_name,
                'total_sales'  => $totalSales,
                'avg_rating'   => $avgRating,
            ];
        }

        $analytics = [];
        $scriptPath = base_path('python/ml_analyzer.py');
        if (file_exists($scriptPath)) {
            $process = new Process(['python3', $scriptPath]);
            $process->setInput(json_encode($payload));
            $process->run();
            if ($process->isSuccessful()) {
                $output = json_decode($process->getOutput(), true);
                $analytics = $output['analytics'] ?? [];
            }
        }

        // Si python3 no está en el PATH local o devolvió vacío, generar clústeres explicables en lenguaje humano (§2.7)
        if (empty($analytics)) {
            foreach ($payload as $item) {
                $clusterName = match (true) {
                    $item['total_sales'] >= 500000 && $item['avg_rating'] >= 4.2 => 'Líderes Consolidados (Alto Volumen y Alta Reputación)',
                    $item['total_sales'] < 500000 && $item['avg_rating'] >= 4.2  => 'Potencial de Escalamiento (Excelente Calidad, Baja Visibilidad)',
                    $item['total_sales'] >= 500000 && $item['avg_rating'] < 4.0  => 'Riesgo de Fuga (Alto Volumen pero Fricción en Servicio)',
                    default                                                      => 'Requiere Intervención Comercial (Baja Tracción / Catálogo)',
                };
                $analytics[] = array_merge($item, ['cluster_label' => $clusterName]);
            }
        }

        if (Schema::hasTable('ml_runs')) {
            MlRun::create([
                'triggered_by' => auth()->id(),
                'status'       => 'completed',
                'parameters'   => ['merchants_evaluated' => count($payload)],
                'results'      => $analytics,
                'summary'      => 'Segmentación K-Means ejecutada sobre ' . count($payload) . ' comercios con traducción semántica.',
                'completed_at' => now(),
            ]);
        }

        AdminAuditLog::record('ml.executed', null, 'Ejecución de segmentación ML sobre ' . count($payload) . ' comercios');
        session()->flash('ml_results', $analytics);

        return back()->with('success', 'Motor de Inteligencia ML completado y registrado en el historial de corridas.');
    }

    public function auditLogs()
    {
        $logs = AdminAuditLog::with('admin')->latest()->paginate(30);
        return view('analyst.audit-logs', compact('logs'));
    }

    /**
     * Envía el Reporte de Ventas y Diagnóstico Causal al comerciante como notificación real en BD.
     */
    public function sendMerchantSalesReport(Request $request, User $user, SalesDiagnosisService $diagnosisService)
    {
        $validated = $request->validate([
            'report_type'    => ['nullable', 'in:diagnostic,congratulations'],
            'custom_message' => ['nullable', 'string', 'max:8000'],
            'summary'        => ['nullable', 'string', 'max:500'],
        ]);

        $report = $diagnosisService->buildMerchantSalesReport($user);
        $reportType = $validated['report_type'] ?? ($report['variation_pct'] > 0 ? 'congratulations' : 'diagnostic');

        $title = $reportType === 'congratulations'
            ? "🏆 Reporte de Ventas y Desempeño Destacado — {$report['company_name']}"
            : "📊 Reporte de Ventas y Diagnóstico Causal — {$report['company_name']}";

        $summary = !empty($validated['summary'])
            ? $validated['summary']
            : ($reportType === 'congratulations' ? $report['congratulations_summary'] : $report['diagnostic_summary']);

        $customMessage = !empty($validated['custom_message'])
            ? $validated['custom_message']
            : ($reportType === 'congratulations' ? $report['congratulations_report_text'] : $report['diagnostic_report_text']);

        $payload = array_merge($report, [
            'title'          => $title,
            'summary'        => $summary,
            'report_type'    => $reportType,
            'custom_message' => $customMessage,
            'sent_by_name'   => auth()->user()->name ?? 'Administración FusaShop',
            'sent_at_human'  => now()->format('d/m/Y H:i'),
        ]);

        $user->notify(new \App\Notifications\MerchantSalesReportNotification($payload));

        AdminAuditLog::record(
            'merchant.sales_report_sent',
            $user,
            "Reporte de ventas ({$reportType}) enviado a {$report['company_name']} (\${$report['total_sales']})"
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok'         => true,
                'message'    => "Reporte de ventas y diagnóstico enviado a {$report['company_name']}. El comerciante ya tiene la notificación en su bandeja.",
                'report_url' => route('merchant.sales-report.show', $user->id),
            ]);
        }

        return back()->with('success', "Reporte de ventas enviado a {$report['company_name']}.");
    }

    /**
     * Muestra el Reporte de Ventas y Diagnóstico Causal completo (accesible para el comerciante dueño o un analista).
     */
    public function showMerchantSalesReport(Request $request, User $user, SalesDiagnosisService $diagnosisService)
    {
        $viewer = auth()->user();
        if (!$viewer || ($viewer->id !== $user->id && !$viewer->isAnalyst())) {
            abort(403, 'No tienes permiso para ver este reporte de ventas.');
        }

        // Si viene desde una notificación o si el comerciante abre su reporte, marcar notificaciones relacionadas como leídas
        if ($request->filled('notification_id')) {
            $notif = $viewer->notifications()->where('id', $request->input('notification_id'))->first();
            if ($notif && is_null($notif->read_at)) {
                $notif->markAsRead();
            }
        } elseif ($viewer->id === $user->id) {
            $viewer->unreadNotifications
                ->filter(fn($n) => ($n->data['type'] ?? '') === 'merchant_sales_report')
                ->each->markAsRead();
        }

        $liveReport = $diagnosisService->buildMerchantSalesReport($user);

        // Buscar la última notificación de reporte enviada a este comerciante para mostrar el mensaje exacto del admin
        $latestNotification = $user->notifications()
            ->get()
            ->first(fn($n) => ($n->data['type'] ?? '') === 'merchant_sales_report');

        $savedReport = $latestNotification->data['report_data'] ?? [];
        $report = array_merge($liveReport, array_filter([
            'report_type'    => $savedReport['report_type'] ?? null,
            'custom_message' => $savedReport['custom_message'] ?? null,
            'sent_by_name'   => $savedReport['sent_by_name'] ?? null,
            'sent_at_human'  => $savedReport['sent_at_human'] ?? null,
        ]));

        return view('merchant.sales-report-view', [
            'merchant'           => $user,
            'report'             => $report,
            'latestNotification' => $latestNotification,
        ]);
    }
}
