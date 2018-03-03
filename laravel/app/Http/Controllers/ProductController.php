<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public $modelClass = Product::class;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('role:seller|admin', ['only' => ['store', 'update']]);
    }

    protected function validationRules(?Model $user)
    {
        $required = !$user ? 'required|' : '';
        return [
            'user_id' => $required . 'exists:users,id',
            'title' => $required . 'string',
            'description' => $required . 'string',
            'dimensions' => $required . 'string',
            'original_price' => $required . 'numeric|between:0,999999999.99',
            'price' => $required . 'numeric|between:0,999999999.99',
            'commission' => $required . 'numeric|between:0,100',
            'brand_id' => $required . 'exists:brands,id',
            'category_id' => [
                trim($required, '|'),
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],
            'color_ids' => $required . 'max:2',
            'color_ids.*' => 'exists:colors,id',
            'condition_id' => $required . 'exists:conditions,id',
            'status_id' => $required . 'exists:statuses,id',
            'images' => $required,
            'images.*' => 'image',
            'delete_images.*' => 'string',
        ];
    }

    protected function validationMessages()
    {
        return ['category_id.exists' => trans('validation.not_in')];
    }

    /**
     * Handle an update request for the product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Illuminate\Database\Eloquent\Model $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Model $product)
    {
        $product = parent::update($request, $product);
        if ($images = $request->file('images')) {
            $product->images = $images;
        }
        if ($deleteImages = $request->delete_images) {
            $product->delete_images = $deleteImages;
        }
        return $product;
    }

    /**
     * Handle a create request for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $product = parent::store($request);
        $product->images = $request->file('images');
        $product->colors()->attach($request->color_ids);
        return $product;
    }

    protected function categories(Request $request, Model $category)
    {
        return Product::where('category_id', $category->id)
            ->orWhereHas('category', function ($query) use ($category) {
                $query->where('parent_id', $category->id);
            })
            ->get();
    }
}
