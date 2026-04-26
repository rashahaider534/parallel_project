<?php

namespace App\Http\Controllers;


use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function getproducts($shop_id = null)
    {
        if ($shop_id == null) {
            // $resulte=DB::table('products')->get();
            $resulte = Product::paginate(6);
            return response()->json(['products' => $resulte], 200);
        } else {
            // $resulte=DB::table('products')->where('shop_id',$shop_id)->get();
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
}
