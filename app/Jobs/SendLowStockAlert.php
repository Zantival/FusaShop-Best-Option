<?php

namespace App\Jobs;

use App\Jobs\Concerns\HandlesIntegrationFailure;
use App\Mail\LowStockAlertMail;
use App\Models\IntegrationLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLowStockAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesIntegrationFailure;

    public ?int $orderId = null;

    public function __construct(public int $productId)
    {
    }

    protected function service(): string { return 'resend'; }
    protected function action(): string { return 'mail.low_stock'; }

    public function handle(): void
    {
        $product = Product::with('merchant')->find($this->productId);
        if (!$product || !$product->isLowStock()) {
            return;
        }

        $recipients = User::where('role', 'analyst')->pluck('email')->push($product->merchant?->email)->filter()->unique();
        foreach ($recipients as $email) {
            Mail::to($email)->send(new LowStockAlertMail($product));
        }
        IntegrationLog::success('resend', 'mail.low_stock', null, ['product_id' => $product->id, 'to' => $recipients->all()]);
    }
}
