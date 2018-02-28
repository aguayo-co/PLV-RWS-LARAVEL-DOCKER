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
            'title' => 'required|string',
            'url' => 'required|string',
            'icon' => 'required|string',
            'parent_id' => 'exists:menu_items,id|empty_with:menu_id',
            'menu_id' => 'exists:menus,id|empty_with:parent_id',
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  Illuminate\Database\Eloquent\Model $menu_item
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Model $menuItem)
    {
        return $menuItem->makeVisible('addresses');
    }

}
