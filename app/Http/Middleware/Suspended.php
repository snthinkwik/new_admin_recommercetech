<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Suspended
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
        // == must be used instead ===, in production suspended is returned as string instead of integer
        if ($request->user()->suspended == 1)
        {
            return redirect()->route('stock')->with('messages.error', "Your account is suspended. Please contact us for further information.");
        }
        return $next($request);
    }
}
