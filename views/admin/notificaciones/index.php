<section class="notifications-panel" style="max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.5rem;">🔔 Notificaciones del Sistema</h2>
            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Fase 10 — Recepción y Consulta de Notificaciones de Permisos Docentes (RF-26)</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">
                &larr; Volver al Panel
            </a>
            <?php if (($conteoNoLeidas ?? 0) > 0): ?>
                <form action="<?= e(url('/administracion/notificaciones/marcar-todas')) ?>" method="POST" style="margin: 0;">
                    <?= csrf_field() ?>
                    <button type="submit" style="padding: 0.5rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                        ✓ Marcar todas leídas
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($mensajeExito)): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($mensajeExito) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <?= e($mensajeError) ?>
        </div>
    <?php endif; ?>

    <!-- Pestañas de Filtro -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
        <a href="<?= e(url('/administracion/notificaciones?filtro=todas')) ?>" 
           style="padding: 0.4rem 0.8rem; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; <?= ($filtro === 'todas') ? 'background: #1e3a8a; color: #fff;' : 'background: #f1f5f9; color: #475569;' ?>">
            Todas
        </a>
        <a href="<?= e(url('/administracion/notificaciones?filtro=no_leidas')) ?>" 
           style="padding: 0.4rem 0.8rem; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; <?= ($filtro === 'no_leidas') ? 'background: #1e3a8a; color: #fff;' : 'background: #f1f5f9; color: #475569;' ?>">
            No Leídas <?php if (($conteoNoLeidas ?? 0) > 0): ?><span style="background: #ef4444; color: #fff; padding: 0.1rem 0.4rem; border-radius: 9999px; font-size: 0.75rem; margin-left: 0.3rem;"><?= (int)$conteoNoLeidas ?></span><?php endif; ?>
        </a>
        <a href="<?= e(url('/administracion/notificaciones?filtro=leidas')) ?>" 
           style="padding: 0.4rem 0.8rem; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; <?= ($filtro === 'leidas') ? 'background: #1e3a8a; color: #fff;' : 'background: #f1f5f9; color: #475569;' ?>">
            Leídas
        </a>
    </div>

    <!-- Lista de Notificaciones -->
    <?php if (empty($notificaciones)): ?>
        <div style="text-align: center; padding: 3rem 1rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; color: #64748b;">
            <p style="font-size: 1.1rem; margin: 0 0 0.5rem 0;">No se encontraron notificaciones en esta bandeja.</p>
            <p style="font-size: 0.875rem; margin: 0;">Las alertas sobre nuevas solicitudes de permisos docentes aparecerán automáticamente aquí.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($notificaciones as $notif): ?>
                <?php $esNoLeida = $notif->isPendiente(); ?>
                <div style="border: 1px solid <?= $esNoLeida ? '#93c5fd' : '#e2e8f0' ?>; background: <?= $esNoLeida ? '#eff6ff' : '#ffffff' ?>; border-left: 5px solid <?= $esNoLeida ? '#2563eb' : '#94a3b8' ?>; border-radius: 6px; padding: 1.25rem; transition: all 0.2s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                                <?php if ($esNoLeida): ?>
                                    <span style="background: #2563eb; color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 4px; text-transform: uppercase;">Nueva</span>
                                <?php else: ?>
                                    <span style="background: #e2e8f0; color: #475569; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.45rem; border-radius: 4px;">Leída</span>
                                <?php endif; ?>
                                <h3 style="margin: 0; color: #1e293b; font-size: 1.05rem; font-weight: 600;">
                                    <?= e($notif->titulo) ?>
                                </h3>
                            </div>
                            <p style="margin: 0.5rem 0; color: #334155; font-size: 0.95rem; line-height: 1.5;">
                                <?= e($notif->mensaje) ?>
                            </p>
                            <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.8rem; color: #64748b; margin-top: 0.5rem;">
                                <span>📅 <strong>Fecha emisión:</strong> <?= e($notif->fecha_creacion) ?></span>
                                <?php if ($notif->fecha_lectura): ?>
                                    <span>👁️ <strong>Leída el:</strong> <?= e($notif->fecha_lectura) ?></span>
                                <?php endif; ?>
                                <?php if ($notif->docente_nombre): ?>
                                    <span>👨‍🏫 <strong>Docente:</strong> <?= e($notif->docente_nombre) ?></span>
                                <?php endif; ?>
                                <?php if ($notif->fecha_solicitada): ?>
                                    <span>🗓️ <strong>Día solicitado:</strong> <?= e($notif->fecha_solicitada) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                            <a href="<?= e(url('/administracion/permisos/ver?id=' . $notif->id_solicitud_permiso)) ?>" 
                               style="padding: 0.35rem 0.75rem; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600; white-space: nowrap;">
                                Ver Solicitud &rarr;
                            </a>
                            <?php if ($esNoLeida): ?>
                                <form action="<?= e(url('/administracion/notificaciones/marcar-leida')) ?>" method="POST" style="margin: 0;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id_notificacion" value="<?= (int) $notif->id_notificacion ?>">
                                    <button type="submit" style="padding: 0.35rem 0.75rem; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; cursor: pointer; white-space: nowrap;">
                                        ✓ Marcar leída
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
