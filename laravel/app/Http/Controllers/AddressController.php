<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    protected $modelClass = Address::class;

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin');
    }

    protected function validationRules(?Model $address)
    {
        $required = !$address ? 'required|' : '';
        return [
            'address' => $required . 'string',
            'region' => $required . 'string',
            'zone' => $required . 'string',
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
     * Return a Closure that modifies the index query.
     * The closure receives the $query as a parameter.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            $user = request()->user;
            return $query->where('user_id', $user->id);
        };
    }

    /**
     * This route get two models, $user and $address.
     * Only $user is passed as a parameter to the parent::ownerDelete,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function show(Request $request, Model $user)
    {
        return parent::show($request, $request->route()->parameters['address']);
    }

    /**
     * This route get two models, $user and $address.
     * Only $user is passed as a parameter to the parent::ownerDelete,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function ownerDelete(Request $request, Model $user)
    {
        return parent::ownerDelete($request, $request->route()->parameters['address']);
    }

    /**
     * This route get two models, $user and $address.
     * Only $user is passed as a parameter to the parent::ownerDelete,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function delete(Request $request, Model $user)
    {
        return parent::delete($request, $request->route()->parameters['address']);
    }

    /**
     * This route get two models, $user and $address.
     * Only $user is passed as a parameter to the parent::ownerDelete,
     * we need to retrieve the $address from the request and pass it
     * to the parent.
     */
    public function update(Request $request, Model $user)
    {
        return parent::update($request, $request->route()->parameters['address']);
    }
}
