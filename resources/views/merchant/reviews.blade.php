@extends('layouts.app')
@section('title','Reseñas de Mis Productos')
@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
  <div class="flex items-center justify-between mb-8">
    <h1 class="text-3xl font-['Manrope'] font-bold text-on-background flex items-center gap-2">
      <span class="material-symbols-outlined text-primary">star</span> Reseñas de Mis Productos
    </h1>
    <div class="flex items-center gap-3 bg-surface-container-lowest rounded-2xl px-5 py-3 shadow-card">
      <span class="text-3xl font-['Manrope'] font-extrabold text-secondary-container" style="color:#7c5800">{{ number_format($avgRating,1) }}</span>
      <div>
        <div class="flex gap-0.5">
          @for($i=1;$i<=5;$i++)
            <span class="material-symbols-outlined text-lg" style="color:{{ $i <= round($avgRating) ? '#feb700' : '#bbcabf' }};font-variation-settings:'FILL' {{ $i <= round($avgRating) ? 1 : 0 }},'wght' 400,'GRAD' 0,'opsz' 24">star</span>
          @endfor
        </div>
        <p class="text-xs text-on-surface-variant">{{ $reviews->total() }} reseñas</p>
      </div>
    </div>
  </div>

  @if($reviews->isEmpty())
    <div class="text-center py-20 bg-surface-container-lowest rounded-2xl shadow-card">
      <span class="material-symbols-outlined text-6xl text-on-surface-variant/40 mb-3 block">rate_review</span>
      <p class="text-on-surface-variant font-semibold">Aún no tienes reseñas en tus productos.</p>
    </div>
  @else
    <div class="space-y-6">
      @foreach($reviews as $review)
      <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-card">
        <div class="flex gap-4 mb-4">
            <div class="w-10 h-10 bg-primary-gradient rounded-full flex items-center justify-center text-white font-bold text-sm shrink-0">
              {{ strtoupper(substr($review->user->name,0,1)) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                  <p class="font-semibold text-on-background text-sm">{{ e($review->user->name) }}</p>
                  <p class="text-xs text-on-surface-variant">en <span class="font-medium text-primary">{{ e($review->product->name) }}</span></p>
                </div>
                <div class="flex gap-0.5 shrink-0">
                  @for($i=1;$i<=5;$i++)
                    <span class="material-symbols-outlined text-base" style="color:{{ $i <= $review->rating ? '#feb700' : '#bbcabf' }};font-variation-settings:'FILL' {{ $i <= $review->rating ? 1 : 0 }},'wght' 400,'GRAD' 0,'opsz' 18">star</span>
                  @endfor
                </div>
              </div>
              @if($review->comment)
                <p class="mt-2 text-sm text-on-surface-variant italic">"{{ e($review->comment) }}"</p>
              @endif
              <p class="text-xs text-on-surface-variant/60 mt-2">{{ $review->created_at->diffForHumans() }}</p>
            </div>
        </div>

        <div class="ml-14 bg-surface-container-low rounded-xl p-4 border border-surface-container">
            @if($review->merchant_reply)
                <div class="flex items-start gap-2 mb-2">
                    <span class="material-symbols-outlined text-primary text-sm">reply</span>
                    <p class="text-xs font-bold text-primary uppercase tracking-wider">Tu Respuesta</p>
                </div>
                <p class="text-sm text-on-surface">{{ e($review->merchant_reply) }}</p>
                <p class="text-[10px] text-on-surface-variant/50 mt-2">Respondido {{ $review->replied_at->diffForHumans() }}</p>
            @else
                <form action="{{ route('merchant.reviews.reply', $review->id) }}" method="POST">
                    @csrf
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Responder a este comentario</label>
                    <div class="flex gap-2">
                        <textarea name="merchant_reply" rows="2" class="flex-1 bg-white border border-surface-container rounded-lg p-2 text-sm focus:ring-1 focus:ring-primary outline-none" placeholder="Escribe tu respuesta aquí..." required></textarea>
                        <button type="submit" class="self-end px-4 py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90 transition-all">
                            Enviar
                        </button>
                    </div>
                </form>
            @endif
        </div>
      </div>
      @endforeach
      <div class="mt-6">{{ $reviews->links() }}</div>
    </div>
  @endif
</div>
@endsection
