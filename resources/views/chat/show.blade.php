@extends('layouts.app')
@section('title', 'Chat con ' . $otherUser->name)
@section('content')

<div class="max-w-2xl mx-auto px-4 py-4 flex flex-col" style="height: calc(100vh - 8rem)">

  <!-- Header -->
  <div class="flex items-center gap-3 mb-4 pb-4 border-b border-surface-container">
    <a href="{{ route('chat.index') }}" class="p-2 hover:bg-surface-container rounded-full transition-colors text-on-surface-variant">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    @if($otherUser->avatar)
      <img src="{{ Storage::url($otherUser->avatar) }}" class="w-10 h-10 rounded-full object-cover">
    @else
      <div class="w-10 h-10 bg-greenhouse-gradient rounded-full flex items-center justify-center text-white font-bold shrink-0">
        {{ strtoupper(substr($otherUser->name, 0, 1)) }}
      </div>
    @endif
    <div class="flex-1 min-w-0">
      <h1 class="font-black text-on-surface leading-tight truncate">{{ e($otherUser->name) }}</h1>
      <p class="text-[10px] text-primary font-bold uppercase tracking-wider">{{ ucfirst($otherUser->role) }}</p>
    </div>
    @if($otherUser->companyProfile?->phone)
      <a href="https://wa.me/57{{ preg_replace('/\D/', '', $otherUser->companyProfile->phone) }}?text=Hola, te escribo desde FusaShop"
         target="_blank"
         class="w-9 h-9 bg-[#25D366] text-white rounded-full flex items-center justify-center hover:opacity-90 transition-opacity shrink-0"
         title="Abrir en WhatsApp">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.507 3.927 1.397 5.591L0 24l6.545-1.714A11.943 11.943 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.844 0-3.579-.477-5.095-1.316L2 22l1.333-4.834A9.955 9.955 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
      </a>
    @endif
  </div>

  <!-- Messages area -->
  <div id="chat-box" class="flex-1 overflow-y-auto space-y-3 pr-1 scrollbar-hide">
    @foreach($messages as $msg)
      @php $mine = $msg->sender_id == auth()->id(); @endphp
      <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-msg-id="{{ $msg->id }}">
        <div class="max-w-[78%]">
          @if($msg->product_id && $msg->product)
            <div class="mb-1.5 rounded-2xl bg-surface-container-low border border-surface-container p-2.5 flex gap-2.5 items-center">
              <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0 bg-white border border-surface-container">
                <x-product-image :product="$msg->product" class="w-full h-full object-cover"/>
              </div>
              <div class="min-w-0">
                <p class="text-[10px] font-bold text-on-surface-variant truncate">{{ e($msg->product->name) }}</p>
                <p class="text-[10px] text-primary font-black">${{ number_format($msg->product->price,0,',','.') }}</p>
              </div>
            </div>
          @endif
          <div class="{{ $mine ? 'chat-bubble-mine' : 'chat-bubble-other' }} px-4 py-2.5 relative group">
            <p class="text-sm leading-relaxed">{{ e($msg->content) }}</p>
            <p class="text-[9px] mt-1 text-right {{ $mine ? 'text-white/60' : 'text-on-surface-variant/60' }}">{{ $msg->created_at->format('H:i') }}</p>
            @if($mine)
              <button onclick="deleteMessage({{ $msg->id }}, this)" class="absolute -left-8 top-1/2 -translate-y-1/2 p-1.5 bg-red-100 text-red-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity hidden md:flex items-center justify-center shadow-sm" title="Eliminar mensaje">
                <span class="material-symbols-outlined text-[14px]">delete</span>
              </button>
              <button onclick="deleteMessage({{ $msg->id }}, this)" class="md:hidden mt-2 text-[10px] text-white/70 hover:text-white flex items-center justify-end w-full gap-1">
                <span class="material-symbols-outlined text-[12px]">delete</span> Eliminar
              </button>
            @endif
          </div>
        </div>
      </div>
    @endforeach

    <!-- Typing indicator (hidden by default) -->
    <div id="typing-indicator" class="flex justify-start hidden">
      <div class="chat-bubble-other px-4 py-3 flex items-center gap-1">
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
      </div>
    </div>
  </div>

  <!-- Input form -->
  <div class="mt-3 pt-3 border-t border-surface-container">
    <div class="flex items-end gap-2">
      <div class="flex-1 relative">
        <textarea id="msg-input" rows="1"
          placeholder="Escribe un mensaje..."
          class="w-full px-4 py-3 bg-surface-container-low rounded-2xl border-2 border-transparent focus:border-primary outline-none resize-none text-sm transition-all"
          style="max-height: 120px;"
          oninput="this.style.height='auto'; this.style.height=this.scrollHeight+'px'; checkProfanity()"></textarea>
        <div id="profanity-warning" class="hidden absolute -top-8 left-0 right-0 bg-red-100 text-red-600 text-[10px] px-3 py-1 rounded-t-xl border border-red-200 font-bold animate-fadeIn">
          Palabra no permitida detectada. Por favor, modera tu lenguaje.
        </div>
      </div>
      <button onclick="handleSendMessage()" id="btn-send-msg"
        class="w-11 h-11 bg-greenhouse-gradient text-white rounded-2xl flex items-center justify-center shadow-md active:scale-95 transition-all shrink-0">
        <span class="material-symbols-outlined text-[20px]" id="icon-send">send</span>
        <span class="material-symbols-outlined text-[18px] hidden" id="icon-sync" style="animation: spin 1s linear infinite;">sync</span>
      </button>
    </div>
  </div>
