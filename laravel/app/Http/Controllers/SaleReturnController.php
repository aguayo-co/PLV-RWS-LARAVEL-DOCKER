<?php

namespace App\Http\Controllers;

use App\SaleReturn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
     * When user is not admin, limit to current user returns.
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

    protected function validationRules(array $data, ?Model $return)
    {
        $validStatuses = [
            SaleReturn::STATUS_SHIPPED,
            SaleReturn::STATUS_DELIVERED,
            SaleReturn::STATUS_RECEIVED,
            SaleReturn::STATUS_COMPLETED,
            SaleReturn::STATUS_CANCELED
        ];
        $minStatus = $return ? $return->status : min($validStatuses);
        return [
            'status' => [
                'bail',
                'integer',
                Rule::in($validStatuses),
                $this->getStatusRule($return),
                // Do not go back in status.
                'min:' . $minStatus,
            ],
        ];
    }

    /**
     * Rule that validates that a SaleReturn status is valid.
     */
    protected function getStatusRule($return)
    {
        return function ($attribute, $value, $fail) use ($return) {
            $user = auth()->user();
            if ($user->hasRole('admin')) {
                return;
            }

            $value = (int)$value;

            if ($value === SaleReturn::STATUS_CANCELED) {
                return $fail(__('Only an Admin can cancel a Return.'));
            }

            $buyerStatuses = [
                SaleReturn::STATUS_SHIPPED,
                SaleReturn::STATUS_DELIVERED,
            ];
            $buyer = $return->sales->first()->order->user;
            if ($user->is($buyer) && !in_array($value, $buyerStatuses, true)) {
                return $fail(__('validation.in', ['values' => implode(', ', $buyerStatuses)]));
            }

            $sellerStatuses = [
                SaleReturn::STATUS_RECEIVED,
                SaleReturn::STATUS_COMPLETED,
            ];
            $seller = $return->sales->first()->user;
            if ($user->is($seller) && !in_array($value, $sellerStatuses, true)) {
                return $fail(__('validation.in', ['values' => implode(', ', $sellerStatuses)]));
            }
        };
    }
}
