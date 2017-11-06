<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommodityInfo extends Model
{
    //
    public function commodities()
    {
        return $this->hasMany('App\Models\Commodity','commodity_id','id');
    }
}
