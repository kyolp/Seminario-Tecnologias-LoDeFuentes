<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Controlador exclusivo de infraestructura para validación de rutas y protocolos (T2-07 y T2-08).
 * No implementa módulos funcionales de fases posteriores.
 */
class TestController extends BaseController
{
    /**
     * Prueba de método GET.
     */
    public function testGet(Request $request): Response
    {
        $param = $request->getQuery('echo', 'default');
        return $this->json([
            'method' => 'GET',
            'status' => 'OK',
            'echo' => $param,
        ]);
    }

    /**
     * Prueba de método POST.
     */
    public function testPost(Request $request): Response
    {
        $payload = $request->getPost('data', 'none');
        return $this->json([
            'method' => 'POST',
            'status' => 'OK',
            'received' => $payload,
        ], 201);
    }
}
