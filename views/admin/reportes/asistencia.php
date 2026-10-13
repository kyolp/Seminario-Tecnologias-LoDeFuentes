<?php
/**
 * Vista: Reporte de Asistencia Docente — Administración (RF-29).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * @var \App\Models\User $user
 * @var array<int, \App\Models\PeriodoEscolar> $periodos
 * @var array<int, \App\Models\Docente> $docentes
 * @var array<string, mixed> $filtros
 * @var array<int, array<string, mixed>> $marcajes
 * @var array<string, int> $resumen
 * @var string|null $mensajeError
 */
?>

<section style="max-width: 1200px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <!-- Encabezado -->
    <div style="border-bottom: 2px solid #0284c7; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #0369a1; font-size: 1.4rem;">🕒 Reporte de Asistencia Docente</h2>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">Fase 12 — Consulta oficial de marcajes de entrada y salida por docente y período escolar (RF-29)</p>
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
    <form method="GET" action="<?= e(url('/administracion/reportes/asistencia')) ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 1rem; align-items: end;">
        <!-- Filtro Docente -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Docente</label>
            <select name="id_docente" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos los Docentes --</option>
                <?php foreach ($docentes as $d): ?>
                    <?php 
                        $docId = is_array($d) ? ($d['id_docente'] ?? 0) : $d->id_docente;
                        $docNom = is_array($d) ? ($d['nombre_completo'] ?? ($d['nombres'] . ' ' . $d['apellidos'])) : $d->getNombreCompleto();
                        $docCod = is_array($d) ? ($d['codigo_docente'] ?? '') : ($d->codigo_docente ?? '');
                        $selected = ((int)($filtros['id_docente'] ?? 0) === (int)$docId) ? 'selected' : '';
                    ?>
                    <option value="<?= e((string)$docId) ?>" <?= $selected ?>>
                        <?= e($docNom) ?> (<?= e($docCod) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

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

        <!-- Filtro Fecha Inicio -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Fecha Inicio</label>
            <input type="date" name="fecha_inicio" value="<?= e((string)($filtros['fecha_inicio'] ?? '')) ?>" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
        </div>

        <!-- Filtro Fecha Fin -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Fecha Fin</label>
            <input type="date" name="fecha_fin" value="<?= e((string)($filtros['fecha_fin'] ?? '')) ?>" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
        </div>

        <!-- Filtro Tipo de Marcaje -->
        <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">Tipo de Marcaje</label>
            <select name="tipo" style="width: 100%; padding: 0.45rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                <option value="">-- Todos --</option>
                <option value="ENTRADA" <?= (strtoupper((string)($filtros['tipo'] ?? '')) === 'ENTRADA') ? 'selected' : '' ?>>ENTRADA</option>
                <option value="SALIDA" <?= (strtoupper((string)($filtros['tipo'] ?? '')) === 'SALIDA') ? 'selected' : '' ?>>SALIDA</option>
            </select>
        </div>

        <!-- Botones de Acción -->
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; background: #0284c7; color: #fff; border: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                Buscar
            </button>
            <a href="<?= e(url('/administracion/reportes/asistencia')) ?>" style="padding: 0.5rem 0.8rem; background: #e2e8f0; color: #475569; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                Limpiar
            </a>
        </div>
    </form>

    <!-- Resumen de Métricas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #0369a1; font-weight: 600; text-transform: uppercase;">Total Marcajes</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #0284c7; margin-top: 0.25rem;"><?= (int)($resumen['total_marcajes'] ?? 0) ?></div>
        </div>
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #047857; font-weight: 600; text-transform: uppercase;">Entradas</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #059669; margin-top: 0.25rem;"><?= (int)($resumen['total_entradas'] ?? 0) ?></div>
        </div>
        <div style="background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #6d28d9; font-weight: 600; text-transform: uppercase;">Salidas</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #7c3aed; margin-top: 0.25rem;"><?= (int)($resumen['total_salidas'] ?? 0) ?></div>
        </div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; text-align: center;">
            <span style="font-size: 0.8rem; color: #475569; font-weight: 600; text-transform: uppercase;">Docentes Filtrados</span>
            <div style="font-size: 1.8rem; font-weight: 700; color: #334155; margin-top: 0.25rem;"><?= (int)($resumen['docentes_registrados'] ?? 0) ?></div>
        </div>
    </div>

    <!-- Tabla de Marcajes -->
    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left;">
            <thead>
                <tr style="background: #f1f5f9; color: #334155; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 0.75rem 1rem;">Fecha</th>
                    <th style="padding: 0.75rem 1rem;">Hora</th>
                    <th style="padding: 0.75rem 1rem;">Tipo</th>
                    <th style="padding: 0.75rem 1rem;">Docente</th>
                    <th style="padding: 0.75rem 1rem;">Código</th>
                    <th style="padding: 0.75rem 1rem;">Geocerca</th>
                    <th style="padding: 0.75rem 1rem;">Distancia</th>
                    <th style="padding: 0.75rem 1rem;">Validación</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marcajes)): ?>
                    <tr>
                        <td colspan="8" style="padding: 2.5rem 1rem; text-align: center; color: #64748b; background: #ffffff;">
                            No se encontraron registros de marcajes con los criterios seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($marcajes as $idx => $m): ?>
                        <?php 
                            $bg = ($idx % 2 === 0) ? '#ffffff' : '#f8fafc';
                            $tipo = (string)($m['tipo'] ?? '');
                            $isEntrada = ($tipo === 'ENTRADA');
                            $fechaHora = (string)($m['fecha_hora'] ?? '');
                            $fecha = $fechaHora !== '' ? substr($fechaHora, 0, 10) : '';
                            $hora = $fechaHora !== '' ? substr($fechaHora, 11, 8) : '';
                            $nombreDocente = trim(($m['docente_nombres'] ?? '') . ' ' . ($m['docente_apellidos'] ?? ''));
                        ?>
                        <tr style="background: <?= $bg ?>; border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 0.65rem 1rem; font-weight: 500; color: #0f172a; white-space: nowrap;">
                                <?= e($fecha) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #334155; white-space: nowrap;">
                                <?= e($hora) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem;">
                                <?php if ($isEntrada): ?>
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #dcfce7; color: #166534; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        ENTRADA
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 0.2rem 0.5rem; background: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
                                        SALIDA
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; font-weight: 600; color: #1e293b;">
                                <?= e($nombreDocente) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #475569; font-family: monospace;">
                                <?= e((string)($m['codigo_docente'] ?? '')) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #475569;">
                                <?= e((string)($m['configuracion_descripcion'] ?? '')) ?>
                            </td>
                            <td style="padding: 0.65rem 1rem; color: #475569;">
                                <?= number_format((float)($m['distancia_metros'] ?? 0), 1) ?> m
                            </td>
                            <td style="padding: 0.65rem 1rem;">
                                <span style="display: inline-block; padding: 0.15rem 0.45rem; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    ✓ En radio
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Nota institucional de inmutabilidad y privacidad -->
    <div style="margin-top: 1.5rem; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.8rem; color: #64748b;">
        <strong>Nota de Integridad y Privacidad (RNF-04, RNF-06):</strong> Los registros de asistencia docente son generados autoritativamente por el servidor, de carácter estrictamente inmutable (solo lectura) y cumplen con el principio de mínima exposición (las coordenadas geográficas directas de los dispositivos privados no son expuestas).
    </div>
</section>
