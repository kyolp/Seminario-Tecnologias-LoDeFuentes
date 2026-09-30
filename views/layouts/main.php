<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Sistema Web - Escuela Oficial Rural Mixta Lo de Fuentes') ?></title>
    <link rel="stylesheet" href="<?= e(url('css/app.css')) ?>">
</head>
<body>
    <header class="app-header">
        <div class="container header-container">
            <div class="brand">
                <h1 class="school-title">Escuela Oficial Rural Mixta Lo de Fuentes</h1>
                <p class="school-subtitle">Sistema Web Integral — Prototipo Académico</p>
            </div>
            <nav class="main-nav">
                <a href="<?= e(url()) ?>" class="nav-link">Inicio</a>
                <a href="<?= e(url('inscripcion')) ?>" class="nav-link">Inscripción en Línea</a>
                <a href="<?= e(url('inscripcion/consultar')) ?>" class="nav-link">Consultar Inscripción</a>
                <a href="<?= e(url('status')) ?>" class="nav-link" target="_blank">Estado JSON</a>
                <?php if (auth()->check()): ?>
                    <?php $currentUser = auth()->user(); ?>
                    <?php if ($currentUser !== null && $currentUser->isAdmin()): ?>
                        <a href="<?= e(url('administracion')) ?>" class="nav-link">Administración</a>
                    <?php endif; ?>
                    <?php if ($currentUser !== null && $currentUser->isDocente()): ?>
                        <a href="<?= e(url('docente')) ?>" class="nav-link">Docente</a>
                    <?php endif; ?>
                    <span class="user-badge" style="color: #cbd5e1; margin-left: 0.5rem; font-size: 0.85rem;">
                        <?= e($currentUser?->nombre_usuario) ?> (<?= e($currentUser?->rol_nombre) ?>)
                    </span>
                    <form action="<?= e(url('logout')) ?>" method="POST" style="display: inline; margin-left: 0.5rem;">
                        <?= csrf_field() ?>
                        <button type="submit" style="background: none; border: 1px solid #cbd5e1; color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">Salir</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('login')) ?>" class="nav-link" style="background: #2563eb; color: #fff; padding: 0.35rem 0.75rem; border-radius: 4px; text-decoration: none;">Iniciar Sesión</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="app-main container">
        <?= $content ?>
    </main>

    <footer class="app-footer">
        <div class="container footer-content">
            <p>&copy; <?= date('Y') ?> Escuela Oficial Rural Mixta Lo de Fuentes — 13 Calle, Lo de Fuentes, Zona 11, Mixco.</p>
            <p class="phase-tag">Fase 3: Autenticación y Roles | Desarrollado con PHP 8.2 + PDO + MySQL 8.0</p>
        </div>
    </footer>

    <script src="<?= e(url('js/app.js')) ?>"></script>
</body>
</html>
