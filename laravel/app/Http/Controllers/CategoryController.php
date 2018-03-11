<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    protected $modelClass = Category::class;

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

        // Custom rule needs message attached to it.
        // Can't use ValidationMessages for this one.
        if ($category) {
            $rules['parent_id'][] = function ($attribute, $value, $fail) use ($category) {
                if (count($category->children) && $value) {
                    return $fail(__('Esta categoría tiene hijos. No pude tener un padre.'));
                }
            };
        };

        return $rules;
    }

    protected function validationMessages()
    {
        return ['parent_id.exists' => __('validation.not_in')];
    }

    protected function alterIndexQuery()
    {
        return function ($query) {
            return $query->whereNull('parent_id')->with(['children']);
        };
    }

    public function show(Request $request, Model $category)
    {
        $category = parent::show($request, $category)->load(['children', 'parent']);

        # Products including those of subcategories.
        $products = Product::where(function ($query) use ($category) {
            # Query grouping creates an OR condition.
            # Products within or category
            $query->where('category_id', $category->id)
            # OR within its subcategories.
            ->orWhereHas('category', function ($query) use ($category) {
                $query->where('parent_id', $category->id);
            });
        });
        $products = $this->applyParamsToQuery($request, $products, ProductController::class);
        $category->products = $products->simplePaginate($request->items);

        return $category;
    }
}
