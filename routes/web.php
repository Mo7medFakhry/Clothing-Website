<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{product_slug}',[ShopController::class,'product_details'])->name('shop.product.details');
Route::get('/cart',[CartController::class,'index'])->name('cart.index');


Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
Route::put('cart/increase-quantity/{rowId}',[CartController::class,'increase_cart_quantity'])->name('cart.qty.increase');
Route::put('cart/decrease-quantity/{rowId}', [CartController::class, 'decrease_cart_quantity'])->name('cart.qty.decrease');
Route::delete('/cart/remove/{rowId}',[CartController::class,'remove_item_from_cart'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'empty_cart'])->name('cart.empty');

Route::post('/cart/apply-coupon', [CartController::class, 'apply_coupon'])->name('cart.apply.coupon');
Route::delete('/cart/remove-coupon', [CartController::class, 'remove_coupon'])->name('cart.remove.coupon');



Route::post('/wishlist/add', [WishlistController::class, 'add_to_wishlist'])->name('wishlist.add');
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::delete('/wishlist/remove/{rowId}', [WishlistController::class, 'remove_item_from_wishlist'])->name('wishlist.remove');
Route::delete('/wishlist/clear', [WishlistController::class, 'empty_wishlist'])->name('wishlist.empty');


Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::post('/place_order' , [CartController::class, 'place_order'])->name('cart.place.order');
Route::get('/order-confirmation', [CartController::class, 'order_confirmation'])->name('cart.order.confirmation');

Route::middleware(['auth'])->group(function () {
    Route::get('/account-dashboard', [UserController::class, 'index'])->name('user.index');
    Route::get('/account-orders', [UserController::class, 'orders'])->name('user.orders');
    Route::get('/account-orders/{order_id}', [UserController::class, 'orderDetails'])->name('user.order.details');
    Route::put('/account-orders/cancel-order', [UserController::class, 'cancel_Order'])->name('user.order.details.cancel');
});

Route::middleware(['auth', AuthAdmin::class])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');

    Route::get('/admin/brands', [AdminController::class, 'brands'])->name('admin.brands');
    Route::get('/admin/brands/add', [AdminController::class, 'add_brand'])->name('admin.brand.add');
    Route::post('/admin/brands/store', [AdminController::class, 'brand_store'])->name('admin.brand.store');
    Route::get('/admin/brands/edit/{id}', [AdminController::class, 'brand_edit'])->name('admin.brand.edit');
    Route::put('/admin/brands/update', [AdminController::class, 'brand_update'])->name('admin.brand.update');
    Route::delete('/admin/brands/{id}/delete', [AdminController::class, 'brand_delete'])->name('admin.brand.delete');


    Route::get('/admin/catogeries', [AdminController::class, 'catogeries'])->name('admin.catogeries');
    Route::get('/admin/catogeries/add', [AdminController::class, 'add_catogery'])->name('admin.catogery.add');
    Route::post('/admin/catogeries/store', [AdminController::class, 'catogery_store'])->name('admin.catogery.store');
    Route::get('/admin/catogeries/edit/{id}', [AdminController::class, 'catogery_edit'])->name('admin.catogery.edit');
    Route::put('/admin/catogeries/update', [AdminController::class, 'catogery_update'])->name('admin.catogery.update');
    Route::delete('/admin/catogeries/{id}/delete', [AdminController::class, 'catogery_delete'])->name('admin.catogery.delete');



    Route::get('/admin/products', [AdminController::class, 'products'])->name('admin.products');
    Route::get('/admin/products/add', [AdminController::class, 'product_add'])->name('admin.product.add');
    Route::post('/admin/products/store', [AdminController::class, 'product_store'])->name('admin.product.store');
    Route::get('/admin/products/edit/{id}', [AdminController::class, 'product_edit'])->name('admin.product.edit');
    Route::put('/admin/products/update', [AdminController::class, 'update_product'])->name('admin.product.update');

    Route::get('/admin/coupons', [AdminController::class, 'coupons'])->name('admin.coupons');
    Route::get('/admin/coupons/add', [AdminController::class, 'coupon_add'])->name('admin.coupon.add');
    Route::post('/admin/coupons/store', [AdminController::class, 'coupon_store'])->name('admin.coupon.store');
    Route::get('/admin/coupons/edit/{id}', [AdminController::class, 'coupon_edit'])->name('admin.coupon.edit');
    Route::put('/admin/coupons/update', [AdminController::class, 'coupon_update'])->name('admin.coupon.update');
    Route::delete('/admin/coupons/{id}/delete', [AdminController::class, 'coupon_delete'])->name('admin.coupon.delete');

    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/admin/orders/{id}', [AdminController::class, 'order_details'])->name('admin.order.details');
    Route::put('/admin/orders/update-status', [AdminController::class, 'update_order_status'])->name('admin.order.update.status');


    Route::get('/admin/slides', [AdminController::class, 'slides'])->name('admin.slides');
    Route::get('/admin/slides/add', [AdminController::class, 'slide_add'])->name('admin.slide.add');
    Route::post('/admin/slides/store', [AdminController::class, 'slide_store'])->name('admin.slide.store');
    Route::get('/admin/slides/edit/{id}', [AdminController::class, 'slide_edit'])->name('admin.slide.edit');
    Route::put('/admin/slides/update', [AdminController::class, 'slide_update'])->name('admin.slide.update');
    Route::delete('/admin/slides/{id}/delete', [AdminController::class, 'slide_delete'])->name('admin.slide.delete');
});
