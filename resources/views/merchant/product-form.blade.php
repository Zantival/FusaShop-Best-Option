@extends('layouts.app')
@section('title', $product ? 'Editar Producto' : 'Nuevo Producto')
@section('content')
<div class="max-w-4xl mx-auto px-6 py-8">
  <div class="flex items-center gap-4 mb-8">
    <a href="{{ route('merchant.products') }}" class="w-10 h-10 bg-surface-container-low rounded-xl flex items-center justify-center hover:bg-surface-container transition-colors">
      <span class="material-symbols-outlined text-on-surface-variant">arrow_back</span>
    </a>
    <h1 class="text-3xl font-['Manrope'] font-bold text-on-background">
      {{ $product ? 'Editar Producto' : 'Nuevo Producto' }}
    </h1>
  </div>

  @if($errors->any())
    <div class="bg-[#ffdad6] text-[#ba1a1a] px-4 py-3 rounded-xl mb-6 text-sm">
      @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
    </div>
  @endif

  <form method="POST"
    action="{{ $product ? route('merchant.products.update', $product->id) : route('merchant.products.store') }}"
    enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    @csrf
    @if($product) @method('PUT') @endif

    <!-- Left Column: Images -->
    <div class="lg:col-span-1 space-y-6">
      <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-sm border border-outline-variant/30">
        <label class="block text-sm font-bold text-on-surface mb-4">Imagen Principal (Catálogo)</label>
        <div class="relative border-2 border-dashed border-outline-variant/50 rounded-2xl p-4 text-center hover:border-primary transition-colors cursor-pointer group" id="drop-zone-main">
          <input type="file" name="image" id="image-input" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"/>
          <div id="image-preview-container">
            @if($product && $product->image)
              <img src="{{ asset('storage/'.$product->image) }}" class="w-full aspect-square object-cover rounded-xl mb-3"/>
              <p class="text-xs text-on-surface-variant">Clic para cambiar</p>
            @else
              <span class="material-symbols-outlined text-on-surface-variant text-4xl mb-2 block group-hover:scale-110 transition-transform">add_a_photo</span>
              <p class="text-xs font-bold text-on-surface">Subir principal</p>
            @endif
          </div>
        </div>

        <label class="block text-sm font-bold text-on-surface mt-6 mb-4">Galería de Fotos General</label>
        <div class="grid grid-cols-2 gap-3 mb-4" id="gallery-preview">
          @if($product && $product->images)
            @foreach($product->images as $img)
              <div class="relative group aspect-square">
                <img src="{{ asset('storage/'.$img) }}" class="w-full h-full object-cover rounded-lg border border-outline-variant/30">
                <label class="absolute top-1 right-1 bg-error text-white rounded-full p-1 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity">
                  <input type="checkbox" name="remove_images[]" value="{{ $img }}" class="hidden">
                  <span class="material-symbols-outlined text-xs">delete</span>
                </label>
              </div>
            @endforeach
          @endif
        </div>
        <div class="relative border-2 border-dashed border-outline-variant/50 rounded-xl p-4 text-center hover:border-primary transition-colors cursor-pointer">
          <input type="file" name="gallery[]" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" id="gallery-input"/>
          <span class="material-symbols-outlined text-on-surface-variant text-2xl mb-1 block">library_add</span>
          <p class="text-[10px] font-bold text-on-surface-variant">Añadir más fotos</p>
        </div>
      </div>
    </div>

    <!-- Right Column: Details & Options -->
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-surface-container-lowest rounded-2xl p-8 shadow-sm border border-outline-variant/30">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div class="sm:col-span-2">
            <label class="block text-sm font-bold text-on-surface mb-2">Nombre del producto *</label>
            <input type="text" name="name" value="{{ old('name', $product?->name) }}" required
              class="w-full px-4 py-3 bg-surface-container-highest rounded-xl border-0 outline-none focus:ring-2 focus:ring-primary shadow-inner"
              placeholder="Ej: Camiseta de Algodón Orgánico"/>
          </div>

          <div>
            <label class="block text-sm font-bold text-on-surface mb-2">Precio (COP) *</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant font-bold text-sm">$</span>
              <input type="number" name="price" value="{{ old('price', $product?->price) }}" min="0" step="100" required
                class="w-full pl-8 pr-4 py-3 bg-surface-container-highest rounded-xl border-0 outline-none focus:ring-2 focus:ring-primary shadow-inner"
                placeholder="45.000"/>
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold text-on-surface mb-2">Stock Total *</label>
            <input type="number" name="stock" value="{{ old('stock', $product?->stock ?? 0) }}" min="0" required
              class="w-full px-4 py-3 bg-surface-container-highest rounded-xl border-0 outline-none focus:ring-2 focus:ring-primary shadow-inner"
              placeholder="100"/>
          </div>

          <div class="sm:col-span-2">
            <label class="block text-sm font-bold text-on-surface mb-2">Categoría *</label>
            <select name="category" id="category-select" required
              class="w-full px-4 py-3 bg-surface-container-highest rounded-xl border-0 outline-none focus:ring-2 focus:ring-primary shadow-inner appearance-none">
              <option value="">Seleccionar categoría</option>
              @foreach(['Ropa','Electrónica','Hogar','Deportes','Alimentos','Belleza','Juguetes','Libros','Otros'] as $cat)
                <option value="{{ $cat }}" {{ old('category', $product?->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
              @endforeach
            </select>
          </div>

          <div class="sm:col-span-2">
            <label class="block text-sm font-bold text-on-surface mb-2">Descripción</label>
            <textarea name="description" rows="4"
              class="w-full px-4 py-3 bg-surface-container-highest rounded-xl border-0 outline-none focus:ring-2 focus:ring-primary shadow-inner resize-none"
              placeholder="Detalles del producto...">{{ old('description', $product?->description) }}</textarea>
          </div>
        </div>

        <hr class="my-8 border-outline-variant/10">

        <!-- PRODUCT OPTIONS / VARIANTS -->
        <div class="space-y-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-bold text-on-surface">Opciones y Variantes</h3>
              <p class="text-[11px] text-on-surface-variant">Define colores (con fotos), tallas y otras especificaciones seleccionables.</p>
            </div>
            <div class="flex gap-2">
               <button type="button" onclick="addColorOption()" class="flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-[10px] font-black text-primary rounded-full hover:bg-primary/20 transition-all">
                <span class="material-symbols-outlined text-sm">palette</span> COLOR CON FOTOS
              </button>
              <button type="button" onclick="addOption()" class="flex items-center gap-1 px-3 py-1.5 bg-surface-container-highest text-[10px] font-black text-on-surface-variant rounded-full hover:bg-surface-container transition-all">
                <span class="material-symbols-outlined text-sm">add_circle</span> OTRA OPCIÓN
              </button>
            </div>
          </div>

          <!-- Sugerencias Inteligentes -->
          <div id="presets-container" class="hidden mb-6 p-4 bg-primary/5 rounded-2xl border border-primary/10 animate-in fade-in slide-in-from-bottom-2">
             <p class="text-[10px] font-black uppercase text-primary mb-3 flex items-center gap-1">
               <span class="material-symbols-outlined text-sm">lightbulb</span> Sugerencias rápidas para {{ old('category', $product?->category) }}:
             </p>
             <div id="presets-list" class="flex flex-wrap gap-2"></div>
          </div>

          <div id="options-container" class="space-y-6">
            @if($product && $product->available_options)
              @foreach($product->available_options as $index => $opt)
                @if(($opt['type'] ?? 'text') === 'color')
                  <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-5 animate-in fade-in zoom-in-95" data-option-type="color">
                    <div class="flex justify-between items-center mb-4">
                       <div class="flex items-center gap-2">
                         <span class="material-symbols-outlined text-primary">palette</span>
                         <input type="text" name="options[{{ $index }}][name]" value="{{ $opt['name'] }}" class="bg-transparent border-0 font-bold text-sm outline-none focus:ring-0 w-32">
                         <input type="hidden" name="options[{{ $index }}][type]" value="color">
                       </div>
                       <button type="button" onclick="this.closest('[data-option-type]').remove()" class="text-on-surface-variant hover:text-error transition-colors">
                         <span class="material-symbols-outlined">delete</span>
                       </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="color-values-{{ $index }}">
                      @foreach($opt['values'] as $vIndex => $val)
                        <div class="relative bg-white rounded-xl p-3 border border-outline-variant/30 flex flex-col items-center gap-2 animate-in zoom-in-90">
                           <div class="w-8 h-8 rounded-full border border-outline-variant/50 shadow-sm" style="background: {{ $val['hex'] }}"></div>
                           <p class="text-[10px] font-black truncate w-full text-center text-on-surface-variant">{{ $val['name'] }}</p>
                           <div class="w-14 h-14 bg-surface-container rounded-md flex items-center justify-center overflow-hidden border border-outline-variant/20" id="preview-img-{{ $index }}-{{ $vIndex }}">
                             @if(!empty($val['image']))
                               <img src="{{ asset('storage/'.$val['image']) }}" class="w-full h-full object-cover">
                               <input type="hidden" name="options[{{ $index }}][color_values][{{ $vIndex }}][existing_image]" value="{{ $val['image'] }}">
                             @else
                               <span class="material-symbols-outlined text-sm opacity-20">image</span>
                             @endif
                           </div>
                           <input type="hidden" name="options[{{ $index }}][color_values][{{ $vIndex }}][name]" value="{{ $val['name'] }}">
                           <input type="hidden" name="options[{{ $index }}][color_values][{{ $vIndex }}][hex]" value="{{ $val['hex'] }}">
                           <label class="mt-1 cursor-pointer bg-primary/10 px-3 py-1 rounded-full hover:bg-primary/20 transition-colors">
                             <input type="file" name="options[{{ $index }}][color_values][{{ $vIndex }}][image]" class="hidden" onchange="previewVariantImg(this, {{ $index }}, {{ $vIndex }})">
                             <span class="text-[9px] text-primary font-black">SUBIR FOTO</span>
                           </label>
                           <button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 bg-error text-white rounded-full p-1 shadow-sm hover:scale-110 transition-transform">
                             <span class="material-symbols-outlined text-[12px]">close</span>
                           </button>
                        </div>
                      @endforeach
                    </div>
                    <button type="button" onclick="openColorPicker({{ $index }})" class="mt-4 w-full py-2 border-2 border-dashed border-outline-variant/50 rounded-xl text-[10px] font-black text-on-surface-variant hover:border-primary hover:text-primary transition-all">
                      + AGREGAR COLOR
                    </button>
                  </div>
                @else
                  <div class="flex gap-3 items-start animate-in fade-in" data-option-type="text">
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-surface-container-low rounded-2xl border border-outline-variant/20">
                      <div>
                        <label class="text-[10px] font-black uppercase text-on-surface-variant mb-1 block">Nombre</label>
                        <input type="text" name="options[{{ $index }}][name]" value="{{ $opt['name'] }}" class="w-full bg-white border-0 rounded-lg text-sm px-3 py-2 outline-none shadow-sm">
                      </div>
                      <div>
                        <label class="text-[10px] font-black uppercase text-on-surface-variant mb-1 block">Valores</label>
                        <input type="text" name="options[{{ $index }}][values]" value="{{ is_array($opt['values']) ? implode(', ', $opt['values']) : $opt['values'] }}" class="w-full bg-white border-0 rounded-lg text-sm px-3 py-2 outline-none shadow-sm">
                      </div>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="mt-8 p-2 text-on-surface-variant"><span class="material-symbols-outlined">delete</span></button>
                  </div>
                @endif
              @endforeach
            @endif
          </div>
        </div>

        <!-- PRODUCT SPECIFICATIONS (DYNAMIC) -->
        <div id="specs-wrapper" class="hidden space-y-6">
          <hr class="my-8 border-outline-variant/10">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-on-surface">Especificaciones Técnicas</h3>
              <p class="text-[11px] text-on-surface-variant">Información detallada según la categoría.</p>
            </div>
          </div>
          <div id="specs-container" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></div>
          <div class="pt-2">
            <button type="button" onclick="addCustomSpec()" class="text-[10px] font-black text-primary hover:underline flex items-center gap-1">
              <span class="material-symbols-outlined text-sm">add</span> AÑADIR ATRIBUTO PERSONALIZADO
            </button>
          </div>
        </div>

        <div class="mt-10 sm:col-span-2">
          <label class="flex items-center gap-3 cursor-pointer">
            <div class="relative">
              <input type="hidden" name="is_active" value="0">
              <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $product?->is_active ?? true) ? 'checked' : '' }}/>
              <div class="w-11 h-6 bg-outline-variant rounded-full peer-checked:bg-primary transition-colors"></div>
              <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 shadow-sm"></div>
            </div>
            <span class="font-bold text-sm text-on-surface">Producto activo y visible</span>
          </label>
        </div>

        <div class="flex gap-4 pt-8">
          <button type="submit" class="flex-1 py-4 bg-primary-gradient text-white font-bold rounded-2xl hover:opacity-95 shadow-lg flex items-center justify-center gap-2 transition-all">
            <span class="material-symbols-outlined">{{ $product ? 'save' : 'add_circle' }}</span>
            {{ $product ? 'Guardar Cambios' : 'Publicar Producto' }}
          </button>
          <a href="{{ route('merchant.products') }}" class="px-8 py-4 bg-surface-container-low text-on-surface font-bold rounded-2xl text-center">Cancelar</a>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- COLOR PICKER MODAL -->
