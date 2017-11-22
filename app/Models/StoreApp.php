<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreApp extends Model
{
    //
    public function info()
    {
        return $this->hasOne('App\Models\StoreInfo','app_id','id');
    }
}
