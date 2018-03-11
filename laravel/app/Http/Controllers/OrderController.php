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
use Illuminate\Support\Facades\Validator;

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

            $sale->status = Sale::SHOPPING_CART;
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
        $order = Order::where(['user_id' => $user->id, 'status' => Order::SHOPPING_CART])->first();
        if (!$order) {
            $order = new Order();
            $order->user_id = $user->id;
            $order->status = Order::SHOPPING_CART;
            $order->save();
        }
        return $order;
    }

    /**
     * Get the products and group them by the user_id..
     */
    protected function getProductsByUser($productIds)
    {
        return Product::whereIn('id', $productIds)->where('status', Product::AVAILABLE)->get()->groupBy('user_id');
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

    protected function setShippingMethods($order, $shippingMethods)
    {
        foreach ($shippingMethods as $saleId => $shippingMethodId) {
            $sale = $order->sales->firstWhere('id', $saleId);
            $sale->shipping_method_id = $shippingMethodId;
            $sale->save();
        }
        return $order;
    }

    protected function markSalesAsReceived($order, $saleIds)
    {
        foreach ($saleIds as $saleId) {
            $sale = $order->sales->firstWhere('id', $saleId);
            $sale->received = now();
            $sale.save();
        }
    }

    /**
     * Mark Order and its Sales as Payed.
     */
    public function approveOrder($order)
    {
        Sale::whereIn('id', $order->sales->pluck('id'))->update(['status' => Sale::PAYED]);
        $order->status = Order::PAYED;
        $order->save();
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

    protected function validationMessages()
    {
        return ['product_ids.*.exists' => __('validation.available')];
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
                    $query->where('status', Product::AVAILABLE);
                }),
            ],

            'remove_product_ids' => 'array',
            'remove_product_ids.*' => 'integer|exists:products,id',

            'received_sale_ids' => 'array',
            'received_sale_ids.*' => [
                'integer',
                $this->getReceivableSaleRule($order)
            ],

            'shipping_methods' => 'array',
            'shipping_methods.*' => [
                'integer',
                $this->getValidShippingMethodRule($order)
            ]
        ];
    }

    /**
     * Rule that validates that the give shipping method is
     * allowed by the owner of the sale item to which it is being assigned.
     */
    protected function getValidShippingMethodRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            if (!$value) {
                return;
            }
            $saleId = str_replace('shipping_methods.', '', $attribute);
            $sale = $order->sales->firstWhere('id', $saleId);
            if (!$sale) {
                return $fail(__('La venta :sale_id no existe o no es parte de esta orden.', ['sale_id' => $saleId]));
            }
            $query = DB::table('shipping_method_user')->where(
                ['user_id' => $sale->user_id, 'shipping_method_id' => $value]
            );
            if (!$query->count()) {
                $error = __(
                    'El método de envío :value no es válido para la venta :sale_id.',
                    ['value' => $value, 'sale_id' => $saleId]
                );
                return $fail($error);
            }
            return;
        };
    }

    /**
     * Rule that validates a a Sale can be marked as received.
     */
    protected function getReceivableSaleRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            if (!$value) {
                return;
            }
            $sale = $order->sales->firstWhere('id', $value);
            if (!$sale) {
                return $fail(__('La venta :value no existe o no es parte de esta orden.', ['value' => $value]));
            }

            if ($sale->status < Sale::PAYED) {
                $error = __(
                    __('La venta :sale_id venta no se puede marcar cómo recibida.'),
                    ['sale_id' => $value]
                );
                return $fail($error);
            }
            return;
        };
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    protected function alterFillData($data, Model $model = null)
    {
        if ($addressId = array_get($data, 'address_id')) {
            $data['shipping_address'] = Address::where('id', $addressId)->first();
        }
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
     * An Order only accepts changes when in SHOPPING_CART.
     *
     * Make shipping_address if changes are accepted.
     */
    public function update(Request $request, Model $order)
    {
        if ($order->status >= Order::PAYMENT) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order can not be changed once payment has started.');
        }

        $order->fillable(['shipping_address']);
        return parent::update($request, $order);
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

        if ($shippingMethods = $request->shipping_methods) {
            $this->setShippingMethods($order, $shippingMethods);
        }

        if ($receivedSaleIds = $request->received_sale_ids) {
            $this->markSalesAsReceived($order, $receivedSaleIds);
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
        $payment->status = Payment::PENDING;
        $payment->order_id = $order->id;
        $payment->save();

        $payment->request = $gateway->paymentRequest($payment, $request->all());
        $payment->save();

        $order->status = Order::PAYMENT;
        DB::transaction(function () use ($order) {
            $order->save();
            Sale::whereIn('id', $order->sales->pluck('id'))->update(['status' => Sale::PAYMENT]);
            Product::whereIn('id', $order->products->pluck('id'))->update(['status' => Product::UNAVAILABLE]);
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

            if ($payment->status == Payment::SUCCESS) {
                $this->approveOrder($payment->order);
            }
        });

        return 'Prilov!';
    }
}
