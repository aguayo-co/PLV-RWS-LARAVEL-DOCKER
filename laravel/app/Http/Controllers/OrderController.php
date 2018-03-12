<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Database\Eloquent\Model;
use App\Gateways\Gateway;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * This Controller handles actions taken on the Order and
 * on the Sale when those actions are initiated by the Buyer.
 *
 * A Buyer should not act directly on the Sale, but always through
 * the Order.
 */
class OrderController extends Controller
{
    protected $modelClass = Order::class;

    /**
     * Get a Sale model for the given seller and order.
     * Create a new one if one does not exist.
     */
    protected function getSale($order, $sellerId)
    {
        $sale = $order->sales->firstWhere('user_id', $sellerId);
        if (!$sale) {
            $sale = new Sale();
            $sale->user_id = $sellerId;
            $sale->order_id = $order->id;

            $sale->status = Sale::STATUS_SHOPPING_CART;
            $sale->save();
        }
        return $sale;
    }

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

    /**
     * Get the products and group them by the user_id..
     */
    protected function getProductsByUser($productIds)
    {
        return Product::whereIn('id', $productIds)->where('status', Product::STATUS_AVAILABLE)
            ->get()->groupBy('user_id');
    }

    /**
     * Add products to the given cart/Order.
     */
    protected function addProducts($order, $productIds)
    {
        foreach ($this->getProductsByUser($productIds) as $userId => $products) {
            $sale = $this->getSale($order, $userId);
            $sale->products()->syncWithoutDetaching($products->pluck('id'));
        }

        return $order;
    }

    /**
     * Remove products from the given cart/Order.
     */
    protected function removeProducts($order, $productIds)
    {
        foreach ($order->sales as $sale) {
            $sale->products()->detach($productIds);
            $sale->load('products');
            if (!count($sale->products)) {
                $sale->delete();
            }
        }

        return $order;
    }

    /**
     * Process data for sales.
     */
    protected function processSalesData($order, $sales)
    {
        foreach ($sales as $saleId => $data) {
            $sale = $order->sales->firstWhere('id', $saleId);
            if ($shippingMethodId = array_get($data, 'shipping_method_id')) {
                $sale->shipping_method_id = $shippingMethodId;
                $sale->save();
            }
            if ($status = array_get($data, 'status')) {
                $sale->status = $status;
                $sale->save();
            }
        }
    }

    /**
     * Mark Order and its Sales as Payed.
     */
    public function approveOrder($order)
    {
        // We want to fire events.
        foreach ($order->sales->whereIn('id', $order->sales->pluck('id')) as $sale) {
            $sale->status = Sale::STATUS_PAYED;
            $sale->save();
        }
        $order->status = Order::STATUS_PAYED;
        $order->save();
    }

    /**
     * Validate that an order can be sent to Checkout.
     */
    protected function validateOrderCanCheckout($order)
    {
        if (!$order->products->where('status', Product::STATUS_AVAILABLE)->count()) {
            abort(Response::HTTP_FAILED_DEPENDENCY, 'No products in shopping cart.');
        }

        if ($order->products->where('status', '<>', Product::STATUS_AVAILABLE)->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Some products are not available anymore.');
        }

        if ($order->sales->where('shipping_method_id', null)->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Some sales do not have a ShippingMethod.');
        }
    }

    /**
     * Set validation messages for ValidationRules.
     */
    protected function validationMessages()
    {
        return [
            'add_product_ids.*.exists' => __('validation.available'),
            'remove_product_ids.*.exists' => __('validation.available')
        ];
    }

    /**
     * Return an array of validations rules to apply to the request data.
     *
     * @return array
     */
    protected function validationRules(?Model $order)
    {
        return [
            'address_id' => [
                'integer',
                Rule::exists('addresses', 'id')->where(function ($query) use ($order) {
                    $query->where('user_id', $order->user_id);
                }),
            ],

            'add_product_ids' => 'array',
            'add_product_ids.*' => [
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('status', Product::STATUS_AVAILABLE);
                }),
            ],

            'remove_product_ids' => 'array',
            'remove_product_ids.*' => 'integer|exists:products,id',

            'sales' => 'array',
            'sales.*' => 'array',
            'sales.*.id' => [
                'integer',
                Rule::in($order->sales->pluck('id')->all())
            ],

            'sales.*.shipping_method_id' => [
                'bail',
                'integer',
                $this->getSaleInShoppingCartRule($order),
                $this->getShippingMethodRule($order)
            ],

