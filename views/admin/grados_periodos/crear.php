<section style="max-width: 650px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Asociar Grado a Período Escolar</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Habilitación de oferta curricular por ciclo lectivo</p>
        </div>
        <a href="<?= e(url('/administracion/grados-periodos')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/administracion/grados-periodos/crear')) ?>" style="display: flex; flex-direction: column; gap: 1rem;">
        <?= csrf_field() ?>

        <div>
            <label for="id_periodo_escolar" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Período Escolar *</label>
            <select id="id_periodo_escolar" name="id_periodo_escolar" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <option value="">-- Seleccione un Período Escolar --</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= e((string)$p->id_periodo_escolar) ?>" <?= ((int)($old['id_periodo_escolar'] ?? 0) === (int)$p->id_periodo_escolar) ? 'selected' : '' ?>>
                        <?= e($p->nombre) ?> (<?= e($p->estado) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_periodo_escolar'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['id_periodo_escolar']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="id_grado" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Grado Académico *</label>
            <select id="id_grado" name="id_grado" required style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <option value="">-- Seleccione un Grado --</option>
                <?php foreach ($grados as $g): ?>
                    <option value="<?= e((string)$g->id_grado) ?>" <?= ((int)($old['id_grado'] ?? 0) === (int)$g->id_grado) ? 'selected' : '' ?>>
                        <?= e($g->nombre) ?> (<?= e($g->codigo) ?>) - <?= $g->isActivo() ? 'Activo' : 'Inactivo' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_grado'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['id_grado']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="habilitado" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Estado en el Período *</label>
            <select id="habilitado" name="habilitado" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <option value="1" <?= (!isset($old['habilitado']) || (int)$old['habilitado'] === 1) ? 'selected' : '' ?>>HABILITADO</option>
                <option value="0" <?= (isset($old['habilitado']) && (int)$old['habilitado'] === 0) ? 'selected' : '' ?>>DESHABILITADO</option>
            </select>
        </div>

        <div style="margin-top: 1rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <a href="<?= e(url('/administracion/grados-periodos')) ?>" style="padding: 0.5rem 1rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem;">Cancelar</a>
            <button type="submit" style="padding: 0.5rem 1.25rem; background: #1e3a8a; color: #ffffff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">Guardar Asociación</button>
        </div>
    </form>
</section>
