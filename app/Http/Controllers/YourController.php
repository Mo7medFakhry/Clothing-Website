<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class YourController extends Controller
{
    public function index()
    {
        $orders = Order::with('order_items')->paginate();
        return view('admin.orders', compact('orders'));
    }
}
