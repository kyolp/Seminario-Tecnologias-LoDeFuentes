<?php
$accesosPrincipales = [
    [
        'titulo' => 'Docentes',
        'descripcion' => 'Cuentas y datos del personal docente.',
        'ruta' => '/administracion/docentes',
        'icono' => 'personas',
    ],
    [
        'titulo' => 'Estudiantes',
        'descripcion' => 'Carnés, datos e información de estudiantes.',
        'ruta' => '/administracion/estudiantes',
        'icono' => 'estudiante',
    ],
    [
        'titulo' => 'Permisos docentes',
        'descripcion' => 'Revisa y resuelve solicitudes de permiso.',
        'ruta' => '/administracion/permisos',
        'icono' => 'documento',
    ],
    [
        'titulo' => 'Notificaciones',
        'descripcion' => 'Avisos sobre solicitudes y eventos del sistema.',
        'ruta' => '/administracion/notificaciones',
        'icono' => 'campana',
    ],
    [
        'titulo' => 'Reportes institucionales',
        'descripcion' => 'Asistencia docente, inscripciones y cupos.',
        'ruta' => '/administracion/reportes',
        'icono' => 'reportes',
    ],
];

$organizacionEscolar = [
    [
        'titulo' => 'Períodos escolares',
        'descripcion' => 'Administra los ciclos escolares.',
        'ruta' => '/administracion/periodos',
        'icono' => 'calendario',
    ],
    [
        'titulo' => 'Grados',
        'descripcion' => 'Organiza el catálogo de grados.',
        'ruta' => '/administracion/grados',
        'icono' => 'libro',
    ],
    [
        'titulo' => 'Grados por período',
        'descripcion' => 'Habilita los grados de cada ciclo.',
        'ruta' => '/administracion/grados-periodos',
        'icono' => 'enlace',
    ],
    [
        'titulo' => 'Secciones y cupos',
        'descripcion' => 'Define las secciones y su capacidad.',
        'ruta' => '/administracion/secciones',
        'icono' => 'cuadricula',
    ],
    [
        'titulo' => 'Aulas',
        'descripcion' => 'Gestiona los espacios de la escuela.',
        'ruta' => '/administracion/aulas',
        'icono' => 'edificio',
    ],
    [
        'titulo' => 'Asignaciones docentes',
        'descripcion' => 'Asigna docentes a secciones y aulas.',
        'ruta' => '/administracion/asignaciones',
        'icono' => 'asignacion',
    ],
];

$iconosPanel = [
    'personas' =>
        '<circle cx="9" cy="8" r="3"/>
         <path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-4-5.65"/>',
    'estudiante' =>
        '<path d="m2 8 10-5 10 5-10 5L2 8ZM6 10v6c3 3 9 3 12 0v-6M22 8v7"/>',
    'documento' =>
        '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z"/>
         <path d="M14 2v6h6M8 13h8M8 17h5"/>',
    'campana' =>
        '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
         <path d="M10 21h4"/>',
    'reportes' =>
        '<rect x="3" y="12" width="4" height="8" rx="1"/>
         <rect x="10" y="8" width="4" height="12" rx="1"/>
         <rect x="17" y="4" width="4" height="16" rx="1"/>
         <path d="M3 20h18"/>',
    'calendario' =>
        '<rect x="3" y="5" width="18" height="16" rx="2"/>
         <path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/>',
    'libro' =>
        '<path d="M12 5C9 3 5 3 2 4v16c3-1 7-1 10 1 3-2 7-2 10-1V4c-3-1-7-1-10 1ZM12 5v16"/>',
    'enlace' =>
        '<path d="m10 13 4-4M8 16l-1 1a4 4 0 0 1-6-6l5-5a4 4 0 0 1 6 0M16 8l1-1a4 4 0 0 1 6 6l-5 5a4 4 0 0 1-6 0" transform="translate(0 1) scale(.92)"/>',
    'cuadricula' =>
        '<rect x="3" y="3" width="7" height="7" rx="1"/>
         <rect x="14" y="3" width="7" height="7" rx="1"/>
         <rect x="3" y="14" width="7" height="7" rx="1"/>
         <rect x="14" y="14" width="7" height="7" rx="1"/>',
    'edificio' =>
        '<path d="M4 21V3h16v18M2 21h20M9 21v-5h6v5M8 7h1M15 7h1M8 11h1M15 11h1"/>',
    'asignacion' =>
        '<rect x="3" y="4" width="18" height="17" rx="2"/>
         <path d="M8 2v4M16 2v4M7 11l3 3 7-7M7 18h10"/>',
];
?>

