<?php
/**
 * Vista de administración: Formulario de Configuración de Ubicación Institucional (Fase 8 — RF-18).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Variables:
 * @var \App\Models\User $user
 * @var \App\Models\ConfiguracionUbicacion|null $configuracionActiva
 * @var string|null $error
 * @var array<string, mixed> $valores
 */
?>

<div class="admin-ubicacion-form-container" style="max-width: 700px; margin: 2rem auto; padding: 0 1rem;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= e(url('/administracion/ubicacion')) ?>" style="color: #2563eb; text-decoration: none; font-size: 0.875rem; font-weight: 600;">&larr; Volver al panel de ubicación</a>
        <h1 style="margin: 0.5rem 0 0.25rem 0; color: #0f172a; font-size: 1.75rem; font-weight: 700;">Configurar Ubicación Institucional</h1>
        <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
            Al registrar una nueva configuración activa, la configuración anterior pasará a estado inactivo de forma automática conservando su historial.
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <strong>Error:</strong> <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 2rem;">
        <form action="<?= e(url('/administracion/ubicacion/configurar')) ?>" method="POST">
            <?= csrf_field() ?>

            <div style="margin-bottom: 1.25rem;">
                <label for="descripcion" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem;">
                    Descripción de la Sede / Ubicación <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" id="descripcion" name="descripcion" required maxlength="150"
                       value="<?= e((string)($valores['descripcion'] ?? 'Escuela Oficial Rural Mixta Lo de Fuentes')) ?>"
                       style="width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box;">
                <small style="color: #64748b; display: block; margin-top: 0.25rem;">Nombre descriptivo de la sede o campus escolar.</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="latitud" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem;">
                        Latitud (grados decimales) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" step="any" min="-90" max="90" id="latitud" name="latitud" required
                           value="<?= e((string)($valores['latitud'] ?? '14.6349150')) ?>"
                           style="width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; font-family: monospace;">
                    <small style="color: #64748b; display: block; margin-top: 0.25rem;">Rango válido: -90.0 a 90.0.</small>
                </div>
                <div>
                    <label for="longitud" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem;">
                        Longitud (grados decimales) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" step="any" min="-180" max="180" id="longitud" name="longitud" required
                           value="<?= e((string)($valores['longitud'] ?? '-90.5068820')) ?>"
                           style="width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; font-family: monospace;">
                    <small style="color: #64748b; display: block; margin-top: 0.25rem;">Rango válido: -180.0 a 180.0.</small>
                </div>
            </div>

            <div style="margin-bottom: 1.75rem;">
                <label for="radio_metros" style="display: block; font-weight: 600; color: #334155; margin-bottom: 0.4rem; font-size: 0.9rem;">
                    Radio Permitido (metros) <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" step="0.1" min="1" id="radio_metros" name="radio_metros" required
                       value="<?= e((string)($valores['radio_metros'] ?? '100')) ?>"
                       style="width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; font-family: monospace;">
                <small style="color: #64748b; display: block; margin-top: 0.25rem;">Distancia máxima en metros desde el punto central en que se admitirá el marcaje docente.</small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
                <a href="<?= e(url('/administracion/ubicacion')) ?>"
                   style="padding: 0.6rem 1.25rem; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                    Cancelar
                </a>
                <button type="submit"
                        style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">
                    Guardar y Activar Ubicación
                </button>
            </div>
        </form>
    </div>
</div>
