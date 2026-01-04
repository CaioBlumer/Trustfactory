<?php

namespace App\Jobs;

use App\Mail\DailySalesReport;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDailySalesReport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $items = OrderItem::query()
            ->with('product:id,name')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $summary = $items
            ->groupBy('product_id')
            ->map(function ($group) {
                $product = $group->first()->product;

                return [
                    'name' => $product?->name ?? 'Unknown',
                    'quantity' => $group->sum('quantity'),
                    'total' => $group->sum('subtotal'),
                ];
            })
            ->values()
            ->all();

        Mail::to(config('shop.admin_email'))
            ->send(new DailySalesReport($summary, $start));
    }
}
