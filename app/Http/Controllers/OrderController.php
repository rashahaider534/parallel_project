<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function store_order_before(Request $request)
{
    try {

        $user = Auth::user();

        $total_price = 0;

        $cartItems = Cart::with('product')
            ->where('user_id', $user->id)
            ->get();

        foreach ($cartItems as $item) {
            $total_price += $item->quantity * $item->product->price;
        }

        $order = Order::create([
            'name' => $request->name,
            'email' => $request->email,
            'user_id' => $user->id,
            'total_price' => $total_price
        ]);
        //sleep(1);
        throw new \Exception("Test Error");

        foreach ($cartItems as $item) {

            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price
            ]);

            Product::where('id', $item->product_id)
                ->increment('order_counter', $item->quantity);

            Cache::forget('top_products');
        }

        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Order placed successfully!'
        ], 201);

    } catch (\Exception $e) {

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function store_order_after(Request $request)
{
    try {

        DB::transaction (function () use ($request) {

            $user = Auth::user();

            $cartItems = Cart::with('product')
                ->where('user_id', $user->id)
                ->get();

            $total_price = 0;

            foreach ($cartItems as $item) {

                $product = Product::lockForUpdate()
                    ->find($item->product_id);

                $total_price +=
                    $item->quantity * $product->price;
            }

            $order = Order::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_id' => $user->id,
                'total_price' => $total_price
            ]);

            throw new \Exception("Simulated Failure");

            foreach ($cartItems as $item) {

                $product = Product::lockForUpdate()
                    ->find($item->product_id);

                $product->quantity -= $item->quantity;
                $product->save();

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $product->price
                ]);
            }

            Cart::where(
                'user_id',
                $user->id
            )->delete();
        });

        return response()->json([
            'message' => 'Order completed successfully'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

}
