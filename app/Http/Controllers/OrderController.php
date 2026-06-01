<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
class OrderController extends Controller
{
     public function store_order_before(Request $request)
    {
        $user = Auth::user();
        $total_price=0;
        $cartItems = Cart::with('product')->where('user_id', $user->id)->with('product')->get();
        foreach($cartItems as $item)
        {
             $total_price=$item->quantity * $item->product->price;
        }
        $order = Order::create([
            'name' =>$request->name,
            'email' =>$request->email,
            'user_id' => $user->id,
            'total_price'=> $total_price
        ]);

        foreach ($cartItems as $item) {
            OrderDetail::create([
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'price'=>$item->product->price
            ]);
            Product::where('id',$item->product_id)->increment('order_counter',$item->quantity);
        }

        Cart::where('user_id', $user->id)->delete();
       return response()->json("Order placed successfully!", 201);
    }
}
