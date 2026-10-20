@extends('layouts.app')
@section('title','Gestión de Usuarios')
@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl md:text-3xl font-['Manrope'] font-bold text-on-background">Gestión de Usuarios</h1>
    <a href="{{ route('analyst.users.create') }}" class="px-4 py-2.5 bg-primary-gradient text-white font-semibold rounded-xl hover:opacity-90 transition-all flex items-center gap-2 shadow-md text-sm">
      <span class="material-symbols-outlined text-sm">person_add</span>
      <span class="hidden sm:inline">Nuevo usuario</span>
    </a>
  </div>

  @if(session('success'))
    <div class="bg-[#6efcb9]/30 text-[#006c47] px-4 py-3 rounded-xl mb-4 text-sm flex items-center gap-2 border border-[#6efcb9]">
      <span class="material-symbols-outlined text-sm">check_circle</span> {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="bg-error-container text-error px-4 py-3 rounded-xl mb-4 text-sm flex items-center gap-2">
      <span class="material-symbols-outlined text-sm">error</span> {{ session('error') }}
    </div>
  @endif

  {{-- Filtros --}}
  <form method="GET" class="flex gap-2 mb-6 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nombre, tienda o email..."
      class="flex-1 min-w-40 px-4 py-2.5 bg-white rounded-xl border border-outline-variant/30 outline-none focus:ring-2 focus:ring-primary text-sm shadow-sm"/>
    <select name="role" class="px-3 py-2.5 bg-white rounded-xl border border-outline-variant/30 outline-none focus:ring-2 focus:ring-primary text-sm shadow-sm">
      <option value="">Todos los roles</option>
      <option value="consumer" {{ request('role')==='consumer'?'selected':'' }}>Consumidor</option>
      <option value="merchant" {{ request('role')==='merchant'?'selected':'' }}>Comerciante</option>
      <option value="analyst" {{ request('role')==='analyst'?'selected':'' }}>Analista</option>
    </select>
    <select name="kyc_status" class="px-3 py-2.5 bg-white rounded-xl border border-outline-variant/30 outline-none focus:ring-2 focus:ring-primary text-sm shadow-sm">
      <option value="">Verificación (Todos)</option>
      <option value="approved" {{ request('kyc_status')==='approved'?'selected':'' }}>✓ Comerciantes Verificados</option>
      <option value="pending" {{ request('kyc_status')==='pending'?'selected':'' }}>⏳ Sin verificar / Pendientes</option>
      <option value="rejected" {{ request('kyc_status')==='rejected'?'selected':'' }}>❌ Verificación Rechazada</option>
    </select>
    <button type="submit" class="px-4 py-2.5 bg-primary text-white font-semibold rounded-xl hover:opacity-90 text-sm cursor-pointer">Buscar</button>
    @if(request()->anyFilled(['search','role','kyc_status']))
      <a href="{{ route('analyst.users') }}" class="px-4 py-2.5 bg-surface-container text-on-surface-variant rounded-xl hover:bg-surface-container-high text-sm">✕</a>
    @endif
  </form>

  {{-- Lista de usuarios (funciona en cualquier pantalla) --}}
  <div class="space-y-4">
    @forelse($users as $u)
    @php
      $rCls = ['consumer'=>'bg-emerald-50 text-emerald-700','merchant'=>'bg-[#6efcb9]/40 text-[#006c47]','analyst'=>'bg-[#ffdea8] text-[#7c5800]'][$u->role] ?? 'bg-surface-container text-on-surface-variant';
      $isMerchantUser = $u->role === 'merchant';
      $kycStatus = $u->companyProfile?->kyc_status ?? 'pending';
      $isMerchantVerified = $isMerchantUser && $kycStatus === 'approved';
      $companyName = $u->companyProfile?->company_name;
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden {{ $u->is_blocked ? 'opacity-70' : '' }}">

      {{-- Cabecera de la tarjeta --}}
      <div class="flex items-center gap-4 p-4 md:p-5">
        {{-- Avatar --}}
        @if($u->avatarUrl())
          <img src="{{ $u->avatarUrl() }}" alt="{{ $u->name }}" class="w-12 h-12 rounded-full object-cover shrink-0 border-2 border-[#10B981] bg-white">
        @else
          <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg shrink-0 {{ $u->is_blocked ? 'bg-gray-400' : 'bg-primary-gradient' }}">
            {{ strtoupper(substr($u->name,0,1)) }}
          </div>
        @endif

        {{-- Info principal --}}
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2 mb-0.5">
            <span class="font-bold text-on-background text-base truncate flex items-center gap-1.5">
              <span>{{ e($u->name) }}</span>
              @if($isMerchantVerified)
                <span class="material-symbols-outlined text-[#006c47] text-[18px] shrink-0" style="font-variation-settings: 'FILL' 1" title="Comerciante con chulito de verificación activo">verified</span>
              @endif
            </span>
            @if($isMerchantUser && $companyName && $companyName !== $u->name)
              <span class="text-xs font-extrabold text-[#006c47] bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg truncate">
                🏪 {{ e($companyName) }}
              </span>
            @endif
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rCls }}">{{ ucfirst($u->role) }}</span>
            @if($isMerchantUser)
              @if($isMerchantVerified)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-[#006c47] border border-emerald-300">
                  <span class="material-symbols-outlined text-[13px]" style="font-variation-settings: 'FILL' 1">verified</span>
                  <span>Verificado</span>
                </span>
              @elseif($kycStatus === 'rejected')
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">
                  <span class="material-symbols-outlined text-[13px]">cancel</span>
                  <span>KYC Rechazado</span>
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                  <span class="material-symbols-outlined text-[13px]">verified_off</span>
                  <span>Sin verificar</span>
                </span>
              @endif
            @endif
            @if($u->is_blocked)
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-error-container text-error">🔒 Bloqueado</span>
            @else
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#6efcb9]/40 text-[#006c47]">✓ Activo</span>
            @endif
          </div>
          <p class="text-xs text-on-surface-variant truncate">{{ e($u->email) }}</p>
          <p class="text-xs text-on-surface-variant/60 mt-0.5">
            {{ $u->phone ?: 'Sin teléfono' }} · Registro: {{ $u->created_at->format('d/m/Y') }}
          </p>
        </div>
      </div>

      {{-- Botones de Acción — siempre visibles, fila completa --}}
      <div class="border-t border-outline-variant/15 grid {{ $isMerchantUser ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-3' }} divide-x divide-outline-variant/15">

        {{-- Editar --}}
        <a href="{{ route('analyst.users.edit', $u->id) }}"
           class="flex items-center justify-center gap-2 py-3 text-on-surface-variant hover:bg-primary hover:text-white transition-all font-semibold text-sm">
          <span class="material-symbols-outlined text-[18px]">edit</span>
          <span>Editar</span>
        </a>

        {{-- Dar / Quitar Chulito de Verificación (Solo para Comerciantes) --}}
        @if($isMerchantUser)
          <form method="POST" action="{{ route('analyst.users.toggle-verification', $u->id) }}">
            @csrf @method('PATCH')
            <button type="submit"
              class="w-full h-full flex items-center justify-center gap-1.5 py-3 font-extrabold text-sm transition-all cursor-pointer {{ $isMerchantVerified ? 'text-slate-600 hover:bg-amber-500 hover:text-white' : 'text-[#006c47] bg-emerald-50/60 hover:bg-[#006c47] hover:text-white' }}"
              title="{{ $isMerchantVerified ? 'Quitarle el chulito de verificación a este comerciante' : 'Otorgarle el chulito de verificación a este comerciante' }}">
              <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' {{ $isMerchantVerified ? '0' : '1' }}">{{ $isMerchantVerified ? 'verified_off' : 'verified' }}</span>
              <span>{{ $isMerchantVerified ? 'Quitar Verificación' : 'Dar Verificación ✓' }}</span>
            </button>
          </form>
        @endif

        @if($u->id !== auth()->id())
          {{-- Bloquear / Desbloquear --}}
          <form method="POST" action="{{ route('analyst.users.toggle-block', $u->id) }}"
                onsubmit="return confirm('{{ $u->is_blocked ? '¿Desbloquear a '.$u->name.'?' : '¿Bloquear a '.$u->name.'?' }}')">
            @csrf @method('PATCH')
            <button type="submit"
              class="w-full h-full flex items-center justify-center gap-2 py-3 font-semibold text-sm transition-all cursor-pointer {{ $u->is_blocked ? 'text-amber-600 hover:bg-amber-500 hover:text-white' : 'text-orange-600 hover:bg-orange-500 hover:text-white' }}">
              <span class="material-symbols-outlined text-[18px]">{{ $u->is_blocked ? 'lock_open' : 'block' }}</span>
              <span>{{ $u->is_blocked ? 'Desbloquear' : 'Bloquear' }}</span>
            </button>
          </form>

          {{-- Eliminar --}}
          <form method="POST" action="{{ route('analyst.users.delete', $u->id) }}"
                onsubmit="return confirm('¿Eliminar a {{ e($u->name) }}? Esta acción no se puede deshacer.')">
            @csrf @method('DELETE')
            <button type="submit"
              class="w-full h-full flex items-center justify-center gap-2 py-3 text-red-600 hover:bg-red-600 hover:text-white transition-all font-semibold text-sm cursor-pointer">
              <span class="material-symbols-outlined text-[18px]">delete</span>
              <span>Eliminar</span>
            </button>
          </form>

        @else
          {{-- Si es la propia cuenta, mostrar celdas vacías --}}
          <div class="flex items-center justify-center py-3 text-on-surface-variant/30 text-xs col-span-2">
            — cuenta propia —
          </div>
        @endif

      </div>
    </div>
    @empty
    <div class="text-center py-20 bg-white rounded-2xl border border-outline-variant/20">
      <span class="material-symbols-outlined text-6xl text-on-surface-variant/30 block mb-3">group_off</span>
      <p class="text-on-surface-variant font-semibold text-lg">No se encontraron usuarios.</p>
    </div>
    @endforelse
  </div>

  {{-- Paginación --}}
  <div class="mt-6">{{ $users->links() }}</div>

</div>
@endsection
