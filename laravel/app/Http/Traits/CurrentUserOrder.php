<?php

namespace App\Http\Traits;

use App\Order;
use Illuminate\Support\Facades\Auth;

trait CurrentUserOrder
{
    /**
     * Get an Order model for the current user.
     */
    protected function currentUserOrder()
    {
        $user = Auth::user();
        $order = Order::where(['user_id' => $user->id, 'status' => Order::STATUS_SHOPPING_CART])->first();
        if (!$order) {
            $order = new Order();
            $order->user_id = $user->id;
            $order->status = Order::STATUS_SHOPPING_CART;
            $order->save();
        }
        return $order;
    }
}
