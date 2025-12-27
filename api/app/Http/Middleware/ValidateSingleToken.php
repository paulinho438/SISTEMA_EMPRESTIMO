<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ValidateSingleToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Verificar se o usuário está autenticado
            if (!auth()->check()) {
                return response()->json([
                    'message' => 'Não autenticado'
                ], 401);
            }

            $user = auth()->user();

            // Se o usuário não tem last_token_jti, permitir (compatibilidade com tokens antigos)
            if (!$user->last_token_jti) {
                return $next($request);
            }

            // Obter o jti do token atual
            $payload = auth()->payload();
            $currentTokenJti = $payload->get('jti');

            // Verificar se o token atual corresponde ao último token válido
            if ($currentTokenJti !== $user->last_token_jti) {
                // Token não é o mais recente, invalidar
                auth()->logout();
                
                return response()->json([
                    'message' => 'Sessão inválida. Você foi desconectado porque fez login em outro dispositivo.'
                ], 401);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao validar token: ' . $e->getMessage()
            ], 401);
        }
    }
}

