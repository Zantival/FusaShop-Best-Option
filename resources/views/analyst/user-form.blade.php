@extends('layouts.app')
@section('title', $user ? 'Editar Usuario' : 'Nuevo Usuario')
@section('content')
<div class="max-w-3xl mx-auto px-6 py-8 space-y-6">
  <div class="flex items-center gap-4">
    <a href="{{ route('analyst.dashboard') }}" class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm hover:bg-[#f0eded] transition-colors">
      <span class="material-symbols-outlined text-[#3c4a41]">arrow_back</span>
    </a>
    <div>
      <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-[#1b1c1c]">
        {{ $user && $user->id === auth()->id() ? 'Mi Perfil de Administrador' : ($user ? 'Editar Usuario' : 'Nuevo Usuario') }}
      </h1>
      <p class="text-xs text-slate-500">Personaliza tu foto de perfil, datos de acceso y credenciales seguras</p>
    </div>
  </div>

  @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-[#006c47] px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
      <span class="material-symbols-outlined text-lg">check_circle</span>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div class="bg-red-50 text-red-700 px-4 py-3 rounded-xl text-sm">
      @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
    </div>
  @endif

  <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-slate-200">
    <form method="POST" action="{{ $user ? route('analyst.users.update', $user->id) : route('analyst.users.store') }}" enctype="multipart/form-data" class="space-y-6">
      @csrf
      @if($user) @method('PUT') @endif

      <!-- Selector de Imagen / Avatar con Previsualización Instantánea -->
      <div class="flex flex-col sm:flex-row items-center gap-5 p-4 bg-emerald-50/50 border border-emerald-200/70 rounded-2xl">
        <div class="relative shrink-0">
          @if($user && $user->avatarUrl())
            <img id="admin-avatar-preview" src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-full object-cover border-2 border-[#006c47] shadow-md">
          @else
            <div id="admin-avatar-placeholder" class="w-20 h-20 rounded-full bg-gradient-to-tr from-[#006c47] to-[#10B981] text-white flex items-center justify-center font-black text-2xl shadow-md">
              {{ strtoupper(substr($user?->name ?? 'A', 0, 1)) }}
            </div>
            <img id="admin-avatar-preview" src="" alt="Preview" style="display: none;" class="w-20 h-20 rounded-full object-cover border-2 border-[#006c47] shadow-md">
          @endif
        </div>
        <div class="flex-1 text-center sm:text-left">
          <label class="block text-sm font-extrabold text-slate-900 mb-1">Foto de Perfil (Se mostrará en el círculo del panel superior)</label>
          <p class="text-xs text-slate-500 mb-2.5">Formatos permitidos: JPG, PNG o WEBP (Máx. 4 MB)</p>
          <input type="file" name="avatar" accept="image/*"
                 onchange="if(this.files && this.files[0]){ const r = new FileReader(); r.onload = function(e){ const img = document.getElementById('admin-avatar-preview'); const ph = document.getElementById('admin-avatar-placeholder'); if(img){ img.src = e.target.result; img.style.display = 'block'; } if(ph){ ph.style.display = 'none'; } }; r.readAsDataURL(this.files[0]); }"
                 class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-[#006c47] file:text-white hover:file:bg-[#10B981] cursor-pointer"/>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
          <label class="block text-sm font-semibold text-[#1b1c1c] mb-2">Nombre completo *</label>
          <input type="text" name="name" value="{{ old('name', $user?->name) }}" required
            class="w-full px-4 py-3 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 focus:ring-[#006c47]" placeholder="Nombre completo"/>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-semibold text-[#1b1c1c] mb-2">Correo electrónico *</label>
          <input type="email" name="email" value="{{ old('email', $user?->email) }}" required
            class="w-full px-4 py-3 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 focus:ring-[#006c47]" placeholder="correo@ejemplo.com"/>
        </div>

        <!-- §2.2: Si edita su propio perfil, no puede cambiar su rol aquí -->
        @if($user && $user->id === auth()->id())
          <div>
            <label class="block text-sm font-semibold text-[#1b1c1c] mb-2">Rol Asignado (Protegido)</label>
            <div class="px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-extrabold text-[#006c47] flex items-center gap-2">
              <span class="material-symbols-outlined text-base">verified_user</span>
              {{ $user->adminSubroleLabel() }} (Rol bloqueado en perfil propio)
            </div>
          </div>
        @else
          <div>
            <label class="block text-sm font-semibold text-[#1b1c1c] mb-2">Rol Principal *</label>
            <select name="role" required class="w-full px-4 py-3 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 focus:ring-[#006c47]">
              @foreach(['consumer'=>'Consumidor','merchant'=>'Comerciante','analyst'=>'Analista / Admin'] as $val=>$label)
                <option value="{{ $val }}" {{ old('role',$user?->role)===$val?'selected':'' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-[#1b1c1c] mb-2">Sub-rol Granular (Solo si es Admin)</label>
            <select name="admin_subrole" class="w-full px-4 py-3 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 focus:ring-[#006c47]">
              @foreach(['super_admin'=>'Super Admin (Control Total)','analyst_readonly'=>'Analista (Solo Lectura y Reportes)','support'=>'Soporte (PQRS + KYC)'] as $sVal=>$sLbl)
                <option value="{{ $sVal }}" {{ old('admin_subrole', $user?->admin_subrole ?? 'super_admin')===$sVal?'selected':'' }}>{{ $sLbl }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div>
          <label class="block text-sm font-semibold text-[#1b1c1c] mb-2">Teléfono</label>
          <input type="tel" name="phone" value="{{ old('phone', $user?->phone) }}"
            class="w-full px-4 py-3 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 focus:ring-[#006c47]" placeholder="+57 300 000 0000"/>
        </div>
        <div>
          <label class="block text-sm font-semibold text-[#1b1c1c] mb-2">
            Contraseña {{ $user ? '(dejar vacío para no cambiar)' : '*' }}
          </label>
          <input type="password" name="password" {{ !$user ? 'required' : '' }}
            class="w-full px-4 py-3 bg-[#f0eded] rounded-xl border-0 outline-none focus:ring-2 focus:ring-[#006c47]" placeholder="Mínimo 8 caracteres"/>
        </div>
      </div>

      <div class="flex gap-4 pt-2">
        <button type="submit" class="flex-1 py-3.5 bg-gradient-to-r from-[#006c47] to-[#00b67a] text-white font-semibold rounded-xl hover:opacity-90 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer">
          <span class="material-symbols-outlined text-sm">{{ $user ? 'save' : 'person_add' }}</span>
          {{ $user ? 'Guardar cambios y foto de perfil' : 'Crear usuario' }}
        </button>
        <a href="{{ route('analyst.dashboard') }}" class="px-6 py-3.5 bg-[#f0eded] text-[#1b1c1c] font-semibold rounded-xl hover:bg-[#e5e2e1] transition-colors text-center">
          Volver al Panel
        </a>
      </div>
    </form>

    @if($user && $user->role === 'merchant' && $user->companyProfile)
      <!-- Corrección manual del rubro sectorial (§2.1) -->
      <div class="mt-8 pt-6 border-t border-slate-200">
        <h3 class="text-base font-extrabold text-slate-900 mb-3 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#006c47]">category</span>
          Clasificación Sectorial y Subsector (Corrección Manual)
        </h3>
        <form method="POST" action="{{ route('analyst.users.sector', $user->id) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
          @csrf
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Sector Principal</label>
            <select name="sector_override" class="w-full px-3 py-2.5 bg-slate-100 rounded-xl text-xs font-bold">
              @foreach(\App\Services\Admin\SectorCensusService::definitions() as $sKey => $def)
                <option value="{{ $sKey }}" {{ ($user->companyProfile->sector_override ?? '') === $sKey ? 'selected' : '' }}>{{ $def['title'] }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Subsector / Especialidad</label>
            <input type="text" name="subsector_override" value="{{ $user->companyProfile->subsector_override }}" placeholder="Ej: Belleza, Agrícola, Glamping..." class="w-full px-3 py-2.5 bg-slate-100 rounded-xl text-xs font-semibold">
          </div>
          <button type="submit" class="px-4 py-2.5 bg-[#006c47] text-white font-bold rounded-xl text-xs hover:bg-[#10B981]">
            Guardar Rubro
          </button>
        </form>
      </div>

      <!-- Verificación KYC con motivos predefinidos de rechazo (§2.2) -->
      <div class="mt-8 pt-6 border-t border-slate-200">
        <h2 class="text-lg font-extrabold text-slate-900 mb-4 flex items-center gap-2">
          <span class="material-symbols-outlined text-[#006c47]">verified_user</span>
          Auditoría Documental (KYC & RUT — Ley 1581)
        </h2>

        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
              <p class="text-xs font-bold text-slate-400 uppercase mb-1">Información Corporativa</p>
              <p class="text-sm font-extrabold">{{ $user->companyProfile->company_name }}</p>
              <p class="text-xs text-slate-600">Actividad declarada: {{ $user->companyProfile->business_type }}</p>
              <p class="text-xs text-slate-600">Tel: {{ $user->companyProfile->phone }} · Dir: {{ $user->companyProfile->address }}</p>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
              <p class="text-xs font-bold text-slate-400 uppercase mb-1">Estado KYC Actual</p>
              @php
                $statusMap = [
                  'pending'  => ['bg-amber-100 text-amber-800', 'Pendiente (' . $user->companyProfile->created_at->diffInDays(now()) . ' días en cola)'],
                  'approved' => ['bg-emerald-100 text-[#006c47]', 'Aprobado y Verificado'],
                  'rejected' => ['bg-rose-100 text-rose-800', 'Rechazado']
                ];
                $s = $statusMap[$user->companyProfile->kyc_status] ?? $statusMap['pending'];
              @endphp
              <span class="px-3 py-1 rounded-full text-xs font-bold {{ $s[0] }}">{{ $s[1] }}</span>
              @if($user->companyProfile->kyc_notes)
                <p class="mt-2 text-xs text-slate-600 italic">Observación: {{ $user->companyProfile->kyc_notes }}</p>
              @endif
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('analyst.users.rut', $user->id) }}" target="_blank" class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-xl hover:border-[#10B981] transition-all">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-red-600">picture_as_pdf</span>
                <div>
                  <span class="text-xs font-extrabold block">Descargar RUT Privado (DIAN)</span>
                  <span class="text-[10px] text-slate-400">El acceso queda registrado en auditoría</span>
                </div>
              </div>
              <span class="material-symbols-outlined text-slate-400">open_in_new</span>
            </a>
          </div>

          <form method="POST" action="{{ route('analyst.users.kyc', $user->id) }}" class="p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-extrabold text-slate-700 mb-1">Causal Estandarizada (Si rechaza)</label>
                <select name="kyc_rejection_code" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold">
                  <option value="">— Seleccionar motivo predefinido —</option>
                  <option value="rut_ilegible">RUT ilegible o incompleto</option>
                  <option value="nit_no_coincide">NIT o razón social no coincide</option>
                  <option value="documento_vencido">Documento desactualizado (>30 días)</option>
                  <option value="actividad_ciiu_invalida">Código CIIU no corresponde al rubro</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-extrabold text-slate-700 mb-1">Observaciones Adicionales</label>
                <input type="text" name="kyc_notes" placeholder="Detalle para el comerciante..." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs">
              </div>
            </div>

            <div class="flex gap-3 pt-1">
              <button type="submit" name="kyc_status" value="approved" class="flex-1 py-2.5 bg-[#006c47] hover:bg-[#10B981] text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-base">check_circle</span> Aprobar Documentación KYC
              </button>
              <button type="submit" name="kyc_status" value="rejected" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-base">cancel</span> Rechazar con Motivo
              </button>
            </div>
          </form>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
