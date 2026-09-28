<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;

/**
 * Controlador provisional de área administrativa para validación de rol ADMINISTRADOR.
 */
class AdminController extends BaseController
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    /**
     * Página provisional de área administrativa.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();

        $html = View::render('admin.index', [
            'title' => 'Área administrativa — sesión válida',
            'user' => $user,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
