<?php

namespace App\Http\Controllers;

use App\Rules\EmptyWith;
use App\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public $modelClass = MenuItem::class;

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
            'title' => 'required|string',
            'url' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => 'nullable|exists:menu_items,id|required_without:menu_id|empty_with:menu_id',
            'menu_id' => 'nullable|exists:menus,id|required_without:parent_id|empty_with:parent_id',
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
