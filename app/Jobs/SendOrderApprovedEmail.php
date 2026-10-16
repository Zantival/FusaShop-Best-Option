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
        $order = Order::with('items.product', 'user')->findOrFail($this->orderId);
        Mail::to($order->user->email)->send(new OrderApprovedMail($order));
        IntegrationLog::success('resend', 'mail.order_approved', $order->id, ['to' => $order->user->email]);
    }
}
