<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeChatUser extends Model
{
    //
    public function apply()
    {
        return $this->hasOne('App\Models\ApplyForm','user_id','id');
    }
}
