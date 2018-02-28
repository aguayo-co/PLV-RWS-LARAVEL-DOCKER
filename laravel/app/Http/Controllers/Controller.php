<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('owner_or_admin:user', ['only' => ['update']]);
    }

    protected function validationRules(?Model $model)
    {
        throw new Exception('Not implemented');
    }

    protected function validationMessages()
    {
        return [];
    }
    /**

     * Validate given data.
     *
     * @param  array  $data
     * @param  \App\User  $user
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validate(array $data, Model $model = null)
    {
        return Validator::make(
            $data,
            $this->validationRules($model),
            $this->validationMessages()
        )->validate();
    }

    /**
     * Display the specified resource.
     *
     * @param  Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Model $model)
    {
        return $model;
    }

    /**
     * Alter data before validation.
     *
     * @param  array  $data
     * @return array
     */
    public function alterValidateData($data)
    {
        return $data;
    }

    /**
     * Alter data to be passed to fill method.
     *
     * @param  array  $data
     * @return array
     */
    public function alterFillData($data)
    {
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $this->validate($this->alterValidateData($data));
        $model = (new $this->modelClass)->create($this->alterFillData($data));
        return $model;
    }

    /**
     * Handle an update request for a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Model $model)
    {
        $data = $request->all();
        $this->validate($this->alterValidateData($data), $model);
        $model->fill($this->alterFillData($data))->save();
        return $model;
    }
}
