<div class="card" style="max-width: 650px; margin: 2rem auto; padding: 2rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="color: #1e3a8a; margin-bottom: 0.5rem;">Consulta de Inscripción Escolar</h2>
        <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
            Ingrese el código único de confirmación recibido al registrar su inscripción (RF-14).
        </p>
    </div>

    <form action="<?= e(url('inscripcion/consultar')) ?>" method="POST" style="margin-bottom: 2rem;">
        <?= csrf_field() ?>
        <div style="display: flex; gap: 0.5rem;">
            <input type="text" name="codigo" required maxlength="64"
                   placeholder="Ejemplo: INS-2026-A1B2C3D4"
                   value="<?= e($codigo ?? '') ?>"
                   style="flex: 1; padding: 0.75rem 1rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 1rem; font-family: monospace; text-transform: uppercase;">
            <button type="submit"
                    style="background: #1e3a8a; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                Consultar
            </button>
        </div>
    </form>

    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <strong>Aviso:</strong> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($resultado): ?>
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase;">Código de Confirmación</span>
                    <div style="font-size: 1.25rem; font-weight: 700; color: #1e3a8a; font-family: monospace;">
                        <?= e($resultado['codigo_confirmacion']) ?>
                    </div>
                </div>
                <div>
                    <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 700; font-size: 0.85rem;">
                        <?= e($resultado['estado']) ?>
                    </span>
                </div>
            </div>

            <dl style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin: 0; font-size: 0.9rem;">
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Estudiante:</dt>
                    <dd style="margin: 0; font-weight: 600; color: #1e293b;"><?= e($resultado['estudiante_nombre']) ?></dd>
                </div>
                <?php if (!empty($resultado['codigo_estudiante'])): ?>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Código MINEDUC:</dt>
                    <dd style="margin: 0; color: #1e293b;"><?= e($resultado['codigo_estudiante']) ?></dd>
                </div>
                <?php endif; ?>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Grado Escolar:</dt>
                    <dd style="margin: 0; color: #1e293b;"><?= e($resultado['grado']) ?></dd>
                </div>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Sección:</dt>
                    <dd style="margin: 0; color: #1e293b;"><?= e($resultado['seccion']) ?></dd>
                </div>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Ciclo Escolar:</dt>
                    <dd style="margin: 0; color: #1e293b;"><?= e($resultado['periodo']) ?></dd>
                </div>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Padre / Encargado:</dt>
                    <dd style="margin: 0; color: #1e293b;"><?= e($resultado['encargado_nombre']) ?> (<?= e($resultado['parentesco']) ?>)</dd>
                </div>
                <div>
                    <dt style="font-weight: 600; color: #64748b; font-size: 0.8rem;">Fecha de Registro:</dt>
                    <dd style="margin: 0; color: #1e293b;"><?= e($resultado['fecha_inscripcion']) ?></dd>
                </div>
            </dl>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?= e(url('inscripcion')) ?>" style="color: #2563eb; text-decoration: none; font-size: 0.9rem; font-weight: 500;">
            &larr; Volver al formulario de inscripción
        </a>
    </div>
</div>
