<section style="max-width: 950px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Catálogo de Grados</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Administración de grados y niveles académicos</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver al Panel</a>
            <a href="<?= e(url('/administracion/grados/crear')) ?>" style="padding: 0.5rem 1rem; background: #1e3a8a; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">+ Nuevo Grado</a>
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

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                    <th style="padding: 0.75rem; color: #475569;">ID</th>
                    <th style="padding: 0.75rem; color: #475569;">Código</th>
                    <th style="padding: 0.75rem; color: #475569;">Nombre</th>
                    <th style="padding: 0.75rem; color: #475569;">Orden</th>
                    <th style="padding: 0.75rem; color: #475569;">Estado</th>
                    <th style="padding: 0.75rem; text-align: right; color: #475569;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($grados)): ?>
                    <tr>
                        <td colspan="6" style="padding: 2rem; text-align: center; color: #64748b;">No hay grados registrados en el sistema.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($grados as $g): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.75rem; font-weight: 600; color: #1e293b;">#<?= e((string)$g->id_grado) ?></td>
                            <td style="padding: 0.75rem; font-family: monospace; font-weight: 600; color: #1e3a8a;"><?= e($g->codigo) ?></td>
                            <td style="padding: 0.75rem; font-weight: 500;"><?= e($g->nombre) ?></td>
                            <td style="padding: 0.75rem; color: #64748b;"><?= e((string)$g->orden) ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if ($g->isActivo()): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">ACTIVO</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">INACTIVO</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                    <a href="<?= e(url('/administracion/grados/editar?id=' . $g->id_grado)) ?>" style="padding: 0.25rem 0.5rem; background: #0d9488; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem;">Editar</a>
                                    
                                    <form method="POST" action="<?= e(url('/administracion/grados/toggle?id=' . $g->id_grado)) ?>" style="display: inline;" onsubmit="return confirm('¿Desea cambiar el estado del grado?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="activo" value="<?= $g->isActivo() ? '0' : '1' ?>">
                                        <button type="submit" style="padding: 0.25rem 0.5rem; background: <?= $g->isActivo() ? '#d97706' : '#16a34a' ?>; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
                                            <?= $g->isActivo() ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="<?= e(url('/administracion/grados/eliminar?id=' . $g->id_grado)) ?>" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este grado?');">
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
