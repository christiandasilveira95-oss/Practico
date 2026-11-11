// front/dist/js/login.js - Pantalla de inicio de sesión (Práctico 2)

var formLogin = document.getElementById("formLogin");
var inputEmail = document.getElementById("email");
var inputPassword = document.getElementById("password");
var btnEntrar = document.getElementById("btnEntrar");
var avisoInfo = document.getElementById("avisoInfo");
var avisoError = document.getElementById("avisoError");

// Si el usuario llegó acá porque lo mandaron desde otra página, se le explica el motivo
var motivo = new URLSearchParams(window.location.search).get("motivo");
var mensajes = {
    admin: "Iniciá sesión como administrador para entrar al panel.",
    expirada: "Tu sesión expiró. Volvé a iniciar sesión."
};
if (mensajes[motivo]) {
    avisoInfo.textContent = mensajes[motivo];
    avisoInfo.hidden = false;
}

// Si ya hay una sesión abierta se avisa, pero NO se saca al usuario de esta pantalla:
// así puede entrar con otra cuenta (por ejemplo, pasar de administrador a usuario).
API.request("/me").then(function (respuesta) {
    if (respuesta.status !== "ok") return;

    avisoInfo.textContent = "Ya iniciaste sesión como " + respuesta.usuario.nombre +
        " (" + respuesta.usuario.rol + "). ";

    var enlace = document.createElement("a");
    enlace.href = respuesta.redirect;
    enlace.className = "alert-link";
    enlace.textContent = "Ir a mi panel";
    avisoInfo.appendChild(enlace);

    avisoInfo.appendChild(document.createTextNode(" o ingresá con otra cuenta."));
    avisoInfo.hidden = false;
});

formLogin.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    avisoError.hidden = true;

    var email = inputEmail.value.trim();
    var password = inputPassword.value;

    if (email === "" || password === "") {
        avisoError.textContent = "Completá tu email y tu contraseña.";
        avisoError.hidden = false;
        return;
    }

    // El usuario se identifica con su EMAIL completo (no alcanza con escribir "usuario")
    if (email.indexOf("@") === -1) {
        avisoError.textContent = "Escribí tu email completo, con @ (por ejemplo: nombre@correo.com).";
        avisoError.hidden = false;
        return;
    }

    btnEntrar.disabled = true;
    btnEntrar.textContent = "Ingresando...";

    var respuesta = await API.request("/login", "POST", { email: email, password: password });

    if (respuesta.status === "ok") {
        // El servidor comprobó el rol y devuelve la ruta que corresponde:
        // administrador -> dist/pages/dashboard.php, usuario -> dist/pages/usuario.html
        window.location.href = respuesta.redirect;
        return;
    }

    avisoError.textContent = respuesta.message;
    avisoError.hidden = false;
    inputPassword.value = "";
    inputPassword.focus();
    btnEntrar.disabled = false;
    btnEntrar.textContent = "Ingresar";
});
