<?php

namespace App\Http\Middleware;

use Closure;
use App\Bankings\BankAccount;
use Illuminate\Contracts\Auth\Factory as Auth;

class SelfBankAccount
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth, BankAccount $bankAccount)
    {
        $this->auth        = $auth;
        $this->bankAccount = $bankAccount;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
            return response('Unauthorized.', 401);
        }
        $uuid                 = $request->route()[2]['uuid'];
        $request->bankAccount = $this->bankAccount->byUuid($uuid);

        return $next($request);
    }
}
