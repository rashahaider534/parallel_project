<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function storebefore(Request $request)
    {
        $user = Auth::user();
        $product = Product::where('id', $request->id)->first();
        if (!$product) {
            return response()->json(['error' => 'Product Notfound']);
        }
        $requestedQuantity = (int)$request->input('quantity');
        $product->quantity = $product->quantity - $requestedQuantity;
        $product->save();

        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $request->id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity = $cartItem->quantity + $requestedQuantity;
            $cartItem->save();
        } else {
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $request->id,
                'quantity' => $requestedQuantity,
            ]);
        }

        return response()->json(['message' =>'add product to cart'], 200);
    }
    public function storeafter(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $user = Auth::user();
            $product = Product::where('id', $request->id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return response()->json(['error' => 'Product Notfound']);
            }

            $requestedQuantity = (int)$request->input('quantity');

            if ($product->quantity < $requestedQuantity) {
                return response()->json(['error' => 'Quantity is not available'], 422);
            }

            $product->quantity = $product->quantity - $requestedQuantity;
            $product->save();

            $cartItem = Cart::where('user_id', $user->id)
                ->where('product_id', $request->id)
                ->first();

            if ($cartItem) {
                $cartItem->quantity = $cartItem->quantity + $requestedQuantity;
                $cartItem->save();
            } else {
                Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $request->id,
                    'quantity' => $requestedQuantity,
                ]);
            }

            return response()->json(['message' => 'تم الاضافة الى السلة'], 200);
        });
    }

    public function storeOptimistic(Request $request)
    {
        $maxRetries = 3;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                return DB::transaction(function () use ($request) {
                    $user = Auth::user();
                    $product = Product::where('id', $request->id)->first();

                    if (!$product) {
                        return response()->json(['error' => ' Product Not Found ']);
                    }

                    $requestedQuantity = $request->input('quantity');

                    if ($product->quantity < $requestedQuantity) {
                        return response()->json(['error' =>'Quantity is not available'], 422);
                    }

                    $oldVersion = $product->version;

                    $updated = Product::where('id', $request->id)
                        ->where('version', $oldVersion)
                        ->update([
                            'quantity' => $product->quantity - $requestedQuantity,
                            'version' => $oldVersion + 1
                        ]);

                    if ($updated === 0) {
                        throw new \Exception('تعارض في البيانات، إعادة محاولة');
                    }

                    // إضافة إلى السلة
                    $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $request->id)->first();

                    if ($cartItem) {
                        $cartItem->quantity += $requestedQuantity;
                        $cartItem->save();
                    } else {
                        Cart::create([
                            'user_id' => $user->id,
                            'product_id' => $request->id,
                            'quantity' => $requestedQuantity,
                        ]);
                    }
                    return response()->json(['message' => 'تم الاضافة الى السلة'], 200);
                });
            } catch (\Exception $e) {
                if ($attempt >= $maxRetries - 1) {
                    return response()->json(['error' => 'فشلت العملية بسبب ازدحام، حاول مرة أخرى']);
                }
                //test
            }
        }
    }
}
