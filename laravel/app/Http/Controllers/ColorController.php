<?php

namespace App\Http\Controllers;

use App\Color;
use Illuminate\Database\Eloquent\Model;

class ColorController extends AdminController
{
    protected $modelClass = Color::class;
    public static $allowedWhereLike = ['slug'];

    protected function alterValidateData($data, Model $color = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $color)
    {
        $required = !$color ? 'required|' : '';
        $ignore = $color ? ',' . $color->id : '';
        return [
            'name' => $required . 'string|unique:colors,name' . $ignore,
            'slug' => 'string|unique:colors,slug' . $ignore,
        ];
    }
}
