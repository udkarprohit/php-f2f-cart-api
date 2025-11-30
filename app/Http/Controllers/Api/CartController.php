<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Models\CartItem;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|min:1',
        ]);

        $quantity = $data['quantity'] ?? 1;

        // Get or create a cart for the user
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'open'],
            ['total_amount' => 0],
        );

        $product = Product::find($data['product_id']);

        // Check if product already in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            // Update quantity and line total
            $cartItem->quantity += $quantity;
            $cartItem->line_total = $cartItem->quantity * $product->price;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'user_id' => $user->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'line_total' => $quantity * $product->price,
            ]);
        }

        // Update cart total price
        $cart->total_amount = CartItem::where('cart_id', $cart->id)->sum('line_total');
        $cart->save();

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem,
            'cart' => $cart,
        ], 200);
    }
}
