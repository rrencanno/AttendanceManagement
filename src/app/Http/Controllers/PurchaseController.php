<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function show($item_id)
    {
        $product = Product::with('purchase')->findOrFail($item_id);
        $user = Auth::user();

        // shipping_addresses がなければ users テーブルの情報を使う
        $address = $user->shippingAddress ?? (object)[
            'post_code' => $user->post_code,
            'address' => $user->address,
            'building_name' => $user->building_name,
        ];

        return view('purchase', compact('product', 'address'));
    }

    public function store(PurchaseRequest $request, $item_id)
    {
        $product = Product::findOrFail($item_id);
        $user = Auth::user();

        $purchase = new Purchase();
        $purchase->user_id = $user->id;
        $purchase->product_id = $product->id;
        $purchase->shipping_address_id = $user->shipping_address_id;
        $purchase->payment_method = $request->payment_method;
        $purchase->amount = $product->price;
        $purchase->save();

        return redirect('/mypage?tab=buy')->with('success', '購入が完了しました');
    }

    //送付先住所変更
    public function editAddress($item_id)
    {
        $address = Auth::user()->shippingAddress;

        return view('purchase_address', [
            'item_id' => $item_id,
            'address' => $address
        ]);
    }

    public function updateAddress(AddressRequest $request, $item_id)
    {
        $user = Auth::user();
        $address = $user->shippingAddress;

        $address->post_code = $request->post_code;
        $address->address = $request->address;
        $address->building_name = $request->building_name;
        $address->save();

        return redirect()->route('purchase.show', ['item_id' => $item_id])
                        ->with('success', '住所を更新しました');
    }
}
