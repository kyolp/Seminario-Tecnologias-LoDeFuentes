<section style="max-width: 700px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Ficha del Docente</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Información detallada del docente registrado</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= e(url('/administracion/docentes')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver</a>
            <a href="<?= e(url('/administracion/reportes/asistencia?id_docente=' . $docente->id_docente)) ?>" style="padding: 0.5rem 1rem; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">🕒 Ver Historial de Asistencia</a>
            <a href="<?= e(url('/administracion/docentes/editar?id=' . $docente->id_docente)) ?>" style="padding: 0.5rem 1rem; background: #0d9488; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">Editar</a>
        </div>
    </div>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.5rem;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0; width: 35%; color: #475569;">ID Interno:</th>
                <td style="padding: 0.75rem 0; font-weight: 600; color: #0f172a;">#<?= e((string)$docente->id_docente) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0; color: #475569;">Código de Docente:</th>
                <td style="padding: 0.75rem 0; font-family: monospace; font-weight: 600; color: #0f172a;"><?= e($docente->codigo_docente ?? 'Sin código asignado') ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0; color: #475569;">Nombre Completo:</th>
                <td style="padding: 0.75rem 0; font-weight: 600; color: #0f172a;"><?= e($docente->getNombreCompleto()) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0; color: #475569;">Nombre de Usuario:</th>
                <td style="padding: 0.75rem 0; color: #0f172a;"><?= e($docente->nombre_usuario) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0; color: #475569;">Correo Electrónico:</th>
                <td style="padding: 0.75rem 0; color: #0f172a;"><?= e($docente->correo) ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0; color: #475569;">Fecha de Ingreso:</th>
                <td style="padding: 0.75rem 0; color: #0f172a;"><?= e($docente->fecha_ingreso ?? 'No especificada') ?></td>
            </tr>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 0.75rem 0; color: #475569;">Historial de Asistencia:</th>
                <td style="padding: 0.75rem 0;">
                    <a href="<?= e(url('/administracion/reportes/asistencia?id_docente=' . $docente->id_docente)) ?>" style="color: #7c3aed; font-weight: 600; text-decoration: none;">
                        Consultar marcajes de este docente &rarr;
                    </a>
                </td>
            </tr>
            <tr>
                <th style="padding: 0.75rem 0; color: #475569;">Estado:</th>
                <td style="padding: 0.75rem 0;">
                    <?php if ($docente->isActivo()): ?>
                        <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem;">ACTIVO</span>
                    <?php else: ?>
                        <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.85rem;">INACTIVO</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</section>
