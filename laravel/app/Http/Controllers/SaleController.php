<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * This Controller handles actions initiated by the Seller,
 * or by Admins but when modifying a Sale for a Seller.
 *
 * Actions that should be taken by the Buyer should be handled in the
 * Order Controller.
 * A Buyer should not act directly on the Sale, but always through
 * the Order.
 */
class SaleController extends Controller
{
    protected $modelClass = Sale::class;

    public static $allowedWhereIn = ['id', 'user_id'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
    }

    /**
     * When user is not admin, limit to current user sales.
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
     * All posible rules for all posible states are set here.
     * These rules validate that the data is correct, not whether it
     * can be used on the current Sale given its status.
     *
     * @return array
     */
    protected function validationRules(?Model $sale)
    {
        return [
            'shipment_details' => 'array',
            'status' => [
                'integer',
                Rule::in([Sale::STATUS_SHIPPED, Sale::STATUS_DELIVERED, Sale::STATUS_CANCELED]),
            ],
        ];
    }

    /**
     * Apart from data validation, validate requested status change.
     */
    protected function validate(array $data, Model $sale = null)
    {
        parent::validate($data, $sale);
        $status = (int)array_get($data, 'status');
        if ($status === Sale::STATUS_SHIPPED && $sale->status < Sale::STATUS_PAYED) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Can not be set as shipped when Sale not PAYED.'
            );
        }

        if ($status === Sale::STATUS_DELIVERED && $sale->status < Sale::STATUS_PAYED) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Can not be set as delivered when Sale not PAYED.'
            );
        }

        if ($status === Sale::STATUS_CANCELED && !auth()->user()->hasRole('admin')) {
            abort(
                Response::HTTP_FORBIDDEN,
                'Only an Admin can cancel a Sale.'
            );
        }
    }

    public function update(Request $request, Model $sale)
    {
        if ($sale->status < Sale::STATUS_PAYED) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Sale can not be changed until it has been PAYED.');
        }
        return parent::update($request, $sale);
    }
}
