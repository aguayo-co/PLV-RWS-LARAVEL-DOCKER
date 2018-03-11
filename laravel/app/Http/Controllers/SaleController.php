<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
            'shipped' => 'bool',
            'delivered' => 'bool',
        ];
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    protected function alterFillData($data, Model $sale = null)
    {
        // I $sale is null, shipped and delivered will not exist due to validations.
        if ((array_get($data, 'delivered') || array_get($data, 'shipped')) && !$sale->shipped) {
            $data['shipped'] = now();
        }
        // I $sale is null, delivered will not exist due to validations.
        if (array_get($data, 'delivered') && !$sale->delivered) {
            $data['delivered'] = now();
        }
        return $data;
    }

    public function update(Request $request, Model $sale)
    {
        if ($sale->status < Sale::PAYED) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Sale can not be changed until it has been PAYED.');
        }

        if ($request->shipped && $sale->status < Sale::PAYED) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Can not be set as shipped when Sale not PAYED.'
            );
        }

        if ($request->delivered && $sale->status < Sale::PAYED) {
            abort(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Can not be set as delivered when Sale not PAYED.'
            );
        }
        return parent::update($request, $sale);
    }
}
