<?php

namespace App\Http\Controllers;

use App\Condition;
use Illuminate\Database\Eloquent\Model;

class ConditionController extends Controller
{
    public $modelClass = Condition::class;

    public function alterValidateData($data, Model $condition = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $condition)
    {
        $required = !$condition ? 'required|' : '';
        $ignore = $condition ? ',' . $condition->id : '';
        return [
            'name' => $required . 'string|unique:conditions,name' . $ignore,
            'slug' => $required . 'string|unique:conditions,slug' . $ignore,
        ];
    }
}
