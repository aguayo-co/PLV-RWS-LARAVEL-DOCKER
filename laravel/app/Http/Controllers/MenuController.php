<?php

namespace App\Http\Controllers;

use App\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public $modelClass = Menu::class;

    protected function validationRules(?Model $menu)
    {
        return [
            'name' => 'required|string|unique:menus',
        ];
    }
}
