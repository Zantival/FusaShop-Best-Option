@extends('layouts.app')
@section('title', 'Mis Conversaciones')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-8">
    <h1 class="text-2xl font-black text-on-surface tracking-tight">Mensajes</h1>
    <span class="text-xs font-bold text-on-surface-variant bg-surface-container px-3 py-1 rounded-full">{{ $conversations->count() }} conversaciones</span>
  </div>

  @if($conversations->isEmpty())
    <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-surface-container-highest">
      <div class="w-16 h-16 bg-surface-container-low rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">forum</span>
      </div>
      <h2 class="font-bold text-on-surface mb-2">Sin conversaciones aún</h2>
      <p class="text-sm text-on-surface-variant max-w-xs mx-auto">Inicia un chat desde la página de un producto para hablar con un comerciante.</p>
      <a href="{{ route('consumer.catalog') }}" class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-greenhouse-gradient text-white text-sm font-bold rounded-xl shadow-sm">
        <span class="material-symbols-outlined text-[18px]">explore</span> Explorar productos
      </a>
    </div>
  @else
    <div class="space-y-2">
      @foreach($conversations as $otherUserId => $msgs)
        @php
          $lastMsg = $msgs->last();
          $otherUser = $lastMsg->sender_id == auth()->id() ? $lastMsg->receiver : $lastMsg->sender;
          $unread = $msgs->where('sender_id', $otherUserId)->where('is_read', false)->count();
        @endphp
        <a href="{{ route('chat.show', $otherUserId) }}"
           class="flex items-center gap-4 p-4 bg-white rounded-2xl border border-surface-container hover:border-primary/30 hover:shadow-md transition-all group">
          <!-- Avatar -->
          <div class="relative shrink-0">
            @if($otherUser->avatar)
              <img src="{{ Storage::url($otherUser->avatar) }}" class="w-12 h-12 rounded-full object-cover">
            @else
              <div class="w-12 h-12 bg-greenhouse-gradient rounded-full flex items-center justify-center text-white font-bold text-lg">
                {{ strtoupper(substr($otherUser->name, 0, 1)) }}
              </div>
            @endif
            @if($unread > 0)
              <span class="absolute -top-1 -right-1 w-5 h-5 bg-[#ba1a1a] text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white">{{ $unread }}</span>
            @endif
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-0.5">
              <p class="font-bold text-on-surface {{ $unread > 0 ? '' : 'font-semibold' }}">{{ e($otherUser->name) }}</p>
              <span class="text-[10px] text-on-surface-variant/60 font-medium shrink-0">{{ $lastMsg->created_at->diffForHumans(null, true) }}</span>
            </div>
            <p class="text-xs text-on-surface-variant truncate {{ $unread > 0 ? 'font-semibold text-on-surface' : '' }}">
              @if($lastMsg->sender_id == auth()->id()) Tú: @endif
              {{ Str::limit(e($lastMsg->content), 50) }}
            </p>
            <span class="text-[9px] font-bold text-primary uppercase tracking-wider">{{ ucfirst($otherUser->role) }}</span>
          </div>

          <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors text-[20px] shrink-0">chevron_right</span>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection
