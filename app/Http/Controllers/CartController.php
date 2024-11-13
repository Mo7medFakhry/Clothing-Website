<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session as FacadesSession;
use Surfsidemedia\Shoppingcart\Facades\Cart;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::instance('cart')->content();
        return view('cart', compact('items'));
    }

    public function addToCart(Request $request)
    {
        Cart::instance('cart')->add($request->id, $request->name, $request->quantity, $request->price)->associate('App\Models\Product');
        return redirect()->back();

    }

    public function increase_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function decrease_cart_quantity($rowId)
    {
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1;
        Cart::instance('cart')->update($rowId, $qty);
        return redirect()->back();
    }

    public function remove_item_from_cart($rowId)
    {
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();
    }


    public function empty_cart()
    {
        Cart::instance('cart')->destroy();
        return redirect()->back();
    }

    public function apply_coupon(Request $request)
    {
        $coupon = $request->coupon_code;
        if (isset($coupon)) {
            $coupon = Coupon::where('code', $coupon)->where('expiry_date', '>=', Carbon::today())
                ->where('cart_value', '<=', Cart::instance('cart')->subtotal())->first();
            if (!$coupon) {
                return redirect()->back()->with('error', 'Invalid coupon code');
            } else {
                session()->put('coupon', [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'cart_value' => $coupon->cart_value
                ]);
                $this->calculateDiscount();
                return redirect()->back()->with('status', 'Coupon has been applied Succesfully!');
            }
        } else {
            return redirect()->back()->with('error', 'Invalid coupon code');
        }
    }

    public function calculateDiscount()
    {
        $discount = 0;
        if (session()->has('coupon')) {
            if (session()->get('coupon')['type'] == 'fixed') {
                $discount = session()->get('coupon')['value'];
            } else {
                $discount = (Cart::instance('cart')->subtotal() * session()->get('coupon')['value']) / 100;
            }
            $subtotalAfterDiscount = Cart::instance('cart')->subtotal() - $discount;
            $taxAfterDiscount = ($subtotalAfterDiscount * config('cart.tax')) / 100;
            $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;

            session()->put('discounts', [
                'discount' => number_format(floatval($discount), 2),
                'subtotal' => number_format(floatval($subtotalAfterDiscount), 2),
                'tax' => number_format(floatval($taxAfterDiscount), 2),
                'total' => number_format(floatval($totalAfterDiscount), 2)
            ]);

        }
    }

    public function remove_coupon()
    {
        Session::forget('coupon');
        Session::forget('discounts');
        return back()->with('status', 'Coupon has been removed Succesfully!');
    }

    public function checkout()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $addresses = Address::where('user_id', Auth::user()->id)->where('is_default', 1)->first();
        return view('checkout', compact('addresses'));
    }

    public function place_order(Request $request)
    {
        $user_id = Auth::user()->id;
        $address = Address::where('user_id', $user_id)->where('is_default', true)->first();

        if (!$address) {
            $request->validate([
                'name' => 'required|max:255',
                'phone' => 'required|numeric|digits:10',
                'address' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required|numeric|digits:6',
                'location' => 'required',
                'landmark' => 'required',
            ]);

            $address = new Address();
            $address->name = $request->name;
            $address->phone = $request->phone;
            $address->address = $request->address;
            $address->city = $request->city;
            $address->state = $request->state;
            $address->zip = $request->zip;
            $address->location = $request->location;
            $address->landmark = $request->landmark;
            $address->country = 'Egypt';
            $address->user_id = $user_id;
            $address->is_default = true;
            $address->save();
        }
        $this->setAmountForCheckout();

        $order = new Order();
        $order->user_id = $user_id;
        $order->subtotal = session::get('checkout')['subtotal'];
        $order->discount = session::get('checkout')['discount'];
        $order->tax = session::get('checkout')['tax'];
        $order->total = session::get('checkout')['total'];
        $order->name = $address->name;
        $order->phone = $address->phone;
        $order->location = $address->location;
        $order->address = $address->address;
        $order->city = $address->city;
        $order->state = $address->state;
        $order->country = $address->country;
        $order->landmark = $address->landmark;
        $order->zip = $address->zip;
        $order->save();

        foreach (Cart::instance('cart')->content() as $item) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $item->id;
            $orderItem->price = $item->price;
            $orderItem->quantity = $item->qty;
            $orderItem->save();
        }


        if ($request->mode == 'cod') {
            $transaction = new Transaction();
            $transaction->user_id = $user_id;
            $transaction->order_id = $order->id;
            $transaction->mode = $request->mode;
            $transaction->status = 'pending';
            $transaction->save();
        }
        else if ($request->mode == 'card') {
           //
        }
        else if ($request->mode == 'paypal') {
            //
        }


        Cart::instance('cart')->destroy();
        Session::forget('checkout');
        Session::forget('coupon');
        Session::forget('discounts');
        Session::put('order_id', $order->id);
        return redirect()->route('cart.order.confirmation');
    }

    public function setAmountForCheckout()
    {
        if (!Cart::instance('cart')->content()->count() > 0) {
            Session::forget('checkout');
            return;
        }

        if (session::has('coupon'))
        {
            session::put('checkout', [
                'subtotal' => session::get('discounts')['subtotal'],
                'discount' => session::get('discounts')['discount'],
                'tax' => session::get('discounts')['tax'],
                'total' => session::get('discounts')['total'],
            ]);
        } else {
            session::put('checkout', [
                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(),
                'tax' => Cart::instance('cart')->tax(),
                'total' => Cart::instance('cart')->total(),
            ]);
        }
    }


    public function order_confirmation()
    {
        if (Session::has('order_id')) {
            $order = Order::find(Session::get('order_id'));
                return view('order-confirmation', compact('order'));
        }
        return redirect()->route('cart.index');
    }




}
