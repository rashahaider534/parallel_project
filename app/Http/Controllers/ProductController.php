<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
        $servers = ['Server_A', 'Server_B', 'Server_C'];

        if ($request->has('use_lb') && $request->use_lb == 0) {
            
            $selectedServer = $servers[0];
            
            usleep(500000); 

            return response()->json([
                'status' => 'BEFORE (No Load Balancer)',
                'dispatched_to' => $selectedServer,
                'server_status' => "Overloaded! High stress on this node.",
                'message' => 'All requests on a one single server.'
            ], 200);
        }

        $currentIndex = Cache::get('rr_index', 0);
        $selectedServer = $servers[$currentIndex];

        $nextIndex = ($currentIndex + 1) % count($servers);
        Cache::put('rr_index', $nextIndex, 60);

        
        return response()->json([
            'status' => 'AFTER (Load Balancer Active)',
            'algorithm' => 'Round Robin (Sequential Distribution)',
            'dispatched_to' => $selectedServer,
            'server_status' => "Healthy. Active Requests on this node: 1",
            'message' => 'Requests distributed sequentially using Round Robin.'
        ], 200);
    }
}