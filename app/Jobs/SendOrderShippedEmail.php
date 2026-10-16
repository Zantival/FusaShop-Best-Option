<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesIntegrationFailure;
use App\Mail\OrderShippedMail;
use App\Models\IntegrationLog;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderShippedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesIntegrationFailure;

    public function __construct(public int $orderId)
    {
    }

    protected function service(): string { return 'resend'; }
    protected function action(): string { return 'mail.order_shipped'; }

    public function handle(): void
    {
        $order = Order::with('user')->findOrFail($this->orderId);
        if (!$order->tracking_number) {
            return;
        }
        Mail::to($order->user->email)->send(new OrderShippedMail($order));
        IntegrationLog::success('resend', 'mail.order_shipped', $order->id, ['to' => $order->user->email]);
    }
}
