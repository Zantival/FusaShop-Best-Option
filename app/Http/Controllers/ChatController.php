<?php           

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use App\Events\MessageSent;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        // Lista de conversaciones (últimos mensajes con otros usuarios)
        $userId = Auth::id();
        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->latest()
            ->get()
            ->groupBy(function($msg) use ($userId) {
                return $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
            });

        return view('chat.index', compact('conversations'));
    }

    public function show($otherUserId)
    {
        $userId = Auth::id();
        $otherUser = User::findOrFail($otherUserId);
        
        $messages = Message::where(function($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
            })->orWhere(function($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
            })
            ->with('product')
            ->orderBy('created_at', 'asc')
            ->get();

        // Marcar como leídos
        Message::where('sender_id', $otherUserId)->where('receiver_id', $userId)->update(['is_read' => true]);

        $badWords = Message::getBadWords();

        return view('chat.show', compact('messages', 'otherUser', 'badWords'));
    }

    /**
     * Polling endpoint — devuelve mensajes nuevos desde un timestamp dado
     */
    public function poll($otherUserId, Request $request)
    {
        $userId = Auth::id();
        $since  = $request->input('since', 0); // Unix timestamp

        $messages = Message::where(function($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
            })->orWhere(function($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
            })
            ->where('created_at', '>', \Carbon\Carbon::createFromTimestamp($since))
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($m) use ($userId) {
                return [
                    'id'        => $m->id,
                    'content'   => e($m->content),
                    'mine'      => $m->sender_id === $userId,
                    'time'      => $m->created_at->format('H:i'),
                    'product'   => $m->product ? ['name' => e($m->product->name), 'price' => number_format($m->product->price,0,',','.')] : null,
                ];
            });

        // Marcar como leídos los del otro
        Message::where('sender_id', $otherUserId)->where('receiver_id', $userId)->update(['is_read' => true]);

        return response()->json(['messages' => $messages, 'server_time' => now()->timestamp]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content'     => 'required|string|max:1000',
            'product_id'  => 'nullable|exists:products,id'
        ]);

        $content = strip_tags($request->content);

        // Filtro de groserías (Profanity Filter)
        $badWords = Message::getBadWords();

        foreach ($badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            $content = preg_replace($pattern, str_repeat('*', mb_strlen($word)), $content);
        }

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'content'     => $content,
            'product_id'  => $request->product_id
        ]);

        // Notificar al receptor
        $receiver = User::find($request->receiver_id);
        if($receiver) {
            $receiver->notify(new NewMessageNotification($message));
        }

        broadcast(new MessageSent($message))->toOthers();

        if ($request->wantsJson()) {
            return response()->json($message);
        }

        return back()->with('success', 'Mensaje enviado.');
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        
        if ($message->sender_id === Auth::id()) {
            $message->delete();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false], 403);
    }
}
