<section style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.35rem;">Detalle de Asignación Docente #<?= e((string)$asignacion->id_asignacion_docente) ?></h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Información oficial de asignación académica (RF-16)</p>
        </div>
        <div>
            <a href="<?= e(url('/administracion/asignaciones')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver al Listado</a>
        </div>
    </div>

    <!-- Resumen de estado -->
    <div style="display: flex; gap: 1rem; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
        <div>
            <span style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: 600; display: block;">Estado de la Asignación</span>
            <?php if ($asignacion->isActiva()): ?>
                <span style="display: inline-block; margin-top: 0.2rem; background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: 700; font-size: 0.9rem;">ACTIVA (VIGENTE)</span>
            <?php else: ?>
                <span style="display: inline-block; margin-top: 0.2rem; background: #f1f5f9; color: #64748b; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: 700; font-size: 0.9rem;">INACTIVA (HISTORIAL)</span>
            <?php endif; ?>
        </div>
        <div style="border-left: 1px solid #cbd5e1; padding-left: 1rem; margin-left: auto; text-align: right;">
            <span style="font-size: 0.8rem; color: #64748b; display: block;">Período Escolar</span>
            <span style="font-weight: 700; color: #1e3a8a; font-size: 1rem;"><?= e($asignacion->periodo_nombre) ?></span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Docente -->
        <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem;">
            <h3 style="margin-top: 0; color: #1e3a8a; font-size: 1.05rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">👨‍🏫 Docente Responsable</h3>
            <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; font-size: 0.9rem; color: #334155;">
                <li><strong>Nombre completo:</strong> <?= e($asignacion->docente_nombre) ?></li>
                <li><strong>Código institucional:</strong> <code style="background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 3px;"><?= e($asignacion->docente_codigo) ?></code></li>
                <li><strong>Correo electrónico:</strong> <?= e($asignacion->docente_correo) ?></li>
                <li><strong>ID Docente en BD:</strong> #<?= e((string)$asignacion->id_docente) ?></li>
            </ul>
        </div>

        <!-- Sección y Grado -->
        <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem;">
            <h3 style="margin-top: 0; color: #1e3a8a; font-size: 1.05rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">🏢 Sección Asignada</h3>
            <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; font-size: 0.9rem; color: #334155;">
                <li><strong>Grado académico:</strong> <?= e($asignacion->grado_nombre) ?></li>
                <li><strong>Código de sección:</strong> <strong style="font-size: 1rem; color: #1e293b;">Sección <?= e($asignacion->seccion_codigo) ?></strong></li>
                <li><strong>Descripción:</strong> <?= e($asignacion->seccion_nombre ?? 'Sin nombre específico') ?></li>
                <li><strong>Capacidad máxima:</strong> <?= e((string)$asignacion->seccion_capacidad) ?> cupos</li>
                <li><strong>ID Sección en BD:</strong> #<?= e((string)$asignacion->id_seccion) ?></li>
            </ul>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Aula Física -->
        <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem;">
            <h3 style="margin-top: 0; color: #1e3a8a; font-size: 1.05rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">🚪 Aula Física</h3>
            <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; font-size: 0.9rem; color: #334155;">
                <li><strong>Código de aula:</strong> <code style="background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 600;"><?= e($asignacion->aula_codigo) ?></code></li>
                <li><strong>Nombre / Referencia:</strong> <?= e($asignacion->aula_nombre ?? 'Sin descripción') ?></li>
                <li><strong>ID Aula en BD:</strong> #<?= e((string)$asignacion->id_aula) ?></li>
            </ul>
        </div>

        <!-- Vigencia y Trazabilidad -->
        <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem;">
            <h3 style="margin-top: 0; color: #1e3a8a; font-size: 1.05rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">📅 Vigencia y Auditoría</h3>
            <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; font-size: 0.9rem; color: #334155;">
                <li><strong>Fecha de inicio:</strong> <?= e($asignacion->fecha_inicio) ?></li>
                <li><strong>Fecha de fin:</strong> <?= e($asignacion->fecha_fin ?? 'Vigente (indefinida)') ?></li>
                <li><strong>Registrado por:</strong> <?= e($asignacion->creador_nombre ?? 'Administrador') ?></li>
                <li><strong>Fecha de creación:</strong> <?= e($asignacion->fecha_creacion ?? 'N/D') ?></li>
                <li><strong>Última actualización:</strong> <?= e($asignacion->fecha_actualizacion ?? 'N/D') ?></li>
            </ul>
        </div>
    </div>

    <!-- Botones de acción contextual -->
    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
        <a href="<?= e(url('/administracion/asignaciones/editar?id=' . $asignacion->id_asignacion_docente)) ?>" style="padding: 0.5rem 1rem; background: #0d9488; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem;">Editar Asignación</a>

        <?php if ($asignacion->isActiva()): ?>
            <a href="<?= e(url('/administracion/asignaciones/reemplazar?id=' . $asignacion->id_asignacion_docente)) ?>" style="padding: 0.5rem 1rem; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem;">Reemplazar Docente</a>

            <form method="POST" action="<?= e(url('/administracion/asignaciones/estado?id=' . $asignacion->id_asignacion_docente)) ?>" style="display: inline;" onsubmit="return confirm('¿Confirma que desea cerrar/desactivar esta asignación?');">
                <?= csrf_field() ?>
                <input type="hidden" name="nuevo_estado" value="INACTIVA">
                <button type="submit" style="padding: 0.5rem 1rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">Cerrar Asignación</button>
            </form>
        <?php else: ?>
            <form method="POST" action="<?= e(url('/administracion/asignaciones/estado?id=' . $asignacion->id_asignacion_docente)) ?>" style="display: inline;" onsubmit="return confirm('¿Desea reactivar esta asignación?');">
                <?= csrf_field() ?>
                <input type="hidden" name="nuevo_estado" value="ACTIVA">
                <button type="submit" style="padding: 0.5rem 1rem; background: #16a34a; color: #fff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">Reactivar Asignación</button>
            </form>
        <?php endif; ?>
    </div>
</section>
