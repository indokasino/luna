<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lewati verifikasi jika ini adalah challenge verification
        if ($request->has('challenge')) {
            return $next($request);
        }

        // Dapatkan token dari header
        $token = null;
        
        // Coba dapatkan token dari header Authorization
        $authHeader = $request->header('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
        
        // Coba dapatkan token dari header X-Webhook-Token jika tidak ada di Authorization
        if (!$token) {
            $token = $request->header('X-Webhook-Token');
        }
        
        // Validasi token
        $webhookSecret = config('app.webhook_secret');
        
        if (!$token || $token !== $webhookSecret) {
            Log::warning('Invalid webhook token', [
                'provided_token' => $token,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid webhook token'
            ], 401);
        }
        
        return $next($request);
    }
}