@props([
    'size' => 'default', // 'sm', 'default', 'lg', 'xl'
    'withMicrocopy' => false,
    'href' => null
])

@php
if (!$href) {
    if (auth()->check()) {
        if (auth()->user()->isMerchant()) {
            $href = route('merchant.dashboard');
        } elseif (auth()->user()->isAnalyst()) {
            $href = route('analyst.dashboard');
        } else {
            $href = route('consumer.home');
        }
    } else {
        $href = route('consumer.home');
    }
}

$sizeClasses = match($size) {
    'sm' => ['icon' => 'w-7 h-7 text-xs', 'text' => 'text-base', 'sub' => 'text-[9px]', 'logo' => 'h-7 max-w-32'],
    'lg' => ['icon' => 'w-11 h-11 text-lg', 'text' => 'text-2xl', 'sub' => 'text-xs', 'logo' => 'h-11 max-w-56'],
    'xl' => ['icon' => 'w-14 h-14 text-2xl', 'text' => 'text-3xl sm:text-4xl', 'sub' => 'text-xs sm:text-sm', 'logo' => 'h-14 max-w-72'],
    default => ['icon' => 'w-9 h-9 text-sm', 'text' => 'text-xl', 'sub' => 'text-[10px]', 'logo' => 'h-9 max-w-44'],
};

$logoPath = collect(\Illuminate\Support\Facades\Storage::disk('public')->files('system'))
    ->first(fn ($path) => preg_match('/^system\/global_logo\.(png|jpe?g|webp)$/i', $path));
@endphp

<a href="{{ $href }}" class="inline-flex items-center gap-2.5 group shrink-0 no-underline" title="FusaShop — El epicentro de compras del Sumapaz">
    @if($logoPath)
        <img src="{{ route('files.serve', ['disk' => 'system', 'path' => basename($logoPath)]) }}?v={{ \Illuminate\Support\Facades\Storage::disk('public')->lastModified($logoPath) }}"
             alt="Logo FusaShop"
             class="{{ $sizeClasses['logo'] }} w-auto object-contain">
    @else
        <!-- Isotipo: Orquídea del Sumapaz + Carrito Digital -->
        <div class="{{ $sizeClasses['icon'] }} rounded-xl flex items-center justify-center relative overflow-hidden shadow-sm group-hover:scale-105 group-hover:shadow-md transition-all duration-300"
             style="background: linear-gradient(135deg, #10B981 0%, #00843D 100%);">
            <svg class="w-3/5 h-3/5 text-white drop-shadow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a4 4 0 0 1 4 4c0 3-4 6-4 6s-4-3-4-6a4 4 0 0 1 4-4z" fill="rgba(255,255,255,0.3)"/>
                <path d="M7 10a3 3 0 0 1 3-3c2 0 3 3 3 3s-3 2-4 3a3 3 0 0 1-2-3z" fill="rgba(255,255,255,0.2)"/>
                <path d="M17 10a3 3 0 0 0-3-3c-2 0-3 3-3 3s3 2 4 3a3 3 0 0 0 2-3z" fill="rgba(255,255,255,0.2)"/>
                <circle cx="9" cy="20" r="1.5" fill="currentColor"/>
                <circle cx="17" cy="20" r="1.5" fill="currentColor"/>
                <path d="M4 5h2l2 9h10l2-6H7"/>
            </svg>
        </div>
    @endif

    <div class="flex flex-col">
        <span class="{{ $sizeClasses['text'] }} font-extrabold tracking-tight leading-none" style="font-family:'Plus Jakarta Sans','Inter',sans-serif">
            <span class="text-[#10B981]">Fus</span><span class="text-[#2563EB]">a</span><span class="text-[#F97316]">Shop</span>
        </span>
        @if($withMicrocopy)
            <span class="{{ $sizeClasses['sub'] }} font-medium text-slate-500 tracking-normal mt-0.5 leading-tight hidden sm:block">
                El epicentro de compras, turismo y negocios del Sumapaz
            </span>
        @endif
    </div>
</a>
