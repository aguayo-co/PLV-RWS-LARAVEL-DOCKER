<?php

namespace App\Http\Controllers;

use App\Order;
use App\Purchase;
use Illuminate\Support\Facades\Validator;
use App\Product;
use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Agregar producto a carro.
     * Eliminar producto de carro.
     * Agrega información de despacho.
     * Selecciona método de pago.
     * Enviar a pago.
     */

    protected function getPurchase($order, $sellerId)
    {
        return Purchase::firstOrCreate(['seller_id' => $sellerId, 'order_id' => $order->id]);
    }

    protected function currentUserOrder()
    {
        $user = Auth::user();
        return Order::firstOrCreate(['buyer_id' => $user->id, 'status' => Order::SHOPPING_CART]);
    }

    protected function validateProductIds($productIds)
    {
        return Validator::make(
            ['product_ids' => $productIds],
            ['product_ids' => 'array', 'product_ids.*' => 'integer|exists:products,id']
        )->validate();
    }

    protected function getProductsByUser($productIds)
    {
        $this->validateProductIds($productIds);
        return Product::whereIn('id', $productIds)->groupBy('user_id');
    }

    public function addProducts(Request $request)
    {
        $order = $this->currentUserOrder();
        foreach ($this->getProductsByUser($request->ids) as $userId => $products) {
            $purchase = $this->getPurchase($order, $userId);
            $purchase->products()->syncWithoutDetaching($products->pluck('id'));
        }

        return $order->fresh()->load('purchases.products');
    }

    public function removeProducts(Request $request)
    {
        $order = $this->currentUserOrder();
        foreach ($this->getProductsByUser($request->ids) as $userId => $products) {
            $purchase = $this->getPurchase($order, $userId);
            $purchase->products()->detach($products->pluck('id'));
            if (!count($purchase->products)) {
                $purchase->delete();
            }
        }

        return $order->fresh()->load('purchases.products');
    }

    public function setShippingAddress(Request $request, Product $product)
    {
        $user = Auth::user();
        $order = $this->getOrder($user);
        return $order->fresh()->makeVisible('purchases');
    }

    public function createPayment(Request $request)
    {
        $gateway = $this->$gateway;
        $payment = Payment($request->all());
        return $payment;
    }

    public function getCart(Request $request)
    {
        return $this->show($request, $this->currentUserOrder());
    }
}
