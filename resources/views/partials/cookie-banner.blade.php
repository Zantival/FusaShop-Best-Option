@if(!request()->hasCookie('cookie_consent') && !session('cookie_consent'))
<div id="cookie-banner" class="cookie-banner">
  <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-start sm:items-center gap-4">
    <div class="flex items-start gap-3 flex-1">
      <span class="material-symbols-outlined text-[#feb700] text-2xl shrink-0 mt-0.5">cookie</span>
      <div>
        <p class="font-bold text-white text-sm">Usamos cookies para mejorar tu experiencia</p>
        <p class="text-white/70 text-xs mt-0.5">
          Usamos cookies para personalizar contenido, analizar tráfico y mejorar nuestros servicios.
          <a href="{{ route('privacy') }}" class="text-[#6efcb9] underline hover:no-underline">Política de Privacidad</a>
        </p>
      </div>
    </div>
    <div class="flex items-center gap-3 shrink-0 w-full sm:w-auto">
      <button onclick="rejectCookies()" class="flex-1 sm:flex-none px-4 py-2 bg-white/10 text-white text-xs font-semibold rounded-lg hover:bg-white/20 transition-colors border border-white/20">
        Rechazar
      </button>
      <button onclick="acceptCookies()" class="flex-1 sm:flex-none px-5 py-2 bg-[#00b67a] text-white text-xs font-bold rounded-lg hover:opacity-90 transition-all">
        Aceptar todas
      </button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(() => document.getElementById('cookie-banner').classList.add('visible'), 1000);
});
function acceptCookies() {
  fetch('/cookie-consent', {method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Content-Type':'application/json'},body:JSON.stringify({consent:true})})
    .then(() => hideCookieBanner(true));
}
function rejectCookies() {
  fetch('/cookie-consent', {method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Content-Type':'application/json'},body:JSON.stringify({consent:false})})
    .then(() => hideCookieBanner(false));
}
function hideCookieBanner(consentValue) {
  if (consentValue !== undefined) {
    document.cookie = "cookie_consent=" + consentValue + "; path=/; max-age=31536000";
  }
  const b = document.getElementById('cookie-banner');
  if (b) {
    b.style.transform = 'translateY(100%)';
    setTimeout(() => b.remove(), 500);
  }
}
</script>
@endif
