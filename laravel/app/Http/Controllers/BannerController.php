<?php

namespace App\Http\Controllers;

use App\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public $modelClass = Banner::class;

    public function alterValidateData($data, Model $banner = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $banner)
    {
        $required = !$banner ? 'required|' : '';
        $ignore = $banner ? ',' . $banner->id : '';
        return [
            'name' => $required . 'string|unique:banners' . $ignore,
            'slug' => $required . 'string|unique:banners' . $ignore,
            'title' => $required . 'string',
            'subtitle' => $required . 'string',
            'image' => $required . 'image',
            'button_text' => $required . 'string',
            'url' => $required . 'string',
        ];
    }

    public function postStore(Request $request, Model $banner)
    {
        if ($image = $request->file('image')) {
            $banner->image = $image;
        }
        return $banner;
    }
}
