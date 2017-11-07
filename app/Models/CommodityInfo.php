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

    public function standards()
    {
        return $this->hasMany('App\Models\Standard','commodity_id','id');
    }
    public function pictures()
    {
        return $this->hasMany('App\Models\CommodityPicture','commodity_id','id');
    }
}
