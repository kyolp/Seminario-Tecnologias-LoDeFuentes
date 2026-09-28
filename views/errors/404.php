<div class="card error-card">
    <div class="card-header bg-warning">
        <h2>404 — Página No Encontrada</h2>
    </div>
    <div class="card-body">
        <p class="error-desc">
            La ruta o recurso solicitado <code><?= e($path ?? '') ?></code> no existe en el sistema.
        </p>
        <p>Por favor verifique la dirección ingresada o regrese a la página principal.</p>
        <div class="action-buttons">
            <a href="<?= e(url()) ?>" class="btn btn-primary">&larr; Volver al Inicio</a>
        </div>
    </div>
</div>
