<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Services\PermisoService;
use App\Services\PortalDocenteService;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador para la gestión de permisos docentes desde el Portal Docente.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 9 — RF-24, RF-25, RF-28).
 */
class DocentePermisoController extends BaseController
{
    private AuthService $auth;
    private PermisoService $permisoService;
    private PortalDocenteService $portalService;

    public function __construct(
        ?AuthService $auth = null,
        ?PermisoService $permisoService = null,
        ?PortalDocenteService $portalService = null
    ) {
        $this->auth = $auth ?? new AuthService();
        $this->permisoService = $permisoService ?? new PermisoService();
        $this->portalService = $portalService ?? new PortalDocenteService();
    }

    /**
     * Listado de permisos propios del docente autenticado (RF-28).
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Solo docentes activos pueden acceder a esta sección.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Su cuenta docente no se encuentra activa.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $solicitudes = $this->permisoService->obtenerSolicitudesDocente($docente->id_docente);

        if ($this->isJsonRequest($request)) {
            $data = array_map(fn($s) => $s->toArray(), $solicitudes);
            return Response::json(['exito' => true, 'solicitudes' => $data]);
        }

        $html = View::render('docente.permisos.index', [
            'title' => 'Mis Permisos — Portal Docente',
            'user' => $user,
            'docente' => $docente,
            'solicitudes' => $solicitudes,
            'mensajeExito' => $request->query('msg'),
            'mensajeError' => $request->query('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Formulario para registrar una nueva solicitud de permiso (RF-24).
     */
    public function createForm(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Solo docentes activos pueden registrar permisos.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Su cuenta docente no se encuentra activa.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $html = View::render('docente.permisos.crear', [
            'title' => 'Solicitar Permiso — Portal Docente',
            'user' => $user,
            'docente' => $docente,
            'mensajeError' => $request->query('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Procesar registro de solicitud de permiso con soporte opcional (RF-24, RF-25, DT-61).
     */
    public function store(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'No autenticado'], 401);
            }
            return Response::redirect('/login');
        }

        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido a docentes.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Su cuenta docente no se encuentra activa.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // Whitelist estricta contra mass-assignment: ignoramos estado, id_docente, timestamps del cliente
        $datos = [
            'fecha_solicitada' => $request->post('fecha_solicitada'),
            'tipo_motivo' => $request->post('tipo_motivo'),
            'descripcion' => $request->post('descripcion'),
        ];

        // Archivo opcional
        $archivo = $request->file('soporte') ?? ($_FILES['soporte'] ?? null);

        try {
            $solicitud = $this->permisoService->crearSolicitud(
                $docente->id_docente,
                $user->id_usuario,
                $datos,
                $archivo
            );

            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => true,
                    'mensaje' => 'Solicitud de permiso registrada exitosamente.',
                    'solicitud' => $solicitud->toArray(),
                ], 200);
            }

            return Response::redirect('/docente/permisos?msg=' . urlencode('Solicitud de permiso registrada exitosamente.'));
        } catch (InvalidArgumentException | DomainException $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => false,
                    'error' => $e->getMessage(),
                ], 422);
            }

            return Response::redirect('/docente/permisos/crear?error=' . urlencode($e->getMessage()));
        } catch (Throwable $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => false,
                    'error' => 'Ocurrió un error inesperado al procesar la solicitud de permiso.',
                ], 500);
            }

            return Response::redirect('/docente/permisos/crear?error=' . urlencode('Ocurrió un error inesperado al procesar la solicitud.'));
        }
    }

    /**
     * Consultar detalle de solicitud propia e historial permitido (RF-28, DT-61).
     */
    public function show(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido a docentes.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Cuenta docente inactiva.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $idSolicitud = (int) $request->query('id', 0);

        try {
            $solicitud = $this->permisoService->obtenerDetalleSolicitudDocente($idSolicitud, $docente->id_docente);

            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => true,
                    'solicitud' => $solicitud->toArray(),
                    'historial' => array_map(fn($h) => $h->toArray(), $solicitud->historial),
                ]);
            }

            $html = View::render('docente.permisos.ver', [
                'title' => 'Detalle de Permiso — Portal Docente',
                'user' => $user,
                'docente' => $docente,
                'solicitud' => $solicitud,
            ], 'main');

            return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (DomainException $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => $e->getMessage()], 403);
            }

            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => $e->getMessage(),
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }

    /**
     * Descarga autorizada de documento de soporte propio (RF-25, DT-59).
     */
    public function descargarDocumento(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response('Acceso Denegado', 403);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response('Cuenta inactiva', 403);
        }

        $idDocumento = (int) $request->query('id', 0);

        try {
            $resultado = $this->permisoService->obtenerDocumentoParaDocente($idDocumento, $docente->id_docente);
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
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (Throwable $e) {
            return new Response('Error al descargar el archivo: ' . e($e->getMessage()), 404);
        }
    }

    private function isJsonRequest(Request $request): bool
    {
        $accept = (string) $request->header('Accept');
        $xReq = (string) $request->header('X-Requested-With');

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }
}
