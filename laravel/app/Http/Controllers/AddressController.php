<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public $modelClass = Address::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin');
    }

    protected function validationRules(?Model $model)
    {
        return [
            'address' => 'required|string',
            'region' => 'required|string',
            'zone' => 'required|string',
        ];
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    protected function alterFillData($data, Model $address = null)
    {
        $user = request()->route('user');
        $data['user_id'] = $user->id;
        return $data;
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Illuminate\Database\Eloquent\Model $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Model $user)
    {
        return $user->addresses()->simplePaginate();
    }
}
