<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\PasswordRecoveryService;

/**
 * Controlador para el flujo público de Recuperación de Contraseña — Fase 13 (RF-03).
 */
class PasswordRecoveryController extends BaseController
{
    private PasswordRecoveryService $recoveryService;
    private AuthService $auth;

    public function __construct(
        ?PasswordRecoveryService $recoveryService = null,
        ?AuthService $auth = null
    ) {
        $this->recoveryService = $recoveryService ?? new PasswordRecoveryService();
        $this->auth = $auth ?? new AuthService();
    }

    /**
     * Mostrar formulario para solicitar recuperación de contraseña.
     */
    public function showRequestForm(Request $request): Response
    {
        if ($this->auth->check()) {
            return $this->redirectPorRol();
        }

        $html = View::render('auth.recuperar-contrasena', [
            'title' => 'Recuperar Contraseña',
            'correo' => '',
            'mensaje_exito' => null,
            'mensaje_error' => null,
            'errors' => [],
            'enlace_simulado' => null,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Procesar solicitud de recuperación y emitir respuesta sin enumeración.
     */
    public function sendRecoveryLink(Request $request): Response
    {
        if ($this->auth->check()) {
            return $this->redirectPorRol();
        }

        $correo = (string) $request->input('correo', '');
        $result = $this->recoveryService->solicitarRecuperacion($correo);

        if (!$result['success']) {
            $html = View::render('auth.recuperar-contrasena', [
                'title' => 'Recuperar Contraseña',
                'correo' => $correo,
                'mensaje_exito' => null,
                'mensaje_error' => $result['message'],
                'errors' => $result['errors'] ?? [],
                'enlace_simulado' => null,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $html = View::render('auth.recuperar-contrasena', [
            'title' => 'Recuperar Contraseña',
            'correo' => '',
            'mensaje_exito' => $result['message'],
            'mensaje_error' => null,
            'errors' => [],
            'enlace_simulado' => $result['enlace_simulado'] ?? null,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Mostrar formulario para restablecer la contraseña validando el token recibido en la URL.
     */
    public function showResetForm(Request $request): Response
    {
        if ($this->auth->check()) {
            return $this->redirectPorRol();
        }

        $token = (string) $request->query('token', '');

        if ($token === '') {
            $html = View::render('auth.restablecer-contrasena', [
                'title' => 'Restablecer Contraseña',
                'token' => '',
                'token_valido' => false,
                'error_token' => 'Debe ingresar a través del enlace de recuperación completo provisto.',
                'mensaje_error' => null,
                'errors' => [],
            ], 'main');

            return new Response($html, 400, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $val = $this->recoveryService->validarToken($token);

        if (!$val['valido']) {
            $html = View::render('auth.restablecer-contrasena', [
                'title' => 'Restablecer Contraseña',
                'token' => $token,
                'token_valido' => false,
                'error_token' => $val['error'],
                'mensaje_error' => null,
                'errors' => [],
            ], 'main');

            return new Response($html, 400, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $html = View::render('auth.restablecer-contrasena', [
            'title' => 'Restablecer Contraseña',
            'token' => $token,
            'token_valido' => true,
            'error_token' => null,
            'mensaje_error' => null,
            'errors' => [],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Procesar el cambio definitivo de contraseña con validación de un solo uso del token.
     */
    public function resetPassword(Request $request): Response
    {
        if ($this->auth->check()) {
            return $this->redirectPorRol();
        }

        $token = (string) $request->input('token', '');
        $password = (string) $request->input('password', '');
        $passwordConfirmation = (string) $request->input('password_confirmation', '');

        // Pre-validar token
        $val = $this->recoveryService->validarToken($token);
        if (!$val['valido']) {
            $html = View::render('auth.restablecer-contrasena', [
                'title' => 'Restablecer Contraseña',
                'token' => $token,
                'token_valido' => false,
                'error_token' => $val['error'],
                'mensaje_error' => null,
                'errors' => [],
            ], 'main');

            return new Response($html, 400, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $result = $this->recoveryService->restablecerContrasena($token, $password, $passwordConfirmation);

        if (!$result['success']) {
            $status = !empty($result['errors']) ? 422 : 400;

            $html = View::render('auth.restablecer-contrasena', [
                'title' => 'Restablecer Contraseña',
                'token' => $token,
                'token_valido' => true,
                'error_token' => null,
                'mensaje_error' => $result['message'],
                'errors' => $result['errors'] ?? [],
            ], 'main');

            return new Response($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::flash('flash_success', $result['message']);
        return Response::redirect('/login');
    }

    private function redirectPorRol(): Response
    {
        $user = $this->auth->user();
        if ($user !== null && $user->isAdmin()) {
            return Response::redirect('/administracion');
        }
        if ($user !== null && $user->isDocente()) {
            return Response::redirect('/docente');
        }
        return Response::redirect('/');
    }
}