<div class="admin-inicio">
    <header class="admin-bienvenida">
        <div>
            <span class="admin-etiqueta">ADMINISTRACIÓN ESCOLAR</span>
            <h1>Panel de administración</h1>
            <p>Organiza la escuela y accede a tus herramientas de gestión.</p>
        </div>

        <div class="admin-identidad">
            <span>Sesión de</span>
            <strong>
                <?= e($user?->getFullName() ?: ($user?->nombre_usuario ?? 'Administración')) ?>
            </strong>
        </div>
    </header>

    <?php if (!empty($flash_success)): ?>
        <div class="admin-mensaje admin-mensaje-exito" role="status">
            <?= e($flash_success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div class="admin-mensaje admin-mensaje-error" role="alert">
            <?= e($flash_error) ?>
        </div>
    <?php endif; ?>

    <section class="admin-seccion" aria-labelledby="titulo-gestion">
        <div class="admin-titulo-seccion">
            <h2 id="titulo-gestion">Gestión diaria</h2>
            <p>Personal, estudiantes, reportes y solicitudes.</p>
        </div>

        <div class="admin-accesos">
            <?php foreach ($accesosPrincipales as $acceso): ?>
                <a href="<?= e(url($acceso['ruta'])) ?>"
                   class="admin-acceso">
                    <span class="admin-icono">
                        <svg viewBox="0 0 24 24" width="24" height="24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <?= $iconosPanel[$acceso['icono']] ?>
                        </svg>
                    </span>

                    <h3><?= e($acceso['titulo']) ?></h3>
                    <p><?= e($acceso['descripcion']) ?></p>

                    <span class="admin-abrir">
                        Gestionar <span aria-hidden="true">→</span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-organizacion" aria-labelledby="titulo-organizacion">
        <div class="admin-titulo-seccion">
            <h2 id="titulo-organizacion">Organización escolar</h2>
            <p>Configura los ciclos, grados y espacios de la escuela.</p>
        </div>

        <div class="admin-lista">
            <?php foreach ($organizacionEscolar as $modulo): ?>
                <a href="<?= e(url($modulo['ruta'])) ?>"
                   class="admin-fila">
                    <span class="admin-icono admin-icono-pequeno">
                        <svg viewBox="0 0 24 24" width="21" height="21"
                             fill="none" stroke="currentColor"
                             stroke-width="1.7" stroke-linecap="round"
                             stroke-linejoin="round" aria-hidden="true">
                            <?= $iconosPanel[$modulo['icono']] ?>
                        </svg>
                    </span>

                    <span class="admin-fila-texto">
                        <strong><?= e($modulo['titulo']) ?></strong>
                        <span><?= e($modulo['descripcion']) ?></span>
                    </span>

                    <span class="admin-flecha" aria-hidden="true">→</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-configuracion" aria-labelledby="titulo-configuracion">
        <div>
            <h2 id="titulo-configuracion">Ubicación para marcaje</h2>
            <p>Define la ubicación de la escuela y el radio permitido para registrar asistencia.</p>
        </div>

        <a href="<?= e(url('/administracion/ubicacion')) ?>"
           class="admin-configurar">
            Configurar ubicación <span aria-hidden="true">→</span>
        </a>
    </section>
</div>
