<?php

namespace Ylplabs\LaravelMsGraphApi;

use Closure;
use Ylplabs\LaravelMsGraphApi\Facades\MsGraphAPI;

class MsGraphAPIAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (MsGraphAPI::getTokenData() === null) {
            return MsGraphAPI::connect();
        }

        return $next($request);
    }
}
