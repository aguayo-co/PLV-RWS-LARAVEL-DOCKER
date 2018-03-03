<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public $modelClass = Category::class;

    public function alterValidateData($data)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $category)
    {
        $required = !$category ? 'required|' : '';
        $ignore = $category ? ',' . $category->id : '';
        $rules = [
            'name' => $required . 'string|unique:categories,name' . $ignore,
            'slug' => $required . 'string|unique:categories,slug' . $ignore,
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('parent_id');
                }),
            ],
        ];

        if ($category) {
            $rules['parent_id'][] = function ($attribute, $value, $fail) use ($category) {
                if (count($category->children) && $value) {
                    return $fail(trans('Esta categoría tiene hijos. No pude tener un padre.'));
                }
            };
        };

        return $rules;
    }

    protected function validationMessages()
    {
        return ['parent_id.exists' => trans('validation.not_in')];
    }

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
}
