<?php
/**
 * Vista: Consulta de Inscripciones y Cupos — Administración (RF-30).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * @var \App\Models\User $user
 * @var array<int, \App\Models\PeriodoEscolar> $periodos
 * @var array<int, \App\Models\Grado> $grados
 * @var array<int, \App\Models\Seccion> $secciones
 * @var array<string, mixed> $filtros
 * @var array<int, array<string, mixed>> $seccionesCupos
 * @var array<int, array<string, mixed>> $estudiantes
 * @var array<string, int> $resumen
 * @var string|null $mensajeError
 */
?>

<section style="max-width: 1200px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <!-- Encabezado -->
    <div style="border-bottom: 2px solid #16a34a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #15803d; font-size: 1.4rem;">📋 Consulta de Inscripciones y Cupos</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Fase 12 — Reporte de disponibilidad de cupos por sección y listado de estudiantes inscritos (RF-30)</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="<?= e(url('/administracion/reportes')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">
                &larr; Centro de Reportes
            </a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($mensajeError)): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            <?= e($mensajeError) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario de Filtros -->
    <form method="GET" action="<?= e(url('/administracion/reportes/inscripciones')) ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 1rem; align-items: end;">
        <!-- Filtro Período Escolar -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Período Escolar</label>
            <select name="id_periodo_escolar" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Períodos --</option>
                <?php foreach ($periodos as $p): ?>
                    <?php 
                        $pId = is_array($p) ? ($p['id_periodo_escolar'] ?? 0) : $p->id_periodo_escolar;
                        $pNom = is_array($p) ? ($p['nombre'] ?? '') : $p->nombre;
                        $pEst = is_array($p) ? ($p['estado'] ?? '') : $p->estado;
                        $selected = ((int)($filtros['id_periodo_escolar'] ?? 0) === (int)$pId) ? 'selected' : '';
                    ?>
                    <option value="<?= e((string)$pId) ?>" <?= $selected ?>>
                        <?= e($pNom) ?> [<?= e($pEst) ?>]
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro Grado -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Grado</label>
            <select name="id_grado" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Grados --</option>
                <?php foreach ($grados as $g): ?>
                    <?php 
                        $gId = is_array($g) ? ($g['id_grado'] ?? 0) : $g->id_grado;
                        $gNom = is_array($g) ? ($g['nombre'] ?? '') : $g->nombre;
                        $gCod = is_array($g) ? ($g['codigo'] ?? '') : $g->codigo;
                        $selected = ((int)($filtros['id_grado'] ?? 0) === (int)$gId) ? 'selected' : '';
                    ?>
                    <option value="<?= e((string)$gId) ?>" <?= $selected ?>>
                        <?= e($gNom) ?> (<?= e($gCod) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro Sección -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Sección</label>
            <select name="id_seccion" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todas las Secciones --</option>
                <?php foreach ($secciones as $s): ?>
                    <?php 
                        $sId = is_array($s) ? ($s['id_seccion'] ?? 0) : $s->id_seccion;
                        $sCod = is_array($s) ? ($s['codigo'] ?? '') : $s->codigo;
                        $sNom = is_array($s) ? ($s['nombre'] ?? '') : ($s->nombre ?? '');
                        $selected = ((int)($filtros['id_seccion'] ?? 0) === (int)$sId) ? 'selected' : '';
                    ?>
                    <option value="<?= e((string)$sId) ?>" <?= $selected ?>>
                        Sección <?= e($sCod) ?><?= $sNom !== '' ? ' - ' . e($sNom) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro Estado de Inscripción -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Estado de Inscripción</label>
            <select name="estado" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos --</option>
                <option value="CONFIRMADA" <?= (strtoupper((string)($filtros['estado'] ?? '')) === 'CONFIRMADA') ? 'selected' : '' ?>>CONFIRMADA</option>
                <option value="PENDIENTE" <?= (strtoupper((string)($filtros['estado'] ?? '')) === 'PENDIENTE') ? 'selected' : '' ?>>PENDIENTE</option>
                <option value="ANULADA" <?= (strtoupper((string)($filtros['estado'] ?? '')) === 'ANULADA') ? 'selected' : '' ?>>ANULADA</option>
            </select>
        </div>

        <!-- Botones de Acción -->
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; background: #16a34a; color: #fff; border: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                Buscar
            </button>
            <a href="<?= e(url('/administracion/reportes/inscripciones')) ?>" style="padding: 0.5rem 0.8rem; background: #e2e8f0; color: #475569; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                Limpiar
            </a>
        </div>
    </form>

    <!-- Resumen de Métricas de Capacidad e Inscripciones -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #15803d; font-weight: 600; text-transform: uppercase;">Capacidad Total</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #16a34a; margin-top: 0.25rem;"><?= (int)($resumen['capacidad_total'] ?? 0) ?></div>
        </div>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #1d4ed8; font-weight: 600; text-transform: uppercase;">Cupos Ocupados</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #2563eb; margin-top: 0.25rem;"><?= (int)($resumen['cupos_ocupados_total'] ?? 0) ?></div>
        </div>
        <div style="background: #fefce8; border: 1px solid #fef08a; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #a16207; font-weight: 600; text-transform: uppercase;">Cupos Disponibles</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #ca8a04; margin-top: 0.25rem;"><?= (int)($resumen['cupos_disponibles_total'] ?? 0) ?></div>
        </div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #475569; font-weight: 600; text-transform: uppercase;">Estudiantes Registrados</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #334155; margin-top: 0.25rem;"><?= (int)($resumen['total_estudiantes_inscritos'] ?? 0) ?></div>
            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">
                Conf: <?= (int)($resumen['total_confirmadas'] ?? 0) ?> | Pend: <?= (int)($resumen['total_pendientes'] ?? 0) ?>
            </div>
        </div>
    </div>

    <!-- Bloque 1: Resumen de Cupos por Sección -->
    <h3 style="color: #1e3a8a; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; margin-bottom: 1rem; font-size: 1.15rem;">
        🏢 Estado de Ocupación y Cupos por Sección
    </h3>

    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 2.5rem;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left;">
            <thead>
                <tr style="background: #f1f5f9; color: #334155; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 0.75rem 1rem;">Período</th>
                    <th style="padding: 0.75rem 1rem;">Grado</th>
                    <th style="padding: 0.75rem 1rem;">Sección</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Capacidad</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Ocupados</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Disponibles</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Ocupación</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Estado Cupo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($seccionesCupos)): ?>
                    <tr>
                        <td colspan="8" style="padding: 2.5rem 1rem; text-align: center; color: #64748b; background: #ffffff;">
                            No se encontraron secciones configuradas para los filtros aplicados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($seccionesCupos as $idx => $sec): ?>
                        <?php 
                            $bg = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
                            $cap = (int)$sec['capacidad'];
                            $ocup = (int)$sec['cupos_ocupados'];
                            $disp = (int)$sec['cupos_disponibles'];
                            $pct = ($cap > 0) ? min(100, round(($ocup / $cap) * 100, 1)) : 0;
                            $isLleno = (($sec['estado_cupo'] ?? '') === 'LLENO');
                        ?>
                        <tr style="background: <?= $bg ?>; border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.65rem 1rem; color: #334155;">
                                <?= e((string)$sec['periodo_nombre']) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; font-weight: 600; color: #1e293b;">
                                <?= e((string)$sec['grado_nombre']) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; font-weight: 500; color: #0f172a;">
                                Sección <?= e((string)$sec['seccion_codigo']) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; text-align: right; color: #334155; font-weight: 600;">
                                <?= $cap ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; text-align: right; color: #2563eb; font-weight: 600;">
                                <?= $ocup ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; text-align: right; color: #15803d; font-weight: 600;">
                                <?= $disp ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <div style="flex: 1; max-width: 60px; background: #e2e8f0; border-radius: 9999px; height: 6px; overflow: hidden;">
                                        <div style="background: <?= $isLleno ? '#dc2626' : '#16a34a' ?>; width: <?= $pct ?>%; height: 100%;"></div>
                                    </div>
                                    <span style="font-size: 0.75rem; color: #475569; font-weight: 600;"><?= $pct ?>%</span>
                                </div>
                            </td>
                            <td style="padding: 0.65rem 1rem; text-align: center;">
                                <?php if ($isLleno): ?>
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        LLENO
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #dcfce7; color: #166534; border: 1px solid #86efac; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        DISPONIBLE
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Bloque 2: Listado Detallado de Estudiantes Inscritos -->
    <h3 style="color: #1e3a8a; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; margin-bottom: 1rem; font-size: 1.15rem;">
        🎒 Estudiantes Inscritos
    </h3>

    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left;">
            <thead>
                <tr style="background: #f1f5f9; color: #334155; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 0.75rem 1rem;">Código Confirmación</th>
                    <th style="padding: 0.75rem 1rem;">Estudiante</th>
                    <th style="padding: 0.75rem 1rem;">Código Estudiante</th>
                    <th style="padding: 0.75rem 1rem;">Grado</th>
                    <th style="padding: 0.75rem 1rem;">Sección</th>
                    <th style="padding: 0.75rem 1rem;">Período</th>
                    <th style="padding: 0.75rem 1rem; text-align: center;">Estado</th>
                    <th style="padding: 0.75rem 1rem;">Fecha Inscripción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estudiantes)): ?>
                    <tr>
                        <td colspan="8" style="padding: 2.5rem 1rem; text-align: center; color: #64748b; background: #ffffff;">
                            No se encontraron estudiantes inscritos con los criterios seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estudiantes as $idx => $est): ?>
                        <?php 
                            $bg = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
                            $estado = (string)($est['inscripcion_estado'] ?? '');
                            $nomEst = trim(($est['estudiante_nombres'] ?? '') . ' ' . ($est['estudiante_apellidos'] ?? ''));
                            $fInsc = substr((string)($est['fecha_inscripcion'] ?? ''), 0, 10);
                        ?>
                        <tr style="background: <?= $bg ?>; border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.65rem 1rem; font-family: monospace; font-weight: 600; color: #0f172a;">
                                <?= e((string)($est['codigo_confirmacion'] ?? '—')) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; font-weight: 600; color: #1e293b;">
                                <?= e($nomEst) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #475569; font-family: monospace;">
                                <?= e((string)($est['codigo_estudiante'] ?? '—')) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #334155;">
                                <?= e((string)$est['grado_nombre']) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #334155;">
                                <?= e((string)$est['seccion_codigo']) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #475569; font-size: 0.8rem;">
                                <?= e((string)$est['periodo_nombre']) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; text-align: center;">
                                <?php if ($estado === 'CONFIRMADA'): ?>
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #dcfce7; color: #166534; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        CONFIRMADA
                                    </span>
                                <?php elseif ($estado === 'PENDIENTE'): ?>
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #fef3c7; color: #92400e; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        PENDIENTE
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #fee2e2; color: #991b1b; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        <?= e($estado) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #475569; white-space: nowrap;">
                                <?= e($fInsc) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Nota institucional de privacidad -->
    <div style="margin-top: 1.5rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.8rem; color: #64748b;">
        <strong>Nota de Privacidad y Cumplimiento (RNF-04):</strong> Los datos expuestos corresponden a la consulta institucional administrativa requerida por RF-30. Conforme a las directrices de privacidad de menores de edad, no se exponen teléfonos, correos ni datos de contacto de los encargados, protegiendo la confidencialidad de los estudiantes.
    </div>
</section>
