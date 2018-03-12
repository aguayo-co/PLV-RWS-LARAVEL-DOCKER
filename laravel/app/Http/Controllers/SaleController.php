<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
                Rule::in([Sale::STATUS_SHIPPED, Sale::STATUS_DELIVERED]),
            ],
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
        $status = array_get($data, 'status');
        switch (array_get($data, 'status')) {
            case Sale::STATUS_DELIVERED:
                if (!$sale->delivered) {
                    $data['delivered'] = now();
                };
                // If marked as delivered, has to be marked as shipped.
                // Do not break the switch to check and fill below.
            case Sale::STATUS_SHIPPED:
                if (!$sale->shipped) {
                    $data['shipped'] = now();
                }
        }
        // Status should never go back, always advance.
        $data['status'] = max($status, $sale->status);
        return $data;
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
    }

    public function update(Request $request, Model $sale)
    {
        if ($sale->status < Sale::STATUS_PAYED) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Sale can not be changed until it has been PAYED.');
        }
        return parent::update($request, $sale);
    }
}
