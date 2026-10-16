@extends('layouts.app')
@section('title', 'Gestión de PQRS')
@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-['Manrope'] font-bold text-on-background">Centro de Gestión de PQRS</h1>
        <p class="text-on-surface-variant">Atiende las solicitudes de los usuarios de la plataforma.</p>
    </div>

    <div class="bg-surface-container-lowest rounded-3xl shadow-card overflow-hidden border border-outline-variant/30">
        <table class="w-full text-left">
            <thead class="bg-surface-container-low border-b border-outline-variant/20">
                <tr>
                    <th class="px-6 py-4 text-xs font-black text-on-surface-variant uppercase">Usuario</th>
                    <th class="px-6 py-4 text-xs font-black text-on-surface-variant uppercase">Tipo / Asunto</th>
                    <th class="px-6 py-4 text-xs font-black text-on-surface-variant uppercase text-center">Estado</th>
                    <th class="px-6 py-4 text-xs font-black text-on-surface-variant uppercase text-right">Fecha</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/10">
                @foreach($pqrs as $item)
                <tr class="hover:bg-surface-container-low/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                                {{ strtoupper(substr($item->user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-on-surface truncate">{{ e($item->user->name) }}</p>
                                <p class="text-[10px] text-on-surface-variant">{{ e($item->user->role) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-[9px] font-black uppercase tracking-widest text-primary mb-1 block">{{ $item->type }}</span>
                        <p class="text-sm font-medium text-on-surface truncate max-w-xs">{{ e($item->subject) }}</p>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider
                            @if($item->status == 'pending') bg-amber-100 text-amber-700 
                            @elseif($item->status == 'resolved') bg-green-100 text-green-700 
                            @else bg-blue-100 text-blue-700 @endif">
                            {{ $item->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <p class="text-xs text-on-surface-variant">{{ $item->created_at->format('d/m/Y') }}</p>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick="openPqrsModal({{ $item->id }}, '{{ e($item->subject) }}', '{{ e($item->content) }}', '{{ e($item->admin_response) }}', '{{ $item->status }}')" 
                                class="p-2 text-primary hover:bg-primary/10 rounded-lg transition-all">
                            <span class="material-symbols-outlined">edit_note</span>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($pqrs->isEmpty())
            <div class="p-20 text-center text-on-surface-variant/40">Sin solicitudes pendientes.</div>
        @endif
    </div>
    <div class="mt-6">{{ $pqrs->links() }}</div>
</div>

{{-- MODAL DE RESPUESTA --}}
<div id="pqrs-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <form id="pqrs-form" method="POST">
            @csrf
            <div class="p-6 border-b border-outline-variant/20 flex items-center justify-between">
                <h2 class="text-xl font-bold text-on-surface">Atender PQRS</h2>
                <button type="button" onclick="closePqrsModal()" class="text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                    <h3 class="text-xs font-black text-on-surface-variant uppercase tracking-widest mb-2">Asunto</h3>
                    <p id="modal-subject" class="font-bold text-on-surface"></p>
                </div>
                <div class="bg-surface-container-low p-4 rounded-xl">
                    <h3 class="text-xs font-black text-on-surface-variant uppercase tracking-widest mb-2">Mensaje del Usuario</h3>
                    <p id="modal-content" class="text-sm text-on-surface leading-relaxed"></p>
                </div>
                <hr class="border-outline-variant/10">
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-2">Respuesta del Administrador</label>
                    <textarea name="admin_response" id="modal-response" rows="5" required
                        class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-4 py-3 text-on-surface focus:ring-2 focus:ring-primary/20 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-on-surface mb-2">Actualizar Estado</label>
                    <select name="status" id="modal-status" class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-4 py-3 text-on-surface outline-none">
                        <option value="in_review">En Revisión</option>
                        <option value="resolved">Resuelto / Solucionado</option>
                        <option value="closed">Cerrado (Sin solución)</option>
                    </select>
                </div>
            </div>
            <div class="p-6 bg-surface-container-low/50 flex justify-end gap-3">
                <button type="button" onclick="closePqrsModal()" class="px-6 py-2.5 text-on-surface-variant font-bold hover:underline">Cancelar</button>
                <button type="submit" class="px-8 py-2.5 bg-primary text-white font-bold rounded-xl shadow-lg hover:opacity-95 transition-all">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPqrsModal(id, subject, content, response, status) {
        document.getElementById('modal-subject').innerText = subject;
        document.getElementById('modal-content').innerText = content;
        document.getElementById('modal-response').value = response;
        document.getElementById('modal-status').value = status;
        document.getElementById('pqrs-form').action = `/admin/pqrs/${id}/reply`;
        document.getElementById('pqrs-modal').classList.remove('hidden');
    }
    function closePqrsModal() {
        document.getElementById('pqrs-modal').classList.add('hidden');
    }
</script>
@endsection
