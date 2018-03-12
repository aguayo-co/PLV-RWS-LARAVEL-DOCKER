<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Return an array of validations rules to apply to the request data.
     *
     * All posible rules for all posible states are set here.
     * These rules validate that the data is correct, not whether it
     * can be used on the current Sale given its status.
     * That mus be handled elsewhere.
     *
     * @return array
     */
    protected function validationRules(?Model $sale)
    {
        return [
            'status' => [
                'integer',
                Rule::in([Sale::STATUS_SHIPPED, Sale::STATUS_DELIVERED, Sale::STATUS_CANCELED]),
            ],
        ];
    }

    protected function validate(array $data, Model $sale = null)
    {
        parent::validate($data, $sale);
        $status = array_get($data, 'status');
        if ($status == Sale::STATUS_SHIPPED && $sale->status < Sale::STATUS_PAYED) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Can not be set as shipped when Sale not PAYED.'
            );
        }

        if ($status == Sale::STATUS_DELIVERED && $sale->status < Sale::STATUS_PAYED) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Can not be set as delivered when Sale not PAYED.'
            );
        }

        if ($status == Sale::STATUS_CANCELED && !Auth::user()->hasRole('admin')) {
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
