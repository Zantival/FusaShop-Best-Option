@props(['product', 'class' => 'w-full h-full'])
@php
use Illuminate\Support\Facades\Storage;
$cats = [
    'Ropa'        => ['checkroom',     '#7c3aed', '#a855f7'],
    'Electrónica' => ['devices',       '#1d4ed8', '#06b6d4'],
    'Hogar'       => ['chair',         '#d97706', '#f59e0b'],
    'Deportes'    => ['sports_soccer', '#16a34a', '#22c55e'],
    'Alimentos'   => ['lunch_dining',  '#ca8a04', '#84cc16'],
    'Belleza'     => ['spa',           '#db2777', '#f43f5e'],
    'Juguetes'    => ['toys',          '#ea580c', '#fb923c'],
    'Libros'      => ['menu_book',     '#0f766e', '#14b8a6'],
];
$c = $cats[$product->category] ?? ['inventory_2', '#006c47', '#00b67a'];
$bg = "background:linear-gradient(135deg,{$c[1]},{$c[2]})";
$hasImage = $product->image && Storage::disk('public')->exists($product->image);
// La imagen se guarda como "products/archivo.jpg", construimos la URL directa
$imageUrl = $hasImage ? url('files/' . $product->image) : null;
@endphp
@if($hasImage)
    <img src="{{ $imageUrl }}"
         alt="{{ e($product->name) }}"
         class="{{ $class }} object-cover"
         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
    <div class="{{ $class }} hidden flex-col items-center justify-center text-white" style="{{ $bg }}">
        <span class="material-symbols-outlined text-5xl" style="font-variation-settings:'FILL' 1,'wght' 300">{{ $c[0] }}</span>
        <span class="text-xs mt-1 opacity-80">{{ $product->category }}</span>
    </div>
@else
    <div class="{{ $class }} flex flex-col items-center justify-center text-white" style="{{ $bg }}">
        <span class="material-symbols-outlined text-5xl" style="font-variation-settings:'FILL' 1,'wght' 300">{{ $c[0] }}</span>
        <span class="text-xs mt-1 opacity-80 font-medium">{{ $product->category }}</span>
    </div>
@endif
