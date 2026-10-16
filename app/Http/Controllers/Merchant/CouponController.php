<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::where('merchant_id', Auth::id())->latest()->get();
        return view('merchant.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'             => 'required|string|unique:coupons,code|max:20',
            'type'             => 'required|in:fixed,percentage',
            'value'            => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'expires_at'       => 'nullable|date|after:today',
            'usage_limit'      => 'nullable|integer|min:1',
        ]);

        $data['merchant_id'] = Auth::id();
        Coupon::create($data);

        return redirect()->back()->with('success', 'Cupón creado correctamente.');
    }

    public function toggle($id)
    {
        $coupon = Coupon::where('merchant_id', Auth::id())->findOrFail($id);
        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        return redirect()->back()->with('success', 'Estado del cupón actualizado.');
    }

    public function destroy($id)
    {
        $coupon = Coupon::where('merchant_id', Auth::id())->findOrFail($id);
        $coupon->delete();

        return redirect()->back()->with('success', 'Cupón eliminado.');
    }
}
