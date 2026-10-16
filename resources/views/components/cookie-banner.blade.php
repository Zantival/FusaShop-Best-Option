<div x-data="{ 
    show: !getCookie('cookie_consent'),
    accept(type) {
        setCookie('cookie_consent', type, 365);
        this.show = false;
        window.location.reload();
    }
}" 
x-show="show" 
x-transition:enter="transition ease-out duration-500"
x-transition:enter-start="translate-y-full opacity-0"
x-transition:enter-end="translate-y-0 opacity-100"
class="fixed bottom-6 left-6 right-6 md:left-auto md:w-[480px] z-[100] bg-surface-container-lowest p-6 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-outline-variant/20">
    <div class="flex flex-col gap-4">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-3xl">cookie</span>
            </div>
            <div>
                <h3 class="text-lg font-bold text-on-surface">Control de Privacidad</h3>
                <p class="text-sm text-on-surface-variant leading-tight mt-1">
                    Utilizamos cookies para personalizar tu experiencia y analizar nuestro tráfico. ¿Cómo prefieres continuar?
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-2 mt-2">
            <button @click="accept('accepted')" class="w-full py-3 bg-primary-gradient text-white font-bold rounded-xl shadow-sm hover:opacity-90 active:scale-[0.98] transition-all text-sm">
                Aceptar todas las cookies
            </button>
            <div class="flex gap-2">
                <button @click="accept('essential')" class="flex-1 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-xl hover:bg-surface-dim active:scale-[0.98] transition-all text-sm">
                    Solo esenciales
                </button>
                <a href="{{ route('privacy') }}" target="_blank" class="flex-1 py-2.5 text-primary font-semibold rounded-xl border border-primary/20 flex items-center justify-center gap-1 hover:bg-primary/5 transition-all text-sm">
                    Ver política
                    <span class="material-symbols-outlined text-xs">open_in_new</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}
function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0;i < ca.length;i++) {
        let c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
</script>
