<section style="max-width: 600px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: #1e3a8a; font-size: 1.3rem;">Registrar Estudiante</h2>
        <a href="<?= e(url('/administracion/estudiantes')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.875rem;">&larr; Cancelar y volver</a>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/administracion/estudiantes/crear')) ?>">
        <?= csrf_field() ?>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem;">Código de Estudiante</label>
            <input type="text" name="codigo_estudiante" value="<?= e($old['codigo_estudiante'] ?? '') ?>" maxlength="30" placeholder="Ej: EST-2026-001 (Opcional)" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
            <?php if (!empty($errors['codigo_estudiante'])): ?>
                <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['codigo_estudiante']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem;">Nombres *</label>
                <input type="text" name="nombres" value="<?= e($old['nombres'] ?? '') ?>" required maxlength="100" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                <?php if (!empty($errors['nombres'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['nombres']) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem;">Apellidos *</label>
                <input type="text" name="apellidos" value="<?= e($old['apellidos'] ?? '') ?>" required maxlength="100" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                <?php if (!empty($errors['apellidos'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['apellidos']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem;">Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" value="<?= e($old['fecha_nacimiento'] ?? '') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                <?php if (!empty($errors['fecha_nacimiento'])): ?>
                    <span style="color: #dc2626; font-size: 0.8rem;"><?= e($errors['fecha_nacimiento']) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem;">Estado Inicial</label>
                <select name="estado" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                    <option value="ACTIVO" <?= ($old['estado'] ?? 'ACTIVO') === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
                    <option value="INACTIVO" <?= ($old['estado'] ?? '') === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO</option>
                </select>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
            <a href="<?= e(url('/administracion/estudiantes')) ?>" style="padding: 0.6rem 1.2rem; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 0.9rem;">Cancelar</a>
            <button type="submit" style="padding: 0.6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 0.9rem;">Guardar Estudiante</button>
        </div>
    </form>
</section>
