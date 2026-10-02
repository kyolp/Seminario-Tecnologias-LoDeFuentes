<?php
/**
 * Vista funcional del Portal Docente — Fase 7 (RF-17).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables disponibles:
 * @var \App\Models\User $user
 * @var \App\Models\Docente $docente
 * @var array<int, \App\Models\AsignacionDocente> $asignaciones
 * @var \App\Models\AsignacionDocente|null $asignacion_seleccionada
 * @var array<int, array<string, mixed>> $estudiantes
 * @var int $total_estudiantes
 * @var bool $sin_asignacion
 * @var string|null $mensaje_aviso
 */
?>

<div class="portal-docente-container" style="max-width: 1100px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Encabezado y Perfil del Docente -->
    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; overflow: hidden;">
        <div style="background: #1e3a8a; color: #ffffff; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="background: #3b82f6; color: #ffffff; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.05em;">Portal docente — sesión válida</span>
                <h1 style="margin: 0.5rem 0 0.25rem 0; font-size: 1.75rem; font-weight: 700;">Bienvenido, <?= e($docente->getNombreCompleto()) ?></h1>
                <p style="margin: 0; color: #bfdbfe; font-size: 0.9rem;">Escuela Oficial Rural Mixta Lo de Fuentes</p>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <a href="<?= e(url('/docente/asistencia')) ?>" style="background: #10b981; color: #ffffff; border: none; border-radius: 6px; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                    Marcaje de Asistencia (RF-20, RF-21)
                </a>
                <form action="<?= e(url('/logout')) ?>" method="POST" style="margin: 0;">
                    <?= csrf_field() ?>
                    <button type="submit" style="background: #dc2626; color: #ffffff; border: none; border-radius: 6px; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/></svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>

        <div style="padding: 1.25rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.9rem;">
            <div>
                <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Código de Docente</span>
                <strong style="color: #1e293b;"><?= e($docente->codigo_docente ?? 'Sin código asignado') ?></strong>
            </div>
            <div>
                <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Nombre de Usuario</span>
                <strong style="color: #1e293b;"><?= e($user->nombre_usuario) ?></strong>
            </div>
            <div>
                <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Correo Electrónico</span>
                <span style="color: #1e293b;"><?= e($user->correo) ?></span>
            </div>
            <div>
                <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Estado de Cuenta</span>
                <span style="display: inline-block; background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;"><?= e($docente->estado) ?></span>
            </div>
        </div>
    </div>

    <?php if ($sin_asignacion): ?>
        <!-- Mensaje amigable cuando el docente no tiene secciones asignadas (T7-06) -->
        <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 3rem 1.5rem; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="width: 64px; height: 64px; background: #fef3c7; color: #d97706; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h2 style="color: #1e293b; font-size: 1.35rem; margin: 0 0 0.5rem 0;"><?= e($mensaje_aviso ?? 'Actualmente no tiene una sección asignada.') ?></h2>
            <p style="color: #64748b; max-width: 520px; margin: 0 auto; line-height: 1.6;">
                Usted no posee ninguna asignación docente activa en el ciclo lectivo en curso. Para registrar la titularidad de su grado y sección, comuníquese con la dirección administrativa de la escuela.
            </p>
        </div>
    <?php else: ?>
        <!-- Selector de Asignaciones (si tiene múltiples) -->
        <?php if (count($asignaciones) > 1): ?>
            <div style="margin-bottom: 1.5rem; background: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                <span style="font-weight: 700; color: #1e293b; margin-right: 1rem; font-size: 0.9rem;">Mis Asignaciones Activas:</span>
                <div style="display: inline-flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;">
                    <?php foreach ($asignaciones as $asig): ?>
                        <?php $isActiva = ($asignacion_seleccionada !== null && $asig->id_seccion === $asignacion_seleccionada->id_seccion); ?>
                        <a href="<?= e(url('/docente?seccion=' . $asig->id_seccion)) ?>"
                           style="padding: 0.4rem 0.9rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; border: 1px solid <?= $isActiva ? '#2563eb' : '#cbd5e1' ?>; background: <?= $isActiva ? '#2563eb' : '#f8fafc' ?>; color: <?= $isActiva ? '#ffffff' : '#334155' ?>;">
                            <?= e($asig->grado_nombre) ?> — <?= e($asig->seccion_codigo) ?> (Aula <?= e($asig->aula_codigo) ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tarjeta de Contexto Académico de la Asignación Activa -->
        <?php if ($asignacion_seleccionada !== null): ?>
            <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2rem; overflow: hidden;">
                <div style="background: #f1f5f9; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h2 style="margin: 0; color: #0f172a; font-size: 1.25rem;">
                            <?= e($asignacion_seleccionada->grado_nombre) ?> — Sección <?= e($asignacion_seleccionada->seccion_codigo) ?>
                            <?php if (!empty($asignacion_seleccionada->seccion_nombre)): ?>
                                <span style="font-weight: 400; color: #64748b; font-size: 1rem;">(<?= e($asignacion_seleccionada->seccion_nombre) ?>)</span>
                            <?php endif; ?>
                        </h2>
                        <span style="color: #64748b; font-size: 0.85rem;">Ciclo Escolar: <strong><?= e($asignacion_seleccionada->periodo_nombre) ?></strong></span>
                    </div>
                    <div>
                        <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 700; font-size: 0.8rem;">
                            <?= e($total_estudiantes) ?> de <?= e($asignacion_seleccionada->seccion_capacidad) ?> cupos ocupados
                        </span>
                    </div>
                </div>

                <div style="padding: 1.25rem 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Aula Asignada</span>
                        <strong style="color: #1e293b;"><?= e($asignacion_seleccionada->aula_codigo) ?></strong>
                        <?php if (!empty($asignacion_seleccionada->aula_nombre)): ?>
                            <span style="color: #64748b; display: block; font-size: 0.8rem;"><?= e($asignacion_seleccionada->aula_nombre) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Fecha de Inicio</span>
                        <span style="color: #1e293b;"><?= e($asignacion_seleccionada->fecha_inicio) ?></span>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Capacidad Máxima</span>
                        <span style="color: #1e293b;"><?= e($asignacion_seleccionada->seccion_capacidad) ?> estudiantes</span>
                    </div>
                    <div>
                        <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block;">Estado de Titularidad</span>
                        <span style="display: inline-block; background: #dcfce7; color: #166534; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;"><?= e($asignacion_seleccionada->estado) ?></span>
                    </div>
                </div>
            </div>

            <!-- Listado de Estudiantes Asignados (RF-17) -->
            <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 style="margin: 0; color: #0f172a; font-size: 1.15rem;">Nómina Oficial de Estudiantes (RF-17)</h3>
                        <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.85rem;">Estudiantes con inscripción vigente y confirmada en su sección.</p>
                    </div>
                    <div>
                        <span style="background: #f1f5f9; color: #334155; padding: 0.3rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; border: 1px solid #cbd5e1;">
                            Total: <?= e($total_estudiantes) ?> estudiantes
                        </span>
                    </div>
                </div>

                <?php if (empty($estudiantes)): ?>
                    <div style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 1rem auto; display: block; color: #94a3b8;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500;">No hay estudiantes inscritos en esta sección actualmente.</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Las nuevas inscripciones confirmadas en línea se reflejarán automáticamente aquí.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; width: 50px; text-align: center;">#</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600;">Código del Estudiante</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600;">Apellidos y Nombres</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600;">Fecha de Nacimiento</th>
                                    <th style="padding: 0.75rem 1rem; font-weight: 600; text-align: center;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $correlativo = 1; ?>
                                <?php foreach ($estudiantes as $est): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 0.75rem 1rem; color: #64748b; font-weight: 500; text-align: center;"><?= $correlativo++ ?></td>
                                        <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.85rem; font-weight: 600; color: #1e3a8a;">
                                            <?= e($est['codigo_estudiante'] ?? 'Sin código asignado') ?>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; font-weight: 600; color: #0f172a;">
                                            <?= e($est['apellidos']) ?>, <?= e($est['nombres']) ?>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; color: #475569;">
                                            <?= e($est['fecha_nacimiento'] ?? 'No registrada') ?>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; text-align: center;">
                                            <span style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.8rem;">
                                                Inscrito
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
