<?php

namespace Netflex\Pages\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Netflex\Support\JWT;
use Netflex\Foundation\Variable;
use Netflex\Pages\JwtPayload;

class JwtProxy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     */
    public function handle($request, Closure $next)
    {
        $this->authenticate($request);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request)
    {
        if ($token = $request->get('token')) {
            if ($payload = JWT::decodeAndVerify($token, Variable::get('netflex_api'))) {
                $request->offsetUnset('token');
                App::bind('JwtPayload', function () use ($payload) {
                    return new JwtPayload(json_decode(json_encode($payload), true));
                });

                return;
            }
        }

        $this->unauthenticated($request);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request)
    {
        abort(401);
    }

}
