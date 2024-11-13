<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Slide;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class AdminController extends Controller
{
    public function index()
    {
        $orders = Order::orderBy('created_at', 'DESC')->take(10)->get();
        $dashboardData = DB::select("Select sum(total) As TotalAmount,
        sum(if(status='ordered',total,0)) As TotalOrderedAmount,
        sum(if(status='delivered',total,0)) As TotalDeliveredAmount,
        sum(if(status='cancelled',total,0)) As TotalCancelledAmount,
        count(*) As TotalOrders,
        sum(if(status='ordered',1,0)) As TotalOrdered,
        sum(if(status='delivered',1,0)) As TotalDelivered,
        sum(if(status='cancelled',1,0)) As TotalCancelled
        FROM orders
        ");

        $monthlyData = DB::select("SELECT M.id As MonthNo , M.name As MonthName,
        IFNULL(D.TotalOrders,0) As TotalOrders,
        IFNULL(D.TotalOrderedAmount,0) As TotalOrderedAmount,
        IFNULL(D.TotalDeliveredAmount,0) As TotalDeliveredAmount,
        IFNULL(D.TotalCancelledAmount,0) As TotalCancelledAmount FROM month_names M
        LEFT JOIN (SELECT DATE_FORMAT(created_at,'%b') As MonthName,
        MONTH(created_at) As MonthNo,
        COUNT(*) As TotalOrders,
        sum(total) As TotalAmount,
        sum(if(status='ordered',total,0)) As TotalOrderedAmount,
        sum(if(status='delivered',total,0)) As TotalDeliveredAmount,
        sum(if(status='cancelled',total,0)) As TotalCancelledAmount
        FROM orders WHERE YEAR(created_at) = YEAR(NOW()) GROUP BY YEAR(created_at),MONTH(created_at) ,DATE_FORMAT(created_at,'%b')
        ORDER BY MONTH(created_at)) D ON M.id = D.MonthNo");

        $AmountM = implode(',', collect($monthlyData)->pluck('TotalAmount')->toArray());
        $AmountO = implode(',', collect($monthlyData)->pluck('TotalOrderedAmount')->toArray());
        $AmountD = implode(',', collect($monthlyData)->pluck('TotalDeliveredAmount')->toArray());
        $AmountC = implode(',', collect($monthlyData)->pluck('TotalCancelledAmount')->toArray());

        $totalAmount = collect($monthlyData)->sum('TotalAmount');
        $totalOrderedAmount = collect($monthlyData)->sum('TotalOrderedAmount');
        $totalDeliveredAmount = collect($monthlyData)->sum('TotalDeliveredAmount');
        $totalCancelledAmount = collect($monthlyData)->sum('TotalCancelledAmount');

        return view('admin.index', compact('orders', 'dashboardData', 'monthlyData', 'AmountM', 'AmountO', 'AmountD', 'AmountC', 'totalAmount', 'totalOrderedAmount', 'totalDeliveredAmount', 'totalCancelledAmount'));
    }


    // **************
    // ****Brands****
    // **************

    public function brands()
    {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }
    public function add_brand()
    {
        return view('admin.brand-add');
    }

    public function brand_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug' . $request->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateBrandThumbailsImage($image, $file_name);
        $brand->image = $file_name;
        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been added Succesfully!');
    }

    public function brand_edit($id)
    {
        $brand = Brand::find($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function brand_update(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = Brand::find($request->id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {
                File::delete(public_path('uploads/brands') . '/' . $brand->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateBrandThumbailsImage($image, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been updated Succesfully!');

    }
    public function GenerateBrandThumbailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/brands');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);

    }

    public function brand_delete($id)
    {
        $brand = Brand::find($id);
        if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {

            File::delete(public_path('uploads/brands') . '/' . $brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand has been deleted Succesfully!');

    }

    // **************
    // ***Catogery***
    // **************

    public function catogeries()
    {

        $catogeries = Category::orderBy('id', 'DESC')->paginate(10);
        return view('admin.catogeries', compact('catogeries'));
    }

    public function add_catogery()
    {
        return view('admin.catogery-add');
    }

    public function catogery_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug' . $request->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $catogery = new Category();
        $catogery->name = $request->name;
        $catogery->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateCatogertyThumbailsImage($image, $file_name);
        $catogery->image = $file_name;
        $catogery->save();
        return redirect()->route('admin.catogeries')->with('status', 'Catogery has been added Succesfully!');
    }

    public function catogery_edit($id)
    {
        $catogery = Category::find($id);
        return view('admin.catogery-edit', compact('catogery'));
    }

    public function catogery_update(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $catogery = Category::find($request->id);
        $catogery->name = $request->name;
        $catogery->slug = Str::slug($request->name);
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/catogeries') . '/' . $catogery->image)) {
                File::delete(public_path('uploads/catogeries') . '/' . $catogery->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateCatogeryThumbailsImage($image, $file_name);
            $catogery->image = $file_name;
        }

        $catogery->save();
        return redirect()->route('admin.catogeries')->with('status', 'Catogery has been updated Succesfully!');

    }
    public function GenerateCatogertyThumbailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/catogeries');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);

    }


    public function catogery_delete($id)
    {
        $catogery = Category::find($id);
        if (File::exists(public_path('uploads/catogeries') . '/' . $catogery->image)) {

            File::delete(public_path('uploads/catogeries') . '/' . $catogery->image);
        }
        $catogery->delete();
        return redirect()->route('admin.catogeries')->with('status', 'Catogery has been deleted Succesfully!');

    }

    public function products()
    {
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.products', compact('products'));
    }

    public function product_add()
    {
        $catogeries = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product-add', compact('catogeries', 'brands'));
    }


    public function product_store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $request->id,
            'catogery_id' => 'required',
            'brand_id' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048'
        ]);


        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image')) {

            if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbailsImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if ($request->hasFile('images')) {

            foreach (explode(',', $product->image) as $ofile) {
                if (File::exists(public_path('upoloads/products') . '/' . $ofile)) {
                    File::delete(public_path('upoloads/products') . '/' . $ofile);
                }
                if (File::exists(public_path('upoloads/products/thumbnails') . '/' . $ofile)) {
                    File::delete(public_path('upoloads/products/thumbnails') . '/' . $ofile);
                }

            }


            $allowedfileExtension = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtension);
                if ($gcheck) {
                    $gfilename = $current_timestamp . "-" . $counter . "." . $gextension;
                    $this->GenerateProductThumbailsImage($file, $gfilename);
                    array_push($gallery_arr, $gfilename);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(', ', $gallery_arr);
        }
        $product->images = $gallery_images;
        $product->catogery_id = $request->catogery_id;
        $product->brand_id = $request->brand_id;
        $product->save();
        return redirect()->route('admin.products')->with('status', 'Record has been updated successfully !');
    }

    public function GenerateProductThumbailsImage($image, $imageName)
    {
        $destinationPathThumbail = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');
        $img = Image::read($image->path());

        $img->cover(540, 689, "top");
        $img->resize(540, 689, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);

        $img->resize(104, 104, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbail . '/' . $imageName);
    }

    public function product_edit($id)
    {
        $product = Product::find($id);
        $catogeries = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();

        return view('admin.product-edit', compact('product', 'catogeries', 'brands'));

    }


    public function update_product(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $request->id,
            'category_id' => 'required',
            'brand_id' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048'
        ]);

        $product = Product::find($request->id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image')) {

            if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbailsImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if ($request->hasFile('images')) {

            foreach (explode(',', $product->image) as $ofile) {
                if (File::exists(public_path('upoloads/products') . '/' . $ofile)) {
                    File::delete(public_path('upoloads/products') . '/' . $ofile);
                }
                if (File::exists(public_path('upoloads/products/thumbnails') . '/' . $ofile)) {
                    File::delete(public_path('upoloads/products/thumbnails') . '/' . $ofile);
                }

            }


            $allowedfileExtension = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtension);
                if ($gcheck) {
                    $gfilename = $current_timestamp . "-" . $counter . "." . $gextension;
                    $this->GenerateProductThumbailsImage($file, $gfilename);
                    array_push($gallery_arr, $gfilename);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(', ', $gallery_arr);
        }
        $product->images = $gallery_images;

        $product->save();
        return redirect()->route('admin.products')->with('status', 'Record has been updated successfully !');
    }


    public function coupons()
    {
        $coupons = Coupon::orderBy('expiry_date', 'DESC')->paginate(10);
        return view('admin.coupons', compact('coupons'));
    }

    public function coupon_add()
    {
        return view('admin.coupon-add');
    }

    public function coupon_store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'
        ]);

        $coupon = new Coupon();
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been added Succesfully!');
    }

    public function coupon_edit($id)
    {
        $coupon = Coupon::find($id);
        return view('admin.coupon-edit', compact('coupon'));
    }

    public function coupon_update(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'
        ]);

        $coupon = Coupon::find($request->id);
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been updated Succesfully!');
    }

    public function coupon_delete($id)
    {
        $coupon = Coupon::find($id);
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status', 'Coupon has been deleted Succesfully!');
    }

    public function orders()
    {
        $orders = Order::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.orders', compact('orders'));
    }

    public function order_details($order_id)
    {
        $order = Order::find($order_id);
        $orderItems = OrderItem::where('order_id', $order_id)->orderBy('id')->paginate(12);
        $transaction = Transaction::where('order_id', $order_id)->first();
        return view('admin.order-details', compact('order', 'orderItems', 'transaction'));
    }

    public function update_order_status(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->status = $request->order_status;

        if ($request->order_status == 'delivered') {
            $order->delivered_date = Carbon::now();
        } elseif ($request->order_status == 'canceled') {
            $order->canceled_date = Carbon::now();
        }
        $order->save();
        if ($request->order_status == 'delivered') {
            $transaction = Transaction::where('order_id', $request->order_id)->first();
            $transaction->status = 'approved';
            $transaction->save();
        }
        return back()->with('status', 'Order status has been updated successfully!');
    }

    // **************
    // ***Slides****
    // **************

    public function slides()
    {
        $slides = Slide::orderBy('id', 'DESC')->paginate(10);
        return view('admin.slides', compact('slides'));
    }

    public function slide_add()
    {
        return view('admin.slide-add');
    }

    public function slide_store(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $slide = new Slide();
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        $image = $request->file('image');
        $file_extension = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extension;
        $this->GenerateSlideImage($image, $file_name);
        $slide->image = $file_name;
        $slide->save();
        return redirect()->route('admin.slides')->with('status', 'Slide has been added successfully!');
    }

    public function GenerateSlideImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/slides');
        $img = Image::read($image->path());
        $img->cover(400, 690, "top");
        $img->resize(400, 690, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
    }

    public function slide_edit($id)
    {
        $slide = Slide::find($id);
        return view('admin.slide-edit', compact('slide'));
    }

    public function slide_update(Request $request)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $slide = Slide::find($request->id);
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/slides') . '/' . $slide->image)) {
                File::delete(public_path('uploads/slides') . '/' . $slide->image);
            }

            $image = $request->file('image');
            $file_extension = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;
            $this->GenerateSlideImage($image, $file_name);
            $slide->image = $file_name;
        }
        $slide->save();
        return redirect()->route('admin.slides')->with('status', 'Slide has been updated successfully!');
    }

    public function slide_delete($id)
    {
        $slide = Slide::find($id);
        if(File::exists(public_path('uploads/slides').'/'.$slide->image)){
            File::delete(public_path('uploads/slides').'/'.$slide->image);
        }
        $slide->delete();
        return redirect()->route('admin.slides')->with('status', 'Slide has been deleted successfully!');
    }

}

