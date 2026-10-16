<section style="max-width: 950px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Administración de Secciones</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Gestión de secciones y capacidades por grado y ciclo lectivo</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver al Panel</a>
            <a href="<?= e(url('/administracion/secciones/crear' . ($gpSeleccionado ? '?grado_periodo_id=' . $gpSeleccionado : ''))) ?>" style="padding: 0.5rem 1rem; background: #1e3a8a; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">+ Nueva Sección</a>
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div style="padding: 0.75rem 1rem; background: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($flash_success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div style="padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($flash_error) ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem;">
        <label for="filtro_gp" style="font-weight: 600; font-size: 0.875rem; color: #374151;">Filtrar por Grado / Período:</label>
        <select id="filtro_gp" onchange="window.location.href = '<?= e(url('/administracion/secciones')) ?>' + (this.value ? '?grado_periodo_id=' + this.value : '')" style="padding: 0.4rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;">
            <option value="">-- Todas las combinaciones Grado-Período --</option>
            <?php foreach ($gradosPeriodos as $gp): ?>
                <option value="<?= e((string)$gp->id_grado_periodo) ?>" <?= ((int)$gpSeleccionado === (int)$gp->id_grado_periodo) ? 'selected' : '' ?>>
                    <?= e($gp->periodo_nombre ?? 'Período #' . $gp->id_periodo_escolar) ?> &raquo; <?= e($gp->grado_nombre ?? 'Grado #' . $gp->id_grado) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                    <th style="padding: 0.75rem; color: #475569;">ID</th>
                    <th style="padding: 0.75rem; color: #475569;">Código</th>
                    <th style="padding: 0.75rem; color: #475569;">Nombre</th>
                    <th style="padding: 0.75rem; color: #475569;">Grado y Ciclo</th>
                    <th style="padding: 0.75rem; color: #475569;">Capacidad</th>
                    <th style="padding: 0.75rem; color: #475569;">Estado</th>
                    <th style="padding: 0.75rem; text-align: right; color: #475569;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($secciones)): ?>
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center; color: #64748b;">No hay secciones registradas para los criterios seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($secciones as $s): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.75rem; font-weight: 600; color: #1e293b;">#<?= e((string)$s->id_seccion) ?></td>
                            <td style="padding: 0.75rem; font-family: monospace; font-weight: 600; color: #1e3a8a;"><?= e($s->codigo) ?></td>
                            <td style="padding: 0.75rem; font-weight: 500;"><?= e($s->nombre) ?></td>
                            <td style="padding: 0.75rem; font-size: 0.85rem; color: #475569;">
                                <?= e($s->grado_nombre ?? 'Grado #' . $s->id_grado_periodo) ?>
                                <br><small style="color: #64748b;"><?= e($s->periodo_nombre ?? '') ?></small>
                            </td>
                            <td style="padding: 0.75rem; font-weight: 600; color: #0f172a;">
                                <?= e((string)$s->capacidad) ?> cupos
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($s->isHabilitada()): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">HABILITADA</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">DESHABILITADA</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                    <a href="<?= e(url('/administracion/secciones/ver?id=' . $s->id_seccion)) ?>" style="padding: 0.25rem 0.5rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem;">Ver</a>
                                    <a href="<?= e(url('/administracion/secciones/editar?id=' . $s->id_seccion)) ?>" style="padding: 0.25rem 0.5rem; background: #0d9488; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem;">Editar</a>
                                    
                                    <form method="POST" action="<?= e(url('/administracion/secciones/toggle?id=' . $s->id_seccion)) ?>" style="display: inline;" onsubmit="return confirm('¿Desea cambiar el estado de la sección?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="habilitada" value="<?= $s->isHabilitada() ? '0' : '1' ?>">
                                        <button type="submit" style="padding: 0.25rem 0.5rem; background: <?= $s->isHabilitada() ? '#d97706' : '#16a34a' ?>; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
                                            <?= $s->isHabilitada() ? 'Deshabilitar' : 'Habilitar' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="<?= e(url('/administracion/secciones/eliminar?id=' . $s->id_seccion)) ?>" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar esta sección?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" style="padding: 0.25rem 0.5rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
