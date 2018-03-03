<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public $modelClass = Category::class;

    /**
     * Return a Closure to be applied to the index query.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            return $query->whereNull('parent_id')->with('children');
        };
    }

    public function alterValidateData($data)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $category)
    {
        return [
            'name' => 'required|string|unique:categories',
            'slug' => 'required|string|unique:categories',
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('parent_id');
                }),
            ]
        ];
    }

    protected function validationMessages()
    {
        return ['parent_id.exists' => trans('validation.not_in')];
    }
}
