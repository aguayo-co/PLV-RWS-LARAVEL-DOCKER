<?php

namespace App\Http\Controllers;

use App\Address;
use App\Http\Controllers\Order\OrderControllerRules;
use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Product;
use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * This Controller handles actions taken on the Order and
 * on the Sale when those actions are initiated by the Buyer.
 *
 * A Buyer should not act directly on the Sale, but always through
 * the Order.
 */
class OrderController extends Controller
{
    use CurrentUserOrder;
    use OrderControllerRules;

    protected $modelClass = Order::class;

    public static $allowedWhereIn = ['id', 'user_id'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('owner_or_admin')->only('show');
    }

    /**
     * Return a Closure that modifies the index query.
     * The closure receives the $query as a parameter.
     *
     * When user is not admin, limit to current user orders.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return;
        }

        return function ($query) use ($user) {
            return $query->where('user_id', $user->id);
        };
    }

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
     * Get the products and group them by the user_id..
     */
    protected function getProductsByUser($productIds)
    {
        return Product::whereIn('id', $productIds)
            ->whereBetween('status', [Product::STATUS_APPROVED, Product::STATUS_AVAILABLE])
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
     *
     * @param  $order \App\Order The order the sales belong to
     * @param  $sales array Data to be applied to sales, keyed by sale id.
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
    protected function validationRules(array $data, ?Model $order)
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
                    $query->whereBetween('status', [Product::STATUS_APPROVED, Product::STATUS_AVAILABLE]);
                }),
            ],

            'remove_product_ids' => 'array',
            'remove_product_ids.*' => 'integer|exists:products,id',

            'sales' => 'array',
            'sales.*' => [
                'bail',
                'array',
                $this->getIdIsValidRule($order),
                $this->getSaleIsValidRule($order),
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
                Rule::in([Sale::STATUS_RECEIVED, Sale::STATUS_COMPLETED]),
                $this->getStatusRule($order),
            ],

        ];
    }


    protected function alterFillData($data, Model $order = null)
    {
        // Never allow shipping_address to be used or passed.
        // Instead calculate from address_id.
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
}
