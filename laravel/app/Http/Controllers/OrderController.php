<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Support\Facades\Log;
use App\Gateways\Gateway;
use App\Order;
use App\Payment;
use App\Product;
use App\Purchase;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $modelClass = Order::class;

    /**
     * Get a Purchase model for the given seller and order.
     * Create a new one if one does not exist.
     */
    protected function getPurchase($order, $sellerId)
    {
        $purchase = $order->purchases->firstWhere('user_id', $sellerId);
        if (!$purchase) {
            $purchase = new Purchase(
                ['user_id' => $sellerId, 'order_id' => $order->id]
            );
            $purchase->status = Purchase::SHOPPING_CART;
            $purchase->save();
        }
        return $purchase;
    }

    /**
     * Get a Purchase model for the given seller and order.
     */
    protected function currentUserOrder()
    {
        $user = Auth::user();
        $order = Order::firstOrNew(['user_id' => $user->id, 'status' => Order::SHOPPING_CART]);
        $order->status = Order::SHOPPING_CART;
        $order->save();
        return $order;
    }

    /**
     * Validate that the IDs we are processing actually exist.
     */
    protected function validateProductIds($productIds)
    {
        $data = ['product_ids' => $productIds];
        $rules = [
            'product_ids' => 'array',
            'product_ids.*' => [
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('status', Product::AVAILABLE);
                }),
            ]
        ];
        $messages = ['product_ids.*.exists' => trans('validation.available')];
        return Validator::make($data, $rules, $messages)->validate();
    }

    /**
     * Get the products and group them by the user_id..
     */
    protected function getProductsByUser($productIds)
    {
        $this->validateProductIds($productIds);
        return Product::whereIn('id', $productIds)->where('status', Product::AVAILABLE)->get()->groupBy('user_id');
    }

    /**
     * Add products to the current user's cart/Order.
     */
    public function addProducts(Request $request)
    {
        $order = $this->currentUserOrder();
        foreach ($this->getProductsByUser($request->ids) as $userId => $products) {
            $purchase = $this->getPurchase($order, $userId);
            $purchase->products()->syncWithoutDetaching($products->pluck('id'));
        }

        return $order->fresh();
    }

    /**
     * Remove products from the current user's cart/Order.
     */
    public function removeProducts(Request $request)
    {
        $order = $this->currentUserOrder();
        foreach ($order->purchases as $purchase) {
            $purchase->products()->detach($request->ids);
            if (!count($purchase->products)) {
                $purchase->delete();
            }
        }

        return $order->fresh();
    }

    /**
     * Set shipping address to the Order.
     */
    public function setShippingAddress(Request $request, Address $address)
    {
        $order = $this->currentUserOrder();
        return $order;
    }

    /**
     * Return the current user's shopping cart/Order.
     */
    public function getCart(Request $request)
    {
        return $this->show($request, $this->currentUserOrder());
    }

    protected function validateOrderCanCheckout($order)
    {
        if (!$order->products->where('status', Product::AVAILABLE)->count()) {
            abort(Response::HTTP_FAILED_DEPENDENCY, 'No products in shopping cart.');
        }

        if ($order->products->where('status', '<>', Product::AVAILABLE)->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Some products are not available anymore.');
        }
    }

    /**
     * Create a new payment for the current user.
     */
    public function createPayment(Request $request)
    {
        $order = $this->currentUserOrder();

        $this->validateOrderCanCheckout($order);

        $gateway = new Gateway($request->gateway);

        $payment = new Payment();
        $payment->gateway = $gateway->getName();
        $payment->status = Payment::PENDING;
        $payment->order_id = $order->id;
        $payment->save();

        $payment->request = $gateway->paymentRequest($payment, $request->all());
        $payment->save();

        $order->status = Order::PAYMENT;
        DB::transaction(function () use ($order) {
            $order->save();
            Purchase::whereIn('id', $order->purchases->pluck('id'))->update(['status' => Purchase::PAYMENT]);
            Product::whereIn('id', $order->products->pluck('id'))->update(['status' => Product::UNAVAILABLE]);
        });

        return $payment;
    }

    /**
     * Return the current user's shopping cart/Order.
     */
    public function approveOrder($order)
    {
        Purchase::whereIn('id', $order->purchases->pluck('id'))->update(['status' => Purchase::PAYED]);
        $order->status = Order::PAYED;
        $order->save();
    }

    /**
     * Process a callback from the gateway.
     */
    public function gatewayCallback(Request $request, $gateway)
    {
        DB::transaction(function () use ($request, $gateway) {
            $gateway = new Gateway($gateway);
            $payment = $gateway->processCallback($request->all());

            if ($payment->status == Payment::SUCCESS) {
                $this->approveOrder($payment->order);
            }
        });

        return 'Prilov!';
    }
}
