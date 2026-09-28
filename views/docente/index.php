<section class="dashboard-panel" style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #059669; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #065f46; font-size: 1.5rem;">Portal docente — sesión válida</h2>
            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Módulo de acceso para perfil Docente</p>
        </div>
        <div>
            <form action="<?= e(url('/logout')) ?>" method="POST" style="margin: 0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary" style="padding: 0.5rem 1rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="margin-top: 0; color: #0f172a; font-size: 1.1rem;">Datos de la Sesión Autorizada</h3>
        <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #334155; font-size: 0.95rem;">
            <li><strong>Usuario:</strong> <?= e($user?->nombre_usuario) ?></li>
            <li><strong>Docente:</strong> <?= e($user?->getFullName()) ?></li>
            <li><strong>Correo:</strong> <?= e($user?->correo) ?></li>
            <li><strong>Rol verificado:</strong> <span style="background: #d1fae5; color: #065f46; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600;"><?= e($user?->rol_nombre) ?></span></li>
            <li><strong>Estado de cuenta:</strong> <span style="background: #dcfce7; color: #166534; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600;"><?= e($user?->estado) ?></span></li>
        </ul>
    </div>

    <div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 1rem; border-radius: 4px; color: #065f46; font-size: 0.9rem;">
        <strong>Información:</strong> Esta es una pantalla provisional de validación de autorización por rol correspondiente a la Fase 3. Los módulos de consulta de asignaciones, registro de asistencia docente y solicitud de permisos se implementarán en sus fases específicas correspondientes.
    </div>
</section>
