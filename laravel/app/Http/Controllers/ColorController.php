<?php

namespace App\Http\Controllers;

use App\Color;
use Illuminate\Database\Eloquent\Model;

class ColorController extends Controller
{
    public $modelClass = Color::class;

    public function alterValidateData($data)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $color)
    {
        $required = !$color ? 'required|' : '';
        $ignore = $color ? ',' . $color->id : '';
        return [
            'name' => $required . 'string|unique:colors' . $ignore,
            'slug' => $required . 'string|unique:colors' . $ignore,
        ];
    }
}
