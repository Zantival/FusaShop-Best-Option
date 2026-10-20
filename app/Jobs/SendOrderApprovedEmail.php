<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesIntegrationFailure;
use App\Mail\OrderApprovedMail;
use App\Models\IntegrationLog;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderApprovedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesIntegrationFailure;

    public function __construct(public int $orderId)
    {
    }

    protected function service(): string { return 'resend'; }
    protected function action(): string { return 'mail.order_approved'; }

    public function handle(): void
    {
        $order = Order::with('items.product.merchant', 'user')->findOrFail($this->orderId);
        $recipients = [];

        // 1. Consumidor (Cliente)
        if ($order->user?->email) {
            Mail::to($order->user->email)->send(new OrderApprovedMail($order, 'consumer', $order->user->name));
            $recipients[] = $order->user->email;
        }

        // 2. Vendedor(es) / Comerciante(s) del pedido
        $merchants = $order->items
            ->map(fn ($item) => $item->product?->merchant)
            ->filter(fn ($m) => $m && !empty($m->email))
            ->unique('id');

        foreach ($merchants as $merchant) {
            if (!in_array($merchant->email, $recipients, true)) {
                Mail::to($merchant->email)->send(new OrderApprovedMail($order, 'merchant', $merchant->name));
                $recipients[] = $merchant->email;
            }
        }

        // 3. E-commerce / Administración
        $admins = \App\Models\User::where('role', 'analyst')->whereNotNull('email')->get();
        foreach ($admins as $admin) {
            if (!in_array($admin->email, $recipients, true)) {
                Mail::to($admin->email)->send(new OrderApprovedMail($order, 'admin', $admin->name));
                $recipients[] = $admin->email;
            }
        }

        IntegrationLog::success('resend', 'mail.order_approved', $order->id, ['to' => $recipients]);
    }
}
