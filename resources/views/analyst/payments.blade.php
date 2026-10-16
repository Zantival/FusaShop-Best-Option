@extends('layouts.app')
@section('title', 'Panel de Pagos')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">

  {{-- Header --}}
  <div class="mb-8 flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-['Manrope'] font-extrabold text-on-background flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-3xl">payments</span> Panel de Pagos e Ingresos
      </h1>
      <p class="text-on-surface-variant mt-1">Registro de pagos por banners publicitarios y cuentas de comerciantes creadas en la plataforma.</p>
    </div>
    <a href="{{ route('analyst.sales-report.print') }}" target="_blank" class="hidden md:flex items-center gap-2 px-4 py-2.5 bg-primary-gradient text-white text-sm font-bold rounded-xl shadow hover:opacity-90 transition-all">
      <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span> PDF Ventas
    </a>
  </div>

  {{-- KPI Cards --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
    @php
      $approvedBanners         = $bannerPayments->where('status','approved')->count();
      $totalMerchants          = $merchantAccounts->count();
      $merchantAccountFee      = 80000;  // Cuota fija por apertura de espacio comercial
      $totalMerchantRevenue    = $merchantAccounts->count() * $merchantAccountFee;
      $totalGeneralRevenue     = $totalBannerRevenue + $totalMerchantRevenue;
    @endphp
    @foreach([
      ['account_balance','Ingresos Totales Plataforma','$'.number_format($totalGeneralRevenue,0,',','.').' COP','text-[#006c47]','bg-[#6efcb9]/30'],
      ['payments','Ingresos por Banners','$'.number_format($totalBannerRevenue,0,',','.').' COP','text-primary','bg-primary-container/30'],
      ['storefront','Ingresos por Cuentas','$'.number_format($totalMerchantRevenue,0,',','.').' COP','text-[#7c5800]','bg-[#ffdea8]/50'],
      ['campaign','Banners Publicados',$approvedBanners.' activos','text-tertiary','bg-tertiary-container/30'],
    ] as [$icon,$label,$val,$col,$bg])
    <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-card">
      <div class="w-11 h-11 {{ $bg }} rounded-xl flex items-center justify-center mb-3">
        <span class="material-symbols-outlined {{ $col }} text-2xl">{{ $icon }}</span>
      </div>
      <p class="text-2xl font-['Manrope'] font-extrabold text-on-background">{{ $val }}</p>
      <p class="text-on-surface-variant text-xs mt-1">{{ $label }}</p>
    </div>
    @endforeach
  </div>

  {{-- ── Sección 1: Pagos de Banners ── --}}
  <div class="bg-surface-container-lowest rounded-2xl shadow-card overflow-hidden mb-10">
    <div class="px-6 py-5 border-b border-outline-variant/20 flex items-center gap-3">
      <div class="w-10 h-10 bg-primary-container/30 rounded-xl flex items-center justify-center">
        <span class="material-symbols-outlined text-primary">image</span>
      </div>
      <div>
        <h2 class="font-['Manrope'] font-bold text-on-background text-lg">Pagos por Banners Publicitarios</h2>
        <p class="text-xs text-on-surface-variant">Cada pago corresponde a una solicitud de espacio publicitario en la página principal.</p>
      </div>
      <span class="ml-auto px-3 py-1 bg-surface-container text-on-surface-variant text-xs font-bold rounded-full">{{ $bannerPayments->count() }} registros</span>
    </div>

    @if($bannerPayments->isEmpty())
      <div class="text-center py-16">
        <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2 block">receipt_long</span>
        <p class="text-on-surface-variant font-semibold">Aún no hay solicitudes de banner registradas.</p>
      </div>
    @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface-container-low">
          <tr>
            @foreach(['#','Comerciante','Correo','Tipo','Monto (COP)','Estado','Fecha','Detalle'] as $h)
            <th class="px-4 py-3 text-left text-xs font-semibold text-on-surface-variant uppercase whitespace-nowrap">{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($bannerPayments as $payment)
          @php
            $statusMap = [
              'pending'  => ['bg-[#ffdea8]/70 text-[#7c5800]', 'hourglass_empty', 'Pendiente'],
              'approved' => ['bg-[#6efcb9]/40 text-[#006c47]', 'verified',        'Aprobado'],
              'rejected' => ['bg-error-container text-error',   'cancel',          'Rechazado'],
            ];
            [$sCls, $sIcon, $sLabel] = $statusMap[$payment['status']] ?? ['bg-surface-container text-on-surface-variant','info','Desconocido'];
          @endphp
          <tr class="border-t border-outline-variant/10 hover:bg-surface-container-low/50 transition-colors">
            <td class="px-4 py-3 text-on-surface-variant font-mono text-xs">#{{ $payment['id'] }}</td>
            <td class="px-4 py-3 font-semibold text-on-background">{{ $payment['merchant'] }}</td>
            <td class="px-4 py-3 text-on-surface-variant text-xs">{{ $payment['email'] }}</td>
            <td class="px-4 py-3 text-on-surface-variant">{{ $payment['type'] }}</td>
            <td class="px-4 py-3 font-bold text-[#006c47]">${{ number_format($payment['amount'], 0, ',', '.') }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $sCls }}">
                <span class="material-symbols-outlined text-[13px]">{{ $sIcon }}</span> {{ $sLabel }}
              </span>
            </td>
            <td class="px-4 py-3 text-on-surface-variant text-xs whitespace-nowrap">{{ $payment['date']->format('d M Y, h:i A') }}</td>
            <td class="px-4 py-3">
              <a href="{{ $payment['detail_url'] }}" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                <span class="material-symbols-outlined text-[14px]">open_in_new</span> Ver
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  {{-- ── Sección 2: Pagos por Apertura de Espacios Comerciales ── --}}
  <div class="bg-surface-container-lowest rounded-2xl shadow-card overflow-hidden mb-10">
    <div class="px-6 py-5 border-b border-outline-variant/20 flex items-center gap-3">
      <div class="w-10 h-10 bg-[#ffdea8]/50 rounded-xl flex items-center justify-center">
        <span class="material-symbols-outlined text-[#7c5800]">store</span>
      </div>
      <div>
        <h2 class="font-['Manrope'] font-bold text-on-background text-lg">Pagos por Apertura de Espacio Comercial</h2>
        <p class="text-xs text-on-surface-variant">Cuota única de registro ($80,000 COP) por cada comerciante que abre su tienda en la plataforma.</p>
      </div>
      <span class="ml-auto px-3 py-1 bg-surface-container text-on-surface-variant text-xs font-bold rounded-full">{{ $merchantAccounts->count() }} cuentas</span>
    </div>

    @if($merchantAccounts->isEmpty())
      <div class="text-center py-16">
        <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2 block">storefront</span>
        <p class="text-on-surface-variant font-semibold">No hay comerciantes registrados aún.</p>
      </div>
    @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface-container-low">
          <tr>
            @foreach(['#','Empresa','Propietario','Correo','Monto Pagado (COP)','Estado KYC','Fecha de Registro','Acciones'] as $h)
            <th class="px-4 py-3 text-left text-xs font-semibold text-on-surface-variant uppercase whitespace-nowrap">{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($merchantAccounts as $account)
          @php
            $kycMap = [
              'pending'  => ['bg-[#ffdea8]/70 text-[#7c5800]', 'hourglass_empty', 'Pendiente'],
              'approved' => ['bg-[#6efcb9]/40 text-[#006c47]', 'verified_user',   'Verificado'],
              'rejected' => ['bg-error-container text-error',   'gpp_bad',         'Rechazado'],
            ];
            [$kCls, $kIcon, $kLabel] = $kycMap[$account['kyc_status']] ?? ['bg-surface-container text-on-surface-variant','help','Sin Estado'];
          @endphp
          <tr class="border-t border-outline-variant/10 hover:bg-surface-container-low/50 transition-colors">
            <td class="px-4 py-3 text-on-surface-variant font-mono text-xs">#{{ $account['id'] }}</td>
            <td class="px-4 py-3 font-semibold text-on-background">{{ $account['company_name'] }}</td>
            <td class="px-4 py-3 text-on-surface-variant">{{ $account['owner'] }}</td>
            <td class="px-4 py-3 text-on-surface-variant text-xs">{{ $account['email'] }}</td>
            <td class="px-4 py-3">
              <span class="font-bold text-[#006c47]">$80,000</span>
              <span class="ml-1 px-1.5 py-0.5 bg-[#6efcb9]/40 text-[#006c47] text-[10px] font-bold rounded-full">PAGADO</span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $kCls }}">
                <span class="material-symbols-outlined text-[13px]">{{ $kIcon }}</span> {{ $kLabel }}
              </span>
            </td>
            <td class="px-4 py-3 text-on-surface-variant text-xs whitespace-nowrap">
              {{ $account['created_at'] ? $account['created_at']->format('d M Y') : 'N/A' }}
            </td>
            <td class="px-4 py-3">
              <a href="{{ route('analyst.users') }}?search={{ urlencode($account['email']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                <span class="material-symbols-outlined text-[14px]">manage_accounts</span> Gestionar
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
        <tfoot class="bg-surface-container-low border-t-2 border-outline-variant/30">
          <tr>
            <td colspan="4" class="px-4 py-3 text-right text-xs font-bold text-on-surface-variant uppercase">Total Recaudado por Cuentas:</td>
            <td class="px-4 py-3 font-extrabold text-[#006c47] font-['Manrope'] text-base">${{ number_format($merchantAccounts->count() * 80000, 0, ',', '.') }} COP</td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    @endif
  </div>

  {{-- ── Sección 3: Cuentas de Comerciantes (Directorio) ── --}}
  <div class="bg-surface-container-lowest rounded-2xl shadow-card overflow-hidden">
    <div class="px-6 py-5 border-b border-outline-variant/20 flex items-center gap-3">
      <div class="w-10 h-10 bg-[#ffdea8]/50 rounded-xl flex items-center justify-center">
        <span class="material-symbols-outlined text-[#7c5800]">storefront</span>
      </div>
      <div>
        <h2 class="font-['Manrope'] font-bold text-on-background text-lg">Cuentas de Comerciantes Creadas</h2>
        <p class="text-xs text-on-surface-variant">Todas las empresas registradas en la plataforma con su estado de verificación KYC.</p>
      </div>
      <span class="ml-auto px-3 py-1 bg-surface-container text-on-surface-variant text-xs font-bold rounded-full">{{ $merchantAccounts->count() }} cuentas</span>
    </div>

    @if($merchantAccounts->isEmpty())
      <div class="text-center py-16">
        <span class="material-symbols-outlined text-5xl text-on-surface-variant/40 mb-2 block">storefront</span>
        <p class="text-on-surface-variant font-semibold">No hay comerciantes registrados aún.</p>
      </div>
    @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-surface-container-low">
          <tr>
            @foreach(['ID','Empresa','Propietario','Correo','Estado KYC','Fecha de Registro','Acciones'] as $h)
            <th class="px-4 py-3 text-left text-xs font-semibold text-on-surface-variant uppercase whitespace-nowrap">{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($merchantAccounts as $account)
          @php
            $kycMap = [
              'pending'  => ['bg-[#ffdea8]/70 text-[#7c5800]', 'hourglass_empty', 'Pendiente'],
              'approved' => ['bg-[#6efcb9]/40 text-[#006c47]', 'verified_user',   'Verificado'],
              'rejected' => ['bg-error-container text-error',   'gpp_bad',         'Rechazado'],
            ];
            [$kCls, $kIcon, $kLabel] = $kycMap[$account['kyc_status']] ?? ['bg-surface-container text-on-surface-variant','help','Sin Estado'];
          @endphp
          <tr class="border-t border-outline-variant/10 hover:bg-surface-container-low/50 transition-colors">
            <td class="px-4 py-3 text-on-surface-variant font-mono text-xs">#{{ $account['id'] }}</td>
            <td class="px-4 py-3 font-semibold text-on-background">{{ $account['company_name'] }}</td>
            <td class="px-4 py-3 text-on-surface-variant">{{ $account['owner'] }}</td>
            <td class="px-4 py-3 text-on-surface-variant text-xs">{{ $account['email'] }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $kCls }}">
                <span class="material-symbols-outlined text-[13px]">{{ $kIcon }}</span> {{ $kLabel }}
              </span>
            </td>
            <td class="px-4 py-3 text-on-surface-variant text-xs whitespace-nowrap">
              {{ $account['created_at'] ? $account['created_at']->format('d M Y') : 'N/A' }}
            </td>
            <td class="px-4 py-3">
              <a href="{{ route('analyst.users') }}?search={{ urlencode($account['email']) }}" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                <span class="material-symbols-outlined text-[14px]">manage_accounts</span> Gestionar
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

</div>
@endsection
