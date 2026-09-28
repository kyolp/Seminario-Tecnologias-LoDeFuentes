<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;

/**
 * Controlador de autenticación: inicio y cierre de sesión.
 */
class AuthController extends BaseController
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    /**
     * Mostrar la pantalla de inicio de sesión.
     */
    public function showLoginForm(Request $request): Response
    {
        // Si ya cuenta con sesión activa, redirigir a su área respectiva
        if ($this->auth->check()) {
            $user = $this->auth->user();
            if ($user !== null && $user->isAdmin()) {
                return Response::redirect('/administracion');
            }
            if ($user !== null && $user->isDocente()) {
                return Response::redirect('/docente');
            }
            return Response::redirect('/');
        }

        $html = View::render('auth.login', [
            'title' => 'Iniciar Sesión',
            'error' => null,
            'old_username' => '',
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Procesar la solicitud de inicio de sesión.
     */
    public function login(Request $request): Response
    {
        $username = (string) $request->input('nombre_usuario', '');
        $password = (string) $request->input('password', '');

        $success = $this->auth->attempt($username, $password);

        if (!$success) {
            $html = View::render('auth.login', [
                'title' => 'Iniciar Sesión',
                'error' => $this->auth->getLastError() ?? 'Credenciales inválidas.',
                'old_username' => $username,
            ], 'main');

            return new Response($html, 401, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $user = $this->auth->user();

        // Redirección hacia el área permitida según rol
        if ($user !== null && $user->isAdmin()) {
            return Response::redirect('/administracion');
        }

        if ($user !== null && $user->isDocente()) {
            return Response::redirect('/docente');
        }

        return Response::redirect('/');
    }

    /**
     * Procesar el cierre de sesión y destrucción de datos sensibles.
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout();

        return Response::redirect('/login');
    }
}
