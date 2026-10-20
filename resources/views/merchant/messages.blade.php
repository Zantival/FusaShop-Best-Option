@extends('layouts.app')
@section('title', 'Conversaciones & Mensajes con Clientes | FusaShop')

@section('content')
<div class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6"
     x-data="{
       searchClient: '',
       activeFilter: 'all',
       quickReply(text) {
         const input = document.getElementById('msg-input');
         if (input) {
           input.value = text;
           input.focus();
           checkProfanity();
         }
       }
     }">

  <!-- HEADER DEL APARTADO -->
  <div class="bg-white p-6 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-[#006c47] border border-emerald-200 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">forum</span> Centro de Atención al Cliente
        </span>
        @if($unreadCount > 0)
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
            {{ $unreadCount }} mensaje(s) nuevo(s)
          </span>
        @else
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
            Al día
          </span>
        @endif
      </div>
      <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-slate-800">
        Conversaciones con Clientes
      </h1>
      <p class="text-slate-500 text-xs md:text-sm mt-1">
        Atiende consultas de compradores sobre tus productos, disponibilidad de stock, envíos con Envia.com y asesoría comercial personalizada.
      </p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('merchant.dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm">dashboard</span> Volver al Tablero
      </a>
    </div>
  </div>

  @if($conversations->isEmpty() && !$activeUser)
    <!-- ESTADO VACÍO (SIN CONVERSACIONES AÚN) -->
    <div class="bg-white rounded-3xl p-12 text-center border-2 border-dashed border-slate-200 shadow-sm max-w-2xl mx-auto space-y-4">
      <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#006c47] flex items-center justify-center mx-auto shadow-inner">
        <span class="material-symbols-outlined text-3xl">question_answer</span>
      </div>
      <h2 class="text-xl font-black text-slate-800 font-['Manrope']">Bandeja de atención lista y al día</h2>
      <p class="text-xs md:text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
        Aún no has recibido mensajes de clientes. Cuando los compradores hagan clic en <strong class="text-slate-700">"Chatear con el vendedor"</strong> desde tus productos o tu vitrina comercial, sus conversaciones aparecerán aquí en tiempo real para que puedas cerrar tus ventas.
      </p>
      <div class="pt-2">
        <a href="{{ route('merchant.products') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all">
          <span class="material-symbols-outlined text-sm">inventory_2</span> Administrar mis Productos
        </a>
      </div>
    </div>
  @else
    <!-- LAYOUT PRINCIPAL: LISTA DE CLIENTES (IZQ) Y VENTANA DE CHAT (DER) -->
    <div class="bg-white rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-slate-200/80 overflow-hidden grid grid-cols-1 lg:grid-cols-12 min-h-[640px]">
      
      <!-- COLUMNA IZQUIERDA: LISTA DE CONVERSACIONES (4/12) -->
      <div class="lg:col-span-4 border-r border-slate-200/80 flex flex-col bg-slate-50/40">
        <!-- Buscador y Filtros -->
        <div class="p-4 border-b border-slate-200/80 bg-white space-y-3">
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-400 text-sm">search</span>
            <input type="text" x-model="searchClient" placeholder="Buscar cliente o mensaje..."
                   class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/40 text-slate-800">
          </div>

          <div class="flex items-center gap-1.5 text-xs">
            <button type="button" @click="activeFilter = 'all'"
                    class="px-3 py-1 rounded-full font-bold text-[11px] transition-all cursor-pointer"
                    :class="activeFilter === 'all' ? 'bg-[#006c47] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
              Todos ({{ $conversations->count() }})
            </button>
            <button type="button" @click="activeFilter = 'unread'"
                    class="px-3 py-1 rounded-full font-bold text-[11px] transition-all cursor-pointer flex items-center gap-1"
                    :class="activeFilter === 'unread' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
              Sin leer
              @if($unreadCount > 0)
                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
              @endif
            </button>
          </div>
        </div>

        <!-- Lista scrolleable de chats -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100 p-2 space-y-1">
          @foreach($conversations as $cUserId => $msgs)
            @php
              $lastMsg = $msgs->first(); // ordenados latest()
              $client = $lastMsg->sender_id == auth()->id() ? $lastMsg->receiver : $lastMsg->sender;
              $unreadThisChat = $msgs->where('sender_id', $cUserId)->where('is_read', false)->count();
              $isActive = $activeUser && $activeUser->id == $cUserId;
              $hasProduct = $msgs->whereNotNull('product_id')->isNotEmpty();
              $productSnippet = $msgs->whereNotNull('product_id')->first()?->product;
            @endphp
            <div x-show="(activeFilter === 'all' || (activeFilter === 'unread' && {{ $unreadThisChat > 0 ? 'true' : 'false' }})) && (!searchClient || '{{ strtolower(addslashes($client->name)) }} {{ strtolower(addslashes($lastMsg->content)) }}'.includes(searchClient.toLowerCase()))"
                 x-cloak>
              <a href="{{ route('merchant.messages', $client->id) }}"
                 class="block p-3 rounded-2xl transition-all duration-200 {{ $isActive ? 'bg-emerald-50/90 border border-emerald-300 shadow-2xs' : 'hover:bg-white hover:shadow-2xs bg-transparent border border-transparent' }}">
                <div class="flex items-start gap-3">
                  <!-- Avatar -->
                  <div class="relative shrink-0">
                    @if($client->avatar)
                      <img src="{{ Storage::url($client->avatar) }}" alt="{{ $client->name }}" class="w-11 h-11 rounded-full object-cover border-2 {{ $isActive ? 'border-emerald-500' : 'border-white' }} shadow-2xs">
                    @else
                      <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-sm flex items-center justify-center shadow-2xs">
                        {{ strtoupper(substr($client->name, 0, 1)) }}
                      </div>
                    @endif
                    @if($unreadThisChat > 0)
                      <span class="absolute -top-1 -right-1 w-5 h-5 bg-rose-600 text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white animate-pulse">
                        {{ $unreadThisChat }}
                      </span>
                    @endif
                  </div>

                  <!-- Información y último mensaje -->
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between mb-0.5">
                      <h3 class="font-extrabold text-xs text-slate-800 truncate {{ $unreadThisChat > 0 ? 'text-emerald-950 font-black' : '' }}">
                        {{ $client->name }}
                      </h3>
                      <span class="text-[10px] text-slate-400 font-medium shrink-0 ml-1">
                        {{ $lastMsg->created_at->diffForHumans(null, true) }}
                      </span>
                    </div>

                    <p class="text-[11px] text-slate-500 truncate {{ $unreadThisChat > 0 ? 'font-bold text-slate-800' : '' }}">
                      @if($lastMsg->sender_id == auth()->id())
                        <span class="text-emerald-700 font-bold">Tú:</span>
                      @endif
                      {{ $lastMsg->content }}
                    </p>

                    @if($productSnippet)
                      <div class="mt-1 flex items-center gap-1 text-[10px] font-bold text-[#006c47] bg-emerald-100/60 px-2 py-0.5 rounded-md truncate max-w-fit">
                        <span class="material-symbols-outlined text-[11px]">shopping_bag</span>
                        <span class="truncate">{{ $productSnippet->name }}</span>
                      </div>
                    @endif
                  </div>
                </div>
              </a>
            </div>
          @endforeach
        </div>
      </div>

      <!-- COLUMNA DERECHA: VENTANA DE CONVERSACIÓN ACTIVA (8/12) -->
      <div class="lg:col-span-8 flex flex-col bg-white">
        @if($activeUser)
          <!-- HEADER DEL CHAT CON EL CLIENTE -->
          <div class="p-4 border-b border-slate-200/80 flex items-center justify-between bg-white shadow-2xs shrink-0">
            <div class="flex items-center gap-3 min-w-0">
              <div class="relative shrink-0">
                @if($activeUser->avatar)
                  <img src="{{ Storage::url($activeUser->avatar) }}" alt="{{ $activeUser->name }}" class="w-10 h-10 rounded-full object-cover border border-slate-200">
                @else
                  <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-sm flex items-center justify-center">
                    {{ strtoupper(substr($activeUser->name, 0, 1)) }}
                  </div>
                @endif
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
              </div>

              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <h2 class="font-extrabold text-sm text-slate-800 truncate">{{ $activeUser->name }}</h2>
                  <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-slate-100 text-slate-600">
                    Comprador
                  </span>
                </div>
                <p class="text-[11px] text-slate-400 truncate flex items-center gap-2 mt-0.5">
                  <span>{{ $activeUser->email }}</span>
                  @if($activeUser->phone)
                    <span>&bull;</span>
                    <span class="font-mono text-slate-600">{{ $activeUser->phone }}</span>
                  @endif
                </p>
              </div>
            </div>

            <!-- Acciones Rápidas con el Cliente -->
            <div class="flex items-center gap-2 shrink-0">
              @if($activeUser->phone)
                <a href="https://wa.me/57{{ preg_replace('/\D/', '', $activeUser->phone) }}?text=Hola {{ urlencode($activeUser->name) }}, te escribimos desde {{ urlencode($merchantUser->companyProfile->company_name ?? 'FusaShop') }} respecto a tu consulta."
                   target="_blank"
                   class="px-3 py-1.5 bg-[#25D366] hover:bg-[#20ba59] text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs"
                   title="Abrir chat en WhatsApp">
                  <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.507 3.927 1.397 5.591L0 24l6.545-1.714A11.943 11.943 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.844 0-3.579-.477-5.095-1.316L2 22l1.333-4.834A9.955 9.955 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                  <span>WhatsApp</span>
                </a>
              @endif

              @if($customerOrders->isNotEmpty())
                <a href="{{ route('merchant.orders') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                   title="Ver pedidos de este cliente">
                  <span class="material-symbols-outlined text-sm text-emerald-600">receipt_long</span>
                  <span>{{ $customerOrders->count() }} Pedido(s)</span>
                </a>
              @endif
            </div>
          </div>

          <!-- BANNER DE PRODUCTO CONSULTADO (SI APLICA) -->
          @if($activeProduct)
            <div class="px-4 py-2.5 bg-emerald-50/70 border-b border-emerald-100 flex items-center justify-between gap-3 text-xs">
              <div class="flex items-center gap-2.5 min-w-0">
                <span class="text-[10px] font-black uppercase text-[#006c47] bg-white px-2 py-0.5 rounded border border-emerald-200 shrink-0">
                  Consulta de Producto
                </span>
                <span class="font-bold text-slate-800 truncate">{{ $activeProduct->name }}</span>
                <span class="font-black text-[#006c47] shrink-0">${{ number_format($activeProduct->price, 0, ',', '.') }} COP</span>
                <span class="text-[10px] text-slate-500 shrink-0">(Stock: {{ $activeProduct->stock }} uds)</span>
              </div>
              <a href="{{ route('consumer.product', $activeProduct->id) }}" target="_blank"
                 class="text-[#006c47] hover:underline font-bold text-[11px] shrink-0 flex items-center gap-0.5">
                Ver en Tienda <span class="material-symbols-outlined text-xs">open_in_new</span>
              </a>
            </div>
          @endif

          <!-- CUERPO DE MENSAJES SCROLLEABLE -->
          <div id="chat-box" class="flex-1 p-4 md:p-6 overflow-y-auto space-y-3.5 bg-slate-50/30" style="max-height: 480px; min-height: 380px;">
            @foreach($activeMessages as $msg)
              @php $mine = $msg->sender_id == auth()->id(); @endphp
              <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-msg-id="{{ $msg->id }}">
                <div class="max-w-[80%] md:max-w-[70%]">
                  <!-- Producto embebido si vino con el mensaje -->
                  @if($msg->product_id && $msg->product)
                    <div class="mb-1.5 rounded-xl bg-white border border-slate-200 p-2 flex gap-2 items-center shadow-2xs">
                      <div class="w-8 h-8 rounded-lg overflow-hidden shrink-0 bg-slate-100 border border-slate-200">
                        @if($msg->product->image_url ?? $msg->product->primary_image_url)
                          <img src="{{ asset($msg->product->image_url ?? $msg->product->primary_image_url) }}" alt="{{ $msg->product->name }}" class="w-full h-full object-cover">
                        @else
                          <span class="material-symbols-outlined text-slate-400 text-sm">inventory_2</span>
                        @endif
                      </div>
                      <div class="min-w-0 text-left">
                        <p class="text-[10px] font-bold text-slate-700 truncate">{{ $msg->product->name }}</p>
                        <p class="text-[10px] text-[#006c47] font-black">${{ number_format($msg->product->price, 0, ',', '.') }} COP</p>
                      </div>
                    </div>
                  @endif

                  <!-- Burbuja de Mensaje -->
                  <div class="p-3.5 rounded-2xl shadow-2xs relative group {{ $mine ? 'bg-gradient-to-r from-[#006c47] to-[#10B981] text-white rounded-tr-xs' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-xs' }}">
                    <p class="text-xs md:text-sm leading-relaxed whitespace-pre-wrap">{{ e($msg->content) }}</p>
                    <div class="flex items-center justify-end gap-1 mt-1 {{ $mine ? 'text-white/70' : 'text-slate-400' }} text-[9.5px]">
                      <span>{{ $msg->created_at->format('H:i') }}</span>
                      @if($mine)
                        <span class="material-symbols-outlined text-[12px] text-emerald-200">done_all</span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            @endforeach

            <!-- Indicador de tipeo / recepción -->
            <div id="typing-indicator" class="flex justify-start hidden">
              <div class="bg-white border border-slate-200 px-4 py-2 rounded-2xl flex items-center gap-1.5 shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.15s"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.3s"></span>
              </div>
            </div>
          </div>

          <!-- ÁREA DE ENTRADA Y RESPUESTAS RÁPIDAS -->
          <div class="p-4 border-t border-slate-200/80 bg-white space-y-3 shrink-0">
            <!-- Respuestas rápidas predefinidas para comerciantes -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-[11px] scrollbar-none">
              <span class="text-[10px] font-extrabold uppercase text-slate-400 shrink-0">Respuestas rápidas:</span>
              <button type="button" @click="quickReply('¡Hola! Con gusto, sí tenemos disponibilidad inmediata.')"
                      class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-medium shrink-0 transition-colors border border-slate-200/60">
                👋 Disponibilidad inmediata
              </button>
              <button type="button" @click="quickReply('El despacho se realiza hoy mismo a través de Envia.com.')"
                      class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-medium shrink-0 transition-colors border border-slate-200/60">
                🚚 Despacho por Envia.com
              </button>
              <button type="button" @click="quickReply('Tu pedido ya está siendo preparado en bodega y empaquetado.')"
                      class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-medium shrink-0 transition-colors border border-slate-200/60">
                📦 Pedido en preparación
              </button>
              <button type="button" @click="quickReply('¡Muchas gracias por elegirnos! ¿En qué más podemos ayudarte?')"
                      class="px-2.5 py-1 rounded-full bg-slate-100 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-medium shrink-0 transition-colors border border-slate-200/60">
                ✨ Agradecimiento
              </button>
            </div>

            <!-- Formulario de envío -->
            <div class="flex items-end gap-2.5 relative">
              <div class="flex-1 relative">
                <textarea id="msg-input" rows="2"
                          placeholder="Escribe tu respuesta al cliente... (Presiona Enter para enviar)"
                          class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs md:text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/40 text-slate-800 resize-none transition-all"
                          oninput="checkProfanity()"></textarea>
                
                <div id="profanity-warning" class="hidden absolute -top-8 left-0 right-0 bg-rose-100 text-rose-700 text-[10px] px-3 py-1 rounded-xl border border-rose-200 font-bold">
                  Lenguaje inapropiado detectado. Por favor mantén una comunicación respetuosa.
                </div>
              </div>

              <button type="button" onclick="handleSendMessage()" id="btn-send-msg"
                      class="px-4 py-3 bg-[#006c47] hover:bg-[#047857] text-white font-bold text-xs rounded-2xl shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center gap-1.5 shrink-0 h-11">
                <span class="material-symbols-outlined text-base" id="icon-send">send</span>
                <span class="material-symbols-outlined text-base hidden animate-spin" id="icon-sync">sync</span>
                <span class="hidden sm:inline">Responder</span>
              </button>
            </div>
          </div>
        @else
          <!-- SELECCIONAR CONVERSACIÓN -->
          <div class="flex-1 flex flex-col items-center justify-center p-12 text-center text-slate-400">
            <span class="material-symbols-outlined text-4xl mb-2 text-slate-300">chat</span>
            <p class="font-bold text-slate-600 text-sm">Selecciona una conversación de la izquierda</p>
            <p class="text-xs text-slate-400 mt-1">Haz clic en un cliente para ver el historial y responder sus inquietudes.</p>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>

@if($activeUser)
  @push('scripts')
  <script>
  if (typeof chatLastTs === 'undefined') {
      var chatLastTs = {{ now()->timestamp }};
  }

  const chatBox = document.getElementById('chat-box');
  const inputField = document.getElementById('msg-input');
  const sendBtn = document.getElementById('btn-send-msg');
  const iconSend = document.getElementById('icon-send');
  const iconSync = document.getElementById('icon-sync');

  const receiverId = {{ $activeUser->id }};
  const pollUrl = '{{ route('chat.poll', $activeUser->id) }}';
  const storeUrl = '{{ route('chat.store') }}';
  const productId = {{ $activeProduct ? $activeProduct->id : 'null' }};
  const csrfToken = '{{ csrf_token() }}';
  const badWords = @json($badWords);

  function checkProfanity() {
      if (!inputField || !sendBtn) return;
      const content = inputField.value.toLowerCase();
      const warning = document.getElementById('profanity-warning');
      
      const hasBadWord = badWords.some(word => {
          const regex = new RegExp('\\b' + word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i');
          return regex.test(content);
      });

      if (hasBadWord) {
          sendBtn.disabled = true;
          sendBtn.classList.add('opacity-50', 'grayscale');
          warning?.classList.remove('hidden');
      } else {
          sendBtn.disabled = false;
          sendBtn.classList.remove('opacity-50', 'grayscale');
          warning?.classList.add('hidden');
      }
  }

  function scrollToBottom() {
      if (chatBox) {
          chatBox.scrollTop = chatBox.scrollHeight;
      }
  }

  function appendMsg(msg) {
      if (!chatBox) return;
      const existing = document.querySelector(`[data-msg-id="${msg.id}"]`);
      if (existing) return;

      const div = document.createElement('div');
      div.className = 'flex ' + (msg.mine ? 'justify-end' : 'justify-start');
      div.setAttribute('data-msg-id', msg.id);

      let productHtml = '';
      if (msg.product) {
          productHtml = `
              <div class="mb-1.5 rounded-xl bg-white border border-slate-200 p-2 flex gap-2 items-center shadow-2xs">
                  <div class="min-w-0 text-left">
                      <p class="text-[10px] font-bold text-slate-700 truncate">${msg.product.name}</p>
                      <p class="text-[10px] text-[#006c47] font-black">$${msg.product.price} COP</p>
                  </div>
              </div>`;
      }

      div.innerHTML = `
          <div class="max-w-[80%] md:max-w-[70%]">
              ${productHtml}
              <div class="p-3.5 rounded-2xl shadow-2xs relative group ${msg.mine ? 'bg-gradient-to-r from-[#006c47] to-[#10B981] text-white rounded-tr-xs' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-xs'}">
                  <p class="text-xs md:text-sm leading-relaxed whitespace-pre-wrap">${msg.content}</p>
                  <div class="flex items-center justify-end gap-1 mt-1 ${msg.mine ? 'text-white/70' : 'text-slate-400'} text-[9.5px]">
                      <span>${msg.time}</span>
                      ${msg.mine ? '<span class="material-symbols-outlined text-[12px] text-emerald-200">done_all</span>' : ''}
                  </div>
              </div>
          </div>`;

      const typingEl = document.getElementById('typing-indicator');
      chatBox.insertBefore(div, typingEl);
  }

  async function handleSendMessage() {
      if (!inputField || !sendBtn || sendBtn.disabled) return;
      const content = inputField.value.trim();
      if (!content) return;

      inputField.value = '';
      iconSend?.classList.add('hidden');
      iconSync?.classList.remove('hidden');
      sendBtn.disabled = true;

      try {
          const res = await fetch(storeUrl, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrfToken,
                  'Accept': 'application/json'
              },
              body: JSON.stringify({ 
                  receiver_id: receiverId, 
                  content: content, 
                  product_id: productId 
              })
          });

          if (res.ok) {
              const msg = await res.json();
              appendMsg({
                  id: msg.id,
                  content: msg.content,
                  mine: true,
                  time: new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' })
              });
              scrollToBottom();
          }
      } catch (e) {
          console.error("Error al enviar mensaje:", e);
          inputField.value = content;
      } finally {
          iconSend?.classList.remove('hidden');
          iconSync?.classList.add('hidden');
          checkProfanity();
          inputField.focus();
      }
  }

  async function poll() {
      try {
          const res = await fetch(`${pollUrl}?since=${chatLastTs}`, {
              headers: { 'Accept': 'application/json' }
          });
          const data = await res.json();
          if (data.messages && data.messages.length > 0) {
              data.messages.forEach(appendMsg);
              scrollToBottom();
          }
          if (data.server_time) chatLastTs = data.server_time;
      } catch (e) {}
  }

  if (inputField) {
      inputField.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              handleSendMessage();
          }
      });
  }

  scrollToBottom();
  setInterval(poll, 3000);
  </script>
  @endpush
@endif
@endsection
