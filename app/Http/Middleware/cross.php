<?php

namespace App\Http\Middleware;

use Closure;

class cross
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //$response = $next($request);
        //$response->header('Access-Control-Allow-Origin', 'http://fwq.gdmeika.com');
//        $response->header('Access-Control-Allow-Origin', 'http://120.78.49.181');
//        $response->header('Access-Control-Allow-Origin', 'http://192.168.3.44:8089');
//        $response->header('Access-Control-Allow-Origin', 'http://119.23.255.177:8091');
        //$response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, Accept');
       // $response->header('Access-Control-Allow-Methods', 'GET,POST,PATCH,PUT,OPTIONS,DELETE');
       // $response->header('Access-Control-Allow-Credentials', 'true');
       // return $response;
        $allowOrigin = [
            'http://fwq.gdmeika.com',
            'http://localhost:8089'
        ];
        $response = $next($request);
        if (in_array($request->header('Origin'),$allowOrigin)){
            $response->header('Access-Control-Allow-Origin', $request->header('Origin'));
            $response->header('Access-Control-Allow-Headers', 'Origin, Access-Control-Request-Headers, SERVER_NAME, Access-Control-Allow-Headers, cache-control, token, X-Requested-With, Content-Type, Accept, Connection, User-Agent, Cookie, X-XSRF-TOKEN');
            $response->header('Access-Control-Allow-Methods', 'OPTIONS,GET, POST, PATCH, PUT, DELETE');
            $response->header('Access-Control-Allow-Credentials', 'true');
        }
        return $response;
    }
}
