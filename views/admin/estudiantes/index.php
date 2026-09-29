<section style="max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.4rem;">Gestión de Estudiantes</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Registro y administración del alumnado</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver al Panel</a>
            <a href="<?= e(url('/administracion/estudiantes/crear')) ?>" style="padding: 0.5rem 1rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">+ Nuevo Estudiante</a>
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($flash_success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($flash_error) ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="<?= e(url('/administracion/estudiantes')) ?>" style="margin-bottom: 1.5rem; display: flex; gap: 0.5rem;">
        <input type="text" name="buscar" value="<?= e($search) ?>" placeholder="Buscar por código, nombres o apellidos..." style="flex: 1; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem;">
        <button type="submit" style="padding: 0.5rem 1rem; background: #1e3a8a; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Buscar</button>
        <?php if (!empty($search)): ?>
            <a href="<?= e(url('/administracion/estudiantes')) ?>" style="padding: 0.5rem 1rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-size: 0.9rem;">Limpiar</a>
        <?php endif; ?>
    </form>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; color: #334155;">
                    <th style="padding: 0.75rem;">Código</th>
                    <th style="padding: 0.75rem;">Apellidos</th>
                    <th style="padding: 0.75rem;">Nombres</th>
                    <th style="padding: 0.75rem;">Fecha Nacimiento</th>
                    <th style="padding: 0.75rem;">Estado</th>
                    <th style="padding: 0.75rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estudiantes)): ?>
                    <tr>
                        <td colspan="6" style="padding: 1.5rem; text-align: center; color: #64748b;">No se encontraron estudiantes registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estudiantes as $est): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.75rem; font-family: monospace; font-weight: 600;"><?= e($est->codigo_estudiante ?? '—') ?></td>
                            <td style="padding: 0.75rem; font-weight: 600; color: #0f172a;"><?= e($est->apellidos) ?></td>
                            <td style="padding: 0.75rem; color: #0f172a;"><?= e($est->nombres) ?></td>
                            <td style="padding: 0.75rem; color: #475569;"><?= e($est->fecha_nacimiento ?? '—') ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if ($est->isActivo()): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">ACTIVO</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">INACTIVO</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right; white-space: nowrap;">
                                <a href="<?= e(url('/administracion/estudiantes/ver?id=' . $est->id_estudiante)) ?>" style="color: #2563eb; text-decoration: none; font-weight: 600; margin-right: 0.5rem;">Ver</a>
                                <a href="<?= e(url('/administracion/estudiantes/editar?id=' . $est->id_estudiante)) ?>" style="color: #0d9488; text-decoration: none; font-weight: 600; margin-right: 0.5rem;">Editar</a>
                                <form method="POST" action="<?= e(url('/administracion/estudiantes/estado?id=' . $est->id_estudiante)) ?>" style="display: inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="nuevo_estado" value="<?= $est->isActivo() ? 'INACTIVO' : 'ACTIVO' ?>">
                                    <button type="submit" style="background: none; border: none; color: <?= $est->isActivo() ? '#dc2626' : '#16a34a' ?>; font-weight: 600; cursor: pointer; padding: 0;">
                                        <?= $est->isActivo() ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
