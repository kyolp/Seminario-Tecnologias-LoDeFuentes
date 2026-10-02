# ESPECIFICACIÓN TÉCNICA DE GEOLOCALIZACIÓN Y FÓRMULA DE HAVERSINE — FASE 8

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 8 — Asistencia Docente y Geolocalización  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Requisitos cubiertos:** RF-18, RF-19, RF-20, RF-21  

---

## 1. Fundamento Matemático: Fórmula de Haversine (DT-50)

Para determinar si un dispositivo se encuentra físicamente dentro del perímetro autorizado de la escuela, el sistema calcula la **distancia de círculo máximo** (geodésica) sobre una esfera de aproximación terrestre con radio medio:

$$R = 6,371,000 \text{ metros}$$

Dadas las coordenadas del punto central institucional $(\phi_1, \lambda_1)$ y las coordenadas reportadas por el dispositivo $(\phi_2, \lambda_2)$ en grados decimales:

1. Conversión de coordenadas a radianes:
   $$\Delta\phi = \text{deg2rad}(\phi_2 - \phi_1)$$
   $$\Delta\lambda = \text{deg2rad}(\lambda_2 - \lambda_1)$$
   $$\phi_1 = \text{deg2rad}(\phi_1), \quad \phi_2 = \text{deg2rad}(\phi_2)$$

2. Cálculo del semiverseno del ángulo central ($a$):
   $$a = \sin^2\left(\frac{\Delta\phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)$$

3. Cálculo de la distancia angular en radianes ($c$):
   $$c = 2 \cdot \text{atan2}\left(\sqrt{a}, \sqrt{1 - a}\right)$$
   *(Con normalización de dominio $a \in [0, 1]$ para evitar excepciones por imprecisión de punto flotante).*

4. Cálculo de la distancia métrica final ($d$):
   $$d = R \cdot c$$

---

## 2. Regla de Frontera del Perímetro Institucional

El sistema evalúa la pertenencia a la geocerca mediante la condición de inclusión de frontera:

$$\text{isDentroRadio}(d, r) \iff d \le r$$

- **Dentro del radio ($d < r$):** Marcaje autorizado y procesado.
- **En la frontera exacta ($d = r$):** Marcaje autorizado (frontera inclusiva).
- **Fuera del radio ($d > r$):** Marcaje denegado inmediatamente con excepción de dominio (`DomainException`) y código HTTP `422 Unprocessable Content`. Cero filas insertadas en la base de datos.

---

## 3. Coordenadas y Parámetros Institucionales de Referencia

| Parámetro | Valor Oficial / Ejemplo | Descripción Técnica |
| :--- | :--- | :--- |
| **Latitud Central** | `14.6349150` | Grados decimales con 7 posiciones de precisión ($\approx 1.1\text{ cm}$). |
| **Longitud Central** | `-90.5068820` | Grados decimales con 7 posiciones de precisión. |
| **Radio Permitido** | `100.00 metros` | Perímetro circular concéntrico alrededor de la escuela. |
| **Zona Horaria** | `America/Guatemala` | UTC-6 sin horario de verano. |
| **Precisión Dispositivo** | `precision_metros` | Reportada opcionalmente por la API Geolocation del navegador. |

---

## 4. Captura en el Cliente mediante HTML5 Geolocation API

La vista `views/docente/asistencia/index.php` implementa el consumo estándar de la API del navegador:

```javascript
navigator.geolocation.getCurrentPosition(
    function(position) {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        const precision = position.coords.accuracy;
        // Envío asíncrono seguro vía POST con token CSRF
    },
    function(error) {
        // Gestión exhaustiva de códigos de error W3C:
        // error.PERMISSION_DENIED (1)
        // error.POSITION_UNAVAILABLE (2)
        // error.TIMEOUT (3)
    },
    {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    }
);
```

---

## 5. Verificación de Seguridad y Cálculo Exclusivo en Backend (DT-49)

> [!IMPORTANT]
> El cliente web solo actúa como sensor emisor de coordenadas brutas (`latitud`, `longitud`, `precision`).  
> **Cualquier intento del cliente de precalcular o falsificar variables de validación (`dentro_radio`, `distancia_metros`, `id_docente`, `fecha_hora`) es deliberadamente purgado por la whitelist del backend y recalculado de manera autoritativa.**
