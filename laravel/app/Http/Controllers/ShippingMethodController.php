<?php

namespace App\Http\Controllers;

use App\ShippingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    public $modelClass = ShippingMethod::class;

    protected function validationRules(?Model $shippingMethod)
    {
        $required = !$shippingMethod ? 'required|' : '';
        $ignore = $shippingMethod ? ',' . $shippingMethod->id : '';
        return [
            'name' => $required . 'string|unique:shipping_methods,name' . $ignore,
            'description_seller' => $required . 'string',
            'description_buyer' => $required . 'string',
        ];
    }
}
