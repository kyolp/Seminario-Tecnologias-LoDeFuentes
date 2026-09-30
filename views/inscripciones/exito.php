<div class="card" style="max-width: 650px; margin: 3rem auto; padding: 2.5rem; text-align: center;">
    <div style="width: 70px; height: 70px; background: #dcfce7; color: #15803d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.5rem;">
        ✓
    </div>

    <h2 style="color: #1e3a8a; margin-bottom: 0.5rem;">¡Inscripción Confirmada!</h2>
    <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 2rem;">
        La inscripción escolar ha sido procesada y confirmada exitosamente en el sistema.
    </p>

    <div style="background: #f8fafc; border: 2px dashed #94a3b8; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
        <span style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
            Código Único de Confirmación (RF-12)
        </span>
        <span style="font-size: 1.8rem; font-weight: 800; color: #1e3a8a; font-family: monospace; letter-spacing: 0.1em;" id="codigo-texto">
            <?= e($codigo) ?>
        </span>
        <p style="color: #64748b; font-size: 0.8rem; margin: 0.5rem 0 0 0;">
            Guarde o anote este código. Le permitirá consultar su comprobante e información de la inscripción en cualquier momento.
        </p>
    </div>

    <?php if ($inscripcion): ?>
        <div style="text-align: left; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 2rem; font-size: 0.9rem;">
            <h4 style="margin: 0 0 0.75rem 0; color: #334155; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">Resumen de la Inscripción</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <div><strong style="color: #64748b;">Estudiante:</strong> <?= e($inscripcion['estudiante_nombre']) ?></div>
                <div><strong style="color: #64748b;">Grado:</strong> <?= e($inscripcion['grado']) ?></div>
                <div><strong style="color: #64748b;">Sección:</strong> <?= e($inscripcion['seccion']) ?></div>
                <div><strong style="color: #64748b;">Ciclo Escolar:</strong> <?= e($inscripcion['periodo']) ?></div>
                <div><strong style="color: #64748b;">Encargado:</strong> <?= e($inscripcion['encargado_nombre']) ?> (<?= e($inscripcion['parentesco']) ?>)</div>
                <div><strong style="color: #64748b;">Estado:</strong> <span style="background: #dcfce7; color: #166534; padding: 0.1rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">CONFIRMADA</span></div>
            </div>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: center; gap: 1rem;">
        <a href="<?= e(url('inscripcion/consultar?codigo=' . urlencode($codigo))) ?>"
           style="background: #2563eb; color: #fff; text-decoration: none; padding: 0.7rem 1.5rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem;">
            Ver Consulta en Línea
        </a>
        <a href="<?= e(url('inscripcion')) ?>"
           style="background: #f1f5f9; color: #334155; text-decoration: none; padding: 0.7rem 1.5rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem;">
            Realizar otra inscripción
        </a>
    </div>
</div>
