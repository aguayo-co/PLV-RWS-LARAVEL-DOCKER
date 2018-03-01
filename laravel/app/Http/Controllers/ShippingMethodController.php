<?php

namespace App\Http\Controllers;

use App\ShippingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    public $modelClass = ShippingMethod::class;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:admin', ['only' => ['store']]);
    }

    protected function validationRules(?Model $menuItem)
    {
        return [
            'name' => 'required|string|unique:shipping_methods',
            'description_seller' => 'required|string',
            'description_buyer' => 'required|string',
        ];
    }
}
