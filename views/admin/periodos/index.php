<section style="max-width: 900px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.4rem;">Períodos Escolares</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Ciclos lectivos institucionales (Regla: Máximo un período activo)</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver al Panel</a>
            <a href="<?= e(url('/administracion/periodos/crear')) ?>" style="padding: 0.5rem 1rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">+ Nuevo Período</a>
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

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; color: #334155;">
                    <th style="padding: 0.75rem;">Nombre del Ciclo</th>
                    <th style="padding: 0.75rem;">Fecha Inicio</th>
                    <th style="padding: 0.75rem;">Fecha Fin</th>
                    <th style="padding: 0.75rem;">Estado</th>
                    <th style="padding: 0.75rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($periodos)): ?>
                    <tr>
                        <td colspan="5" style="padding: 1.5rem; text-align: center; color: #64748b;">No existen períodos escolares registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($periodos as $p): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.75rem; font-weight: 600; color: #0f172a;"><?= e($p->nombre) ?></td>
                            <td style="padding: 0.75rem; color: #475569;"><?= e($p->fecha_inicio) ?></td>
                            <td style="padding: 0.75rem; color: #475569;"><?= e($p->fecha_fin) ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if ($p->isActivo()): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">ACTIVO (VIGENTE)</span>
                                <?php elseif ($p->isPlanificado()): ?>
                                    <span style="background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">PLANIFICADO</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">CERRADO</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right; white-space: nowrap;">
                                <a href="<?= e(url('/administracion/grados-periodos?periodo_id=' . $p->id_periodo_escolar)) ?>" style="color: #6366f1; text-decoration: none; font-weight: 600; margin-right: 0.5rem;">Grados</a>
                                <a href="<?= e(url('/administracion/periodos/ver?id=' . $p->id_periodo_escolar)) ?>" style="color: #2563eb; text-decoration: none; font-weight: 600; margin-right: 0.5rem;">Ver</a>
                                <a href="<?= e(url('/administracion/periodos/editar?id=' . $p->id_periodo_escolar)) ?>" style="color: #0d9488; text-decoration: none; font-weight: 600; margin-right: 0.5rem;">Editar</a>
                                <?php if (!$p->isActivo() && !$p->isCerrado()): ?>
                                    <form method="POST" action="<?= e(url('/administracion/periodos/estado?id=' . $p->id_periodo_escolar)) ?>" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="nuevo_estado" value="ACTIVO">
                                        <button type="submit" style="background: none; border: none; color: #16a34a; font-weight: 600; cursor: pointer; padding: 0; margin-right: 0.5rem;">Activar</button>
                                    </form>
                                <?php elseif ($p->isActivo()): ?>
                                    <form method="POST" action="<?= e(url('/administracion/periodos/estado?id=' . $p->id_periodo_escolar)) ?>" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="nuevo_estado" value="CERRADO">
                                        <button type="submit" style="background: none; border: none; color: #dc2626; font-weight: 600; cursor: pointer; padding: 0;">Cerrar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
