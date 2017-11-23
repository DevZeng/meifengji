<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreInfo extends Model
{
    //
    public function pic()
    {
        return $this->hasMany('App\Models\InfoPic','info_id','id');
    }
}