            'sales.*.status' => [
                'bail',
                'integer',
                Rule::in([Sale::STATUS_RECEIVED]),
                $this->getStatusRule($order),
            ],
        ];
    }

    /**
     * Rule that validates that the given sale is still in a ShoppingCart.
     */
    protected function getSaleInShoppingCartRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $saleId = preg_replace('/.*\.([0-9]+)\..*/', '$1', $attribute);
            $sale = $order->sales->firstWhere('id', $saleId);
            if ($sale) {
                if ($sale->status > Sale::STATUS_SHOPPING_CART) {
                    return $fail(__('No se puede modificar Orden que no está en ShoppingCart.'));
                }
            }
        };
    }

    /**
     * Rule that validates that the given shipping method is
     * allowed by the owner of the sale item to which it is being assigned.
     */
    protected function getShippingMethodRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $saleId = preg_replace('/.*\.([0-9]+)\..*/', '$1', $attribute);
            $sale = $order->sales->firstWhere('id', $saleId);
            if ($sale) {
                $shippingMethodIds = DB::table('shipping_method_user')->where('user_id', $sale->user_id)
                    ->select('shipping_method_id')->pluck('shipping_method_id');
                if (!$shippingMethodIds->contains($value)) {
                    return $fail(__('validation.in', ['values' => $shippingMethodIds->implode(', ')]));
                }
            }
        };
    }

    /**
     * Rule that validates that a Sale status is valid.
     */
    protected function getStatusRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $saleId = preg_replace('/.*\.([0-9]+)\..*/', '$1', $attribute);
            $sale = $order->sales->firstWhere('id', $saleId);
            switch ($value) {
                case Sale::STATUS_RECEIVED:
                    if ($sale->status < Sale::STATUS_PAYED) {
                        return $fail(__('No puede marcar una compra cómo recibida antes de estar pagada.'));
                    }
                    break;
            }
        };
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    protected function alterValidateData($data, Model $model = null)
    {
        if ($sales = array_get($data, 'sales')) {
            foreach ($sales as $saleId => $values) {
                $data['sales'][$saleId] = ['id' => $saleId] + $values;
            }
        }
        return $data;
    }

    protected function alterFillData($data, Model $model = null)
    {
        // Never allow shipping_address to be used or passed.
        array_forget($data, 'shipping_address');
        if ($addressId = array_get($data, 'address_id')) {
            $data['shipping_address'] = Address::where('id', $addressId)->first();
        }
        // Remove 'sales' from $data since it is not fillable.
        $data = array_except($data, ['sales']);
        return $data;
    }

    /**
     * An alias for the show() method for the current logged in user.
     */
    public function getShoppingCart(Request $request)
    {
        return $this->show($request, $this->currentUserOrder());
    }

    /**
     * An alias for the update() method for the current logged in user.
     */
    public function updateShoppingCart(Request $request)
    {
        $order = $this->currentUserOrder();

        return $this->update($request, $order);
    }

    /**
     * Perform changes to associated Models.
     */
    public function postUpdate(Request $request, Model $order)
    {
        if ($addProductIds = $request->add_product_ids) {
            $this->addProducts($order, $addProductIds);
        }

        if ($removeProductIds = $request->remove_product_ids) {
            $this->removeProducts($order, $removeProductIds);
        }

        if ($sales = $request->sales) {
            $this->processSalesData($order, $sales);
        }

        return parent::postUpdate($request, $order);
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
        $payment->status = Payment::STATUS_PENDING;
        $payment->order_id = $order->id;
        $payment->save();

        $payment->request = $gateway->paymentRequest($payment, $request->all());
        $payment->save();

        $order->status = Order::STATUS_PAYMENT;
        DB::transaction(function () use ($order) {
            $order->save();
            foreach ($order->sales as $sale) {
                $sale->status = Sale::STATUS_PAYMENT;
                $sale->save();
            }
            foreach ($order->products as $product) {
                $product->status = Product::STATUS_UNAVAILABLE;
                $product->save();
            }
        });

        return $payment;
    }

    /**
     * Process a callback from the gateway.
     */
    public function gatewayCallback(Request $request, $gateway)
    {
        DB::transaction(function () use ($request, $gateway) {
            $gateway = new Gateway($gateway);
            $payment = $gateway->processCallback($request->all());

            if ($payment->status == Payment::STATUS_SUCCESS) {
                $this->approveOrder($payment->order);
            }
        });

        return 'Prilov!';
    }
}
