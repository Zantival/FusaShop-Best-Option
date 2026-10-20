@extends('layouts.app')
@section('title', 'Soporte Técnico con Analista | FusaShop')

@section('content')
<div class="max-w-6xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6"
     x-data="{
       quickSupportMsg(text) {
         const input = document.getElementById('msg-input');
         if (input) {
           input.value = text;
           input.focus();
           checkProfanity();
         }
       }
     }">

  <!-- HEADER DE SOPORTE TÉCNICO -->
  <div class="bg-white p-6 rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-[#006c47] border border-emerald-200 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">support_agent</span> Mesa de Ayuda Oficial para Comerciantes
        </span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Analista en Línea
        </span>
      </div>
      <h1 class="text-2xl md:text-3xl font-['Manrope'] font-extrabold text-slate-800">
        Soporte Técnico & Comunicación con el Analista
      </h1>
      <p class="text-slate-500 text-xs md:text-sm mt-1">
        Canal directo y exclusivo con la Administración de FusaShop para resolver dudas de KYC/RUT, pagos Wompi, guías de Envia.com, facturación Factus DIAN o aprobación de pautas publicitarias.
      </p>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <a href="{{ route('pqrs.create') }}" class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-[#006c47] border border-emerald-200 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm">assignment_add</span> Radicar Ticket PQRS
      </a>
      <a href="{{ route('merchant.dashboard') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm">dashboard</span> Volver al Tablero
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- COLUMNA IZQUIERDA: LISTA DE ANALISTAS Y TEMAS RÁPIDOS (4/12) -->
    <div class="lg:col-span-4 space-y-4">
      <div class="bg-white rounded-2xl p-4 shadow-[0_8px_24px_rgba(0,0,0,0.04)] border border-slate-200/80 space-y-3">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-sm text-[#006c47]">admin_panel_settings</span>
          Equipo de Analistas FusaShop
        </h2>

        <div class="space-y-2">
          @foreach($analysts as $analystItem)
            @php $isSelected = $admin && $admin->id === $analystItem->id; @endphp
            <a href="{{ route('merchant.support.contact', ['analyst' => $analystItem->id]) }}"
               class="flex items-center gap-3 p-3 rounded-xl transition-all border {{ $isSelected ? 'bg-emerald-50/90 border-emerald-300 shadow-2xs' : 'bg-slate-50/60 hover:bg-white border-slate-200/70' }}">
              <div class="relative shrink-0">
                @if($analystItem->avatar)
                  <img src="{{ Storage::url($analystItem->avatar) }}" alt="{{ $analystItem->name }}" class="w-10 h-10 rounded-full object-cover border-2 border-emerald-500">
                @else
                  <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#006c47] to-[#10B981] text-white font-black text-sm flex items-center justify-center">
                    {{ strtoupper(substr($analystItem->name, 0, 1)) }}
                  </div>
                @endif
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between">
                  <p class="font-extrabold text-xs text-slate-800 truncate">{{ $analystItem->name }}</p>
                  <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-emerald-100 text-[#006c47]">Analista</span>
                </div>
                <p class="text-[11px] text-slate-500 truncate">{{ $analystItem->email }}</p>
              </div>
            </a>
          @endforeach
        </div>
      </div>

      <!-- Motivos frecuentes de consulta al Analista -->
      <div class="bg-white rounded-2xl p-4 shadow-[0_8px_24px_rgba(0,0,0,0.04)] border border-slate-200/80 space-y-2.5">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-sm text-amber-600">bolt</span>
          Consultas Frecuentes al Analista
        </h3>
        <p class="text-[11px] text-slate-400">Haz clic en un tema para autocompletar tu mensaje de soporte:</p>

        <div class="space-y-1.5 text-xs">
          <button type="button"
                  @click="quickSupportMsg('Hola Analista, solicito apoyo con la revisión y validación de mi RUT / KYC comercial.')"
                  class="w-full text-left p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-semibold border border-slate-200/70 transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-base text-[#10B981]">verified_user</span>
            <span>Validación de RUT y Cuenta (KYC)</span>
          </button>

          <button type="button"
                  @click="quickSupportMsg('Hola Analista, tengo una consulta sobre la liquidación de pagos en Wompi y mis comisiones.')"
                  class="w-full text-left p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-semibold border border-slate-200/70 transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-base text-[#10B981]">payments</span>
            <span>Pagos Wompi & Liquidación</span>
          </button>

          <button type="button"
                  @click="quickSupportMsg('Hola Analista, necesito asistencia técnica con una guía de despacho en Envia.com / Factura Factus DIAN.')"
                  class="w-full text-left p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-semibold border border-slate-200/70 transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-base text-[#10B981]">local_shipping</span>
            <span>Guías Envia.com & Factura Factus</span>
          </button>

          <button type="button"
                  @click="quickSupportMsg('Hola Analista, acabo de enviar una solicitud de pauta publicitaria (Banner) para revisión.')"
                  class="w-full text-left p-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-[#006c47] text-slate-700 font-semibold border border-slate-200/70 transition-colors flex items-center gap-2 cursor-pointer">
            <span class="material-symbols-outlined text-base text-[#10B981]">campaign</span>
            <span>Aprobación de Pauta Publicitaria</span>
          </button>
        </div>
      </div>
    </div>

    <!-- COLUMNA DERECHA: VENTANA DE CHAT DIRECTO CON EL ANALISTA (8/12) -->
    <div class="lg:col-span-8 bg-white rounded-2xl shadow-[0_12px_32px_rgba(27,28,28,.06)] border border-slate-200/80 overflow-hidden flex flex-col min-h-[560px]">
      <!-- ENCABEZADO DEL ANALISTA -->
      <div class="p-4 border-b border-slate-200/80 bg-gradient-to-r from-slate-900 to-emerald-950 text-white flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 font-black text-sm flex items-center justify-center shrink-0">
            {{ strtoupper(substr($admin->name, 0, 1)) }}
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h2 class="font-extrabold text-sm truncate">{{ $admin->name }}</h2>
              <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                Analista & Soporte Oficial
              </span>
            </div>
            <p class="text-[11px] text-slate-300 truncate">{{ $admin->email }} · Atención prioritaria a comercios</p>
          </div>
        </div>

        @if($admin->phone)
          <a href="https://wa.me/57{{ preg_replace('/\D/', '', $admin->phone) }}?text=Hola Analista {{ urlencode($admin->name) }}, te escribo desde el comercio {{ urlencode(auth()->user()->companyProfile->company_name ?? auth()->user()->name) }} en FusaShop."
             target="_blank"
             class="px-3 py-1.5 bg-[#25D366] hover:bg-[#20ba59] text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs shrink-0">
            <span>WhatsApp Soporte</span>
          </a>
        @endif
      </div>

      <!-- HISTORIAL DE MENSAJES CON EL ANALISTA -->
      <div id="chat-box" class="flex-1 p-4 md:p-6 overflow-y-auto space-y-3.5 bg-slate-50/40" style="max-height: 440px; min-height: 340px;">
        @if($messages->isEmpty())
          <div id="empty-support-placeholder" class="py-12 text-center space-y-2 text-slate-400">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-[#006c47] flex items-center justify-center mx-auto">
              <span class="material-symbols-outlined text-2xl">support_agent</span>
            </div>
            <p class="text-xs font-bold text-slate-700">Inicia tu conversación con {{ $admin->name }}</p>
            <p class="text-[11px] text-slate-500 max-w-sm mx-auto">
              Escribe tu inquietud técnica o administrativa abajo y el analista recibirá una alerta inmediata en la consola de operaciones.
            </p>
          </div>
        @endif

        @foreach($messages as $msg)
          @php $mine = $msg->sender_id == auth()->id(); @endphp
          <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-msg-id="{{ $msg->id }}">
            <div class="max-w-[80%] md:max-w-[70%]">
              <div class="p-3.5 rounded-2xl shadow-2xs relative {{ $mine ? 'bg-gradient-to-r from-[#006c47] to-[#10B981] text-white rounded-tr-xs' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-xs' }}">
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

        <div id="typing-indicator" class="flex justify-start hidden">
          <div class="bg-white border border-slate-200 px-4 py-2 rounded-2xl flex items-center gap-1.5 shadow-2xs">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.15s"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0.3s"></span>
          </div>
        </div>
      </div>

      <!-- CAJA DE ENVÍO DE MENSAJE AL ANALISTA -->
      <div class="p-4 border-t border-slate-200/80 bg-white space-y-2 shrink-0">
        <div class="flex items-end gap-2.5 relative">
          <div class="flex-1 relative">
            <textarea id="msg-input" rows="2"
                      placeholder="Escribe tu mensaje para el Analista de Soporte Técnico... (Enter para enviar)"
                      class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs md:text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/40 text-slate-800 resize-none transition-all"
                      oninput="checkProfanity()"></textarea>
            <div id="profanity-warning" class="hidden absolute -top-8 left-0 right-0 bg-rose-100 text-rose-700 text-[10px] px-3 py-1 rounded-xl border border-rose-200 font-bold">
              Lenguaje inapropiado detectado. Por favor mantén una comunicación respetuosa.
            </div>
          </div>

          <button type="button" onclick="handleSendMessage()" id="btn-send-msg"
                  class="px-5 py-3 bg-[#006c47] hover:bg-[#047857] text-white font-bold text-xs rounded-2xl shadow-md hover:shadow-lg active:scale-95 transition-all flex items-center justify-center gap-1.5 shrink-0 h-11 cursor-pointer">
            <span class="material-symbols-outlined text-base" id="icon-send">send</span>
            <span class="material-symbols-outlined text-base hidden animate-spin" id="icon-sync">sync</span>
            <span>Enviar al Analista</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

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

const receiverId = {{ $admin->id }};
const pollUrl = '{{ route('chat.poll', $admin->id) }}';
const storeUrl = '{{ route('chat.store') }}';
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
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
}

function appendMsg(msg) {
    if (!chatBox) return;
    const existing = document.querySelector(`[data-msg-id="${msg.id}"]`);
    if (existing) return;

    document.getElementById('empty-support-placeholder')?.remove();

    const div = document.createElement('div');
    div.className = 'flex ' + (msg.mine ? 'justify-end' : 'justify-start');
    div.setAttribute('data-msg-id', msg.id);
    div.innerHTML = `
        <div class="max-w-[80%] md:max-w-[70%]">
            <div class="p-3.5 rounded-2xl shadow-2xs relative ${msg.mine ? 'bg-gradient-to-r from-[#006c47] to-[#10B981] text-white rounded-tr-xs' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-xs'}">
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
                content: content
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
@endsection
