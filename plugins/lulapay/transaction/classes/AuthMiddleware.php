<?php namespace Lulapay\Transaction\Classes;


use Closure;
use Lulapay\Merchant\Models\Merchant;
use Response;

class AuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $publicKey = $request->header('Public-Key');
        
        $serverKey = $request->header('Server-Key');
        if ( ! $publicKey OR ! $serverKey) {
            return Response::json([
                'error'   => true,
                'message' => 'Authorization Required',
            ], 401);
        }

        $merchant = Merchant::wherePublicKey($publicKey)->whereServerKey($serverKey)->first();
        
        if ( ! $merchant) {
            return Response::json([
                'error'   => true,
                'message' => 'Forbidden -- Check your API Key!',
            ], 403);
        }

        $request->merchant = $merchant;

        return $next($request);
    }
}
