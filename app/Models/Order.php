<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    public function express()
    {
        return $this->hasOne('App\Models\Express','number','number');
    }
}
