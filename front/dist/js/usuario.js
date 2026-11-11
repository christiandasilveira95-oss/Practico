// front/dist/js/usuario.js - Sección del usuario (Práctico 2)
// Comprueba que haya una sesión; si no la hay, manda a iniciar sesión.

async function iniciar() {
    var respuesta = await API.request("/me");

    if (respuesta.status !== "ok") {
        window.location.href = "/front/dist/pages/login.html";
        return;
    }

    document.getElementById("nombreUsuario").textContent = respuesta.usuario.nombre;
    document.getElementById("rolUsuario").textContent = respuesta.usuario.rol;

    // Un administrador también puede pasar por acá: se le ofrece volver a su panel
    if (respuesta.usuario.rol === "Administrador") {
        document.getElementById("enlaceAdmin").hidden = false;
    }

    document.getElementById("contenido").hidden = false;
}

document.getElementById("btnSalir").addEventListener("click", async function (evento) {
    evento.preventDefault();
    await API.request("/logout", "POST");
    window.location.href = "/front/index.html";
});

iniciar();