</div>

@push('scripts')
<script>
// Use a unique name to avoid 'already declared' errors
if (typeof chatLastTs === 'undefined') {
    var chatLastTs = {{ now()->timestamp }};
}

const chatBox = document.getElementById('chat-box');
const inputField = document.getElementById('msg-input');
const sendBtn = document.getElementById('btn-send-msg');
const iconSend = document.getElementById('icon-send');
const iconSync = document.getElementById('icon-sync');

const receiverId = {{ $otherUser->id }};
const pollUrl = '{{ route('chat.poll', $otherUser->id) }}';
const storeUrl = '{{ route('chat.store') }}';
const productId = {{ request('product_id', 'null') }};
const csrfToken = '{{ csrf_token() }}';
const badWords = @json($badWords);

function checkProfanity() {
    const content = inputField.value.toLowerCase();
    const warning = document.getElementById('profanity-warning');
    
    // Check if any bad word is present
    const hasBadWord = badWords.some(word => {
        // Simple word boundary check in JS
        const regex = new RegExp('\\b' + word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i');
        return regex.test(content);
    });

    if (hasBadWord) {
        sendBtn.disabled = true;
        sendBtn.classList.add('opacity-50', 'grayscale');
        warning.classList.remove('hidden');
    } else {
        sendBtn.disabled = false;
        sendBtn.classList.remove('opacity-50', 'grayscale');
        warning.classList.add('hidden');
    }
}

function scrollToBottom() {
    chatBox.scrollTop = chatBox.scrollHeight;
}

function appendMsg(msg) {
    const existing = document.querySelector(`[data-msg-id="${msg.id}"]`);
    if (existing) return;

    const div = document.createElement('div');
    div.className = 'flex ' + (msg.mine ? 'justify-end' : 'justify-start');
    div.setAttribute('data-msg-id', msg.id);

    let productHtml = '';
    if (msg.product) {
        productHtml = `
            <div class="mb-1.5 rounded-2xl bg-surface-container-low border border-surface-container p-2.5 flex gap-2.5 items-center">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-on-surface-variant truncate">${msg.product.name}</p>
                    <p class="text-[10px] text-primary font-black">$${msg.product.price}</p>
                </div>
            </div>`;
    }

    let deleteHtml = '';
    if (msg.mine) {
        deleteHtml = `
            <button onclick="deleteMessage(${msg.id}, this)" class="absolute -left-8 top-1/2 -translate-y-1/2 p-1.5 bg-red-100 text-red-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity hidden md:flex items-center justify-center shadow-sm" title="Eliminar mensaje">
                <span class="material-symbols-outlined text-[14px]">delete</span>
            </button>
            <button onclick="deleteMessage(${msg.id}, this)" class="md:hidden mt-2 text-[10px] text-white/70 hover:text-white flex items-center justify-end w-full gap-1">
                <span class="material-symbols-outlined text-[12px]">delete</span> Eliminar
            </button>
        `;
    }

    div.innerHTML = `
        <div class="max-w-[78%]">
            ${productHtml}
            <div class="${msg.mine ? 'chat-bubble-mine' : 'chat-bubble-other'} px-4 py-2.5 animate-fadeIn relative group">
                <p class="text-sm leading-relaxed">${msg.content}</p>
                <p class="text-[9px] mt-1 text-right ${msg.mine ? 'text-white/60' : 'text-on-surface-variant/60'}">${msg.time}</p>
                ${deleteHtml}
            </div>
        </div>`;

    const typingEl = document.getElementById('typing-indicator');
    chatBox.insertBefore(div, typingEl);
}

async function handleSendMessage() {
    if (sendBtn.disabled) return;
    const content = inputField.value.trim();
    if (!content) return;

    // UI Feedback
    inputField.value = '';
    inputField.style.height = 'auto';
    iconSend.classList.add('hidden');
    iconSync.classList.remove('hidden');
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
        console.error("Error sending message:", e);
        inputField.value = content; // restore on error
    } finally {
        iconSend.classList.remove('hidden');
        iconSync.classList.add('hidden');
        checkProfanity();
        inputField.focus();
    }
}

async function deleteMessage(id, btn) {
    if(!confirm('¿Estás seguro de que deseas eliminar este mensaje?')) return;
    
    // Optimistic UI update
    const msgDiv = btn.closest('[data-msg-id]');
    if (msgDiv) msgDiv.style.display = 'none';

    try {
        const res = await fetch(`/chat/message/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        if (!res.ok) {
            // Revert if failed
            if (msgDiv) msgDiv.style.display = 'flex';
            alert('No se pudo eliminar el mensaje');
        }
    } catch (e) {
        if (msgDiv) msgDiv.style.display = 'flex';
        console.error(e);
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

// Enter to send
inputField.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSendMessage();
    }
});

// Initial scroll and start polling
scrollToBottom();
setInterval(poll, 3000);
</script>
@endpush
@endsection
