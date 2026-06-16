<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\DailySalesReport;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessDailySalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        try {

            $totalSales = 0;
            $totalOrders = 0;

            Order::query()
                ->chunk(100, function ($orders) use (&$totalSales, &$totalOrders) {

                    foreach ($orders as $order) {
                        $totalSales += $order->total_price;
                        $totalOrders++;
                    }

                });

            
            DailySalesReport::updateOrCreate(
                ['date' => today()],
                [
                    'total_sales' => $totalSales,
                    'total_orders' => $totalOrders
                ]
            );

            Log::info('Daily Sales Report Generated', [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
            ]);

        } catch (\Throwable $e) {

            Log::error('Daily Sales Report Failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}