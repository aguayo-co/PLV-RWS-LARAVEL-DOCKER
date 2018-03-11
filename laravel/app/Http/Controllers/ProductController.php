<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected $modelClass = Product::class;

    public static $allowedOrderBy = ['id', 'created_at', 'updated_at', 'price', 'commission'];
    public static $orderByAliases = ['prilov' => 'commission'];

    public static $allowedWhereIn = [
        'id',
        'brand_id',
        'category_id',
        'condition_id',
        'status_id',
    ];
    public static $allowedWhereHas = ['color_ids' => 'colors', 'campaign_ids' => 'campaigns'];
    public static $allowedWhereBetween = ['price'];

    public static $searchIn = ['title', 'description'];

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

    protected function alterValidateData($data, Model $product = null)
    {
        $data['slug'] = str_slug(array_get($data, 'title'));
        return $data;
    }

    protected function validationRules(?Model $product)
    {
        $required = !$product ? 'required|' : '';
        return [
            'user_id' => 'integer|exists:users,id',
            'title' => $required . 'string',
            'slug' => 'string',
            'description' => $required . 'string',
            'dimensions' => $required . 'string',
            'original_price' => $required . 'numeric|between:0,999999999.99',
            'price' => $required . 'numeric|between:0,999999999.99',
            'commission' => $required . 'numeric|between:0,100',
            'brand_id' => $required . 'integer|exists:brands,id',
            # Sólo permite una categoría que tenga padre.
            'category_id' => [
                trim($required, '|'),
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],
            'color_ids' => $required . 'array|max:2',
            'color_ids.*' => 'integer|exists:colors,id',
            'campaign_ids' => 'array',
            'campaign_ids.*' => 'integer|exists:campaigns,id',
            'condition_id' => $required . 'integer|exists:conditions,id',
            'status_id' => $required . 'integer|exists:statuses,id',
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

    protected function alterFillData($data, Model $product = null)
    {
        if (!$product && !array_get($data, 'user_id')) {
            $user = Auth::user();
            $data['user_id'] = $user->id;
        }
        return $data;
    }

    /**
     * Only show available products on collections.
     */
    protected function alterIndexQuery()
    {
        return function ($query) {
            return $query->where('status', Product::AVAILABLE);
        };
    }
}
