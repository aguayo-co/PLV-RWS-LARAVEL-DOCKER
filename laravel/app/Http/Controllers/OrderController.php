<?php

namespace App\Http\Controllers;

use App\Address;
use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Product;
use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                Rule::in([Sale::STATUS_RECEIVED]),
                $this->getStatusRule($order),
            ],
        ];
    }

    protected function getIdFromAttribute($attribute)
    {
        $matches = [];
        $matched = preg_match('/.*?\.([0-9]+)(\..*)?$/', $attribute, $matches);
        return $matched ? $matches[1] : null;
    }

    protected function getSaleFromAttribute($attribute, $order)
    {
        return $order->sales->firstWhere('id', $this->getIdFromAttribute($attribute));
    }

    /**
     * Rule that validates that the given sale is still in a ShoppingCart.
     */
    protected function getIdIsValidRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $saleId = $this->getIdFromAttribute($attribute);
            if (!$saleId) {
                return $fail(__('validation.integer'));
            }
        };
    }

    /**
     * Rule that validates that the given sale is still in a ShoppingCart.
     */
    protected function getSaleIsValidRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $sale = $this->getSaleFromAttribute($attribute, $order);
            if (!$sale) {
                $validIds = implode(', ', $order->sales->pluck('id')->all());
                return $fail(__('validation.in', ['values' => $validIds]));
            }
        };
    }

    /**
     * Rule that validates that the given sale is still in a ShoppingCart.
     */
    protected function getSaleInShoppingCartRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $sale = $this->getSaleFromAttribute($attribute, $order);
            // If no Sale found, skip.
            // Sale validation done on a different Rule.
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
            $sale = $this->getSaleFromAttribute($attribute, $order);
            // If no Sale found, skip.
            // Sale validation done on a different Rule.
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
