<?php

namespace App\Http\Controllers;

use App\Condition;
use Illuminate\Database\Eloquent\Model;

class ConditionController extends Controller
{
    public $modelClass = Condition::class;

    public function alterValidateData($data)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $condition)
    {
        return [
            'name' => 'required|string|unique:conditions',
            'slug' => 'required|string|unique:conditions',
        ];
    }
}
