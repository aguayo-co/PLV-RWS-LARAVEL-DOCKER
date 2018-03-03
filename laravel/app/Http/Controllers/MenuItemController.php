<?php

namespace App\Http\Controllers;

use App\Rules\EmptyWith;
use App\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public $modelClass = MenuItem::class;

    protected function validationRules(?Model $menuItem)
    {
        return [
            'name' => 'required|string',
            'url' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => 'nullable|exists:menu_items,id|required_without:menu_id|empty_with:menu_id',
            'menu_id' => 'nullable|exists:menus,id|required_without:parent_id|empty_with:parent_id',
        ];
    }
}
