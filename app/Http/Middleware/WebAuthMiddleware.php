<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class WebAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Contracts\Response\RedirectResponse)  $next
     * @return \Illuminate\Http\Contracts\Response\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is already authenticated via session
        if (Auth::check()) {
            return $next($request);
        }

        // Try to get token from request header or query parameter
        $token = $request->header('Authorization') ? 
                 str_replace('Bearer ', '', $request->header('Authorization')) : 
                 $request->query('token');

        if (!$token) {
            return redirect('/login');
        }

        try {
            // Verify the token
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return redirect('/login');
            }

            // Log the user in via session
            Auth::login($user);
            
            return $next($request);
        } catch (TokenExpiredException $e) {
            return redirect('/login')->with('error', 'Token has expired');
        } catch (TokenInvalidException $e) {
            return redirect('/login')->with('error', 'Invalid token');
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Authentication failed');
        }
    }
} 