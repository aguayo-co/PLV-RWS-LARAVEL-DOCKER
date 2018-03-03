<?php

namespace App\Http\Controllers;

use App\Brand;
use Illuminate\Database\Eloquent\Model;

class BrandController extends Controller
{
    public $modelClass = Brand::class;

    public function alterValidateData($data)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $brand)
    {
        return [
            'name' => 'required|string|unique:brands',
            'slug' => 'required|string|unique:brands',
            'url' => 'nullable|string',
        ];
    }
}
