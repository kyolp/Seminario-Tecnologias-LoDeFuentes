<?php
/**
 * Formulario para Solicitar Nuevo Permiso Docente (RF-24, RF-25).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var \App\Models\Docente $docente
 * @var string|null $mensajeError
 */
?>

<div class="crear-permiso-container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
    <!-- Migas de pan / Regresar -->
    <div style="margin-bottom: 1rem;">
        <a href="<?= e(url('/docente/permisos')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">&larr; Volver a Mis Permisos</a>
    </div>

    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="background: #1e3a8a; color: #ffffff; padding: 1.25rem 1.5rem;">
            <h1 style="margin: 0; font-size: 1.35rem; font-weight: 700;">Solicitud de Permiso Docente</h1>
            <p style="margin: 0.25rem 0 0 0; color: #bfdbfe; font-size: 0.85rem;">Complete la información requerida para remitir su solicitud a la administración escolar (RF-24)</p>
        </div>

        <?php if (!empty($mensajeError)): ?>
            <div style="background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem; margin: 1.5rem 1.5rem 0 1.5rem; border-radius: 4px; font-size: 0.95rem;">
                <?= e($mensajeError) ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('/docente/permisos/crear')) ?>" method="POST" enctype="multipart/form-data" style="padding: 1.5rem;">
            <?= csrf_field() ?>

            <!-- Información del solicitante -->
            <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.875rem;">
                <div>
                    <span style="color: #64748b; display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Docente Solicitante</span>
                    <strong style="color: #1e293b;"><?= e($docente->getNombreCompleto()) ?></strong>
                </div>
                <div>
                    <span style="color: #64748b; display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Código Docente</span>
                    <span style="color: #1e293b; font-family: monospace;"><?= e($docente->codigo_docente ?? 'N/A') ?></span>
                </div>
            </div>

            <!-- Campo: Fecha solicitada -->
            <div style="margin-bottom: 1.25rem;">
                <label for="fecha_solicitada" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem;">
                    Fecha Solicitada del Permiso <span style="color: #dc2626;">*</span>
                </label>
                <input type="date" id="fecha_solicitada" name="fecha_solicitada" required
                       style="width: 100%; max-width: 300px; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; color: #1e293b;">
                <small style="display: block; color: #64748b; margin-top: 0.25rem; font-size: 0.8rem;">Indique la fecha en que se ausentará de sus labores escolares.</small>
            </div>

            <!-- Campo: Tipo de Motivo -->
            <div style="margin-bottom: 1.25rem;">
                <label for="tipo_motivo" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem;">
                    Motivo Principal <span style="color: #dc2626;">*</span>
                </label>
                <select id="tipo_motivo" name="tipo_motivo" required
                        style="width: 100%; max-width: 400px; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; color: #1e293b; background: #ffffff;">
                    <option value="">-- Seleccione una clasificación --</option>
                    <option value="Salud / Cita Médica">Salud / Cita Médica (IGSS o particular)</option>
                    <option value="Asunto Personal Justificado">Asunto Personal Justificado</option>
                    <option value="Capacitación / Comisión Oficial">Capacitación / Comisión Oficial del MINEDUC</option>
                    <option value="Trámite Administrativo o Judicial">Trámite Administrativo o Judicial</option>
                    <option value="Calamidad Doméstica">Calamidad Doméstica</option>
                    <option value="Otro Motivo Justificado">Otro Motivo Justificado</option>
                </select>
            </div>

            <!-- Campo: Descripción detallada -->
            <div style="margin-bottom: 1.25rem;">
                <label for="descripcion" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem;">
                    Descripción Detallada <span style="color: #dc2626;">*</span>
                </label>
                <textarea id="descripcion" name="descripcion" rows="4" maxlength="1000" required
                          placeholder="Describa el motivo de la ausencia laboral y cualquier consideración relevante para la administración..."
                          style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; color: #1e293b; font-family: inherit; resize: vertical;"></textarea>
                <small style="display: block; color: #64748b; margin-top: 0.25rem; font-size: 0.8rem;">Máximo 1000 caracteres.</small>
            </div>

            <!-- Campo: Documento de soporte opcional (RF-25) -->
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 1.25rem; margin-bottom: 1.75rem;">
                <label for="soporte" style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 0.4rem; font-size: 0.9rem;">
                    Documento de Respaldo o Justificante (Opcional — RF-25)
                </label>
                <input type="file" id="soporte" name="soporte" accept=".pdf,.png,.jpg,.jpeg,.webp"
                       style="display: block; width: 100%; font-size: 0.875rem; color: #475569; margin-bottom: 0.5rem;">
                <div style="font-size: 0.8rem; color: #64748b; line-height: 1.4;">
                    <strong>Formatos permitidos:</strong> PDF (.pdf), Imágenes PNG (.png), JPEG (.jpg, .jpeg) y WebP (.webp).<br>
                    <strong>Tamaño máximo:</strong> 5 MB. Los archivos son almacenados de forma segura y privada fuera del acceso web directo (DT-58).
                </div>
            </div>

            <!-- Botones de Acción -->
            <div style="display: flex; gap: 1rem; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
                <button type="submit" style="background: #2563eb; color: #ffffff; border: none; border-radius: 6px; padding: 0.65rem 1.5rem; font-size: 0.95rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enviar Solicitud
                </button>
                <a href="<?= e(url('/docente/permisos')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 500;">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
