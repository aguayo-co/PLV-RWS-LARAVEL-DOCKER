<?php

namespace App\Http\Controllers;

use App\Status;
use Illuminate\Database\Eloquent\Model;

class StatusController extends AdminController
{
    protected $modelClass = Status::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $status = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $status)
    {
        $required = !$status ? 'required|' : '';
        $ignore = $status ? ',' . $status->id : '';
        return [
            'name' => $required . 'string|unique:statuses,name' . $ignore,
            'slug' => 'string|unique:statuses,slug' . $ignore,
        ];
    }
}
