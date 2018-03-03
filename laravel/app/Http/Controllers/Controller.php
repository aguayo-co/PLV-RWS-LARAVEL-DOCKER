<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Schema;
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
        /**
         * Only an admin or the owner can update models.
         */
        $this->middleware('owner_or_admin', ['only' => ['update']]);
        $this->middleware('self_or_admin', ['only' => ['update', 'store']]);
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
        # This is a hack to ensure we don't pass a string we can't put in the DB.
        $rules = $this->validationRules($model);
        foreach ($rules as &$rule) {
            if (is_string($rule) && strpos($rule, 'string') !== false && strpos($rule, 'max:') === false) {
                $rule = $rule . '|max:' . Schema::getFacadeRoot()::$defaultStringLength;
            }
        }

        return Validator::make(
            $data,
            $rules,
            $this->validationMessages()
        )->validate();
    }


    /**
     * Return a Closure to be applied to the index query.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        return;
    }

    /**
     * Display all the resources.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $alter = $this->alterIndexQuery();
        return call_user_func($this->modelClass . '::when', $alter, $alter)->get();
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
        $model = call_user_func($this->modelClass . '::create', $this->alterFillData($data));
        return $model->fresh();
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
        return $model->fresh();
    }
}
