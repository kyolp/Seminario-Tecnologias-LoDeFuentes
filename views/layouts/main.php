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
                <a href="<?= e(url('status')) ?>" class="nav-link" target="_blank">Estado JSON</a>
            </nav>
        </div>
    </header>

    <main class="app-main container">
        <?= $content ?>
    </main>

    <footer class="app-footer">
        <div class="container footer-content">
            <p>&copy; <?= date('Y') ?> Escuela Oficial Rural Mixta Lo de Fuentes — 13 Calle, Lo de Fuentes, Zona 11, Mixco.</p>
            <p class="phase-tag">Fase 2: Estructura Base de la Aplicación | Desarrollado con PHP 8.2 + PDO + MySQL 8.0</p>
        </div>
    </footer>

    <script src="<?= e(url('js/app.js')) ?>"></script>
</body>
</html>
