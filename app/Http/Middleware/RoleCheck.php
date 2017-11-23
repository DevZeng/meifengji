<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\RoleUser;
use Closure;
use Illuminate\Support\Facades\Auth;

class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role_name)
    {
        $uid = Auth::id();
        $role_id = RoleUser::where('user_id','=',$uid)->pluck('role_id')->first();
        $role = Role::find($role_id);
        if ($role->name == $role_name){
            return $next($request);
        }else{
            return response()->json([
                'code'=>'401',
                'msg'=>'无权访问！'
            ]);
        }

    }
}
