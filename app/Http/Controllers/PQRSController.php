<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\PQRS;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PQRSController extends Controller
{
    // --- Lado Usuario (Consumidor/Comerciante) ---
    public function index()
    {
        $pqrs = PQRS::where('user_id', Auth::id())->latest()->paginate(10);
        return view('pqrs.index', compact('pqrs'));
    }

    public function create()
    {
        return view('pqrs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'     => 'required|in:peticion,queja,reclamo,sugerencia',
            'category' => 'nullable|in:envio,producto,pago,comercio,plataforma',
            'priority' => 'nullable|in:baja,media,alta,urgente',
            'subject'  => 'required|string|max:255',
            'content'  => 'required|string|max:2000',
        ]);

        $slaDays = Setting::pqrsSlaBusinessDays();

        $pqrs = PQRS::create([
            'user_id'              => Auth::id(),
            'type'                 => $data['type'],
            'category'             => $data['category'] ?? 'plataforma',
            'priority'             => $data['priority'] ?? ($data['type'] === 'reclamo' ? 'alta' : 'media'),
            'subject'              => strip_tags($data['subject']),
            'content'              => strip_tags($data['content']),
            'status'               => 'pending',
            'due_at'               => now()->addWeekdays($slaDays),
            'conversation_history' => [
                [
                    'sender' => Auth::user()->name,
                    'role'   => Auth::user()->role,
                    'text'   => strip_tags($data['content']),
                    'date'   => now()->format('d/m/Y H:i'),
                ],
            ],
        ]);

        $analysts = User::where('role', 'analyst')->get();
        foreach ($analysts as $analyst) {
            $analyst->notify(new \App\Notifications\NewPQRSNotification($pqrs));
        }

        return redirect()->route('pqrs.index')->with('success', "Tu solicitud ha sido radicada (SLA legal máximo: {$slaDays} días hábiles).");
    }

    public function show(PQRS $pqrs)
    {
        if ($pqrs->user_id !== Auth::id() && !Auth::user()->isAnalyst()) {
            abort(403);
        }
        return view('pqrs.show', compact('pqrs'));
    }

    // --- Lado Administrador (Analyst / Support) ---
    public function adminIndex(Request $request)
    {
        $query = PQRS::with(['user', 'assignee']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $pqrs = $query->latest()->paginate(20)->withQueryString();

        // Métricas SLA y categorías (§2.6)
        $allPqrs = PQRS::all();
        $openCount = $allPqrs->whereIn('status', ['pending', 'in_review'])->count();
        $overdueCount = $allPqrs->filter(fn ($p) => $p->slaInfo()['status'] === 'vencido')->count();
        $byCategory = $allPqrs->groupBy(fn ($p) => $p->category ?? 'plataforma')->map->count();

        return view('analyst.pqrs.index', compact('pqrs', 'openCount', 'overdueCount', 'byCategory'));
    }

    public function adminReply(Request $request, PQRS $pqrs)
    {
        $request->validate([
            'admin_response' => 'required|string|max:2000',
            'status'         => 'required|in:in_review,resolved,closed',
            'category'       => 'nullable|in:envio,producto,pago,comercio,plataforma',
            'priority'       => 'nullable|in:baja,media,alta,urgente',
        ]);

        $cleanResponse = strip_tags($request->admin_response);
        $history = $pqrs->conversation_history ?? [];
        $history[] = [
            'sender' => Auth::user()->name . ' (Soporte FusaShop)',
            'role'   => 'analyst',
            'text'   => $cleanResponse,
            'date'   => now()->format('d/m/Y H:i'),
        ];

        $pqrs->update([
            'admin_response'       => $cleanResponse,
            'status'               => $request->status,
            'category'             => $request->category ?? ($pqrs->category ?? 'plataforma'),
            'priority'             => $request->priority ?? ($pqrs->priority ?? 'media'),
            'assigned_to'          => Auth::id(),
            'resolved_at'          => in_array($request->status, ['resolved', 'closed'], true) ? now() : $pqrs->resolved_at,
            'conversation_history' => $history,
        ]);

        AdminAuditLog::record('pqrs.replied', $pqrs, "Respuesta oficial PQRS #{$pqrs->id} ({$request->status})");

        if ($pqrs->user) {
            $pqrs->user->notify(new \App\Notifications\PQRSResponseNotification($pqrs));
        }

        return back()->with('success', 'Respuesta enviada, historial actualizado y usuario notificado.');
    }
}
