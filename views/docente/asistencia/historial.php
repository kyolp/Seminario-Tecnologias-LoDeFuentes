<?php
/**
 * Vista de Historial Personal de Asistencia Docente (RF-23).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables disponibles:
 * @var \App\Models\User $user
 * @var \App\Models\Docente $docente
 * @var array<int, \App\Models\MarcajeAsistencia> $historial
 */
?>

<div class="docente-historial-container" style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Encabezado y Navegación -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.25rem;">
                <a href="<?= e(url('/docente/asistencia')) ?>" style="color: #2563eb; text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr; Volver a Marcaje</a>
                <span style="color: #cbd5e1;">|</span>
                <a href="<?= e(url('/docente')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.875rem; font-weight: 500;">Portal Docente</a>
                <span style="background: #3b82f6; color: #ffffff; font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 9999px; text-transform: uppercase;">Historial Personal</span>
            </div>
            <h1 style="margin: 0; color: #0f172a; font-size: 1.75rem; font-weight: 700;">Mi Historial de Asistencia</h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.9rem;">
                Docente: <strong><?= e($docente->getNombreCompleto()) ?></strong> (<?= e($docente->codigo_docente ?? 'Docente') ?>)
            </p>
        </div>
        <div style="text-align: right;">
            <a href="<?= e(url('/docente/asistencia')) ?>" style="padding: 0.5rem 1rem; background: #16a34a; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.875rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem;">
                <span>🕒</span> Registrar Marcaje
            </a>
        </div>
    </div>

    <!-- Tarjeta de Historial -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0; font-size: 1.15rem; color: #1e293b; font-weight: 600;">Registro Cronológico de Marcajes</h2>
                <span style="font-size: 0.8rem; color: #64748b;">Eventos inmutables de entrada y salida registrados bajo validación institucional</span>
            </div>
            <span style="background: #e2e8f0; color: #475569; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px;">
                Total: <?= count($historial) ?> marcajes
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                <thead>
                    <tr style="background: #f1f5f9; color: #475569; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 0.85rem 1.25rem;">Fecha</th>
                        <th style="padding: 0.85rem 1.25rem;">Hora</th>
                        <th style="padding: 0.85rem 1.25rem;">Tipo de Marcaje</th>
                        <th style="padding: 0.85rem 1.25rem;">Estado / Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historial)): ?>
                        <tr>
                            <td colspan="4" style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📅</div>
                                <div style="font-weight: 600; color: #334155; margin-bottom: 0.25rem;">No se encontraron marcajes de asistencia</div>
                                <div style="font-size: 0.85rem;">Aún no cuenta con registros de entrada o salida en el sistema escolar.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historial as $m): ?>
                            <?php
                            $ts = strtotime($m->fecha_hora);
                            $fechaFormateada = date('d/m/Y', $ts);
                            $horaFormateada = date('H:i:s', $ts);
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                                <td style="padding: 0.85rem 1.25rem; font-weight: 600; color: #1e293b;">
                                    <?= e($fechaFormateada) ?>
                                </td>
                                <td style="padding: 0.85rem 1.25rem; color: #334155; font-family: monospace; font-size: 0.95rem;">
                                    <?= e($horaFormateada) ?>
                                </td>
                                <td style="padding: 0.85rem 1.25rem;">
                                    <?php if ($m->isEntrada()): ?>
                                        <span style="background: #dcfce7; color: #15803d; padding: 0.25rem 0.65rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">
                                            🟢 ENTRADA
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #ffedd5; color: #c2410c; padding: 0.25rem 0.65rem; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">
                                            🟠 SALIDA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.85rem 1.25rem;">
                                    <?php if ($m->dentro_radio === 1): ?>
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem;">
                                            ✓ Marcaje Válido
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #b91c1c; padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                            ✗ Fuera de Rango
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
