<?php
$currentUser = auth()->user();
$panelUrl = null;
$notificacionesUrl = null;
$conteoAvisos = 0;

if ($currentUser !== null) {
    if ($currentUser->isAdmin()) {
        $panelUrl = 'administracion';
        $notificacionesUrl = 'administracion/notificaciones';
    } elseif ($currentUser->isDocente()) {
        $panelUrl = 'docente';
        $notificacionesUrl = 'docente/notificaciones';
    }

    if ($notificacionesUrl !== null) {
        $conteoAvisos = (new \App\Services\NotificacionService())
            ->obtenerConteoNoLeidas($currentUser->id_usuario);
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Escuela Lo de Fuentes') ?></title>
    <link rel="stylesheet" href="<?= e(url('css/app.css')) ?>?v=6">
</head>

<body class="sitio-escolar">
    <a class="saltar-contenido" href="#contenido">Ir al contenido</a>

    <header class="app-header">
        <div class="container header-container">
            <a href="<?= e(url()) ?>" class="marca-escolar">
                <img src="<?= e(url('img/logo-escuela.png')) ?>" alt="Escudo de la Escuela Lo de Fuentes"
                    class="logo-escolar" width="64" height="72">
                <span>
                    <span class="marca-descripcion">
                        Escuela Oficial Rural Mixta
                    </span>
                    <span class="marca-nombre">Lo de Fuentes</span>
                    <span class="marca-jornada">Jornada vespertina</span>
                </span>
            </a>

            <nav class="main-nav" aria-label="Navegación principal">
                <?php if ($currentUser === null): ?>
                    <a href="<?= e(url()) ?>" class="nav-link">Inicio</a>
                    <a href="<?= e(url('inscripcion')) ?>" class="nav-link">
                        Inscripciones
                    </a>
                    <a href="<?= e(url('inscripcion/consultar')) ?>" class="nav-link">
                        Consultar inscripción
                    </a>
                    <a href="<?= e(url('login')) ?>" class="nav-link acceso-principal">
                        Iniciar sesión
                    </a>
                <?php else: ?>
                    <?php if ($panelUrl !== null): ?>
                        <a href="<?= e(url($panelUrl)) ?>" class="nav-link">
                            Mi panel
                        </a>
                    <?php endif; ?>

                    <?php if ($notificacionesUrl !== null): ?>
                        <a href="<?= e(url($notificacionesUrl)) ?>" class="nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                                <path d="M10 21h4" />
                            </svg>
                            Notificaciones
                            <?php if ($conteoAvisos > 0): ?>
                                <span class="contador-avisos" aria-label="<?= (int) $conteoAvisos ?> sin leer">
                                    <?= $conteoAvisos > 99 ? '99+' : (int) $conteoAvisos ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <span class="usuario-navegacion">
                        <?= e($currentUser->nombre_usuario) ?>
                    </span>

                    <form action="<?= e(url('logout')) ?>" method="POST" class="form-salir">
                        <?= csrf_field() ?>
                        <button type="submit" class="boton-salir">
                            Salir
                        </button>
                    </form>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main id="contenido" class="app-main container">
        <?= $content ?>
    </main>

    <footer class="app-footer">
        <div class="container footer-content">
            <p class="footer-escuela">
                Escuela Oficial Rural Mixta Lo de Fuentes
            </p>
            <p>13 Calle, Lo de Fuentes, Zona 11, Mixco.</p>
            <p class="footer-derechos">
                &copy; <?= date('Y') ?> · Jornada vespertina
            </p>
        </div>
    </footer>

    <script src="<?= e(url('js/app.js')) ?>"></script>
</body>

</html>