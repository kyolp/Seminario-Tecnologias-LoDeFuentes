/**
 * Harness de prueba para la lógica JavaScript de Geolocation API
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 - T8-39)
 */

const fs = require('fs');
const path = require('path');

// Cargar el archivo de vista para extraer la lógica JS exacta
const viewPath = path.join(__dirname, '..', 'views', 'docente', 'asistencia', 'index.php');
const viewContent = fs.readFileSync(viewPath, 'utf8');

// Extraer el bloque <script>
const scriptMatch = viewContent.match(/<script>([\s\S]*?)<\/script>/);
if (!scriptMatch) {
    console.error(JSON.stringify({ ok: false, error: 'No se encontró bloque <script> en la vista' }));
    process.exit(1);
}

const scriptCode = scriptMatch[1];

function createMockEnvironment() {
    const elements = {
        'alerta-gps': { style: { display: 'none' }, textContent: '' },
        'indicador-carga': { style: { display: 'none' } },
        'btn-marcar-entrada': { disabled: false },
        'btn-marcar-salida': { disabled: false },
        'lat-entrada': { value: '' },
        'lon-entrada': { value: '' },
        'prec-entrada': { value: '' },
        'form-entrada': { submitted: false, submit: function() { this.submitted = true; } },
        'lat-salida': { value: '' },
        'lon-salida': { value: '' },
        'prec-salida': { value: '' },
        'form-salida': { submitted: false, submit: function() { this.submitted = true; } }
    };

    const documentMock = {
        getElementById: function(id) {
            return elements[id] || { style: {}, value: '', submit: () => {} };
        }
    };

    return { elements, documentMock };
}

const results = {
    caseA: false, // PERMISSION_DENIED
    caseB: false, // POSITION_UNAVAILABLE
    caseC: false, // TIMEOUT
    caseD: false  // No navigator.geolocation
};

// CASO A: PERMISSION_DENIED (código 1)
{
    const env = createMockEnvironment();
    const navigatorMock = {
        geolocation: {
            getCurrentPosition: function(success, error, options) {
                const err = new Error('User denied Geolocation');
                err.code = 1;
                err.PERMISSION_DENIED = 1;
                err.POSITION_UNAVAILABLE = 2;
                err.TIMEOUT = 3;
                error(err);
            }
        }
    };

    const fnContext = new Function('document', 'navigator', `${scriptCode}; return { iniciarMarcaje, mostrarErrorGPS, deshabilitarBotones };`);
    const fns = fnContext(env.documentMock, navigatorMock);

    fns.iniciarMarcaje('ENTRADA');

    const alerta = env.elements['alerta-gps'];
    const form = env.elements['form-entrada'];
    const msgOk = alerta.textContent.includes('Permiso denegado');
    const noSubmit = (form.submitted === false);
    const noCoords = (env.elements['lat-entrada'].value === '');

    results.caseA = msgOk && noSubmit && noCoords;
}

// CASO B: POSITION_UNAVAILABLE (código 2)
{
    const env = createMockEnvironment();
    const navigatorMock = {
        geolocation: {
            getCurrentPosition: function(success, error, options) {
                const err = new Error('Position unavailable');
                err.code = 2;
                err.PERMISSION_DENIED = 1;
                err.POSITION_UNAVAILABLE = 2;
                err.TIMEOUT = 3;
                error(err);
            }
        }
    };

    const fnContext = new Function('document', 'navigator', `${scriptCode}; return { iniciarMarcaje, mostrarErrorGPS, deshabilitarBotones };`);
    const fns = fnContext(env.documentMock, navigatorMock);

    fns.iniciarMarcaje('ENTRADA');

    const alerta = env.elements['alerta-gps'];
    const form = env.elements['form-entrada'];
    const msgOk = alerta.textContent.includes('Ubicación no disponible');
    const noSubmit = (form.submitted === false);
    const buttonsReenabled = (env.elements['btn-marcar-entrada'].disabled === false);

    results.caseB = msgOk && noSubmit && buttonsReenabled;
}

// CASO C: TIMEOUT (código 3)
{
    const env = createMockEnvironment();
    const navigatorMock = {
        geolocation: {
            getCurrentPosition: function(success, error, options) {
                const err = new Error('Timeout expired');
                err.code = 3;
                err.PERMISSION_DENIED = 1;
                err.POSITION_UNAVAILABLE = 2;
                err.TIMEOUT = 3;
                error(err);
            }
        }
    };

    const fnContext = new Function('document', 'navigator', `${scriptCode}; return { iniciarMarcaje, mostrarErrorGPS, deshabilitarBotones };`);
    const fns = fnContext(env.documentMock, navigatorMock);

    fns.iniciarMarcaje('SALIDA');

    const alerta = env.elements['alerta-gps'];
    const form = env.elements['form-salida'];
    const msgOk = alerta.textContent.includes('Tiempo de espera agotado');
    const noSubmit = (form.submitted === false);
    const buttonsReenabled = (env.elements['btn-marcar-salida'].disabled === false);

    results.caseC = msgOk && noSubmit && buttonsReenabled;
}

// CASO D: Sin soporte de navigator.geolocation
{
    const env = createMockEnvironment();
    const navigatorMock = {}; // Sin geolocation

    const fnContext = new Function('document', 'navigator', `${scriptCode}; return { iniciarMarcaje, mostrarErrorGPS, deshabilitarBotones };`);
    const fns = fnContext(env.documentMock, navigatorMock);

    fns.iniciarMarcaje('ENTRADA');

    const alerta = env.elements['alerta-gps'];
    const form = env.elements['form-entrada'];
    const msgOk = alerta.textContent.includes('no es compatible con la API de geolocalización');
    const noSubmit = (form.submitted === false);
    const noCoords = (env.elements['lat-entrada'].value === '');

    results.caseD = msgOk && noSubmit && noCoords;
}

const allPassed = results.caseA && results.caseB && results.caseC && results.caseD;
console.log(JSON.stringify({ ok: allPassed, results }));
process.exit(allPassed ? 0 : 1);
