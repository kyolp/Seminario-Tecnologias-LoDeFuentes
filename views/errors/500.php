<div class="card error-card">
    <div class="card-header bg-danger">
        <h2>500 — Error Interno del Servidor</h2>
    </div>
    <div class="card-body">
        <p class="error-desc">
            Ha ocurrido un problema al procesar su solicitud. El evento ha sido registrado internamente.
        </p>
        <?php if (!empty($debug)): ?>
            <div class="debug-panel">
                <h4>Detalle de depuración (Modo Desarrollo):</h4>
                <p><strong>Mensaje:</strong> <?= e($message ?? 'Error sin detalle') ?></p>
                <?php if (!empty($file)): ?>
                    <p><strong>Archivo:</strong> <?= e($file) ?>:<?= e((string) ($line ?? 0)) ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Por motivos de seguridad, los detalles técnicos del error no son expuestos.</p>
        <?php endif; ?>
        <div class="action-buttons">
            <a href="<?= e(url()) ?>" class="btn btn-primary">&larr; Volver al Inicio</a>
        </div>
    </div>
</div>
