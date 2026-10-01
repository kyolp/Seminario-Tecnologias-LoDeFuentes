<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Services\PortalDocenteService;

/**
 * Controlador del Portal Docente.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Administra el acceso exclusivo para usuarios con rol DOCENTE a su información académica,
 * asignaciones vigentes y nómina de estudiantes matriculados (RF-17).
 */
class DocenteController extends BaseController
{
    private AuthService $auth;
    private PortalDocenteService $portalService;

    public function __construct(?AuthService $auth = null, ?PortalDocenteService $portalService = null)
    {
        $this->auth = $auth ?? new AuthService();
        $this->portalService = $portalService ?? new PortalDocenteService();
    }

    /**
     * Página principal del Portal Docente.
     * Muestra el perfil del docente, sus asignaciones activas y la nómina de estudiantes.
     */
    public function index(Request $request): Response
    {
        // 1. Verificación de autenticación
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        // 2. Revalidación autoritativa en tiempo real de rol y estado activo (Fase 3 / Fase 4)
        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'No posee los privilegios suficientes para acceder a esta área institucional.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // 3. Resolución autoritativa de identidad: Docente derivado exclusivamente de la sesión
        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Su cuenta docente no se encuentra activa o no existe un perfil docente vinculado.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // 4. Detección y defensa contra manipulación de parámetros (Parameter Tampering)
        // Soporte para parámetro `seccion` o `id_seccion`
        $seccionParam = $request->query('seccion') ?? $request->query('id_seccion');
        $idSeccionSolicitada = null;

        if ($seccionParam !== null && is_numeric($seccionParam)) {
            $idSeccionSolicitada = (int)$seccionParam;

            // Barrera de aislamiento horizontal: Si la sección no pertenece a sus asignaciones activas -> HTTP 403
            if (!$this->portalService->isSeccionAsignadaADocente($docente->id_docente, $idSeccionSolicitada)) {
                return new Response(View::render('errors.403', [
                    'title' => 'Acceso Denegado - 403',
                    'message' => 'No tiene autorización para consultar la nómina de estudiantes ni la información de la sección solicitada.',
                    'userRole' => 'DOCENTE',
                    'requiredRole' => 'DOCENTE TITULAR DE LA SECCIÓN',
                ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
            }
        }

        // 5. Carga de datos autorizados a través del servicio
        $resumen = $this->portalService->obtenerResumenPortal($docente->id_docente, $idSeccionSolicitada);

        if (!$resumen['autorizado']) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => $resumen['mensaje_aviso'] ?? 'Acceso no autorizado.',
                'userRole' => 'DOCENTE',
                'requiredRole' => 'DOCENTE TITULAR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // 6. Renderizado de vista con variables saneadas
        $html = View::render('docente.index', [
            'title' => 'Portal Docente — Escuela Oficial Rural Mixta Lo de Fuentes',
            'user' => $user,
            'docente' => $docente,
            'asignaciones' => $resumen['asignaciones'],
            'asignacion_seleccionada' => $resumen['asignacion_seleccionada'],
            'estudiantes' => $resumen['estudiantes'],
            'total_estudiantes' => $resumen['total_estudiantes'],
            'sin_asignacion' => $resumen['sin_asignacion'],
            'mensaje_aviso' => $resumen['mensaje_aviso'] ?? null,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
