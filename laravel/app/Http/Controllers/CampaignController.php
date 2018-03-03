<?php

namespace App\Http\Controllers;

use App\Campaign;
use Illuminate\Database\Eloquent\Model;

class CampaignController extends Controller
{
    public $modelClass = Campaign::class;

    public function alterValidateData($data)
    {
        $data['slug'] = str_slug(array_get($data, 'name'));
        return $data;
    }

    protected function validationRules(?Model $campaign)
    {
        $required = !$campaign ? 'required|' : '';
        $ignore = $campaign ? ',' . $campaign->id : '';
        return [
            'name' => $required . 'string|unique:campaigns,name' . $ignore,
            'slug' => $required . 'string|unique:campaigns,slug' . $ignore,
        ];
    }
}
