<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public $modelClass = Category::class;

    protected function alterValidateData($data, Model $category = null)
    {
        # ID needed to validate it is not self-referenced.
        $data['id'] = $category ? $category->id : false;
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $category)
    {
        $required = !$category ? 'required|' : '';
        $ignore = $category ? ',' . $category->id : '';
        $rules = [
            'name' => $required . 'string|unique:categories,name' . $ignore,
            'slug' => 'string|unique:categories,slug' . $ignore,
            'parent_id' => [
                'nullable',
                'integer',
                'different:id',
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
            return $query->whereNull('parent_id');
        };
    }

    public function show(Request $request, Model $category)
    {
        $category = parent::show($request, $category);

        #Products including those of subcategories.
        $products = Product::where('category_id', $category->id)
        ->orWhereHas('category', function ($query) use ($category) {
            $query->where('parent_id', $category->id);
        });

        $products = $products->setEagerLoads(array_except($products->getEagerLoads(), 'category'));
        $category->products = $products->simplePaginate($request->items);
        return $category->load(['children', 'parent']);
    }
}
