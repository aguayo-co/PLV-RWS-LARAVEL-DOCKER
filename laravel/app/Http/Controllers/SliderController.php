<?php

namespace App\Http\Controllers;

use App\Slider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public $modelClass = Slider::class;

    protected function alterValidateData($data, Model $slider = null)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $slider)
    {
        $required = !$slider ? 'required|' : '';
        $ignore = $slider ? ',' . $slider->id : '';
        return [
            'name' => $required . 'string|unique:sliders,name' . $ignore,
            'slug' => 'string|unique:sliders,slug' . $ignore,
            'large_text' => $required . 'string',
            'small_text' => 'nullable|string',
            'orientation' => $required . 'string',
            'font_color' => $required . 'string',
            'image' => $required . 'image',
            'button_text' => 'nullable|string',
            'url' => $required . 'string',
        ];
    }
}
