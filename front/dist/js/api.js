// front/dist/js/api.js
// El mensajero: las páginas nunca hablan con PHP directo, siempre pasan por acá.
// (Mismo patrón que front/dist/js/api.js, ampliado con subida de archivos y código HTTP.)

const API = {
    // En Docker: http://localhost:8080/api
    urlBase: '/api',

    // Pedido normal con JSON. Siempre devuelve un objeto: { status: 'ok' | 'error', ..., codigo: 200 }
    async request(endpoint, method = 'GET', data = null) {
        const opciones = {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin' // envía la cookie de sesión
        };

        // GET y DELETE no llevan body. POST/PUT sí.
        if (data) {
            opciones.body = JSON.stringify(data);
        }
        return this.enviar(endpoint, opciones);
    },

    // Subida de archivos (multipart/form-data). El navegador pone solo el Content-Type.
    async subirArchivo(endpoint, formData) {
        return this.enviar(endpoint, { method: 'POST', body: formData, credentials: 'same-origin' });
    },

    async enviar(endpoint, opciones) {
        try {
            const respuesta = await fetch(this.urlBase + endpoint, opciones);
            const json = await respuesta.json();
            json.codigo = respuesta.status;
            return json;
        } catch (error) {
            console.error('Error de conexión con la API:', error);
            return { status: 'error', message: 'No se pudo conectar con la API', codigo: 0 };
        }
    }
};
