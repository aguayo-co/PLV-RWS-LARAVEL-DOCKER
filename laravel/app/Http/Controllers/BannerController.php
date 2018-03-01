<?php

namespace App\Http\Controllers;

use App\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public $modelClass = Banner::class;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('role:admin', ['only' => ['store']]);
    }

    protected function validationRules(?Model $menuItem)
    {
        return [
            'name' => 'required|string|unique:banners',
            'title' => 'required|string',
            'subtitle' => 'required|string',
            'image' => 'required|image',
            'button_text' => 'required|string',
            'url' => 'required|string',
        ];
    }

    /**
     * Handle an update request for a banner.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $banner = parent::store($request);
        if ($image = $request->file('image')) {
            $banner->image = $image;
        }
        return $banner;
    }
}
