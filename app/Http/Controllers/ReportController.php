<?php

namespace App\Http\Controllers;


use App\Jobs\ProcessDailySalesJob; 
use App\Models\DailySalesReport;
use App\Models\Order;

class ReportController extends Controller
{
    
    public function before()
    {
        return response()->json([
            'orders_count' => Order::whereDate('created_at', today())->count(),
            'total_sales' => Order::whereDate('created_at', today())->sum('total_price'),
        ]);
    }




public function run()
    {
        DailySalesReport::truncate();

        for ($i = 0; $i < 50; $i++) {
            ProcessDailySalesJob::dispatch();
        }

        return response()->json([
            'message' => 'تم تصفير الجدول وشحن الطابور بـ 50 طلب !'
        ]);
    }





   /* public function run()
    {قديمة
        
        ProcessDailySalesJob::dispatch();

        return response()->json([
            'message' => 'Job dispatched'
        ]);
    }*/

    
    public function after()
    {
        return DailySalesReport::where('date', today())->first();
    }
}