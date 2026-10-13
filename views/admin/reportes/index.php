<?php
/**
 * Vista: Centro de Reportes Institucionales — Administración (RF-29 y RF-30).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * @var \App\Models\User $user
 */
?>

<section style="max-width: 1000px; margin: 2rem auto; padding: 2rem; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px;">
    <!-- Encabezado -->
    <div style="border-bottom: 2px solid #1e3a8a; padding-bottom: 1rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0; color: #1e3a8a; font-size: 1.5rem;">📊 Reportes Institucionales</h2>
            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">Fase 12 — Generación de Reportes Administrativos de Asistencia Docente, Inscripciones y Cupos</p>
        </div>
        <div>
            <a href="<?= e(url('/administracion')) ?>" style="padding: 0.5rem 1rem; background: #64748b; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">
                &larr; Volver al Panel
            </a>
        </div>
    </div>

    <div style="margin-bottom: 1.5rem; color: #334155; font-size: 0.95rem; line-height: 1.6;">
        <p style="margin: 0;">Seleccione el reporte administrativo que desea generar y consultar con base en los datos oficiales del centro educativo:</p>
    </div>

    <!-- Módulos de Reportes -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
        <!-- Tarjeta RF-29: Reporte de Asistencia Docente -->
        <div style="background: #ffffff; border: 2px solid #0284c7; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(2,132,199,0.1); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.5rem;">🕒</span>
                    <h3 style="margin: 0; color: #0369a1; font-size: 1.2rem;">Reporte de Asistencia Docente</h3>
                </div>
                <span style="display: inline-block; background: #e0f2fe; color: #0369a1; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.75rem;">
                    RF-29
                </span>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.25rem;">
                    Consulte los registros y marcajes de entrada y salida del personal docente, filtrando por docente, período escolar o rango de fechas, con validación de geocerca institucional.
                </p>
            </div>
            <div>
                <a href="<?= e(url('/administracion/reportes/asistencia')) ?>" style="display: block; text-align: center; padding: 0.6rem 1rem; background: #0284c7; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 0.9rem; font-weight: 600;">
                    Consultar Asistencia &rarr;
                </a>
            </div>
        </div>

        <!-- Tarjeta RF-30: Consulta de Inscripciones y Cupos -->
        <div style="background: #ffffff; border: 2px solid #16a34a; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(22,163,74,0.1); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.5rem;">📋</span>
                    <h3 style="margin: 0; color: #15803d; font-size: 1.2rem;">Consulta de Inscripciones y Cupos</h3>
                </div>
                <span style="display: inline-block; background: #dcfce7; color: #15803d; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.75rem;">
                    RF-30
                </span>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.25rem;">
                    Consulte el estado general de cupos por sección y el listado de estudiantes inscritos, filtrando por ciclo escolar, grado, sección y estado de inscripción con cálculo en tiempo real.
                </p>
            </div>
            <div>
                <a href="<?= e(url('/administracion/reportes/inscripciones')) ?>" style="display: block; text-align: center; padding: 0.6rem 1rem; background: #16a34a; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 0.9rem; font-weight: 600;">
                    Consultar Inscripciones y Cupos &rarr;
                </a>
            </div>
        </div>
    </div>
</section>
