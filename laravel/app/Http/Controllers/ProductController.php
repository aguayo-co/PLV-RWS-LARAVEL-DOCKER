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

    protected function validationRules(?Model $product)
    {
        $required = !$product ? 'required|' : '';
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
            'color_ids' => $required . 'array|max:2',
            'color_ids.*' => 'exists:colors,id',
            'condition_id' => $required . 'exists:conditions,id',
            'status_id' => $required . 'exists:statuses,id',
            'images' => $required . 'array',
            'images.*' => 'image',
            'delete_images' => 'array',
            'delete_images.*' => 'string',
        ];
    }

    protected function validationMessages()
    {
        return ['category_id.exists' => trans('validation.not_in')];
    }

    public function postUpdate(Request $request, Model $product)
    {
        $product->campaigns()->sync($request->campaign_ids);
        return parent::postUpdate($request, $product);
    }

    public function postStore(Request $request, Model $product)
    {
        $product->campaigns()->attach($request->campaign_ids);
        return parent::postStore($request, $product);
    }

    protected function withCategory(Request $request, Model $category)
    {
        return Product::where('category_id', $category->id)
            ->orWhereHas('category', function ($query) use ($category) {
                $query->where('parent_id', $category->id);
            })
            ->simplePaginate($request->items);
    }

    protected function withCampaign(Request $request, Model $campaign)
    {
        return $campaign->products()->simplePaginate($request->items);
    }
}
