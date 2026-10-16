<section style="max-width: 950px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Grados por Período Escolar</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Configuración de la oferta académica por ciclo lectivo</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver al Panel</a>
            <a href="<?= e(url('/administracion/grados-periodos/crear' . ($periodoSeleccionado ? '?periodo_id=' . $periodoSeleccionado : ''))) ?>" style="padding: 0.5rem 1rem; background: #1e3a8a; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">+ Asociar Grado</a>
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
        <label for="filtro_periodo" style="font-weight: 600; font-size: 0.875rem; color: #374151;">Filtrar por Período:</label>
        <select id="filtro_periodo" onchange="window.location.href = '<?= e(url('/administracion/grados-periodos')) ?>' + (this.value ? '?periodo_id=' + this.value : '')" style="padding: 0.4rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;">
            <option value="">-- Todos los períodos --</option>
            <?php foreach ($periodos as $p): ?>
                <option value="<?= e((string)$p->id_periodo_escolar) ?>" <?= ((int)$periodoSeleccionado === (int)$p->id_periodo_escolar) ? 'selected' : '' ?>>
                    <?= e($p->nombre) ?> (<?= e($p->estado) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                    <th style="padding: 0.75rem; color: #475569;">ID</th>
                    <th style="padding: 0.75rem; color: #475569;">Período Escolar</th>
                    <th style="padding: 0.75rem; color: #475569;">Grado</th>
                    <th style="padding: 0.75rem; color: #475569;">Estado en Ciclo</th>
                    <th style="padding: 0.75rem; text-align: right; color: #475569;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gradosPeriodos)): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: #64748b;">No hay asignaciones de grados para los criterios seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gradosPeriodos as $gp): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.75rem; font-weight: 600; color: #1e293b;">#<?= e((string)$gp->id_grado_periodo) ?></td>
                            <td style="padding: 0.75rem; font-weight: 500;"><?= e($gp->periodo_nombre ?? 'ID ' . $gp->id_periodo_escolar) ?></td>
                            <td style="padding: 0.75rem; font-weight: 600; color: #1e3a8a;">
                                <?= e($gp->grado_nombre ?? 'ID ' . $gp->id_grado) ?>
                                <?php if (!empty($gp->codigo_grado)): ?>
                                    <span style="font-family: monospace; font-size: 0.8rem; color: #64748b;">(<?= e($gp->codigo_grado) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?php if ($gp->isHabilitado()): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">HABILITADO</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">DESHABILITADO</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                    <form method="POST" action="<?= e(url('/administracion/grados-periodos/toggle?id=' . $gp->id_grado_periodo)) ?>" style="display: inline;" onsubmit="return confirm('¿Desea cambiar el estado de este grado en el ciclo?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="habilitado" value="<?= $gp->isHabilitado() ? '0' : '1' ?>">
                                        <button type="submit" style="padding: 0.25rem 0.5rem; background: <?= $gp->isHabilitado() ? '#d97706' : '#16a34a' ?>; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
                                            <?= $gp->isHabilitado() ? 'Deshabilitar' : 'Habilitar' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="<?= e(url('/administracion/grados-periodos/eliminar?id=' . $gp->id_grado_periodo)) ?>" style="display: inline;" onsubmit="return confirm('¿Está seguro de remover este grado del período escolar?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" style="padding: 0.25rem 0.5rem; background: #dc2626; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">Remover</button>
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
