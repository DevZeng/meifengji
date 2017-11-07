<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Standard extends Model
{
    //
    public function attr()
    {
        return $this->hasMany('App\Models\Attribute','standard_id','id');
    }
}
