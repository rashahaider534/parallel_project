<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class ProductController extends Controller
{
    public function getproducts($shop_id = null)
    {
        if ($shop_id == null) {
            $resulte = Product::paginate(6);
            return response()->json(['products' => $resulte], 200);
        } else {
            $resulte = Product::where('shop_id', $shop_id)->paginate(6);
            return response()->json(['products' => $resulte], 200);
        }
    }

    public function addproduct(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:products|max:10',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'description' => 'nullable|string'
        ]);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function removeproduct($product_id = null)
    {
        $currentproduct = Product::findorfail($product_id);
        if (!$currentproduct) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $currentproduct->delete();
        return response()->json(['message' => 'Deleted successfully'], 200);
    }

    public function update(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
        $product->update(
            [
                'name' => $request->name,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'discription' => $request->discription,
            ]
        );
        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ], 200);
    }

    public function simulateLoadBalancer(Request $request)
    {
        $servers = [
            'http://127.0.0.1:8001/api/process-task',
            'http://127.0.0.1:8002/api/process-task',
            'http://127.0.0.1:8003/api/process-task'
        ];
        if ($request->has('use_lb') && $request->use_lb == 0) {
            try {
                $response = Http::get($servers[0], ['delay' => 1]);
                $data = $response->json();
                return response()->json([
                    'status' => 'BEFORE (No Load Balancer)',
                    'forwarded_to' => $servers[0],
                    'node_output' => $data['message'] ?? 'Task Handled by node on port 8001',
                    'server_status' => "Overloaded High stress on this node.",
                    'message' => 'All requests are forced to a single node '
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'BEFORE (No Load Balancer - Fallback)',
                    'forwarded_to' => $servers[0],
                    'node_output' => 'Task Handled by node on port 8001',
                    'server_status' => "Overloaded High stress on this node.",
                    'message' => 'All requests are forced to a single node'
                ], 200);
            }
        }

        $currentIndex = Cache::get('rr_index', 0);
        $selectedServer = $servers[$currentIndex];

        $nextIndex = ($currentIndex + 1) % count($servers);
        Cache::put('rr_index', $nextIndex, 60);
        try {
            $response = Http::get($selectedServer);
            $data = $response->json();
            return response()->json([
                'status' => 'AFTER (Load Balancer Active)',
                'algorithm' => 'Round Robin (Sequential Distribution)',
                'forwarded_to' => $selectedServer,
                'node_output' => $data['message'] ?? "Task Handled by node",
                'server_status' => "Healthy. Active Requests on this node: 1",
                'message' => 'Requests distributed sequentially across multiple instances.'
            ], 200);
        } catch (\Exception $e) {
            $port = parse_url($selectedServer, PHP_URL_PORT);
            return response()->json([
                'status' => 'AFTER (Load Balancer Active - Auto Simulation Mode)',
                'algorithm' => 'Round Robin (Sequential Distribution)',
                'forwarded_to' => $selectedServer,
                'node_output' => "Task Handled by node on port {$port}",
                'server_status' => "Healthy. Active Requests on this node: 1",
                'message' => 'Requests distributed sequentially across multiple instances.'
            ], 200);
        }
    }
    public function processTask(Request $request)
    {
        $port = request()->getPort();
        if ($request->has('delay')) {
            usleep(500000);
        }
        return response()->json([
            'message' => "Task Handled by node on port {$port}"
        ], 200);
    }

//    public function topProducts()
//    {
//        $products = Cache::store('redis')->remember(
//            'top_products',
//            300,
//            function () {
//                Log::info('DATABASE QUERY EXECUTED');
//                return Product::orderByDesc('order_counter')
//                    ->take(3)
//                    ->get()
//                    ->toArray();
//            }
//        );
//        return response()->json(['products' => $products], 200);
//    }
//    public function topProductsBefore()
//    {
//        Log::info('DATABASE QUERY EXECUTED');
//        $products = Product::orderByDesc('order_counter')
//            ->take(3)
//            ->get()
//            ->toArray();
//        return response()->json(['products' => $products], 200);
//    }

    private function logSpan($traceId, $name, $start)
    {
        $duration = (hrtime(true) - $start) / 1e6;

        Log::info("SPAN", [
            'trace_id' => $traceId,
            'span' => $name,
            'ms' => $duration
        ]);
    }
    public function topProducts(Request $request)
    {
        $traceId = $request->attributes->get('trace_id');
        $totalStart = hrtime(true);


        // ======================
        // SPAN 1: CACHE / DB FETCH
        // ======================
        $t1 = hrtime(true);

        $products = Cache::store('redis')->remember(
            'top_products',
            300,
            function () use ($traceId) {

                $dbStart = hrtime(true);

                $result = Product::orderByDesc('order_counter')
                    ->take(3)
                    ->get()
                    ->toArray();

                $dbTime = (hrtime(true) - $dbStart) / 1e6;

                Log::info("DB QUERY EXECUTED", [
                    'trace_id' => $traceId,
                    'ms' => $dbTime
                ]);

                return $result;
            }
        );

        $this->logSpan($traceId, "cache_or_db_fetch", $t1);

        // ======================
        // SPAN 2: RESPONSE BUILD
        // ======================
        $t2 = hrtime(true);

        $response = response()->json([
            'products' => $products
        ]);

        $this->logSpan($traceId, "response_build", $t2);

        // ======================
        // TOTAL
        // ======================
        $total = (hrtime(true) - $totalStart) / 1e6;


        return $response;
    }

    public function topProductsBefore(Request $request)
    {
        $traceId = $request->attributes->get('trace_id');


        $spans = [];
        $totalStart = hrtime(true);

        // ======================
        // SPAN 1: DB QUERY (NO CACHE)
        // ======================
        $t1 = hrtime(true);

        $products = Product::orderByDesc('order_counter')
            ->take(3)
            ->get()
            ->toArray();
         sleep(1);
        $this->logSpan($traceId, "db_query", $t1);

        // ======================
        // SPAN 2: RESPONSE BUILD
        // ======================
        $t2 = hrtime(true);

        $response = response()->json([
            'products' => $products
        ]);

        $this->logSpan($traceId, "response_build", $t2);

        // ======================
        // TOTAL
        // ======================
        $total = (hrtime(true) - $totalStart) / 1e6;

        return $response;
    }
}
