<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAddToCart implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
///
    public $tries = 3;
    public $userId;
    public $productId;
    public $quantity;

    public function __construct($userId, $productId, $quantity)
    {
        $this->userId = $userId;
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    public function handle(): void
    {
        try {
        DB::transaction(function () {

            $product = Product::where('id', $this->productId)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw new \Exception('Product Not Found');
            }

            if ($product->quantity < $this->quantity) {
                throw new \Exception('Quantity is not available');
            }

            $product->quantity =
                $product->quantity - $this->quantity;

            $product->save();

            $cartItem = Cart::where('user_id', $this->userId)
                ->where('product_id', $this->productId)
                ->first();

            if ($cartItem) {

                $cartItem->quantity =
                    $cartItem->quantity + $this->quantity;

                $cartItem->save();

            } else {

                Cart::create([
                    'user_id' => $this->userId,
                    'product_id' => $this->productId,
                    'quantity' => $this->quantity,
                ]);
            }
        });
        } catch (\Throwable $e) {
            Log::error($e);
            throw $e;
        }
    }
}
