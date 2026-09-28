<div class="card error-card">
    <div class="card-header bg-danger">
        <h2>Servicio de Base de Datos No Disponible</h2>
    </div>
    <div class="card-body">
        <p class="error-desc">
            No fue posible establecer comunicación con el servidor de base de datos.
        </p>
        <div class="notice-box alert-warning">
            <strong>Estado de seguridad:</strong> El sistema ha bloqueado la exposición de credenciales y parámetros de conexión hacia el usuario final.
        </div>
        <p>Por favor contacte al administrador del sistema o verifique el servicio de base de datos.</p>
        <div class="action-buttons">
            <a href="<?= e(url()) ?>" class="btn btn-primary">&larr; Reintentar</a>
        </div>
    </div>
</div>
