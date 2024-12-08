<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;


class ProductController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = Auth::id();

        // Hitung Total Produk
        $totalProducts = Product::where('seller_id', $sellerId)->count();

        // Hitung Total Terjual (produk yang telah dibeli dan status 'paid' atau 'shipped')
        $totalSold = OrderItem::whereHas('order', function ($query) {
        // Menggunakan whereIn untuk memeriksa dua status
            $query->whereIn('status', ['paid', 'shipped']);
        })->whereHas('product', function ($query) use ($sellerId) {
            $query->where('seller_id', $sellerId);
        })->sum('quantity');

        // Hitung Order yang Sedang Diproses (status 'pending')
        $ordersInProcess = Order::where('status', 'paid')->whereHas('orderItems.product', function ($query) use ($sellerId) {
            $query->where('seller_id', $sellerId);
        })->count();

        //shippeditem
        $shippedOrders = Order::with('orderItems.product')->where('status', 'shipped')->get();

        // Mendapatkan daftar produk milik seller
        $products = Product::where('seller_id', $sellerId)->get();

        // Mendapatkan daftar orders yang sudah dibayar
        $orders = Order::where('status', 'paid') // Ambil hanya yang sudah dibayar
            ->with('orderItems.product') // Load order items dan produk
            ->get();

        return view('products.index', compact('products', 'totalProducts', 'totalSold', 'ordersInProcess', 'orders', 'shippedOrders'));
    }


    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $price = (int) $request->price;
        $stock = (int) $request->stock;
        $category_id = (int) $request->category_id;

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $price,
            'stock' => $stock,
            'seller_id' => Auth::id(),
            'category_id' => $category_id,
            'image' => $imagePath,
        ];

        $response = Http::post('http://localhost:8080/products', $data);

        // Buat Testing

        // if ($response->successful()) {
        //     $result = $response->json();
        //     dd($result);
        // } else {
        //     dd([
        //         'url' => 'http://localhost:8080/products',
        //         'data' => $data,
        //         'response' => $response->body(),
        //         'status' => $response->status(),
        //     ]);
        // }

        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
            'image' => $imagePath,
        ]);

        return redirect()->route('products.index');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return redirect()->route('products.index');
    }

    public function setDiscount(Request $request, $id)
    {
        $request->validate([
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'new_price' => 'nullable|numeric|min:0',
        ]);

        $product = Product::where('id', $id)->where('seller_id', Auth::id())->firstOrFail();

        if ($request->has('discount_percentage') && $request->input('discount_percentage') !== null) {
            $discountedPrice = $product->price * ((100 - $request->input('discount_percentage')) / 100);
            $product->price = $discountedPrice;
        } elseif ($request->has('new_price') && $request->input('new_price') !== null) {
            $product->price = $request->input('new_price');
        }

        $product->save();

        return redirect()->back()->with('success', 'Discount has been applied successfully!');
    }

    public function manageStock(Request $request, $id)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $product = Product::where('id', $id)->where('seller_id', Auth::id())->firstOrFail();

        $product->stock = $request->input('stock');
        $product->save();

        return redirect()->back()->with('success', 'Stock updated successfully!');
    }

        public function show($id)
    {
        $product = Product::with('reviews.user')->findOrFail($id);
        $user = auth()->user();

        $isWishlisted = $product->wishlistedBy->contains($user);
        return view('products.show2', compact('product', 'isWishlisted'));
    }

    public function search(Request $request)
    {
        $user = auth()->user();
        $searchTerm = $request->input('search');

        \Log::info('Search term: ' . $searchTerm);

        $products = Product::where('name', 'LIKE', '%' . $searchTerm . '%')->get();

        foreach ($products as $product) {
            $product->isWishlisted = $user ? $product->wishlistedBy->contains($user) : false;
        }

        return view('home', compact('products'));
    }
}
