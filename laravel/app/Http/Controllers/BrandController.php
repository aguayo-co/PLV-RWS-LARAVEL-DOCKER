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
        $required = !$brand ? 'required|' : '';
        $ignore = $brand ? ',' . $brand->id : '';
        return [
            'name' => $required . 'string|unique:brands' . $ignore,
            'slug' => $required . 'string|unique:brands' . $ignore,
            'url' => 'nullable|string',
        ];
    }
}
