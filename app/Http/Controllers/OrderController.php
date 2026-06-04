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
        $user = Auth::user();
        $total_price=0;
        $cartItems = Cart::with('product')->where('user_id', $user->id)->with('product')->get();
        foreach($cartItems as $item)
        {
             $total_price +=$item->quantity * $item->product->price;
        }
        $order = Order::create([
            'name' =>$request->name,
            'email' =>$request->email,
            'user_id' => $user->id,
            'total_price'=> $total_price
        ]);

        throw new \Exception("Test Error");

        foreach ($cartItems as $item) {
            OrderDetail::create([
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'price'=>$item->product->price
            ]);
            Product::where('id',$item->product_id)->increment('order_counter',$item->quantity);
            Cache::forget('top_products');
        }

        Cart::where('user_id', $user->id)->delete();
       return response()->json("Order placed successfully!", 201);
    }

    public function store_order_after(Request $request)
{
    return DB::transaction(function () use ($request) {

        $user = Auth::user();

        $total_price = 0;

        $cartItems = Cart::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'error' => 'Cart is empty'
            ], 422);
        }

        foreach ($cartItems as $item) {

            $product = Product::lockForUpdate()
                ->find($item->product_id);

            if (!$product) {
                throw new \Exception("Product not found");
            }

            if ($product->quantity < $item->quantity) {
                throw new \Exception("Insufficient stock");
            }

                $total_price +=
                $item->quantity *
                $product->price;
        }

        $order = Order::create([
            'name' => $request->name,
            'email' => $request->email,
            'user_id' => $user->id,
            'total_price' => $total_price
        ]);
        throw new \Exception("Test Error");

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

            Product::where('id', $item->product_id)
                ->increment(
                    'order_counter',
                    $item->quantity
                );

            Cache::forget('top_products');
        }

        Cart::where(
            'user_id',
            $user->id
        )->delete();

        return response()->json([
            'message' => 'Order placed successfully!'
        ], 201);

    });
}
}
