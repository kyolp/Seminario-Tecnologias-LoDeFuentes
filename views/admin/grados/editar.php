<section style="max-width: 650px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Editar Grado</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Modificación de información del grado académico</p>
        </div>
        <a href="<?= e(url('/administracion/grados')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">&larr; Volver</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/administracion/grados/editar?id=' . $grado->id_grado)) ?>" style="display: flex; flex-direction: column; gap: 1rem;">
        <?= csrf_field() ?>

        <div>
            <label for="codigo" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Código Único *</label>
            <input type="text" id="codigo" name="codigo" required maxlength="20" value="<?= e($old['codigo'] ?? $grado->codigo) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-family: monospace;">
            <?php if (!empty($errors['codigo'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['codigo']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="nombre" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Nombre del Grado *</label>
            <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($old['nombre'] ?? $grado->nombre) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
            <?php if (!empty($errors['nombre'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['nombre']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="orden" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Orden Numérico de Progresión *</label>
            <input type="number" id="orden" name="orden" required min="1" step="1" value="<?= e((string)($old['orden'] ?? $grado->orden)) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
            <?php if (!empty($errors['orden'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['orden']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <label for="activo" style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.25rem; color: #374151;">Estado *</label>
            <select id="activo" name="activo" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box;">
                <option value="1" <?= ((int)($old['activo'] ?? $grado->activo) === 1) ? 'selected' : '' ?>>ACTIVO</option>
                <option value="0" <?= ((int)($old['activo'] ?? $grado->activo) === 0) ? 'selected' : '' ?>>INACTIVO</option>
            </select>
        </div>

        <div style="margin-top: 1rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <a href="<?= e(url('/administracion/grados')) ?>" style="padding: 0.5rem 1rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem;">Cancelar</a>
            <button type="submit" style="padding: 0.5rem 1.25rem; background: #1e3a8a; color: #ffffff; border: none; border-radius: 4px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">Actualizar Grado</button>
        </div>
    </form>
</section>
