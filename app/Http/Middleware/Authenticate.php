<?php

namespace App\Http\Middleware;

use App\AllowedIp;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Contracts\Auth\Guard;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth) {

        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {


        if ($this->auth->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest('auth/login');
            }
        }

        return $next($request);
        //*****new code******//
        if (
            $this->auth->user()->type == 'admin' &&
            ($request->ip() !== dns_get_record('trg1.dyndns-remote.com')[0]['ip']) &&
            !in_array($request->user()->email, ['radoslaw.kowalczyk@netblink.net', 'anna@recommercetech.co.uk', 'lukasz.tlalka@netblink.net'])) {
            $ipAddress = AllowedIp::where('ip_address', $request->ip())->first();
            if ($ipAddress) {
                $ip = AllowedIp::find($ipAddress->id);
            } else {
                $ip = new AllowedIp();
            }
            $ip->ip_address = $request->ip();
            $ip->save();
        }


        // if allowed_ip, update last_login date
        if ($this->auth->user()->type == 'admin' && in_array($request->ip(), AllowedIp::get()->lists('ip_address'))) {
            $allowedIp = AllowedIp::where('ip_address', $request->ip())->first();
            $allowedIp->last_login = Carbon::now();
            $allowedIp->save();
        }

        if ($this->auth->user()->type == 'user') {
            $url = config('services.trg_uk.url') . "/auth/login?userhash=" . $this->auth->user()->hash;
            $this->auth->logout();
            return redirect()->away($url);
        }

        return $next($request);

    }


}
