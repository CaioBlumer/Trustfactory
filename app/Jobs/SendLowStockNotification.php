<?php

namespace App\Jobs;

use App\Mail\LowStockNotification;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendLowStockNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $productId)
    {
    }

    /**
     * Create a new job instance.
     */
    public function handle(): void
    {
        $product = Product::find($this->productId);

        if (! $product) {
            return;
        }

        Mail::to(config('shop.admin_email'))
            ->send(new LowStockNotification($product));
    }
}
