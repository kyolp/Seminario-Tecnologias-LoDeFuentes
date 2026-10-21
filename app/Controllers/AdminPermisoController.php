<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\DocenteRepository;
use App\Repositories\DocenteRepositoryInterface;
use App\Services\AuthService;
use App\Services\PermisoService;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador para la gestión y resolución de solicitudes de permiso docente en administración.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 9 — RF-27, RNF-06).
 */
class AdminPermisoController extends BaseController
{
    private AuthService $auth;
    private PermisoService $permisoService;
    private DocenteRepositoryInterface $docenteRepo;

    public function __construct(
        ?AuthService $auth = null,
        ?PermisoService $permisoService = null,
        ?DocenteRepositoryInterface $docenteRepo = null
    ) {
        $this->auth = $auth ?? new AuthService();
        $this->permisoService = $permisoService ?? new PermisoService();
        $this->docenteRepo = $docenteRepo ?? new DocenteRepository();
    }

    /**
     * Bandeja de solicitudes de permiso para administradores con filtros (RF-27).
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Solo administradores activos pueden acceder a este módulo.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $filtros = [
            'estado' => $request->query('estado'),
            'id_docente' => $request->query('id_docente'),
            'fecha_solicitada' => $request->query('fecha_solicitada'),
        ];

        $solicitudes = $this->permisoService->obtenerSolicitudesAdmin($filtros);
        $docentes = $this->docenteRepo->findAll();

        if ($this->isJsonRequest($request)) {
            $data = array_map(fn($s) => $s->toArray(), $solicitudes);
            return Response::json(['exito' => true, 'solicitudes' => $data]);
        }

        $html = View::render('admin.permisos.index', [
            'title' => 'Gestión de Permisos Docentes — Administración',
            'user' => $user,
            'solicitudes' => $solicitudes,
            'docentes' => $docentes,
            'filtros' => $filtros,
            'mensajeExito' => $request->query('msg'),
            'mensajeError' => $request->query('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Detalle administrativo de una solicitud, documento e historial completo (RF-27, RF-28).
     */
    public function show(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido a administradores.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $idSolicitud = (int) $request->query('id', 0);

        try {
            $solicitud = $this->permisoService->obtenerDetalleSolicitudAdmin($idSolicitud);

            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => true,
                    'solicitud' => $solicitud->toArray(),
                    'historial' => array_map(fn($h) => $h->toArray(), $solicitud->historial),
                ]);
            }

            $html = View::render('admin.permisos.ver', [
                'title' => 'Detalle de Solicitud de Permiso #' . $solicitud->id_solicitud_permiso . ' — Administración',
                'user' => $user,
                'solicitud' => $solicitud,
                'mensajeExito' => $request->query('msg'),
                'mensajeError' => $request->query('error'),
            ], 'main');

            return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (DomainException $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => $e->getMessage()], 404);
            }

            return Response::redirect('/administracion/permisos?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Procesar aprobación de solicitud de permiso (RF-27, DT-56, DT-62).
     */
    public function aprobar(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'No autenticado'], 401);
            }
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Solo administradores activos pueden aprobar solicitudes.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $idSolicitud = (int) $request->post('id_solicitud_permiso', 0);
        $comentario = $request->post('comentario');

        try {
            $solicitud = $this->permisoService->aprobarSolicitud($idSolicitud, $user->id_usuario, $comentario);

            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => true,
                    'mensaje' => sprintf('Solicitud #%d aprobada exitosamente.', $solicitud->id_solicitud_permiso),
                    'solicitud' => $solicitud->toArray(),
                ], 200);
            }

            return Response::redirect('/administracion/permisos/ver?id=' . $idSolicitud . '&msg=' . urlencode('Solicitud de permiso aprobada exitosamente.'));
        } catch (DomainException | InvalidArgumentException $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => $e->getMessage()], 422);
            }

            return Response::redirect('/administracion/permisos/ver?id=' . $idSolicitud . '&error=' . urlencode($e->getMessage()));
        } catch (Throwable $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'Error al aprobar la solicitud.'], 500);
            }

            return Response::redirect('/administracion/permisos/ver?id=' . $idSolicitud . '&error=' . urlencode('Error inesperado al aprobar la solicitud.'));
        }
    }

    /**
     * Procesar rechazo de solicitud de permiso (RF-27, DT-56, DT-62).
     */
    public function rechazar(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'No autenticado'], 401);
            }
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Solo administradores activos pueden rechazar solicitudes.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $idSolicitud = (int) $request->post('id_solicitud_permiso', 0);
        $comentario = $request->post('comentario');

        try {
            $solicitud = $this->permisoService->rechazarSolicitud($idSolicitud, $user->id_usuario, $comentario);

            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => true,
                    'mensaje' => sprintf('Solicitud #%d rechazada exitosamente.', $solicitud->id_solicitud_permiso),
                    'solicitud' => $solicitud->toArray(),
                ], 200);
            }

            return Response::redirect('/administracion/permisos/ver?id=' . $idSolicitud . '&msg=' . urlencode('Solicitud de permiso rechazada exitosamente.'));
        } catch (DomainException | InvalidArgumentException $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => $e->getMessage()], 422);
            }

            return Response::redirect('/administracion/permisos/ver?id=' . $idSolicitud . '&error=' . urlencode($e->getMessage()));
        } catch (Throwable $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'Error al rechazar la solicitud.'], 500);
            }

            return Response::redirect('/administracion/permisos/ver?id=' . $idSolicitud . '&error=' . urlencode('Error inesperado al rechazar la solicitud.'));
        }
    }

    /**
     * Descarga autorizada de documento de soporte para administradores (RF-25, DT-59).
     */
    public function descargarDocumento(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response('Acceso Denegado', 403);
        }

        $idDocumento = (int) $request->query('id', 0);

        try {
            $resultado = $this->permisoService->obtenerDocumentoParaAdmin($idDocumento);
            $doc = $resultado['documento'];
            $rutaFisica = $resultado['rutaFisica'];

            $contenido = (string) file_get_contents($rutaFisica);
            $nombreLimpio = PermisoService::sanitizarNombreArchivo($doc->nombre_original);

            return new Response($contenido, 200, [
                'Content-Type' => $doc->tipo_mime,
                'Content-Length' => (string) strlen($contenido),
                'Content-Disposition' => 'attachment; filename="' . addslashes($nombreLimpio) . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (DomainException $e) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => $e->getMessage(),
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (Throwable $e) {
            \App\Core\Logger::error('Fallo en descarga de documento de soporte administrativo: ' . $e->getMessage());
            return new Response('El documento solicitado no se encuentra disponible.', 404);
        }
    }

    private function isJsonRequest(Request $request): bool
    {
        $accept = (string) $request->header('Accept');
        $xReq = (string) $request->header('X-Requested-With');

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }
}
