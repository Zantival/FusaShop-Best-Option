@php $f = fn ($k, $d = '') => old($k, $a?->{$k} ?? $d); $cls = 'rounded-xl border border-outline-variant/40 px-4 py-3 text-sm w-full'; @endphp
<input class="{{ $cls }}" name="label" value="{{ $f('label', 'Casa') }}" placeholder="Etiqueta (Casa, Oficina)" required maxlength="60">
<input class="{{ $cls }}" name="recipient" value="{{ $f('recipient', auth()->user()->name) }}" placeholder="Quién recibe" required maxlength="120">
<input class="{{ $cls }}" name="phone" value="{{ $f('phone', auth()->user()->phone) }}" placeholder="Teléfono" required maxlength="20">
<input class="{{ $cls }}" name="street" value="{{ $f('street') }}" placeholder="Calle / Carrera" required maxlength="160">
<input class="{{ $cls }}" name="number" value="{{ $f('number') }}" placeholder="Número (# 45-23)" maxlength="30">
<input class="{{ $cls }}" name="district" value="{{ $f('district') }}" placeholder="Barrio" maxlength="100">
<input class="{{ $cls }}" name="city" value="{{ $f('city') }}" placeholder="Ciudad / Municipio" required maxlength="100">
<select class="{{ $cls }}" name="state" required>
  <option value="">Departamento…</option>
  @foreach($states as $code => $name)
    <option value="{{ $code }}" {{ $f('state') === $code ? 'selected' : '' }}>{{ $name }}</option>
  @endforeach
</select>
<input class="{{ $cls }}" name="postal_code" value="{{ $f('postal_code') }}" placeholder="Código postal (6 dígitos)" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric">
<input class="{{ $cls }}" name="reference" value="{{ $f('reference') }}" placeholder="Indicaciones adicionales" maxlength="255">