<div id="color-picker-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-6">
  <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl animate-in zoom-in-95">
    <div class="p-6 border-b border-outline-variant/10 flex justify-between items-center">
      <h3 class="font-bold">Elegir Color</h3>
      <button onclick="closeColorPicker()" class="material-symbols-outlined">close</button>
    </div>
    <div class="p-6 grid grid-cols-4 gap-4" id="swatch-list"></div>
  </div>
</div>

@push('scripts')
<script>
  const colorSwatches = [
    { name: 'Blanco', hex: '#FFFFFF' }, { name: 'Negro', hex: '#000000' }, { name: 'Rojo', hex: '#FF0000' },
    { name: 'Azul', hex: '#0000FF' }, { name: 'Verde', hex: '#008000' }, { name: 'Gris', hex: '#808080' },
    { name: 'Amarillo', hex: '#FFFF00' }, { name: 'Naranja', hex: '#FFA500' }, { name: 'Morado', hex: '#800080' },
    { name: 'Rosa', hex: '#FFC0CB' }, { name: 'Café', hex: '#A52A2A' }, { name: 'Celeste', hex: '#87CEEB' },
  ];

  const categoryPresets = {
    'Ropa': [{ name: 'Color', type: 'color' }, { name: 'Talla', values: 'S, M, L, XL, XXL' }],
    'Electrónica': [{ name: 'Color', type: 'color' }, { name: 'Capacidad', values: '64GB, 128GB, 256GB' }],
    'Zapatos': [{ name: 'Color', type: 'color' }, { name: 'Talla', values: '36, 37, 38, 39, 40, 41, 42' }],
    'Belleza': [{ name: 'Contenido', values: '30ml, 50ml, 100ml' }],
    'Alimentos': [{ name: 'Peso', values: '500g, 1kg, 2kg' }]
  };

  const specSchemas = {
    'Ropa': [{ key: 'material', label: 'Material' }, { key: 'gender', label: 'Género' }, { key: 'fit', label: 'Corte' }],
    'Electrónica': [{ key: 'brand', label: 'Marca' }, { key: 'model', label: 'Modelo' }, { key: 'screen', label: 'Pantalla' }, { key: 'ram', label: 'RAM' }, { key: 'storage', label: 'Almacenamiento' }, { key: 'os', label: 'S. Operativo' }],
    'Alimentos': [{ key: 'exp', label: 'Vencimiento' }, { key: 'origin', label: 'Origen' }],
    'Hogar': [{ key: 'dims', label: 'Dimensiones' }, { key: 'material', label: 'Material' }]
  };

  let optionCount = {{ $product && $product->available_options ? count($product->available_options) : 0 }};
  let currentOptionIndex = null;
  const existingSpecs = {!! json_encode($product?->specifications ?? []) !!};

  // Main Image Preview
  document.getElementById('image-input').addEventListener('change', e => {
    const f = e.target.files[0]; if(!f) return;
    const r = new FileReader(); r.onload = x => document.getElementById('image-preview-container').innerHTML = `<img src="${x.target.result}" class="w-full aspect-square object-cover rounded-xl mb-3">`;
    r.readAsDataURL(f);
  });

  // Category change
  const catSelect = document.getElementById('category-select');
  catSelect.addEventListener('change', function() {
    const cat = this.value;
    const presetsCont = document.getElementById('presets-container');
    if (categoryPresets[cat]) {
      presetsCont.classList.remove('hidden');
      document.getElementById('presets-list').innerHTML = categoryPresets[cat].map(p => `<button type="button" onclick="applyPreset('${p.name}', '${p.type||'text'}', '${p.values||''}')" class="px-4 py-2 bg-primary/10 border border-primary/20 rounded-full text-[10px] font-black text-primary hover:bg-primary hover:text-white transition-all">+ ${p.name.toUpperCase()}</button>`).join('');
    } else presetsCont.classList.add('hidden');

    const sw = document.getElementById('specs-wrapper');
    if (specSchemas[cat] || Object.keys(existingSpecs).length > 0) { sw.classList.remove('hidden'); renderSpecs(cat); }
    else sw.classList.add('hidden');
  });
  if(catSelect.value) catSelect.dispatchEvent(new Event('change'));

  function renderSpecs(cat) {
    const schema = specSchemas[cat] || [], cont = document.getElementById('specs-container');
    let h = '';
    schema.forEach(s => { const v = existingSpecs[s.key] || ''; h += `<div class="bg-surface-container-highest/50 p-4 rounded-2xl border border-outline-variant/10"><label class="text-[10px] font-black uppercase text-on-surface-variant mb-1 block">${s.label}</label><input type="text" name="specs[${s.key}]" value="${v}" class="w-full bg-transparent border-0 text-sm p-0 outline-none focus:ring-0 font-bold text-on-surface"></div>`; });
    Object.keys(existingSpecs).forEach(k => { if(!schema.find(s=>s.key===k)) h += createCustomSpecHtml(k, existingSpecs[k]); });
    cont.innerHTML = h;
  }

  function addCustomSpec() { document.getElementById('specs-container').insertAdjacentHTML('beforeend', createCustomSpecHtml('', '', Date.now())); }
  function createCustomSpecHtml(k, v, id='') { return `<div class="bg-surface-container-highest/50 p-4 rounded-2xl border border-outline-variant/10 relative group" id="cs-${id}"><div class="grid grid-cols-2 gap-4"><div><input type="text" value="${k}" placeholder="Nombre" onchange="const val=this.closest('div').nextElementSibling.querySelector('input');val.name='specs['+this.value+']'" class="w-full bg-transparent border-0 text-[10px] font-black uppercase text-primary p-0 outline-none"></div><div><input type="text" name="specs[${k}]" value="${v}" placeholder="Valor" class="w-full bg-transparent border-0 text-sm p-0 outline-none font-bold text-on-surface"></div></div><button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 bg-error text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"><span class="material-symbols-outlined text-[12px]">close</span></button></div>`; }

  function applyPreset(n, t, v) { if(t==='color') addColorOption(n); else addOption(n, v); }

  function addOption(n='', v='') {
    const c = document.getElementById('options-container');
    c.insertAdjacentHTML('beforeend', `<div class="flex gap-3 items-start animate-in fade-in" data-option-type="text"><div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-surface-container-low rounded-2xl border border-outline-variant/20"><div><label class="text-[10px] font-black uppercase text-on-surface-variant mb-1 block">Nombre</label><input type="text" name="options[${optionCount}][name]" value="${n}" class="w-full bg-white border-0 rounded-lg text-sm px-3 py-2 outline-none"></div><div><label class="text-[10px] font-black uppercase text-on-surface-variant mb-1 block">Valores</label><input type="text" name="options[${optionCount}][values]" value="${v}" class="w-full bg-white border-0 rounded-lg text-sm px-3 py-2 outline-none"></div></div><button type="button" onclick="this.parentElement.remove()" class="mt-8 p-2"><span class="material-symbols-outlined">delete</span></button></div>`);
    optionCount++;
  }

  function addColorOption(n='Color') {
    const c = document.getElementById('options-container'), i = optionCount;
    c.insertAdjacentHTML('beforeend', `<div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-5 animate-in zoom-in-95" data-option-type="color"><div class="flex justify-between items-center mb-4"><div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">palette</span><input type="text" name="options[${i}][name]" value="${n}" class="bg-transparent border-0 font-bold text-sm w-32"><input type="hidden" name="options[${i}][type]" value="color"></div><button type="button" onclick="this.closest('[data-option-type]').remove()" class="text-on-surface-variant"><span class="material-symbols-outlined">delete</span></button></div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="color-values-${i}"></div><button type="button" onclick="openColorPicker(${i})" class="mt-4 w-full py-2 border-2 border-dashed border-outline-variant/50 rounded-xl text-[10px] font-black text-on-surface-variant">+ SELECCIONAR COLORES</button></div>`);
    optionCount++;
  }

  function openColorPicker(i) {
    currentOptionIndex = i; const m = document.getElementById('color-picker-modal');
    document.getElementById('swatch-list').innerHTML = colorSwatches.map(s => `<button type="button" onclick="pickColor('${s.name}', '${s.hex}')" class="flex flex-col items-center gap-1 group"><div class="w-10 h-10 rounded-full border-2 border-transparent group-hover:border-primary shadow-sm" style="background:${s.hex}"></div><span class="text-[9px] font-bold text-on-surface-variant">${s.name}</span></button>`).join('');
    m.classList.remove('hidden');
  }
  function closeColorPicker() { document.getElementById('color-picker-modal').classList.add('hidden'); }

  function pickColor(n, h) {
    const c = document.getElementById(`color-values-${currentOptionIndex}`), vi = c.children.length;
    c.insertAdjacentHTML('beforeend', `<div class="relative bg-white rounded-xl p-3 border border-outline-variant/30 flex flex-col items-center gap-2 animate-in zoom-in-90"><div class="w-8 h-8 rounded-full border border-outline-variant/50 shadow-sm" style="background: ${h}"></div><p class="text-[10px] font-black truncate w-full text-center text-on-surface-variant">${n}</p><div class="w-14 h-14 bg-surface-container rounded-md flex items-center justify-center overflow-hidden border border-outline-variant/20" id="preview-img-${currentOptionIndex}-${vi}"><span class="material-symbols-outlined text-sm opacity-20">image</span></div><input type="hidden" name="options[${currentOptionIndex}][color_values][${vi}][name]" value="${n}"><input type="hidden" name="options[${currentOptionIndex}][color_values][${vi}][hex]" value="${h}"><label class="mt-1 cursor-pointer bg-primary/10 px-3 py-1 rounded-full hover:bg-primary/20 transition-colors"><input type="file" name="options[${currentOptionIndex}][color_values][${vi}][image]" class="hidden" onchange="previewVariantImg(this, ${currentOptionIndex}, ${vi})"><span class="text-[9px] text-primary font-black uppercase">Subir foto</span></label><button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 bg-error text-white rounded-full p-1"><span class="material-symbols-outlined text-[12px]">close</span></button></div>`);
    closeColorPicker();
  }

  window.previewVariantImg = (input, oi, vi) => {
    if (input.files && input.files[0]) {
      const r = new FileReader(); r.onload = e => {
        const p = document.getElementById(`preview-img-${oi}-${vi}`);
        if(p) p.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
      };
      r.readAsDataURL(input.files[0]);
    }
  };
</script>
@endpush
@endsection