<?php

namespace App\Http\Controllers;

use App\Jobs\SendLowStockNotification;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index()
    {
        $items = CartItem::query()
            ->where('user_id', auth()->id())
            ->with('product:id,name,price,stock_quantity')
            ->get()
            ->map(function (CartItem $item) {
                $subtotal = $item->quantity * $item->product->price;

                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price' => $item->product->price,
                        'stock_quantity' => $item->product->stock_quantity,
                    ],
                ];
            });

        $total = $items->sum('subtotal');

        return Inertia::render('Cart/Index', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $cartItem = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        $newQuantity = $cartItem->exists
            ? $cartItem->quantity + $validated['quantity']
            : $validated['quantity'];

        if ($newQuantity > $product->stock_quantity) {
            return back()->withErrors([
                'quantity' => 'Requested quantity exceeds available stock.',
            ]);
        }

        $cartItem->quantity = $newQuantity;
        $cartItem->save();

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $cartItem = CartItem::query()
            ->whereKey($cartItem->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($cartItem->product_id);

        if ($validated['quantity'] > $product->stock_quantity) {
            return back()->withErrors([
                'quantity' => 'Requested quantity exceeds available stock.',
            ]);
        }

        $cartItem->update([
            'quantity' => $validated['quantity'],
        ]);

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        CartItem::query()
            ->whereKey($cartItem->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('success', 'Item removed.');
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $lowStockProductIds = [];
        $threshold = config('shop.low_stock_threshold');

        try {
            DB::transaction(function () use ($user, $threshold, &$lowStockProductIds) {
                $cartItems = CartItem::query()
                    ->where('user_id', $user->id)
                    ->with('product')
                    ->lockForUpdate()
                    ->get();

                if ($cartItems->isEmpty()) {
                    throw new \RuntimeException('Your cart is empty.');
                }

                $total = 0;
                $order = Order::create([
                    'user_id' => $user->id,
                    'total' => 0,
                    'status' => 'paid',
                    'placed_at' => now(),
                ]);

                foreach ($cartItems as $item) {
                    $product = Product::query()
                        ->whereKey($item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($item->quantity > $product->stock_quantity) {
                        throw new \RuntimeException(
                            "Insufficient stock for {$product->name}."
                        );
                    }

                    $newStock = $product->stock_quantity - $item->quantity;
                    $subtotal = $item->quantity * $product->price;
                    $total += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ]);

                    $product->update(['stock_quantity' => $newStock]);

                    if ($newStock <= $threshold) {
                        $lowStockProductIds[] = $product->id;
                    }
                }

                $order->update(['total' => $total]);

                CartItem::query()
                    ->where('user_id', $user->id)
                    ->delete();
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors([
                'checkout' => $exception->getMessage(),
            ]);
        }

        foreach (array_unique($lowStockProductIds) as $productId) {
            SendLowStockNotification::dispatch($productId);
        }

        return redirect()->route('cart.index')->with('success', 'Order placed.');
    }
}
