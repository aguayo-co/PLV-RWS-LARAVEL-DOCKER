<?php

namespace App\Http\Controllers;

use App\SaleReturn;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    protected $modelClass = SaleReturn::class;

    public static $allowedWhereIn = ['id', 'sale_id'];

    public function __construct()
    {
        parent::__construct();
        $this->middleware('owner_or_admin')->only('show');
    }

    /**
     * When user is not admin, limit to current user sales.
     *
     * @return Closure
     */
    protected function alterIndexQuery()
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return;
        }

        return function ($query) use ($user) {
            return $query->where('user_id', $user->id);
        };
    }
}
