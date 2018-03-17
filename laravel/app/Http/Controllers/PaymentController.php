<?php

namespace App\Http\Controllers;

use App\Gateways\Gateway;
use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use CurrentUserOrder;

    protected $modelClass = Payment::class;

    public function __construct()
    {
        parent::__construct();

        $this->middleware(self::class . '::validateUserIsOwner')->only(['generatePayment']);
    }

    /**
     * Middleware that validates permissions get the payment.
     */
    public static function validateUserIsOwner($request, $next)
    {
        $user = auth()->user();
        $order = $request->order;

        if ($user->is($order->user)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Only owner can get payment.');
    }

    /**
     * Return an array of validations rules to apply to the request data.
     *
     * @return array
     */
    protected function validationRules(?Model $order)
    {
        return [];
    }

    /**
     * Validate that an order can be sent to Checkout.
     */
    protected function validateOrderCanCheckout($order)
    {
        if ($order->status !== Order::STATUS_SHOPPING_CART) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order is not in Shopping Cart.');
        }

        if (!$order->products->where('status', Product::STATUS_AVAILABLE)->count()) {
            abort(Response::HTTP_FAILED_DEPENDENCY, 'No products in shopping cart.');
        }

        if ($order->products->where('status', '<>', Product::STATUS_AVAILABLE)->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Some products are not available anymore.');
        }

        if ($order->sales->where('shipping_method_id', null)->count()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Some sales do not have a ShippingMethod.');
        }

        if (!$order->shipping_address) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Order needs shipping address.');
        }
    }

    /**
     * Create a new payment for the given order, with the given gateway.
     */
    protected function getPayment(Gateway $gateway, Model $order)
    {
        $payment = new Payment();
        $payment->gateway = $gateway->getName();
        $payment->status = Payment::STATUS_PENDING;
        $payment->order_id = $order->id;
        $payment->save();

        return $payment;
    }

    /**
     * Create a new payment for the current user.
     */
    public function store(Request $request)
    {
        $order = $this->currentUserOrder();

        return $this->generatePayment($request, $order);
    }

    /**
     * Create a new payment for give order.
     */
    public function generatePayment(Request $request, Order $order)
    {
        $this->validateOrderCanCheckout($order);

        // Get the gateway to use.
        $gateway = new Gateway($request->gateway);
        // Create a Payment model with the selected gateway.
        $payment = $this->getPayment($gateway, $order);
        // Save the Gateway's request data in the Payment model.
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

            if ($payment->status === Payment::STATUS_SUCCESS) {
                $this->approveOrder($payment->order);
            }
        });

        return 'Prilov!';
    }
}
