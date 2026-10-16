<?php

namespace App\Http\Controllers;

use App\Models\{User, Product, Cart, CartItem, Order, OrderItem};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user, 'role' => $user->role]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role'     => 'required|in:consumer,merchant',
        ]);
        $user = User::create([
            'name'     => strip_tags($data['name']),
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);
        if ($user->isConsumer()) Cart::create(['user_id' => $user->id]);
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function products(Request $request)
    {
        $q = Product::active();
        if ($request->filled('search'))   $q->search($request->search);
        if ($request->filled('category')) $q->where('category', $request->category);
        return response()->json($q->paginate(12));
    }

    public function productShow($id)
    {
        return response()->json(Product::active()->findOrFail($id));
    }

    public function cart(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        return response()->json($cart->load('items.product'));
    }

    public function cartAdd(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id', 'quantity' => 'integer|min:1']);
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $item = CartItem::firstOrNew(['cart_id' => $cart->id, 'product_id' => $request->product_id]);
        $item->quantity = ($item->quantity ?? 0) + ($request->quantity ?? 1);
        $item->save();
        return response()->json($cart->load('items.product'));
    }

    public function cartRemove(Request $request, CartItem $item)
    {
        if ($item->cart->user_id !== $request->user()->id) abort(403);
        $item->delete();
        return response()->json(['message' => 'Eliminado.']);
    }

    public function orders(Request $request)
    {
        return response()->json($request->user()->orders()->with('items.product')->latest()->get());
    }
}
