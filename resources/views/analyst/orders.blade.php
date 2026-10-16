@extends('layouts.app')
@section('title','Todos los Pedidos')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-['Manrope'] font-bold text-on-background mb-8">Todos los Pedidos</h1>
  <div class="bg-surface-container-lowest rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-surface-container-low">
          <tr>
            @foreach(['#','Cliente','Total','Estado','Pago','Fecha'] as $h)
            <th class="px-6 py-4 text-left text-xs font-semibold text-on-surface-variant uppercase tracking-wider">{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant/10">
          @foreach($orders as $o)
          @php
            $sc=['pending'=>'bg-[#ffdea8] text-[#7c5800]','processing'=>'bg-[#6efcb9]/40 text-[#003f28]','shipped'=>'bg-blue-100 text-blue-700','delivered'=>'bg-[#6efcb9]/60 text-[#006c47]','cancelled'=>'bg-[#ffdad6] text-[#ba1a1a]'];
            $pc=['pending'=>'bg-[#ffdad6] text-[#ba1a1a]','paid'=>'bg-[#6efcb9]/40 text-[#006c47]','failed'=>'bg-[#ffdad6] text-[#ba1a1a]'];
          @endphp
          <tr class="hover:bg-surface-container-low/50 transition-colors">
            <td class="px-6 py-4 text-sm font-mono text-on-surface-variant">#{{ $o->id }}</td>
            <td class="px-6 py-4">
              <p class="font-semibold text-on-background text-sm">{{ e($o->user->name) }}</p>
              <p class="text-on-surface-variant text-xs">{{ e($o->user->email) }}</p>
            </td>
            <td class="px-6 py-4 font-bold text-primary">${{ number_format($o->total,0,',','.') }}</td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $sc[$o->status]??'' }}">{{ ucfirst($o->status) }}</span></td>
            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $pc[$o->payment_status]??'' }}">{{ ucfirst($o->payment_status) }}</span></td>
            <td class="px-6 py-4 text-sm text-on-surface-variant">{{ $o->created_at->format('d/m/Y') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="px-6 py-4">{{ $orders->links() }}</div>
  </div>
</div>
@endsection
